<?php
namespace App\Libs;

use App\Models\ConfigSite;
use App\Models\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class Lib{
    protected static $env;
    protected static $modules;
    protected static $breadcrumb = [];
    protected static $defLang;
    protected static $local = 'local';
    protected static $dev = 'product-dev';
    protected static $storage = [];
    protected static $days = array('Mon' => 'Thứ 2', 'Tue' => 'Thứ 3', 'Wed' => 'Thứ 4', 'Thu' => 'Thứ 5', 'Fri' => 'Thứ 6', 'Sat' => 'Thứ 7', 'Sun' => 'Chủ nhật');

    /* ****************
    *                 *
    *  BASE FUNTIONS  *
    *                 *
    ******************/
    public static function appEnv(){
        if(empty(self::$env)){
            //Load file config/module.php
            $app_env = env('APP_ENV', self::$local);

            if (Str::contains(request()->getBaseUrl(), '/admin') || \Request::is('admin/*')) {
                $app_env = 'admin';
            }

            if (Str::contains(request()->getBaseUrl(), '/api') || \Request::is('api/*')) {
                $app_env = 'api';
            }

            $noNeedMobile = ['admin', 'api'];
            if(!in_array($app_env, $noNeedMobile)) {
                $mobile_active = self::getSiteConfig('mobile_active', 0);
                if ($mobile_active == 1) {
                    $mobileDetect = new Agent();
                    if ($mobileDetect->isMobile()){
                        switch($app_env){
//                            case 'wowbiz':
//                                $app_env = 'wowbizmobile';
//                                break;
                            default:
                                $app_env = 'mobile';
                        }
                    }
                }
            }
            if($app_env == self::$local){
                $urls = parse_url(request()->url());
                switch ($urls['host']){
//                    case 'ta.local':case 'ta.todo.vn':
//                        $app_env = 'ta-dev';
//                        break;
                    default:
                        $app_env = self::$dev;
                }
            }
            self::$env = $app_env;
        }
        return self::$env;
    }

    public static function modules(){
        if(empty(self::$modules)) {
            self::$modules = config('module');
        }
        return self::$modules;
    }

    public static function module_config($key = '', $def = ''){
        //load module config
        $modules = self::modules();

        //load env
        $env = self::appEnv();
        if(isset($modules[$env]) && isset($modules[$env][$key])){
            return $modules[$env][$key];
        }
        return $def;
    }

    public static function config($key = '', $def = ''){
        //load filename config
        $conf_name = self::module_config('config_name');
        $conf = config($conf_name, array());
        if(!empty($conf) && !empty($conf[$key])){
            return $conf[$key];
        }
        return $def;
    }

    public static function getSiteConfig($key = '', $default = ''){
        $config = self::get('site_config', null);
        if(empty($config)) {
            $config = ConfigSite::getConfig('config');
            $config = !empty($config) ? json_decode($config, true) : null;
            self::set('site_config', $config);
        }
        if(!empty($key)){
            return !empty($config[$key]) ? $config[$key] : $default;
        }
        return $config;
    }

    public static function set($k, $v){
        self::$storage[$k] = $v;
    }

    public static function get($k, $def = null){
        return isset(self::$storage[$k]) ? self::$storage[$k] : $def;
    }

    /* ***************
    *                *
    * BLADE FUNTIONS *
    *                *
    *****************/
    public static function addBreadcrumb($title = '', $link = '', $def = true){
        if($title == '' && $link == ''){
            $defVal = self::defaultBreadcrumb();
            $title = $defVal['title'];
            $link = $defVal['link'];
        }elseif(empty(self::$breadcrumb) && $def){
            self::addBreadcrumb();
        }
        $key = md5($link != '' ? $link : $title);
        self::$breadcrumb[$key] = array(
            'title' =>	ucwords($title),
            'link'	=>	$link
        );
    }

    public static function defaultBreadcrumb($folder = ''){
        if(empty($folder)){
            $folder = self::module_config('folder_name');
        }
        return [
            'folder' => $folder,
            'title' => __($folder == 'BackEnd' ? 'admin.trangchu' : 'site.trangchu'),
            'link' => route($folder == 'BackEnd' ? 'admin.home' : 'home')
        ];
    }

    public static function renderBreadcrumb($extraCommand = [], $noExtra = false){
        $html = '';
        if(!empty(self::$breadcrumb)) {
            $defVal = self::defaultBreadcrumb();
            if($defVal['folder'] == 'BackEnd' && !$noExtra && empty($extraCommand)){
                $routeName = \Route::current()->getName();
                if(\Route::has($routeName.'.add')){
                    $key = explode('.', $routeName);
                    $size= count($key);
                    if($size >= 2) {
                        if ($size > 2) {
                            $routeName = explode('.', $routeName);
                            $routeName = array_chunk($routeName, 2);
                            $routeName = implode('.', $routeName[0]);
                        }
                        $extraCommand = [
                            [
                                'title' => __('admin.themmoi'),
                                'link' => $routeName . '.add',
                                'icon' => 'icon-plus',
                                'perm' => self::can(false, 'add', $key[1])
                            ]
                        ];
                    }
                }
            }
            $html = \View::make($defVal['folder'].'::layouts.breadcrumb', [
                'breadcrumb' => self::$breadcrumb,
                'defBr' => $defVal,
                'extraCommand' => $extraCommand
            ])->render();
        }
        return $html;
    }

    public static function addMedia($src = '', $echo = false, $extend = []){
        $html = '';
        if(!empty($src)){
            $version = ['ver' => self::getSiteConfig('version', rand())];
            $link = self::isUrl($src) ? $src : asset($src);
            $echo = $echo && !self::isUrl($src);

            //rebuild link
            $parseLink = parse_url($link);
            if(!empty($parseLink['query'])){
                parse_str($parseLink['query'], $queryString);
                $queryString = array_merge($queryString, $version);
                $link = str_replace('?'.$parseLink['query'], '', $link);
                if($echo){
                    $src = str_replace('?'.$parseLink['query'], '', $src); //clean src from query string for echo
                }
            }else{
                $queryString = $version;
            }
            if(!$echo){
                $link .= '?' . http_build_query($queryString);
            }

            //more html properties
            $moreExt = '';
            if(!empty($extend)){
                foreach($extend as $k => $v){
                    if(!empty($v)) {
                        $moreExt .= sprintf(' %s="%s"', $k, $v);
                    }else{
                        $moreExt .= ' '.$k;
                    }
                }
            }
            $ext = pathinfo($parseLink['path'], PATHINFO_EXTENSION);
            switch ($ext){
                case 'js':case 'ok':
                if($echo){
                    $fileName = sprintf('%s/public/%s', base_path(), $src);
                    $contentJs = \File::exists($fileName) ? file_get_contents($fileName) : '';
                    if(!empty($contentJs)) {
                        $html = sprintf('<script type="text/javascript"%s>%s</script>', $moreExt, $contentJs);
                    }
                }else {
                    $html = sprintf('<script type="text/javascript" src="%s"%s></script>', $link, $moreExt);
                }

                break;
                case 'css':
                    if($echo){
                        $fileName = sprintf('%s/public/%s', base_path(), $src);
                        $contentCss = \File::exists($fileName) ? file_get_contents($fileName) : '';
                        if(!empty($contentCss)) {
                            $html = sprintf('<style type="text/css">%s</style>', $contentCss);
                        }
                    }else {
                        $html = sprintf('<link href="%s" rel="stylesheet">', $link);
                    }
                    break;
                case 'png':
                case 'jpg':
                case 'ico':
                case 'gif':
                    $html = sprintf('<link href="%s" rel="shortcut icon">', $link);
                    break;
            }
        }
        return $html;
    }

    public static function siteTitle($title = '', $def = '', $is_admin = false){
        $host = \Request::getHost();
        $title = trim($title != '' ? $title : $def);
        if($is_admin) {
            return '<title>' . ($title == '' ? $host : ($title . ' - ' . $host)) . '</title>';
        }
        return '<title>' . ($title == '' ? $host : ($host . ' - ' . $title)) . '</title>';
    }

    public static function tplShareGlobal($module_dir = ''){
        //load config website
        $config = self::getSiteConfig();
        $config2 = [
            'site_description' => !empty($config['description']) ? $config['description'] : '',
            'site_keyword' => !empty($config['keywords']) ? $config['keywords'] : '',
            'site_title' => !empty($config['site_name']) ? $config['site_name'] : config('app.name'),
            'site_media' => self::config('media', array()),
            'site_css' => self::config('css', array()),
            'site_js' => self::config('js', array()),
            'site_js_val' => [
                'SITE_NAME' => !empty($config['site_name']) ? $config['site_name'] : config('app.name'),
                'BASE_URL' => asset('/'.$module_dir).(!empty($module_dir)?'/':''),
                'PUBLIC_URL' => asset('/'),
                'LANG' => self::getDefaultLang(),
                'Hotline' => $config['hotline'],
                'isAdminUrl' => self::isAdminUrl() ? 1 : 0,
                'MAX_UPLOAD_SIZE' => ImageURL::getMaxFileSizeImage()
            ]
        ];
        if(!empty($config)) {
            $config += $config2;
        }else{
            $config = $config2;
        }
        //set default
        if(!empty($config['logo_no_active'])){
            $config['logo_img'] = '';
        }
        if(!empty($config['favicon_no_active'])){
            $config['favicon_img'] = '';
        }
        if(empty($config['favicon_img'])){
            $config['favicon_img'] = self::config('favicon', '');
        }
        if(empty($config['version'])){
            $config['version'] = env('APP_VER');
        }
        if(empty($config['site_name'])){
            $config['site_name'] = config('app.name');
        }
        return $config;
    }

    /* *******************
    *                    *
    * EXTENSION FUNTIONS *
    *                    *
    *********************/
    public static function ajaxRespond($success = true, $msg = '', $data = []){
        return [
            'error' => $success ? 0 : 1,
            'msg' => $msg,
            ($success ? 'data' : 'code') => $data
        ];
    }

    public static function getRoutes($fromDB = false, $mix = false, $sort = false){
        if(!$fromDB) {
            $getRouteCollection = \Route::getRoutes(); //get and returns all returns route collection
            $routes = [];
            $ignore = ['admin.checkAuthNow', 'ajax', 'debugbar.openhandler', 'debugbar.clockwork', 'debugbar.assets.css', 'debugbar.assets.js', 'debugbar.cache.delete', 'debugbar.telescope'];
            foreach ($getRouteCollection as $route) {
                $name = $route->getName();
                if (!empty($name) && !in_array($name, $ignore)) {
                    //bo neu no la post
                    if(!Str::contains($name, ['.post', '.delete', '.edit'])) {
                        array_push($routes, $name);
                    }
                }
            }
            if($sort){
                sort($routes);
            }
        }else{
            $public = ConfigSite::getConfig('public-routes', []);
            $admin = ConfigSite::getConfig('admin-routes', []);
            $routes = [
                'public' => !empty($public) ? json_decode($public) : [],
                'admin'  => !empty($admin) ? json_decode($admin) : []
            ];
            if($sort){
                sort($routes['public']);
                sort($routes['admin']);
            }
            if($mix){
                $routes = array_merge($routes['public'], $routes['admin']);
                if($sort){
                    sort($routes);
                }
            }
        }
        return $routes;
    }

    public static function saveRoutes($public = true){
        $key = $public ? 'public-routes' : 'admin-routes';
        $routes = self::getRoutes();
        ConfigSite::setConfig($key, json_encode($routes));
        return $routes;
    }

    public static function getUrlBack($isButton = false, $client = true){
        $targetUrl = redirect()->back()->getTargetUrl();
        $fullUrl = request()->fullUrl();
        if($targetUrl == $fullUrl){
            if($client) {
                $goback = 'window.history.go(-1)';
                return $isButton ? $goback : 'javascript: ' . $goback;
            }
            $env = self::appEnv();
            switch ($env) {
                case 'admin':
                    return route('admin.home');
                case 'product':case 'product-dev':case 'mobile':
                return route('home');
            }
        }
        if($client) {
            return $isButton ? 'shop.redirect("' . $targetUrl . '")' : $targetUrl;
        }
        return $targetUrl;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param boole $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    public static function getGravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val )
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    /* ******************
    *                   *
    * LANGUAGE FUNTIONS *
    *                   *
    ********************/
    public static function getLanguageOptions($full = false){
        if($full){
            return Language::getLanguages();
        }
        return config('app.locales', []);
    }

    public static function getLanguageKey(){
        return self::module_config('slug_name').'_lang';
    }

    public static function getDefaultLang($code = true){
        $allLang = self::getLanguageOptions();
        if(empty(self::$defLang)){
            $defaultLanguage = config('app.fallback_locale');
            self::$defLang = Cookie::get(self::getLanguageKey(), $defaultLanguage);
            if(empty($allLang[self::$defLang])){
                self::$defLang = Crypt::decrypt(self::$defLang, false);
                self::$defLang = self::parseCookie(self::$defLang, $defaultLanguage);
            }
        }
        if($code) {
            return self::$defLang;
        }
        return !empty($allLang[self::$defLang]) ? $allLang[self::$defLang] : '';
    }

    public static function setLang($lang = ''){
        if(empty($lang)){
            $lang = self::getDefaultLang();
        }
        App::setLocale($lang);
    }

    public static function getJsLanguage($key = 'lang.ok'){
        \Cache::forget($key);
        $strings = \Cache::rememberForever($key, function () {
            $lang = \App::getLocale();

            $files   = glob(resource_path('lang/' . $lang . '/*.php'));
            $strings = [];

            foreach ($files as $file) {
                $name           = basename($file, '.php');
                $strings[$name] = require $file;
            }

            return $strings;
        });
        return json_encode($strings);
    }

    public static function translateTitle($title = '', $type = 'module'){
        $str = '';
        switch($type){
            case 'module':
                $str = sprintf('%s.%s', $type, Str::slug($title));
                break;
            case 'banner':case 'category':case 'customer':case 'page':case 'tag':case 'user':case 'userlog':case 'role':
                $str = sprintf('model.%s-%s', $type, Str::slug($title));
                break;
        }
        return !empty($str) ? __($str) : '';
    }

    /* ******************
    *                   *
    *  PARSER FUNTIONS  *
    *                   *
    ********************/
    public static function parseCookie($cookieValue, $default = ''){
        $cookieValue = explode('|', $cookieValue);
        if(!empty($cookieValue[1])){
            return $cookieValue[1];
        }
        return $default;
    }

    public static function numberFormat($number = 0){
        if($number >= 1000){
            return number_format($number,0,',','.');
        }
        return $number;
    }

    public static function priceFormat($price = 0,$currency = 'đ'){
        if($currency == ''){
            $currency = self::getSiteConfig('currency', 'đ');
        }
        return self::numberFormat($price)." $currency";
    }

    public static function dateFormat($time = 0, $format = 'd/m - H:i', $vietnam = false, $show_time = false){
        if(!is_int($time)){
            $time = date_create($time)->getTimestamp();
        }
        $return = date($format,$time);
        if ($vietnam){
            $return = ($show_time ? date('H:i - ',$time) : '') . self::$days[date('D',$time)] . ', ngày ' . date('d/m/Y',$time);
        }
        return $return;
    }

    public static function getTimestamp($time = ''){
        return date_create($time)->getTimestamp();
    }

    public static function getTimestampFromVNDate($str_date = '', $end = false){
        $time_str = str_replace('/', '-', $str_date);
        if($end){
            $time_str .= " 23:59:59";
        }
        return strtotime($time_str);
    }

    public static function getDurationTime($time){
        if($time >= 60){
            $hour = floor($time/60);
            $min = $time - $hour*60;
            return $hour.'h'.($min > 0 ? $min : '0').'m';
        }
        return $time;
    }

    /* ******************
    *                   *
    * CHECKING FUNTIONS *
    *                   *
    ********************/
    public static function can($permArr = false, $keyCheck = '', $key = ''){
        if($key != ''){
            $permArr = self::get('perm-'.$key);
            if(empty($permArr)) {
                $permArr = \Role::getPermOfUserByKey($key);
                self::set('perm-' . $key, $permArr);
            }
        }
        if(!empty($permArr) && !empty($keyCheck) && !empty($permArr[$keyCheck])){
            return $permArr[$keyCheck];
        }
        return false;
    }

    public static function isEmail($email = '') {
        return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", strtolower(trim($email)));
    }

    public static function isMobile($value = '') {
        return preg_match('#^(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})$#', $value);
    }

    public static function isImageFile($fname = ''){
        return preg_match("#.*\.(?:jpg|gif|png|jpeg|bmp)$#", strtolower($fname)) > 0;
    }

    public static function isVideoFile($fname = ''){
        return preg_match("#.*\.(?:ogg|ogv|mp4|mp4v|mpg4|webm)$#", strtolower($fname)) > 0;
    }

    public static function isUrl($url = ''){
        return !(filter_var($url, FILTER_VALIDATE_URL) === FALSE);
    }

    public static function isAdminUrl(){
        return Str::contains(request()->fullUrl(), '/admin/');
    }

    public static function isWeakPassword($pass = ''){
        if(!empty($pass)) {
            $weakpass = self::getSiteConfig('weakpass');
            if (!empty($weakpass)) {
                $arr = explode(';', $weakpass);
                return in_array($pass, $arr);
            }
        }
        return false;
    }
}