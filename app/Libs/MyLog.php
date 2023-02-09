<?php
namespace App\Libs;

use App\Models\UserLog;
use Illuminate\Support\Facades\Route;
use Jenssegers\Agent\Agent;

class MyLog{
    protected static $instance;

    public function __construct(){}

    public static function do() {
        if (empty(self::$instance)) {
            self::$instance = new MyLog();
        }
        return self::$instance;
    }

    public function add($model, $key, $object_id = 0, $before = null, $after = null, $note = '')
    {
        try {
            $mobileDetect = new Agent();
            $isMobile = $mobileDetect->isMobile();

            if(empty($before)) {
                $before = [];
            }elseif (is_object($before)) {
                $before = $before->toArray();
            }
            $before = json_encode($before, JSON_UNESCAPED_UNICODE);

            if(empty($after)) {
                $after = [];
            }elseif (is_object($after)) {
                $after = $after->toArray();
            }
            $after = json_encode($after, JSON_UNESCAPED_UNICODE);

            // create new user log
            $log = new UserLog([
                'object_id' => $object_id,
                'user_id' => \Auth::id(),
                'action' => $key,
                'route' => Route::currentRouteName(),
                'ip' => request()->ip(),
                'url' => url()->full(),
                'env' => Lib::appEnv(),
                'device' => $isMobile ? 1 : 0,
                'note' => $note,
                'model' => $model,
                'before' => $before,
                'after' => $after,
                'no_change' => $before == $after ? 1 : 0
            ]);
            $log->save();

        } catch (\Exception $e) {
            throw $e;
        }

        return $log;
    }
}