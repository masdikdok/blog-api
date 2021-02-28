<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function(){
    /*
        Endpoint Category
    */
    Route::resource('category', CategoryController::class)->except(['index', 'edit', 'create']);
    Route::get('category/{page?}/{per_page?}', [CategoryController::class,'index'])->name('category.index');

    /*
        Endpoint Tag
    */
    Route::resource('tag', TagController::class)->except(['index', 'edit', 'create']);
    Route::get('tag/{page?}/{per_page?}', [TagController::class,'index'])->name('tag.index');

});
