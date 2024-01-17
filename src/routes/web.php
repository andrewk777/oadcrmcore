<?php

Route::group(
    [
        'namespace'     => 'App\Http\Controllers\Common'
    ],
    function() {

        Route::post('login', [ 'as' => 'login', 'uses' => 'AuthController@login' ]);
        Route::get('logout', [ 'as' => 'logout', 'uses' => 'AuthController@logout' ]);
        
        //send a request to reset password
        Route::post('resetPasswordSendEmail', 'AuthController@resetPasswordSendEmail' );

        Route::post('verifyDevice', 'AuthController@verifyDevice');
        
        //validate reset token
        Route::get('auth/reset_password_link/{email}/{token}', [
            'as'    => 'password.reset',
            'uses'  =>'AuthController@resetPasswordLink'
        ]);
        
        //update passwords
        Route::post('resetPasswordComplete', 'AuthController@resetPasswordComplete'); //submit new password
        
        Route::get('view_file/{hash}/{name?}', 'FileController@view')->where('hash', '([a-zA-Z0-9\-]+)');
        Route::get('download_file/{hash}/{name?}', 'FileController@download')->where('hash', '([a-zA-Z0-9\-]+)');
        Route::get('view-tmp-file/{file_name}', 'FileController@view_tmp_file')->where('file_name', '([a-zA-Z0-9\_\-\.]+)');
    

    }
);

//web route - point all requests to this view
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|sanctum|view_file|download_file\/)[\/\w\.-]*')->middleware('web'); 