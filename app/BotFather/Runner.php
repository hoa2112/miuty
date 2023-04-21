<?php
namespace App\BotFather;

use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class Runner
{
    protected static $token = '6094443316:AAGBV34HsdDDgk1nRreI45TKm2i9pPSz26I';
    protected static $chat_id = '-911651755';
    protected static $hoadz_id = '710206129';

    public static function index($user_id, $text)
    {
        if (strpos($text, 'hey miu ty') !== false || strpos($text, 'hey Miu Ty') !== false) {
//            if (self::$hoadz_id == $user_id) {
                self::sendNow('Dạ em nghe ^^');
//            } else {
//                self::sendNow('Bạn không có quyền thực hiện chức năng này');
//            }
        }

        if ($text == '/dl') {
            self::registerRun();
        }
    }

    public static function registerRun()
    {
        $deadline = strtotime('2023/05/01');
        $today = strtotime(date('Y/m/d'));
        $day = ($deadline - $today) / 86400;

        if ($day > 0) {
            $html = 'Trời ơi, chỉ còn '. $day . ' nữa là hết hạn đăng ký giải chay VP rồi, đăng ký đi thôi nào !';
            self::sendNow($html);
        } elseif($day == 0) {
            $html = 'Hôm nay là hạn cuối đăng ký giải chay VP rồi, đăng ký nhanh nhanh nào';
            self::sendNow($html);
        }
    }

    public static function sendNow($mess)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        Telegram::sendMessage([
            'chat_id' => self::$chat_id,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);
    }
}