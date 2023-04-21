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


// Common
Route::get('/contact', 'TelegramBotController@contactForm');
Route::post('/send-message', 'TelegramBotController@storeMessage');
Route::any('bot/{botname}', 'TelegramBotController@index')->name('bot.webhook');
Route::get('/autoSend', 'TelegramBotController@index')->name('tele.autoSend');
Route::get('/updated-activity', 'TelegramBotController@updatedActivity');


// MiuTy
Route::get('/listEmployee', 'TelegramBotController@listEmployee');
Route::get('/bookFood', 'TelegramBotController@bookFood');
Route::get('/kickOff', 'TelegramBotController@kickOff');


// Runner
Route::get('/registerRun', 'TelegramBotController@registerRun');


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