<?php
namespace App\Libs;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MyFile{
    const DEFAULT_DIR = 'upload';
    const DEFAULT_DIR_FILE = 'files';
    const DEFAULT_MAX_SIZE = 5;
    const DEFAULT_FILE_ALLOW = 'bmp;jpg;jpeg;gif;png;ico;svg;pdf;txt;xlsx;xls;doc;docx;csv;zip;rar;gzip;mp3;ogv;mp4;mp4v;mpg4;webm;webp';

    protected static $storage = [];

    /* --------------------
     *
     * Process File
     *
    ----------------------*/
    public static function upload($sourceFile, $filename, $key, $time, &$err='') {
        return self::clone($sourceFile, $filename, $key, $time, $err);
    }

    public static function clone($sourceFile, $filename, $key, $time, &$err=''){
        $dir = self::getDirByTime($key, $time, true);

        //create dir if not existed
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        try {
            return File::copy($sourceFile, $dir . '/' . $filename);
        }catch (\Exception $e){
            $err = $e->getMessage();
        }
        return false;
    }

    public static function remove($filename, $key, $time, &$err=''){
        $file = self::getFileUrl($filename, $key, $time, true);
        return self::delete($file, $err);
    }

    public static function write($filename, $content, $filePath = ''){
        if (! File::exists($filePath)) {
            File::makeDirectory($filePath, 0755, true);
        }
        return File::put($filePath . '/' . $filename, $content);
    }

    public static function load($fileSoure){
        if (File::exists($fileSoure)) {
            return require $fileSoure;
        }
        return null;
    }

    public static function rename($sourceFile, $destinationFile, &$err=''){
        $pathInfo = pathinfo($destinationFile);
        if (! File::exists($pathInfo['dirname'])) {
            File::makeDirectory($pathInfo['dirname'], 0755, true);
        }
        try {
            return File::move($sourceFile, $destinationFile);
        }catch (\Exception $e){
            $err = $e->getMessage();
        }
        return false;
    }

    public static function delete($sourceFile, &$err=''){
        try{
            return File::delete($sourceFile);
        }catch (\Exception $e){
            $err = $e->getMessage();
        }
        return false;
    }

    public static function allFiles($path){
        $fileList = [];
        $files = File::allFiles($path);
        if(!empty($files)) {
            foreach ($files as $file) {
                $fileList[] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPath(),
                    'extension' => $file->getExtension(),
                    'fullpath' => $file->getRealPath()
                ];
            }
        }
        return $fileList;
    }

    /* --------------------
     *
     * Public functions
     *
    -----------------------*/
    public static function getFileUrl($filename, $key, $time, $isPath = false){
        $dir = self::getDirByTime($key, $time, $isPath);

        return $dir . '/' . $filename;
    }

    public static function makeFileName($fname, $tail){
        $fname = preg_replace('/[0-9]+/', '', $fname);
        $fname = str_replace('-', ' ', $fname);
        return substr(Str::slug($fname), 0, 100).'-' . time() . "." . $tail;
    }

    public static function getDir($key){
        $data = self::getConfig();
        $dir = self::getDefaultDir();
        if(isset($data[$key]['dir'])){
            $dir .= '/' . $data[$key]['dir'];
        }
        return $dir;
    }

    public static function getDefaultDir(){
        return self::getConfig('upload_dir', self::DEFAULT_DIR);
    }

    public static function getMaxFileSize($kb = false, $def = 0){
        if(empty($def)){
            $def = self::DEFAULT_MAX_SIZE;
        }
        $fileSize = self::getConfig('filesize', $def);
        return $kb ? $fileSize * 1024 : $fileSize;
    }

    public static function getInitMaxUploadSize($kb = false){
        $maxUploadSize = substr(ini_get('upload_max_filesize'), 0, -1);
        $maxUploadSize = intval($maxUploadSize);
        return $kb ? $maxUploadSize * 1024 : $maxUploadSize;
    }

    public static function getFileAllows(){
        $fileAllows = self::getConfig('file_allow', self::DEFAULT_FILE_ALLOW);

        return str_replace(';', ',', $fileAllows);
    }

    public static function format($size)
    {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    /* --------------------
     *
     * Base functions
     *
    -----------------------*/
    protected static function getDirByTime($key, $time, $isPath = false){
        //default dir
        $dir = self::getDir($key);

        $dir = sprintf('%s/%s', $dir, self::getFileDir());

        //dir by time
        $dir = sprintf('%s/%d/%d/%d', $dir, $time->year, $time->month, $time->day);

        return $isPath ? public_path($dir) : asset($dir);
    }

    protected static function getFileDir(){
        return self::getConfig('file_dir', self::DEFAULT_DIR_FILE);
    }

    protected static function getConfig($key = '', $def = ''){
        if(empty(self::$storage)) {
            $data = config('image.data');
            foreach ($data as $k => $v) {
                $data[$k]['dir'] = $k;
            }

            $config = \App\Models\ConfigSite::getConfig('config', '', true);
            $config = !empty($config) ? json_decode($config, true) : null;
            if(!empty($config)) {
                $initSettings = [
                    'filesize' => !empty($config['filesize']) ? $config['filesize'] : self::DEFAULT_MAX_SIZE,
                    'upload_dir' => !empty($config['upload_dir']) ? $config['upload_dir'] : self::DEFAULT_DIR,
                    'file_dir' => !empty($config['file_dir']) ? $config['file_dir'] : self::DEFAULT_DIR_FILE,
                    'file_allow' => !empty($config['file_allow']) ? $config['file_allow'] : self::DEFAULT_FILE_ALLOW,
                ];
            }else{
                $initSettings = [
                    'filesize' => self::DEFAULT_MAX_SIZE,
                    'upload_dir' => self::DEFAULT_DIR,
                    'file_allow' => self::DEFAULT_FILE_ALLOW
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