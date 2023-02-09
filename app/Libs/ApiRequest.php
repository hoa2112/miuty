<?php
namespace App\Libs;

use App\Models\Customer;
use App\Models\CustomerLog;
use Illuminate\Http\Request;

class ApiRequest{

    public static function validAccessToken($token, $request_from = 'app', &$id = 0){
        $old = CustomerLog::where([
            ['request_from', '=', $request_from],
            ['request_type', '=', 'login'],
            ['access_token', '=', $token],
        ])
            ->orderBy('created_at', 'desc')
            ->first();
        if($old) {
            $id = $old->request_id;
            return $old->validAccessToken();
        }
        return false;
    }

    public static function validRequest(Request $request, $arrValidate, &$errors = ''){
        $allData = $request->all();
        if(empty($allData)){
            $allData = $request->json()->all();
        }
        $validator = \Validator::make($allData, $arrValidate['rules'], !empty($arrValidate['messages']) ? $arrValidate['messages'] : []);
        if($validator->fails()){
            $errors = $validator->errors()->toArray();
            $eArr = array_keys($errors);
            $firstCode = array_shift($eArr);
            $code = !empty($arrValidate['codes']) && !empty($arrValidate['codes'][$firstCode]) ? $arrValidate['codes'][$firstCode] : 1;
            $errors = ApiResponse::sendErr($errors[$firstCode][0], $code);
            return false;
        }
        return true;
    }

    public static function validAgencyRequest(Request $request, &$err = ''){
        $phone = $request->phone;
        $email = $request->email;
        $id = $request->id;

        if(self::validAccessToken($request->access_token, 'app', $request_id)) {
            if (!empty($id)) {
                $agency = Customer::whereId($id)->first();
            }
            if (empty($agency) && !empty($email)) {
                $agency = Customer::whereEmail($email)->first();
                if($agency) {
                    $id = $agency->id;
                }
            }
            if (empty($agency) && !empty($phone)) {
                $agency = Customer::wherePhone($phone)->first();
                if($agency) {
                    $id = $agency->id;
                }
            }
            if($id == $request_id) {
                if (!empty($agency)) {
                    if ($agency->status > 0 && $agency->active > 0) {
                        return $agency;
                    }
                    $err = ApiResponse::sendErr('Agency is banned or not actived yet!', 3);
                    return false;
                }
                $err = ApiResponse::sendErr('Agency not existed!', 2);
                return false;
            }
        }
        $err = ApiResponse::sendErr('Access denied!', 5);
        return false;
    }
}