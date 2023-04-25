<?php
namespace App\BotFather;

use Telegram\Bot\Laravel\Facades\Telegram;

class Cinema
{
    protected static $token = '6094443316:AAGBV34HsdDDgk1nRreI45TKm2i9pPSz26I';
//    protected static $chat_id_test = '-1001698447448';
//    protected static $chat_id = '-627129126';
    protected static $chat_id = '-1001658309430';
//    protected static $hoadz_id = '710206129';

    public static function index($user_id, $text)
    {
        if (strpos($text, 'hey miu ty') !== false || strpos($text, 'hey Miu Ty') !== false) {
            self::sendNow('Dạ em nghe ^^');
        }

        if ($text == '/help') {
            self::showCommand();
        }

        if ($text == '/showBHD') {
            self::showTimesBhd();
        }

        if ($text == '/showNCT') {
            self::showTimesCgv();
        }
    }

    public static function showCommand()
    {
        $html = '• Tổng hợp các lệnh Miu Ty:'. "\r\n". "\r\n";
        $html .= "<b>hey miu ty</b> => Gọi cho vui" . "\r\n";
        $html .= "<b>/showBHD</b> => Lịch chiếu BHD Phạm Ngọc Thạch" . "\r\n";
        $html .= "<b>/showNCT</b> => Lịch chiếu CGV Nguyễn Chí Thanh" . "\r\n";

        self::sendNow($html);
    }

    public static function showTimesBhd()
    {
        $html = "<b>".'--- BHD Phạm Ngọc Thạch ---'."\r\n"."</b>";
        $html .= '--- Lịch chiếu film ngày '. date('d-m-Y') . ' ---'."\r\n"."\r\n";

        $response = self::crawl('https://www.bhdstar.vn/wp-admin/admin-ajax.php');

        $res1 = preg_replace("/[\n\r]/", "", $response);
        $res2 = preg_replace('!\s+!', ' ', $res1);

        preg_match_all('/<li class="scheduled-film-[^>]*>(.*?)<\/ul> <\/li>/is', $res2, $matches);

        $index = 1;
        foreach ($matches[0] as $key => $step1){
            $date_now_fm = date('Y-m-d');

            preg_match('/<ul class="times date_'.$date_now_fm.'[^>]*>(.*?) <\/ul>/is', $step1, $time_ul);

            if (!empty($time_ul)) {
                preg_match_all('/<a(.*?)<\/a>/is', $time_ul[0], $times);

                $html_time = '';

                foreach($times[0] as $time) {
                    $html_time .=  $time ."\r\n";
                }

                preg_match('/<img[^>]*>(.*?)<div/is', $step1, $images);

                preg_match('/<h3[^>]*>(.*?)<\/h3>/is', $step1, $names);
                $name_send = $names[1];

                $html .= "<b>".$index .': '. $name_send."</b>"."\r\n"."\r\n";
                $html .= "<b>".'Thời gian: '."</b>"."\r\n". $html_time."\r\n";

                $index++;
            }
        }

        self::sendNow($html);
    }

    public static function showTimesCgv()
    {
        $html = "<b>".'--- CGV Nguyễn Chí Thanh ---'."\r\n"."</b>";
        $html .= '--- Lịch chiếu film ngày '. date('d-m-Y') . ' ---'."\r\n"."\r\n";

        $response = self::crawl('https://moveek.com/cinema/showtime/124432?date=&header=0');
        $res1 = preg_replace("/[\n\r]/", "", $response);
        $res2 = preg_replace('!\s+!', ' ', $res1);

        preg_match_all('/<div class="card-body(\s.*?)?">(.*?)<\/div><\/div>/is', $res2, $matches);

        foreach ($matches[2] as $key => $match) {
            preg_match('/<h4 class="card-title(\s.*?)?">(.*?)<\/h4>/is', $match, $link);
            $html .= $key+1 . '. '."<b>". str_replace('<a href="/', '<a href="https://moveek.com/', $link[2]). "</b>" ."\r\n";

            preg_match_all('/<span class="time">(.*?)<\/span>/is', $match, $times);

            foreach ($times[1] as $time) {
                $html .= "<b>" . $time . "</b>"."\r\n";
            }

            $html .= "\r\n";
        }
        self::sendNow($html);
    }

    public static function crawl($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'action' => 'bhd_lichchieu_chonrap',
                'cinema_id' => '0000000007',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public static function sendNow($mess)
    {
        Telegram::sendMessage([
            'chat_id' => self::$chat_id,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);
    }
}