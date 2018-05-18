<?php

/**
 * @author Mahad Tech Solutions
 * Define all Routes needed by the framework to make the authentication process
 */
Route::group(['prefix' => 'auth'], function(){
    Route::get('/welcome', ['middleware' => 'remembered', 'Yuga\Controllers\WelcomeController@getWelcome'])->name('yuga.welcome');
    Route::form("/signin", 'Yuga\Controllers\AuthController@getSignin')->name('yuga.auth.signin');
    Route::form("/signup", 'Yuga\Controllers\AuthController@getSignup')->name('yuga.auth.signup');
    Route::get("/signout", 'Yuga\Controllers\AuthController@getSignout')->name('yuga.auth.signout');
    Route::form("/reset", 'Yuga\Controllers\AuthController@getReset')->name('yuga.auth.reset');
});

Route::get('/not-found', 'Yuga\Controllers\PageController@notFound');

Route::error(function(Request $request, Exception $exception) {
    
    if($exception instanceof NotFoundHttpException && $exception->getCode() == 404) {
        $request->setRewriteCallback('Yuga\Controllers\PageController@notFound');
        return $request;
    }
});