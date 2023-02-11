<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    public $chat_id_test;
    public $chat_id;
    public $token;

    public function __construct()
    {
        // $this->chat_id_test = '-892516421';
        $this->chat_id_main = '-1001819101421';
        $this->chat_id = $this->chat_id_main;
        $this->token = '6094443316:AAGBV34HsdDDgk1nRreI45TKm2i9pPSz26I';
    }

    public function index()
    {
        $telegram = new \App\Libs\Telegram($this->token);
        $text = $telegram->Text();

        if (strpos($text, 'hey miu ty') !== false || strpos($text, 'hey Miu Ty') !== false) {
            $mess = 'Dạ em nghe ^^';
            $this->sendNow($mess);
        }

        if (strpos($text, '/help') !== false) {
            $this->showCommand($text);
        }


        if (strpos($text, '/add') !== false) {
            $this->addNewMember($text);
        }

        if (strpos($text, '/ll') !== false) {
            $this->listEmployee();
        }

        if (strpos($text, '/booked') !== false) {
            $this->booked($text);
        }

        if (strpos($text, '/unbooked') !== false) {
            $this->unbooked($text);
        }

        if (strpos($text, '/rm') !== false) {
            $this->removeMember($text);
        }
    }

    public function showCommand($text)
    {
        $html = '• Tổng hợp các lệnh Miu Ty:'. "\r\n". "\r\n";
        $html .= "<b>hey miu ty</b> => Gọi cho vui" . "\r\n";
        $html .= "<b>/ll</b> => Hiển thị danh sách member" . "\r\n";
        $html .= "<b>/add | name | user name</b> => Thêm mới member" . "\r\n";
        $html .= "<b>/rm username</b> => Xóa member" . "\r\n";
        $html .= "<b>/booked username</b> => update member đã mời trà sữa" . "\r\n";

        $this->sendNow($html);
    }

    public function addNewMember($text)
    {
        $name = trim(substr(substr($text, 4), 0, strpos(substr($text, 4), '|')));
        $username = trim(substr($text, strpos($text, '|') + 1));

        $exits = DB::table('list_invite_tea')
            ->where('username', $username)
            ->exists();

        if ($exits !== true) {
            $end = DB::table('list_invite_tea')
                ->where([
                    ['status', 1],
                    ['booked', 0],
                ])
                ->orderByRaw('sort_order desc, id desc')
                ->first();

            DB::table('list_invite_tea')->insert([
                'name' => $name,
                'username' => $username,
                'sort_order' => $end->sort_order + 1,
                'booked' => 0,
                'status' => 1,
            ]);

            $mess = 'Chào mừng ' . "<b>$name</b>" . ' gia nhập hội healthy, chúc bạn hay ăn chóng lớn';
            $this->sendNow($mess);
        } else {
            $mess = 'username ' . "<b>$username</b>" . ' đã tồn tại, vui lòng kiểm tra lại';
            $this->sendNow($mess);
        }
    }

    public function removeMember($text)
    {
        $username = trim(substr($text, 3));

        $exits = DB::table('list_invite_tea')
            ->where('username', $username)
            ->first();

        if (!empty($exits)) {
            DB::table('list_invite_tea')
                ->where('username', $username)
                ->delete();

            $mess = 'Tạm biệt ' . "<b>$username</b>" . ' hãy luôn tươi cười nha ♥';
            $this->sendNow($mess);
        } else {
            $mess = 'username ' . "<b>$username</b>" . ' không tồn tại, vui lòng kiểm tra lại';
            $this->sendNow($mess);
        }
    }

    public function listEmployee()
    {
        $list = DB::table('list_invite_tea')
            ->where([
                ['status', 1],
            ])
            ->orderByRaw('sort_order asc, id asc')
            ->get();

        $html = '--- Danh sách người chơi hệ ăn vặt ---'."\n\r";

        $next = DB::table('list_invite_tea')
            ->where([
                ['status', 1],
                ['booked', 0],
            ])
            ->orderByRaw('sort_order asc, id asc')
            ->first();

        foreach ($list as $key => $value) {
            $booked = $value->booked == 1 ? '( Đã mua )' : ($value->id == $next->id ? '( Sắp tới lượt )' : '');
            $html .= $key + 1 .'.'. $value->name ."( $value->username )" . "<b> $booked</b>" . "\n\r";
        }

        $this->sendNow($html);
    }

    public function bookFood()
    {
        $list = DB::table('list_invite_tea')
            ->where([
                ['status', 1],
                ['booked', 0],
            ])
            ->orderByRaw('sort_order asc, id asc')
            ->first();

        $general_history = DB::table('general_history')
            ->where([
                ['type', 1],
            ])
            ->orderByRaw('id desc')
            ->first();

        $ting = false;

        if ($general_history->week % 2 === 0 && (int)date('W') % 2 === 0) {
            $ting = true;
        } elseif ($general_history->week % 2 !== 0 && (int)date('W') % 2 !== 0) {
            $ting = true;
        }

        $name = $list->username ?? '';
        $user_name = $list->username ?? '';

        $day = date('l');

        if (!empty($list) && $day == 'Monday' && $ting === true) {
            $html = '• Theo quy luật lối sống lành mạnh của team:'. "\r\n". "\r\n";
            $html .= "<b>2 tuần 1 bữa bổ sung vitamin C.</b>" . "\r\n";
            $html .= '------------------------'. "\r\n";
            $html .= 'Chiều nay đến lượt ' . $name .' mời trà sữa ' . $user_name . "\r\n";
            $html .= '------------------------'. "\r\n";
            $html .= '☺☺ Đặt đồ thôi nào ☺☺'. "\r\n";
            $html .= 'Anh em Zẩy lên Zẩy lên !!!'. "\r\n";;
            $this->sendNow($html);
        }
    }

    public function unbooked($text)
    {
        $username = trim(substr($text, 9));
        if (!empty($username)) {
            $exits = DB::table('list_invite_tea')
                ->where('username', $username)
                ->first();

            DB::table('list_invite_tea')
                ->where([
                    ['username', $username],
                ])
                ->update(['booked' => 0]);

            $html = 'Đã hoàn thao tác '. $username .' mua đồ hôm nay'. "\r\n". "\r\n";
            $this->sendNow($html);
        }
    }

    public function booked($text)
    {
        $username = trim(substr($text, 7));

        if (!empty($username)) {
            $exits = DB::table('list_invite_tea')
                ->where('username', $username)
                ->first();

            if(!empty($exits)) {
                DB::table('list_invite_tea')
                    ->where([
                        ['username', $username],
                    ])
                    ->update(['booked' => 1]);

                DB::table('general_history')->insert([
                    'status' => 1,
                    'type' => 1,
                    'week' => (int)date('W'),
                    'month' => (int)date('m'),
                ]);

                $html = 'Cảm ơn '. $username .' đã bổ sung vitamin cho team ♥♥'. "\r\n". "\r\n";
                $this->sendNow($html);

                $last = DB::table('list_invite_tea')
                    ->where([
                        ['booked', 0],
                        ['status', 1],
                    ])
                    ->first();

                if(empty($last)) {
                    DB::table('list_invite_tea')
                        ->update(['booked' => 0]);

                    $html = 'Tất cả member đã mua, reset lại thôi'. "\r\n". "\r\n";
                    $this->sendNow($html);
                }
            } else {
                $html = 'user name '. $username .' không tồn tại'. "\r\n". "\r\n";
                $this->sendNow($html);
            }
        }
    }

    public function kickOff()
    {
        $month = (int)date('m');
        $day = (int)date('N');
        $day_of_month = date('j');

        if($day_of_month >= 7 && in_array($day, [2,3,4,5]) && ($month == 1 || $month == 4 || $month == 7 || $month == 10)) {
            switch($day) {
                case 1:
                    $day_vi = 'Thứ Hai';
                    break;
                case 3:
                    $day_vi = 'Thứ Tư';
                    break;
                case 4:
                    $day_vi = 'Thứ Năm';
                    break;
                case 5:
                    $day_vi = 'Thứ Sáu';
                    break;
                default;
                    $day_vi = 'Thứ Ba';
                    break;
            }

            $html = '• Theo quy luật lối sống lành mạnh của team:'. "\r\n". "\r\n";
            $html .= "<b>Hôm nay là '.$day_vi.' của đầu tiên trong quý này, nhậu thôi, nhậu thôi ♥♥</b>" . "\r\n". "\r\n";
            $html .= '☺☺ Hãy Cho tôi thấy cánh tay của các bạn ☺☺'. "\r\n";

            $this->sendNow($html);
        }
    }

    public function wasKickOff($text)
    {
        DB::table('general_history')->insert([
            'status' => 1,
            'type' => 2,
            'week' => (int)date('W'),
            'month' => (int)date('m'),
        ]);

        $html = 'Anh em no say chưa, hẹn gặp lại vào quý sau nhé ♥♥'. "\r\n". "\r\n";
        $this->sendNow($html);
    }

    public function contactForm()
    {
        return view('contactForm');
    }

    public function storeMessage(Request $request)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $mess = $request->message;

        Telegram::sendMessage([
            'chat_id' => $this->chat_id,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);

        return redirect()->back();
    }

    public function sendNow($mess)
    {
        Telegram::sendMessage([
            'chat_id' => $this->chat_id,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);
    }
}
