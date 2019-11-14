<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | is assigned the "api" middleware group. Enjoy building your API!
 * |
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware([
    'auth:api'
])->group(function () {
    Route::prefix('files')->group(function () {
        Route::get('/', 'API\MediaController@index');
        Route::post('/upload', 'API\MediaController@store');
        Route::delete('/{id}', 'API\MediaController@destroy');
    });
});

Route::middleware([
    'auth:api'
])->group(function () {
    Route::prefix('projects')->group(function () {
        Route::get('/', 'API\ProjectsController@index');
        Route::get('/{id}', 'API\ProjectsController@show');
        Route::get('/{id}/files', 'API\ProjectsController@getAvailableFiles');
        Route::get('/{id}/inputs', 'API\ProjectsController@getInputs');
        Route::post('/{id}/inputs', 'API\ProjectsController@updateInputs');
        Route::post('/create', 'API\ProjectsController@store');
        Route::delete('/{id}', 'API\ProjectsController@destroy');
    });
});