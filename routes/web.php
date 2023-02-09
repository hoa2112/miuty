<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// GET

Route::get('/autoSend', 'TelegramBotController@index')->name('tele.autoSend');
Route::get('/listEmployee', 'TelegramBotController@listEmployee');
Route::get('/contact', 'TelegramBotController@contactForm');
Route::get('/bookFood', 'TelegramBotController@bookFood');
Route::get('/kickOff', 'TelegramBotController@kickOff');
Route::get('/updated-activity', 'TelegramBotController@updatedActivity');

// POST
Route::post('/send-message', 'TelegramBotController@storeMessage');

// CACHE
Route::get('/clear-cache', function() {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('cache:clear');
    // return what you want
    echo $exitCode;
    echo 'Clear Done!';
});
//Clear Config cache:
Route::get('/config-cache', function() {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('config:cache');
    return '<h1>Clear Config cleared</h1>';
});