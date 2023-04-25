<?php

namespace App\Http\Controllers;

use App\BotFather\Cinema;
use App\BotFather\MiuTy;
use App\BotFather\Runner;
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
    public $hoadz_id;
    public $chat_id_request;
    public $chat_id_miuty;
    public $chat_runner;
    public $chat_movie;

    public $token;

    public function __construct()
    {
        $this->chat_id_test = '-892516421';
        $this->chat_id_miuty = '-1001819101421';
        $this->chat_runner = '-911651755';
        $this->chat_movie = '-1001658309430';

        $this->hoadz_id = '710206129';

        $this->token = '6094443316:AAGBV34HsdDDgk1nRreI45TKm2i9pPSz26I';
    }

    public function index()
    {
        $telegram = new \App\Libs\Telegram($this->token);
        $text = $telegram->Text();
        $this->chat_id_request = $telegram->ChatID();
        $user_id = $telegram->UserID();

        if($this->chat_id_request == $this->chat_id_miuty) {
             MiuTy::index($user_id, $text);
        }

        if($this->chat_id_request == $this->chat_runner) {
            Runner::index($user_id, $text);
        }

        if($this->chat_id_request == $this->chat_movie) {
            Cinema::index($user_id, $text);
        }
    }

    public function contactForm()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        return view('contactForm');
    }

    public function storeMessage(Request $request)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $request->validate([
            'message' => 'required'
        ]);

        $mess = $request->message;
        $chat_id = $request->index_group == 3 ? $this->chat_id : ($request->index_group == 2 ? $this->chat_runner : $this->chat_id_test);

        Telegram::sendMessage([
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);

        return redirect()->back();
    }

    public function sendNow($mess)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        Telegram::sendMessage([
            'chat_id' => $this->chat_id_request,
            'parse_mode' => 'HTML',
            'text' => $mess
        ]);
    }


    // Chạy Cronjob cho các project
    public function bookFood()
    {
        MiuTy::bookFood();
    }

    public function listEmployee()
    {
        MiuTy::listEmployee();
    }

    public function kickOff()
    {
        MiuTy::kickOff();
    }

    public function registerRun()
    {
        Runner::registerRun();
    }

    public function showTimes()
    {
        Cinema::showTimesCgv();
    }

}
