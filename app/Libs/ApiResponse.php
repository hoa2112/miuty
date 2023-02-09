<?php
namespace App\Libs;

use App\Models\ApiServerLog;

class ApiResponse{
    const SUCCESS = 0;
    const API_SIGN_EMPTY = 1;
    const API_KEY_EMPTY = 2;
    const API_KEY_NOT_EXISTED = 3;
    const API_SIGN_NOT_VALID = 4;
    const BAD_REQUEST = 5;
    const ACCESS_DENIED = 6;
    const PARAM_NOT_VALID = 7;
    const AUTHORIZE_FAIL = 8;

    //customer
    const EMPTY_DATA = 9;
    const SAVE_ERROR = 15;
    const PASSWORD_FAIL = 10;
    const CUSTOMER_NOT_ACTIVE = 11;
    const CUSTOMER_NOT_FOUND = 12;
    const PASSWORD_CONFIRM_FAIL = 13;
    const IMAGE_UPLOAD_FAIL = 14;


    public static function getMsgApi($err_code = 0){
        switch ($err_code){
            case self::SUCCESS: return 'Success';
            //api response
            case self::API_SIGN_EMPTY: return 'Api sign is empty!!!';
            case self::API_KEY_EMPTY:  return 'Api key is empty!!!';
            case self::API_KEY_NOT_EXISTED:  return 'Api key is invalid!!!';
            case self::API_SIGN_NOT_VALID:  return 'Api sign is invalid!!!';
            case self::BAD_REQUEST: return 'Bad request!';
            case self::ACCESS_DENIED: return 'Access denied!';
            case self::PARAM_NOT_VALID: return 'Params invalid!';
            case self::AUTHORIZE_FAIL: return 'Authorize fail!';
            case self::EMPTY_DATA: return 'Empty data!';
            case self::SAVE_ERROR: return 'Error!';

            //customer response
            case self::PASSWORD_FAIL: return 'Password not match!';
            case self::CUSTOMER_NOT_ACTIVE: return 'Not actived yet!';
            case self::CUSTOMER_NOT_FOUND: return 'Not existed!';
            case self::PASSWORD_CONFIRM_FAIL: return 'Confirm password not match!';
            case self::IMAGE_UPLOAD_FAIL: return 'Creating image was failed!';
        }
        return 'Nothing to do';
    }

    public static function sendErr($code = 5, $message = '', $data = [], $exit = false){
        if(is_array($message)){
            $message = implode('. ', $message);
        }
        $ret = [
            'code' => $code,
            'message' => self::getMsgApi($code) . ' '. $message,
            'data' => $data
        ];
        $return = response()->json($ret, 400);

        ApiServerLog::log(['error' => json_encode($ret)]);
        if($exit){
            die($return);
        }
        return $return;
    }

    public static function send($data = [], $message = '', $exit = false){
        $ret = [
            'code' => 0,
            'message' => self::getMsgApi(0) . ' '. $message,
            'data' => $data
        ];
        //build log
        $log = ['return' => json_encode($ret)];
        ApiServerLog::log($log);

        $return = response()->json($ret);
        if($exit){
            die($return);
        }
        return $return;
    }
}