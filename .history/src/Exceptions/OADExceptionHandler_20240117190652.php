<?php

namespace Oadsoft\Crmcore\Exceptions;

use Exception, App, Auth, Request;
/**
 *
 */
class OADExceptionHandler extends Exception
{

    public static function report(Exception $e)
    {

        $data = [
            'client'        => config('client'),
            'developer'     => config('developer'),
            'user_id'       => Auth::check() ? Auth::user()->id : '',
            'user_name'     => Auth::check() ? Auth::user()->name : '',
            'ip'            => Request::ip(),
            'datetime'      => date('Y-m-d h:m:s'),
            'url'           => Request::url(),
            'exception'     => get_class($e),
            'line'          => $e->getLine(),
            'file'          => $e->getFile(),
            'msg'           => $e->getMessage(),
            'code'          => $e->getCode(),
            'request'       => Request::all(),
            'env'           => App::environment(),
            'trace'         => $e->getTrace()
        ];
        // file_put_contents('log.txt', json_encode($data));
    }
}
