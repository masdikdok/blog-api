<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

use App\Models\PostSection;
use App\Http\Resource\PostSectionResource;
use App\CustomHelpers\Helpers;

class PostSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function index(Request $request, $post_id)
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

         $query = PostSection::where('post_id', $post_id);

         $total_rows = $query->count();
         $post_sections = $query
             ->take($filter['per_page'])
             ->skip($filter['per_page'] * ($filter['page'] - 1))
             ->get();

         $filter['total_page'] = ceil($total_rows / $filter['per_page']);
         $filter['total_rows'] = $total_rows;
         $post_sections = PostSectionResource::collection($posts);

         $end_process = microtime(true);
         $output = [
             'header' => [
                 'process_time' => $end_process - $start_process,
                 'status' => Response::HTTP_OK,
                 'message' => 'Get list of post section resource is success',
             ],
             'data' => [
                 'filter' => $filter,
                 'sections' => $posts
             ],
         ];

         return response()->json($output);
     }

}
