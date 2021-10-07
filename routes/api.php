<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\data;
use App\Http\Controllers\data_analytics;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::group(['middleware' => ['Cors']],function(){
Route::post('data', [data::class, 'list']);
Route::post('chart', [data::class, 'chart']);
// });
Route::post('analytics/chart', [data_analytics::class, 'chart']);
Route::post('analytics/correlation', [data_analytics::class, 'correlation']);




