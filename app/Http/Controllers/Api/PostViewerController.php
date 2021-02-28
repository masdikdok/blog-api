<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Post;
use App\Models\PostSection;
use App\Models\PostComment;
use App\Models\PostImage;
use App\CustomHelpers\Helpers;

class PostViewerController extends Controller
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
        $this->path_upload = storage_path('app/public/images/post');
        $this->path_delete = 'public/images/post';
        $this->image_dimensions = ['200'];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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

        $query = Category::query()->with(['category'])
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
