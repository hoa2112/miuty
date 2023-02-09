<?php
namespace App\Libs;

use App\Models\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;
use Ramsey\Uuid\Uuid;

class MyLogin{
    protected static $instance;

    protected $myDeviceKey = 'myDeviceAuthorize';

    protected $my2FaKey = 'my2Fa';

    protected $myCookieKey = 'remember-device';

    protected $myCookieTime = 60 * 24 * 365; //365 ngay

    public function __construct(){}

    public static function do() {
        if (empty(self::$instance)) {
            self::$instance = new MyLogin();
        }
        return self::$instance;
    }

    public function save2Fa($type = 'user'){
        $data = null;
        if($type == 'user'){
            $data = Auth::user();
        }else{
            $data = Auth::guard('customer')->user();
        }
        $key2Fa = $this->getKey($type, '2fa');
        session([$key2Fa => $data]);
    }

    public function isActive2Fa($type = 'user'){
        $key2Fa = $this->getKey($type, '2fa');
        $dataUser = session($key2Fa, null);
        return !empty($dataUser);
    }

    public function isActiveDevice($type = 'user', $id = 0){
        $dataDevice = $this->getDevice($type, $id);
        return !empty($dataDevice) && $dataDevice->isActive();
    }

    public function saveDevice($type = 'user', $active = false){
        $authorize = $this->checkAndMakeNewDevice();
        if(!empty($authorize) && $active) {
            //update active
            if($active) {
                $authorize->active();
            }

            //savecookie
            $id = $authorize->user_id ?? $authorize->customer_id;
            $keyCookie = $this->getKey($type, 'cookie', $id);
            $dataCookie= $this->dataCookie([
                'type' => $type,
                'id' => $id,
                'token' => $authorize->token
            ]);
            Cookie::queue($keyCookie, $dataCookie, $this->myCookieTime);

            //save session
            $this->setDevice($authorize, $type);
        }
    }

    public function setDevice($authorize, $type = 'user'){
        $keyDevice = $this->getKey($type, 'device');
        session([$keyDevice => $authorize]);
    }

    public function getDevice($type = 'user', $id = 0){
        $keyDevice = $this->getKey($type, 'device');
        $dataDevice= session($keyDevice, null);
        if(empty($dataDevice)){
            //auto load
            $keyCookie = $this->getKey($type, 'cookie', $id);
            $dataCookie = Cookie::get($keyCookie);
            $dataCookie = $this->dataCookie($dataCookie, false);
            if(!empty($dataCookie)){
                $dataDevice = $this->autoLoadDevice($dataCookie, $type);
                if(!empty($dataCookie)){
                    $this->setDevice($dataDevice, $type);
                }
            }
        }
        return $dataDevice;
    }

    protected function getKey($type = 'user', $typeKey = 'device', $id = 0){
        switch ($typeKey){
            case 'device':
                return sprintf('%s-%s', $this->myDeviceKey, $type);
            case '2fa':
                return sprintf('%s-%s', $this->my2FaKey, $type);
            case 'cookie':
                return sprintf('%s-%s-%s', $this->myCookieKey, $type, $id);
        }
        return '';
    }

    protected function dataCookie($data, $make = true){
        if($make){
            return !empty($data) ? sprintf('%s|%s|%s', $data['type'], $data['id'], $data['token']) : '';
        }
        if(empty($data)){
            return false;
        }
        $data = explode('|', $data);
        return [
            'type' => $data[0],
            'id' => $data[1],
            'token' => $data[2]
        ];
    }

    protected function autoLoadDevice($data, $type = 'user'){
        $item = Authorize::where('token', $data['token']);
        if($type == 'user'){
            $item->where('user_id', $data['id']);
        }else{
            $item->where('customer_id', $data['id']);
        }
        return $item->first();
    }

    protected function checkAndMakeNewDevice($type = 'user')
    {
        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->device();
        $lang = $agent->languages();
        if($type == 'user') {
            $userId = $type == 'user' ? Auth::id() : 0;
        }else{
            $userId = 0;
        }
        if($type == 'customer') {
            $customerId = $type == 'customer' ? Auth::guard('customer')->id() : 0;
        }else{
            $customerId = 0;
        }
        $ip = request()->ip();

        $item = Authorize::where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->where('ip_address', $ip)
            ->where('browser', $browser)
            ->where('platform', $platform)
            ->where('device', $device)->first();
        if(empty($item)){
            $item = new Authorize([
                'user_id' => $userId,
                'customer_id' => $customerId,
                'ip_address' => $ip,
                'browser_version' => $agent->version($browser),
                'browser'  => $browser,
                'platform_version' => $agent->version($platform),
                'platform' => $platform,
                'device' => $device,
                'languages' => is_string($lang) ?: json_encode($lang),
                'token' => Uuid::uuid4()
            ]);
            $item->save();
        }
        return $item;
    }
}