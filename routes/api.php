<?php
use Illuminate\Http\Request;

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

// Route::get('/test', 'MediaClerk@test');

// Route::middleware('auth:api', 'throttle:60,1')->group(function () {
//     Route::get('/test', function () {
//         return 'hi';
//     });
// });

// Route::middleware(['auth:api'])->group(function () {
//     Route::get('test', 'MediaClerk@test');
// });

Route::get('/demo', 'MediaClerk@test');
//     Route::get('/demo', function(Request $request){
//         response(['hi'=>'fucker'])->send();
//     });