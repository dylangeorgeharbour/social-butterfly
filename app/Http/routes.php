<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');



//Social Login
Route::get('/login/{provider}', 'Auth\AuthController@socialAuthRedirect')->name('auth.social.redirect');
Route::get('/login/{provider}/callback', 'Auth\AuthController@socialAuthCallback')->name('auth.social.callback');


