<?php
namespace App\Libs;
use Illuminate\Support\Str;

class ImageURL{
    const DEFAULT_DIR_ORIGINAL = 'original';
    const DEFAULT_DIR_THUMB = 'thumb';
    const DEFAULT_NO_IMAGE = 'no_photo.jpg';
    const DEFAULT_QUALITY = 100;
    const DEFAULT_MAX_SIZE = 2;
    const DEFAULT_WATER_MARK = false;
    const DEFAULT_WATER_MARK_IMG = '';
    const DEFAULT_WATER_MARK_POS = 'bottom-right';
    const DEFAULT_WATER_MARK_POS_X = 10;
    const DEFAULT_WATER_MARK_POS_Y = 10;
    const DEFAULT_WATER_MARK_LIMIT = 300;
    const DEFAULT_WATER_MARK_OPACITY = 60;

    protected static $storage = [];

    /* --------------------
     *
     * Process Image File
     *
    ----------------------*/
    public static function upload($image, $filename, $key, &$err='') {
        $dir = sprintf('%s/%s', public_path(MyFile::getDir($key)), self::getOriginalDir());

        //create dir if not existed
        if (! \File::exists($dir)) {
            \File::makeDirectory($dir, 0755, true);
        }

        //create image from source
        $quality = self::getQuality();
        $image = \Image::make($image);
        return $image->save($dir . '/' . $filename, $quality);
    }

    public static function clone($image, $filename, $key, &$err=''){
        $dir = sprintf('%s/%s', public_path(MyFile::getDir($key)), self::getOriginalDir());

        //create dir if not existed
        if (! \File::exists($dir)) {
            \File::makeDirectory($dir, 0755, true);
        }

        $quality = self::getQuality();
        $image = \Image::make($image);
        return $image->save($dir . '/' . $filename, $quality);
    }

    public static function remove($filename, $key){
        $files = [];
        $size = self::getAllSizes($key);
        foreach ($size as $k => $v){
            $files[] = self::getImageUrl($filename, $key, $k, true);
        }
        if(!empty($files)){
            \File::delete($files);
        }
    }

    public static function cleanImages(){
        $allDir = self::getConfig();
        $originalDir = self::getOriginalDir();

        foreach ($allDir as $key => $dir){
            if(!empty($dir['size'])){
                foreach($dir['size'] as $size){
                    $filePath = self::makeDirAndGetFile('', $key, $size['width'], $size['height']);
                    if(!Str::contains($filePath, '/'.$originalDir) && \File::exists($filePath)) {
                        \File::deleteDirectory($filePath);
                    }
                }
            }
        }
    }

    /* --------------------
     *
     * Auto Gen Image
     *
    -----------------------*/
    public static function getImageUrl($file_name, $key, $sizeName, $isPath = false){
        $size = self::getSize($key, $sizeName);
        $width = $size['width'];
        $height = $size['height'];
        $path = !empty($file_name) ? self::makeDirAndGetFile($file_name, $key, $width, $height) : self::getNoPhoto(false, $key, $sizeName);
        return $isPath ? $path : asset($path);
    }

    public static function autoGenImageFromURL($path = ''){
        $defWidth = null;
        $defHeight= null;
        if(!empty($path)) {
            $path = explode('/', $path);
            $filename = array_pop($path);
            $thumb_str = array_pop($path);
            $key = array_pop($path);

            //process thumb_str
            $thumb_str = str_replace(self::getThumbnailDir().'_', '', $thumb_str);
            $thumb_str = explode('x', $thumb_str);

            if(count($thumb_str) == 2) {
                $thumb_str[0] = intval($thumb_str[0]);
                $thumb_str[1] = intval($thumb_str[1]);

                $sizeName = self::getSizeName($key, $thumb_str[0], $thumb_str[1]);
                if (!empty($sizeName)) {
                    $ret = self::thumb($filename, $key, $sizeName);
                    if ($ret) {
                        return $ret;
                    }
                } else {
                    $defWidth = !empty($thumb_str[0]) ? $thumb_str[0] : $defWidth;
                    $defHeight = !empty($thumb_str[1]) ? $thumb_str[1] : $defHeight;
                }
            }
        }
        $path = self::getNoPhoto(true);
        return \Image::make($path)
            ->resize($defWidth, $defHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
    }

    /* --------------------
     *
     * Public functions
     *
    -----------------------*/
    public static function makeFileName($fname, $tail){
        $fname = preg_replace('/[0-9]+/', '', $fname);
        $fname = str_replace('-', ' ', $fname);
        return substr(Str::slug($fname), 0, 100).'-' . time() . "." . $tail;
    }

    public static function getThumbnailDir(){
        return self::getConfig('thumb_prefix', self::DEFAULT_DIR_THUMB);
    }

    public static function getMaxFileSizeImage($kb = false, $def = 0){
        if(empty($def)){
            $def = self::DEFAULT_MAX_SIZE;
        }
        $fileSize = self::getConfig('filesize', $def);
        return $kb ? $fileSize * 1024 : $fileSize;
    }

    public static function getSize($key, $sizeName){
        $data = self::getConfig();
        if(isset($data[$key]) && isset($data[$key]['size'][$sizeName])){
            return $data[$key]['size'][$sizeName];
        }
        return ['width' => 0, 'height' => 0];
    }

    /* --------------------
     *
     * Base functions
     *
    -----------------------*/
    protected static function thumb($filename, $key, $sizeName) {
        //check original
        $original = self::makeDirAndGetFile($filename, $key, 0, 0, true);
        if (!\File::exists($original)) {
            return false;
        }

        //get size defined
        $size = self::getSize($key, $sizeName);
        $width = $size['width'];
        $height = $size['height'];

        //resize image from original
        $image = \Image::make($original);
        $image->resize($width > 0 ? $width : null, $height > 0 ? $height : null, function ($constraint) {
            $constraint->aspectRatio();
        });

        //make water mark
        if(self::isWaterMarkActive($width, $height)){
            $waterMark = self::getWaterMarkImage(true);
            $position = self::getWaterMarkPosition();
            $padding = self::getWaterMarkPadding();
            $opacity = self::getWaterMarkOpacity();
            $waterMark = \Image::make($waterMark)->opacity($opacity);
            $image->insert($waterMark, $position, $padding['x'], $padding['y']);
        }

        //get file path to save
        $filePath = self::makeDirAndGetFile($filename, $key, $width, $height, true);
        return $image->save($filePath, self::getQuality());
    }

    protected static function makeDirAndGetFile($fname = '', $key = '', $width = 0, $height = 0, $isPath = false){
        $dir = MyFile::getDir($key);
        $isOriginal = empty($width) && empty($height);

        if($isOriginal){
            $temp = !empty($fname) ? '%s/%s/%s' : '%s/%s%s';
            $filePath = sprintf($temp, $dir, self::getOriginalDir(), $fname);
        }else{
            $filePath = sprintf('%s/%s_%sx%s', $dir, self::getThumbnailDir(), $width, $height);

            //create dir if not existed
            if (! \File::exists($filePath)) {
                \File::makeDirectory($filePath, 0755, true);
            }
            if(!empty($fname)) {
                $filePath = sprintf('%s/%s', $filePath, $fname);
            }
        }
        return $isPath ? public_path($filePath) : $filePath;
    }

    protected static function isWaterMarkActive($width = 0, $height = 0){
        $isActive   =   self::getConfig('water_mark_active', self::DEFAULT_WATER_MARK);
        $wmImage    =   self::getConfig('water_mark', self::DEFAULT_WATER_MARK_IMG);
        $limit      =   self::getWaterMarkLimit();
        $checkLimit =   false;
        $width = intval($width);
        if($width > 0){
            $checkLimit = $width > $limit;
        }
        $height = intval($height);
        if($height > 0){
            $checkLimit = $height > $limit;
        }
        return $isActive && !empty($wmImage) && $checkLimit;
    }

    protected static function getWaterMarkImage($path = false){
        $waterImage = self::getConfig('water_mark', self::DEFAULT_WATER_MARK_IMG);
        if($waterImage == self::DEFAULT_WATER_MARK_IMG){
            $waterImage = MyFile::getDefaultDir() . '/' . $waterImage;
        }else {
            $waterImage = sprintf('%s/config/original/%s', MyFile::getDefaultDir(), $waterImage);
        }
        return $path ? public_path($waterImage) : $waterImage;
    }

    protected static function getWaterMarkPosition(){
        return self::getConfig('water_mark_position', self::DEFAULT_WATER_MARK_POS);
    }

    protected static function getWaterMarkOpacity(){
        return self::getConfig('water_mark_opacity', self::DEFAULT_WATER_MARK_OPACITY);
    }

    protected static function getWaterMarkPadding(){
        $paddingX = self::getConfig('water_mark_x', self::DEFAULT_WATER_MARK_POS_X);
        $paddingY = self::getConfig('water_mark_y', self::DEFAULT_WATER_MARK_POS_Y);

        return [
            'x' => intval($paddingX),
            'y' => intval($paddingY),
        ];
    }

    protected static function getWaterMarkLimit(){
        return self::getConfig('water_mark_limit', self::DEFAULT_WATER_MARK_LIMIT);
    }

    protected static function getNoPhoto($path = false, $key = 'config', $sizeName = 'original'){
        $fileNoPhoto = self::getConfig('no_photo', self::DEFAULT_NO_IMAGE);
        if($fileNoPhoto != self::DEFAULT_NO_IMAGE){
            $size = self::getSize($key, $sizeName);
            $fileNoPhoto = self::makeDirAndGetFile($fileNoPhoto, 'config', $size['width'], $size['height']);
        }else {
            $fileNoPhoto = MyFile::getDefaultDir() . '/' . $fileNoPhoto;
        }
        return $path ? public_path($fileNoPhoto) : $fileNoPhoto;
    }

    protected static function getQuality($def = 0){
        if(empty($def)){
            $def = self::DEFAULT_QUALITY;
        }
        return self::getConfig('image_quality', $def);
    }

    protected static function getOriginalDir(){
        return self::getConfig('original_dir', self::DEFAULT_DIR_ORIGINAL);
    }

    protected static function getAllSizes($key){
        $data = self::getConfig();
        return isset($data[$key]) && isset($data[$key]['size']) ? $data[$key]['size'] : [];
    }

    protected static function getSizeName($key, $with = 0, $height = 0){
        $data = self::getConfig();
        if(isset($data[$key])){
            foreach($data[$key]['size'] as $k => $size){
                if($size['width'] == $with && $size['height'] == $height){
                    return $k;
                }
            }
        }
        return '';
    }

    protected static function maxSize($key){
        $data = self::getConfig();
        if(isset($data[$key])){
            return $data[$key]['max'];
        }
        return [];
    }

    protected static function getConfig($key = '', $def = ''){
        if(empty(self::$storage)) {
            $default = config('image.defaultImg');
            $data = config('image.data');
            foreach ($data as $k => $v) {
                $data[$k]['dir'] = $k;
                foreach ($default as $kd => $vd) {
                    if (!isset($data[$k][$kd])) {
                        $data[$k][$kd] = $vd;
                    }
                }
                foreach ($v as $kk => $vv) {
                    if (isset($default[$kk])) {
                        $data[$k][$kk] = array_merge($default[$kk], $data[$k][$kk]);
                    }
                }
            }

            //more init config
            $config = \App\Models\ConfigSite::getConfig('config', '', true);
            $config = !empty($config) ? json_decode($config, true) : null;
            if(!empty($config)) {
                $initSettings = [
                    'filesize' => !empty($config['filesize_img']) ? $config['filesize_img'] : self::DEFAULT_MAX_SIZE,
                    'image_quality' => !empty($config['image_quality']) ? $config['image_quality'] : self::DEFAULT_QUALITY,
                    'original_dir' => !empty($config['original_dir']) ? $config['original_dir'] : self::DEFAULT_DIR_ORIGINAL,
                    'thumb_prefix' => !empty($config['thumb_prefix']) ? $config['thumb_prefix'] : self::DEFAULT_DIR_THUMB,
                    'no_photo' => !empty($config['default_image']) ? $config['default_image'] : self::DEFAULT_NO_IMAGE,
                    'water_mark_active' => !empty($config['water_mark_no_active']) ? true : self::DEFAULT_WATER_MARK,
                    'water_mark' => !empty($config['water_mark']) ? $config['water_mark'] : self::DEFAULT_WATER_MARK_IMG,
                    'water_mark_position' => !empty($config['water_mark_position']) ? $config['water_mark_position'] : self::DEFAULT_WATER_MARK_POS,
                    'water_mark_x' => !empty($config['water_mark_x']) ? $config['water_mark_x'] : self::DEFAULT_WATER_MARK_POS_X,
                    'water_mark_y' => !empty($config['water_mark_y']) ? $config['water_mark_y'] : self::DEFAULT_WATER_MARK_POS_Y,
                    'water_mark_limit' => !empty($config['water_mark_limit']) ? $config['water_mark_limit'] : self::DEFAULT_WATER_MARK_LIMIT,
                    'water_mark_opacity' => !empty($config['water_mark_opacity']) ? $config['water_mark_opacity'] : self::DEFAULT_WATER_MARK_OPACITY,
                ];
            }else{
                $initSettings = [
                    'filesize' => self::DEFAULT_MAX_SIZE,
                    'image_quality' => self::DEFAULT_QUALITY,
                    'original_dir' => self::DEFAULT_DIR_ORIGINAL,
                    'thumb_prefix' => self::DEFAULT_DIR_THUMB,
                    'no_photo' => self::DEFAULT_NO_IMAGE,
                    'water_mark_active' => self::DEFAULT_WATER_MARK,
                    'water_mark' => self::DEFAULT_WATER_MARK_IMG,
                    'water_mark_position' => self::DEFAULT_WATER_MARK_POS,
                    'water_mark_x' => self::DEFAULT_WATER_MARK_POS_X,
                    'water_mark_y' => self::DEFAULT_WATER_MARK_POS_Y,
                    'water_mark_limit' => self::DEFAULT_WATER_MARK_LIMIT,
                    'water_mark_opacity' => self::DEFAULT_WATER_MARK_OPACITY,
                ];
            }
            $data['iSettings'] = $initSettings;

            self::$storage = $data;
        }
        if(!empty($key)){
            if(!empty(self::$storage['iSettings'])){
                return !empty(self::$storage['iSettings'][$key]) ? self::$storage['iSettings'][$key] : $def;
            }
            return $def;
        }
        return self::$storage;
    }
}