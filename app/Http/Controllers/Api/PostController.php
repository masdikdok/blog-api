<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;

use App\Models\Post;
use App\Models\PostSection;
use App\Models\PostImage;
use App\Http\Resources\PostResource;
use App\CustomHelpers\Helpers;

class PostController extends Controller
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
            'category_id' => ['nullable', 'min:1', 'numeric'],
            'start_publish_at' => ['nullable', 'numeric'],
            'end_publish_at' => ['nullable', 'numeric'],
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

        $query = Post::query()
            ->where('status', POST::STATUS_ACTIVE);

        if ($request->has('category_id')) {
            $filter['category_id'] = (int) $request->category_id;
            $query = $query->where('category_id', $filter['category_id']);
        }

        if ($request->has('start_publish_at') && $request->has('end_publish_at')) {
            $filter['start_publish_at'] = date('Y-m-d H:i:s', (double) $request->start_publish_at);
            $filter['end_publish_at'] = date('Y-m-d H:i:s', (double) $request->end_publish_at);
            $query = $query->whereBetween('publish_at', [$filter['end_publish_at'], $filter['start_publish_at']]);
        }

        $total_rows = $query->count();
        $posts = $query
            ->take($filter['per_page'])
            ->skip($filter['per_page'] * ($filter['page'] - 1))
            ->get();

        $filter['total_page'] = ceil($total_rows / $filter['per_page']);
        $filter['total_rows'] = $total_rows;
        $posts = PostResource::collection($posts);

        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get list of post resource is success',
            ],
            'data' => [
                'filter' => $filter,
                'posts' => $posts
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:posts'],
            'summary' => ['required', 'string'],
            'status' => ['required', 'numeric', 'in:0,1'],
            'category_id' => ['required', 'numeric'],
            'publish_at' => ['nullable', 'datetime'],
            'post_section.*' => ['required', 'string'],
            'image.file.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp'],
            'image.note.*' => ['nullable', 'string']
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            $output['header']['error'] = $validator->errors();

            return response()->json($output, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $continue = true;
        $post_id = false;
        $temp_images = [];
        $user_id = Auth::id();

        DB::beginTransaction();
        try {
            $post = Post::create([
                'user_id' => $user_id,
                'title' => $request->title,
                'slug' => $request->slug,
                'summary' => $request->summary,
                'status' => (int) $request->status,
                'category_id' => (int) $request->category_id,
                'publish_at' => ($request->has('publish_at')) ? date('Y-m-d H:i:s', strtotime($request->publish_at)) : date('Y-m-d H:i:s')
            ]);

            if($post){
                $post_id = $post->id;
                $postSection = [];
                foreach ($request->post_section as $key => $value) {
                    $postSection[] = [
                        'post_id' => $post->id,
                        'content' => $value
                    ];
                }

                if(! empty($postSection)){
                    $tambahSection = PostSection::insert($postSection);

                    if(! $tambahSection){
                        $output['header']['message'] = 'Error when save section article!';
                        $continue = false;
                    }
                }else{
                    $output['header']['message'] = 'Section article is cannot empty!';
                }

                if ($continue) {
                    $postImage = [];

                    foreach ($request->file("image.file") as $key => $image) {
                        $filenametostore = Helpers::uploadImage($image, $this->path_upload . '/' . $post->id, $this->image_dimensions);
                        if($filenametostore){
                            $temp_images[] = $filenametostore;
                            $postImage[] = [
                                'post_id' => $post->id,
                                'name' => $filenametostore,
                                'is_main' => ($key == 0) ? 1 : 0,
                                'note' => isset($request->image['note'][$key]) ? $request->image['note'][$key] : null
                            ];
                        }
                    }

                    if(! empty($postImage)){
                        $postImage = PostImage::insert($postImage);

                        if(! $postImage){
                            $output['header']['message'] = 'Error when create post image!';
                            $continue = false;
                        }
                    }
                }

            }else{
                $continue = false;
            }

            if ($continue) {
                // rollback
                DB::commit();

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Store post resource is success';
                $output['data'] = new PostResource($post->with(['postSection']));
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
            if (! empty($temp_images)) {
                foreach ($temp_images as $key => $value) {
                    Helpers::deleteUploadImage($value, $this->path_delete . "/{$post_id}", $this->image_dimensions);
                }
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
        $post = Post::with(['postSection', 'postImage', 'lastComment'])->find($id);

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

        $post = new PostResource($post);
        $end_process = microtime(true);
        $output = [
            'header' => [
                'process_time' => $end_process - $start_process,
                'status' => Response::HTTP_OK,
                'message' => 'Get detail of post resource is success',
            ],
            'data' => $post,
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

        $post = Post::find($id);
        if(empty($post)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('posts')->ignore($post->id),
            ],
            'summary' => ['required', 'string'],
            'status' => ['required', 'numeric', 'in:0,1'],
            'category_id' => ['required', 'numeric'],
            'publish_at' => ['nullable', 'datetime'],
            'post_section.*.id' => ['required', 'string'],
            'post_section.*.content' => ['nullable', 'numeric'],
            'image.*.file' => ['image', 'mimes:jpeg,png,jpg,gif,webp'],
            'image.*.note' => ['nullable', 'string']
        ]);

        if($validator->fails()){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            $output['header']['error'] = $validator->errors();

            return response()->json($output, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $continue = true;
        $post_id = $post->id;
        $old_images = $new_images = [];
        $user_id = Auth::id();

        DB::beginTransaction();
        try {
            $post->user_id = $user_id;
            $post->title = $request->title;
            $post->slug = $request->slug;
            $post->summary = $request->summary;
            $post->status = (int) $request->status;
            $post->category_id = (int) $request->category_id;

            if ($request->has('publish_at')) {
                $post->publish_at = date('Y-m-d H:i:s', strtotime($request->publish_at));
            }

            if($post->update()){
                // delete row if not exists
                $deletedRows = PostSection::where('post_id', $post_id)
                    ->whereNotIn('id', array_column($request->post_section, 'id'))
                    ->delete();

                $postSection = [];
                foreach ($request->post_section as $key => $item) {
                    if (isset($item['id'])) {
                        // update post section
                        $temp_post_section = PostSection::find((int) $item['id']);
                        if (! empty($temp_post_section)) {
                            $temp_post_section->content = $item['content'];

                            if (! $temp_post_section->update()) {
                                $output['header']['message'] = 'Error when update section article!';
                                $continue = false;
                            }
                        }
                    }else{
                        $postSection[] = [
                            'post_id' => $post->id,
                            'content' => $value
                        ];
                    }
                }

                if(! empty($postSection)){
                    $tambahSection = PostSection::insert($postSection);

                    if(! $tambahSection){
                        $output['header']['message'] = 'Error when save section article!';
                        $continue = false;
                    }
                }else{
                    $output['header']['message'] = 'Section article is cannot empty!';
                }

                if ($continue) {
                    // delete row if not exists
                    $deletedRows = PostImage::where('post_id', $post_id)
                        ->whereNotIn('id', array_column($request->post_image, 'id'))
                        ->delete();

                    $postImage = [];
                    foreach ($request->image as $key => $item) {
                        if (isset($item['id'])) {
                            // update post image
                            $temp_file = $request->file('image.' . $key . 'file');
                            $temp_post_image = PostImage::find((int) $item['id']);

                            if (! empty($temp_post_image) && ! empty($temp_file)) {
                                $old_images[] = $temp_post_image->name;
                                $filenametostore = Helpers::uploadImage($temp_file, $this->path_upload . '/' . $post->id, $this->image_dimensions);
                                $new_images[] = $filenametostore;

                                $temp_post_image->name = $filenametostore;
                                $temp_post_image->is_main = (isset($item['is_main'])) ? (int) $item['is_main'] : 0;
                                $temp_post_image = isset($request->image['note'][$key]) ? $request->image['note'][$key] : null;
                                if (! $temp_post_image->update()) {
                                    $output['header']['message'] = 'Error when update image article!';
                                    $continue = false;
                                }
                            }
                        }else{
                            $filenametostore = Helpers::uploadImage($image, $this->path_upload . '/' . $post->id, $this->image_dimensions);
                            if($filenametostore){
                                $new_images[] = $filenametostore;
                                $postImage[] = [
                                    'post_id' => $post->id,
                                    'name' => $filenametostore,
                                    'is_main' => ($key == 0) ? 1 : 0,
                                    'note' => isset($request->image['note'][$key]) ? $request->image['note'][$key] : null
                                ];
                            }
                        }
                    }

                    if(! empty($postImage)){
                        $postImage = PostImage::insert($postImage);

                        if(! $postImage){
                            $output['header']['message'] = 'Error when create post image!';
                            $continue = false;
                        }
                    }
                }

            }else{
                $continue = false;
            }

            if ($continue) {
                // rollback
                DB::commit();

                $output['header']['status'] = Response::HTTP_ACCEPTED;
                $output['header']['message'] = 'Update post resource is success';
                $output['data'] = new PostResource($post->with(['postSection']));
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

        if($continue){
            // delete if image is exist
            if (! empty($old_images)) {
                foreach ($old_images as $key => $value) {
                    Helpers::deleteUploadImage($value, $this->path_delete . "/{$post_id}", $this->image_dimensions);
                }
            }
        }else{
            // delete if image is exist
            if (! empty($new_images)) {
                foreach ($new_images as $key => $value) {
                    Helpers::deleteUploadImage($value, $this->path_delete . "/{$post_id}", $this->image_dimensions);
                }
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

        $post = Post::with(['postImage'])->find($id);
        if(empty($post)){
            $end_process = microtime(true);
            $output['header']['process_time'] = $end_process - $start_process;
            $output['header']['status'] = Response::HTTP_NOT_FOUND;
            $output['header']['message'] = 'Data is not found';

            return response()->json($output, Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            $post_id = $post->id;
            $old_images = [];
            foreach ($post->postImage as $key => $item) {
                $old_images[] = $item['name'];
            }

            if($category->delete()){
                // commit
                DB::commit();

                // delete if image is exist
                if(! empty($old_images)){
                    foreach ($old_images as $key => $value) {
                        Helpers::deleteUploadImage($value, $this->path_delete . "/{$post_id}", $this->image_dimensions);
                    }
                }

                $output['header']['status'] = Response::HTTP_OK;
                $output['header']['message'] = 'Delete of post resource is success';
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
