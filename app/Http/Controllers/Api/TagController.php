<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

use App\Models\Tag;
use App\Http\Resources\TagResource;
use App\CustomHelpers\Helpers;

class TagController extends Controller
{
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

        $query = Tag::query()->orderByRaw('id');

        $total_rows = $query->count();
        $categories = $query
            ->take($filter['per_page'])
            ->skip($filter['per_page'] * ($filter['page'] - 1))
            ->get();

        $filter['total_page'] = ceil($total_rows / $filter['per_page']);
        $filter['total_rows'] = $total_rows;
        $tags = TagResource::collection($categories);

        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get list of tag resource is success',
            ],
            'data' => [
                'filter' => $filter,
                'tags' => $tags
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'status' => ['required', 'numeric', 'in:0,1'],
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
                'name' => $request->name,
                'description' => $request->description,
                'status' => (int) $request->status
            ];

            $category = Tag::create($data);
            if($category){
                // rollback
                DB::commit();

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Store tag resource is success';
                $output['data'] = new TagResource($category);
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
        $tag = Tag::find($id);

        if(empty($tag)){
            $end_process = microtime(true);
            return response()->json([
                'header' => [
                    'process_time' => $end_process - $start_process,
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data is not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $tag = new TagResource($tag);
        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get detail of tag resource is success',
            ],
            'data' => $tag,
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
        $category = Tag::find($id);

        if(empty($category)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'status' => ['required', 'numeric', 'in:0,1'],
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            $output['header']['message'] = 'Updated of tag resource is failed';
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
            $category->status = (int) $request->status;

            if($category->update()){
                // commit
                DB::commit();

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Store tag resource is success';
                $output['data'] = new TagResource($category);
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

        $tag = Tag::find($id);
        if(empty($tag)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            if($tag->delete()){
                // commit
                DB::commit();

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
