<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const RESPONSE_ERROR      = 0;
    const RESPONSE_SYNC       = 1;
    const RESPONSE_SUCCESS    = 2;

    const AUTH_ERROR_CODE     = 0;
    const VALIDATE_ERROR_CODE = 1;
    const PROCESS_ERROR_CODE  = 2;

    public function responseSuccess($data, $message = ''){
        return ['status'=> self::RESPONSE_SUCCESS, 'data' => $data, 'message' => $message];
    }

    public function responseSync($data, $message = ''){
        return ['status'=> self::RESPONSE_SYNC, 'data' => $data, 'message' => $message];
    }

    public function responseError($data, $code = 0, $message = ''){
        return ['status'=> self::RESPONSE_ERROR, 'data' => $data, 'code' => $code, 'message' => $message];
    }
}
