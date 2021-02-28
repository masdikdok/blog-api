<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\CategoryResource;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\CustomHelpers\Helpers;

class CategoryController extends Controller
{
    public $path_upload = '';
    public $path_delete = '';
    public $image_dimensions = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->path_upload = storage_path('app/public/images/category');
        $this->path_delete = 'public/images/category';
        $this->image_dimensions = ['200', '300'];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $start_process = microtime(true);
        $validator = Validator::make($request->all(), [
            'page' => ['nullable', 'min:1', 'numeric'],
            'min_page' => ['nullable', 'min:1', 'numeric'],
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            return response()->json([
                'header' => [
                    'process_time' => $end_process - $start_process,
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Failed',
                    'error' => $validator->errors()
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $filter = [
            'page' => $request->has('page') ? (int) abs($request->page) : 1,
            'per_page' => $request->has('per_page') ? (int) abs($request->per_page) : 100
        ];

        $query = Category::query()->with(['child.child.child.child'])
            ->where('status', Category::STATUS_ACTIVE)
            ->whereNull('parent_id')
            ->orderByRaw('no_order, id');

        $total_rows = $query->count();
        $categories = $query
            ->take($filter['per_page'])
            ->skip($filter['per_page'] * ($filter['page'] - 1))
            ->get();

        $filter['total_page'] = ceil($total_rows / $filter['per_page']);
        $filter['total_rows'] = $total_rows;
        $categories = CategoryResource::collection($categories);

        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get list of category resource is success',
            ],
            'data' => [
                'filter' => $filter,
                'categories' => $categories
            ],
        ];

        return response()->json($output);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $start_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => 0,
                'status' => Response::HTTP_NOT_IMPLEMENTED,
                'message' => 'Failed',
            ],
        ];

        $validator = Validator::make($request->all(), [
            'parent_id' => ['nullable', 'min:1', 'numeric'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'no_order' => ['nullable', 'numeric'],
            'status' => ['required', 'numeric', 'in:0,1'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5000']
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            $output['header']['error'] = $validator->errors();

            return response()->json($output, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $continue = false;
        $filenametostore = "";

        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => ($request->has('parent_id') && ! empty($request->parent_id)) ? $request->parent_id : null,
                'name' => $request->name,
                'description' => $request->description,
                'no_order' => (int) $request->no_order,
                'status' => (int) $request->status
            ];

            if($request->hasFile('image') && ! empty($request->image)){
                $image = $request->file('image');

                // Upload File
                $filenametostore = Helpers::uploadImage($image, $this->path_upload, $this->image_dimensions);
                if($filenametostore){
                    $data = Arr::add($data, 'image', $filenametostore);
                }
            }

            $category = Category::create($data);
            if($category){
                // rollback
                DB::commit();

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Store category resource is success';
                $output['data'] = new CategoryResource($category);
                $continue = true;
            }else{
                // rollback
                DB::rollback();
            }
        } catch (Exception $e) {
            // rollback
            DB::rollback();

            $output['header']['error'] = $e;
            $output['header']['message'] = $e->getMessage();
        }

        if($continue == false){
            // delete if image is exist
            if(! empty($filenametostore)){
                Helpers::uploadImage($filenametostore, $this->path_delete, $this->image_dimensions);
            }
        }

        $end_process = microtime(true);
        $output['header']['process_time'] = $end_process - $start_process;

        return response()->json($output, $output['header']['status']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $start_process = microtime(true);
        $category = Category::with(['child.child.child'])->find($id);

        if(empty($category)){
            $end_process = microtime(true);
            return response()->json([
                'header' => [
                    'process_time' => $end_process - $start_process,
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data is not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $category = new CategoryResource($category);
        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get detail of category resource is success',
            ],
            'data' => $category,
        ];

        return response()->json($output, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $start_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => 0,
                'status' => Response::HTTP_NOT_IMPLEMENTED,
                'message' => 'Failed',
            ],
        ];
        $category = Category::find($id);

        if(empty($category)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'parent_id' => ['nullable', 'min:1', 'numeric'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'no_order' => ['nullable', 'numeric'],
            'status' => ['required', 'numeric', 'in:0,1'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5000']
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            $output['header']['message'] = 'Updated of category resource is failed';
            $output['header']['error'] = $validator->errors();

            return response()->json($output, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $continue = false;
        $oldimage = "";
        $filenametostore = "";

        DB::beginTransaction();
        try {
            $category->name = $request->name;
            $category->description = $request->description;
            $category->no_order = (int) $request->no_order;
            $category->status = (int) $request->status;

            if($request->hasFile('image') && ! empty($request->image)){
                $image = $request->file('image');

                // Upload File
                $filenametostore = Helpers::uploadImage($image, $this->path_upload, $this->image_dimensions);
                if($filenametostore){
                    $oldimage = $category->image;
                    $category->image = $filenametostore;
                }
            }

            if($category->update()){
                // commit
                DB::commit();

                // delete if image is exist
                if(! empty($oldimage)){
                    Helpers::uploadImage($oldimage, $this->path_delete, $this->image_dimensions);
                }

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Store category resource is success';
                $output['data'] = new CategoryResource($category);
                $continue = true;
            }else{
                // rollback
                DB::rollback();
            }
        } catch (Exception $e) {
            // rollback
            DB::rollback();

            $output['header']['error'] = $e;
            $output['header']['message'] = $e->getMessage();
        }

        if($continue == false){
            // delete if image is exist
            if(! empty($filenametostore)){
                Helpers::uploadImage($filenametostore, $this->path_delete, $this->image_dimensions);
            }
        }

        $end_process = microtime(true);
        $output['header']['process_time'] = $end_process - $start_process;

        return response()->json($output, $output['header']['status']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $start_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => 0,
                'status' => Response::HTTP_NOT_IMPLEMENTED,
                'message' => 'Failed',
            ],
        ];

        $category = Category::find($id);
        if(empty($category)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            $oldimage = $category->image;
            if($category->delete()){
                // commit
                DB::commit();

                // delete if image is exist
                if(! empty($oldimage)){
                    Helpers::uploadImage($oldimage, $this->path_delete, $this->image_dimensions);
                }

                $output['header']['status'] = Response::HTTP_OK;
                $output['header']['message'] = 'Delete of category resource is success';
            }else{
                // rollback
                DB::rollback();
            }
        } catch (Exception $e) {
            // rollback
            DB::rollback();

            $output['header']['error'] = $e;
            $output['header']['message'] = $e->getMessage();
        }

        $end_process = microtime(true);
        $output['header']['process_time'] = $end_process - $start_process;

        return response()->json($output, $output['header']['status']);
    }

}
