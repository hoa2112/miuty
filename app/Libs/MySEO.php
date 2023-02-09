<?php
namespace App\Libs;

use Illuminate\Support\Str;

class MySEO{
    // type = article | website
    static public function getSeoDefault($dataSeo = [], $type = 'website'){
        return [
            'facebook_meta' => self::metaFacebook($dataSeo, $type),
            'twitter_meta' => self::metaTwitter($dataSeo, $type),
            'g_meta' => self::metaGoogle($dataSeo, $type)
        ];
    }

    static public function metaFacebook($dataSeo, $type = 'website'){
        self::refined($dataSeo);

        $modify = !empty($dataSeo['modify']) ? $dataSeo['modify'] : null;
        $publish = !empty($dataSeo['publish']) ? $dataSeo['publish'] : null;

        $out = sprintf('<meta name="title" content="%s" />', $dataSeo['title']);
        $out.= sprintf('<meta name="description" content="%s" />', $dataSeo['description']);
        $out.= sprintf('<meta name="keywords" content="%s" />', $dataSeo['keywords']);

        $out.= sprintf('<meta property="og:title" content="%s" />', $dataSeo['title']);
        $out.= sprintf('<meta property="og:description" content="%s" />', $dataSeo['description']);

        $out.= sprintf('<meta property="og:url" content="%s" />', $dataSeo['link']);
        $out.= sprintf('<meta property="og:site_name" content="%s" />', $dataSeo['site_name']);

        //locale
        $out.= '<meta property="og:locale" content="vi_VN" />';

        if (!empty($dataSeo['image'])) {
            $out .= sprintf('<meta property="og:image" content="%s" />', $dataSeo['image']);
            if(env('APP_HTTPS')){
                $out .= sprintf('<meta property="og:image:secure_url" content="%s" />', $dataSeo['image']);
            }
            $out .= sprintf('<meta property="og:image:alt" content="%s" />', $dataSeo['title']);
            $mime = self::getMimeType($dataSeo['image']);
            if(!empty($mime)){
                $out .= sprintf('<meta property="og:image:type" content="%s" />', $mime);
            }
            $out .= '<meta property="og:image:width" content="800" />';
            $out .= '<meta property="og:image:height" content="800" />';
        }

        if(!empty($modify)) {
            $out .= sprintf('<meta property="og:updated_time" content="%s" />', $modify->toIso8601String());
        }
        $out.= sprintf('<meta property="og:type" content="%s" />', $type);

        switch ($type){
            case 'article':
                $out.= sprintf('<meta property="article" content="%s" />', $dataSeo['link']);
                if(!empty($dataSeo['category'])) {
                    $out .= sprintf('<meta property="article:section" content="%s" />', $dataSeo['category']);
                }
                if(!empty($publish)) {
                    $out .= sprintf('<meta property="article:published_time" content="%s" />', $publish->toIso8601String());
                }
                if(!empty($modify)) {
                    $out .= sprintf('<meta property="article:modified_time" content="%s" />', $modify->toIso8601String());
                }
                if(!empty($dataSeo['tags'])){
                    foreach ($dataSeo['tags'] as $tag){
                        $out .= sprintf('<meta property="article:tag" content="%s" />', $tag->title);
                    }
                }
                if(!empty($dataSeo['facebook_name'])){
                    $out.= sprintf('<meta property="article:author" content="https://www.facebook.com/%s" />', $dataSeo['facebook_name']);
                }
                break;
            case 'website':
                $out.= sprintf('<meta property="website" content="%s" />', $dataSeo['link']);
                break;
        }

        return $out;
    }

    static public function metaGoogle($dataSeo){
        self::refined($dataSeo);

        $out = sprintf('<meta itemprop="name" content="%s" />', $dataSeo['title']);
        $out.= sprintf('<meta itemprop="description" content="%s" />', $dataSeo['description']);
        if(empty($dataSeo['image'])){
            $out.= sprintf('<meta itemprop="image" content="%s" />', $dataSeo['image']);
        }
        return $out;
    }

    static public function metaTwitter($dataSeo){
        self::refined($dataSeo);

        $out = '<meta name="twitter:card" content="summary_large_image" />';
        $out.= sprintf('<meta name="twitter:title" content="%s" />', $dataSeo['title']);
        $out.= sprintf('<meta name="twitter:description" content="%s" />', $dataSeo['description']);
        if(empty($dataSeo['image'])){
            $out.= sprintf('<meta name="twitter:image" content="%s" />', $dataSeo['image']);
            $out.= sprintf('<meta name="twitter:image:alt" content="%s" />', $dataSeo['site_name']);
        }
        //twitter:site - @username of website. Either twitter:site or twitter:site:id is required.
        $out.= sprintf('<meta name="twitter:site" content="%s" />', $dataSeo['link']);

        return $out;
    }

    static protected function refined(&$dataSeo = []){
        $dataDefault = Lib::getSiteConfig();

        if(empty($dataSeo['title'])){
            $dataSeo['title'] = !empty($dataDefault['site_name']) ? $dataDefault['site_name'] : '';
        }

        if(empty($dataSeo['description'])){
            $dataSeo['description'] = !empty($dataDefault['description']) ? $dataDefault['description'] : '';
        }

        if(empty($dataSeo['keywords'])){
            $dataSeo['keywords'] = !empty($dataDefault['keywords']) ? $dataDefault['keywords'] : '';
        }

        if(empty($dataSeo['image'])){
            $dataSeo['image'] = !empty($dataDefault['image_seo']) ? $dataDefault['image_seo'] : '';
        }

        if(empty($dataSeo['site_name'])){
            $dataSeo['site_name'] = !empty($dataDefault['site_name']) ? $dataDefault['site_name'] : '';
        }

        if(empty($dataSeo['link'])){
            $dataSeo['link'] = url()->current();
        }

        if(empty($dataSeo['facebook_name'])){
            $dataSeo['facebook_name'] = !empty($dataDefault['facebook_name']) ? $dataDefault['facebook_name'] : '';
        }

        $dataSeo['title'] = StringLib::plainText($dataSeo['title']);

        $dataSeo['description'] = StringLib::plainText($dataSeo['description']);

        $dataSeo['keywords'] = StringLib::plainText($dataSeo['keywords']);

        $dataSeo['site_name'] = StringLib::plainText($dataSeo['site_name']);
    }

    static protected function getMimeType($filename = ''){
        if(Str::contains($filename, ['.jpg', '.jpeg'])){
            return 'image/jpeg';
        }
        if(Str::contains($filename, '.png')){
            return 'image/png';
        }
        if(Str::contains($filename, '.gif')){
            return 'image/gif';
        }
        return '';
    }
}