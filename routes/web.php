<?php

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |
 */
Route::get('/', 'MediaClerk@index');

Route::prefix('storage')->group(function () {
    Route::get('/thumbs/{mediaId}/{ratio?}', 'MediaClerk@getThumb');
    Route::get('/media/{mediaId}', 'MediaClerk@getMedia');
});

Auth::routes();