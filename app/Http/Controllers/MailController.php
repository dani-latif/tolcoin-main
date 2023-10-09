<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;


use App\Mail\DemoEmail;


use Illuminate\Support\Facades\Mail;
use App\Currencies;
use stdClass;

/**
 * @deprecated  24-Sep-2020
 * */
class MailController extends Controller
{
    public function send()
    {
        $objDemo = new stdClass();

        $objDemo->demo_one = 'Demo One Value';

        $objDemo->demo_two = 'Demo Two Value';

        $objDemo->sender = 'SenderUserName';

        $objDemo->receiver = 'ReceiverUserName';

        Mail::to("receiver@example.com")->send(new DemoEmail($objDemo));
    }
}
