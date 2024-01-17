<?php

// $router->addResourceRoute('notes','Common\NoteController');
// $router->addResourceRoute('files','Common\FileController');

Route::group(
    [
        'middleware'    => ['auth:sanctum','App\Http\Middleware\OADAuth'],
        'namespace'     => 'App\Http\Controllers'
    ], 
    function() use ($router) 
    {
        Route::get('layout', 'Common\LayoutController@full_menu');  
        Route::post('file-upload', 'Common\FileController@store');
        Route::post('change-file-name', 'Common\FileController@changeFileName');
        Route::post('file-delete', 'Common\FileController@delete');
        Route::get('auth-check', 'Common\AuthController@auth_check');

        //developers tools
        Route::get('devtools/build-menu', 'DEV\NavBuilderController@getSectionsTree');
        Route::post('devtools/build-menu', 'DEV\NavBuilderController@storeSectionsTree');
        Route::post('devtools/build-menu/saveSection', 'DEV\NavBuilderController@storeSection');
        Route::post('devtools/build-menu/storeVueRoute', 'DEV\NavBuilderController@storeVueRoute');
        Route::post('devtools/build-menu/delete', 'DEV\NavBuilderController@delete');
        Route::get('devtools/build-menu/generatevuejs', 'DEV\NavBuilderController@generatevuejs');

        Route::get('devtools/getRoutesTree', 'DEV\NavBuilderController@getRoutesTree');

        Route::post('devtools/get-routes', 'DEV\NavBuilderController@listRoutes');
        Route::get('devtools/build-menu/iseed', 'DEV\NavBuilderController@updateSeeds');

        //allows to call additional methods for the resource controllers
        Route::any('/d/{routeUrl}/{method}', function($routeUrl,$method) use ($router) {
            return App::call('App\Http\Controllers\\' . $router->getRouteController($routeUrl) . '@' . $method);
        });

        $router->genFormRoutes()->genResourceRoutes()->genSingleRoutes();

    }
);