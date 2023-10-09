<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SendOtp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $contactNo;
    protected $randomNo;
    protected $country;

    public function __construct($contactNo,$randomNo,$country)
    {
        $this->contactNo = $contactNo;
        $this->randomNo  = $randomNo;
        $this->country   = $country;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       //  /* /* $url = "http://Lifetimesms.com/plain";
       //          $contactNo = str_replace(' ','', $this->contactNo);
       //          $parameters = array(
       //              "api_token" => "c27a10da21cf0ea5e7d7467db2581c5fa01c742010",
       //              "api_secret" => "b4utrades",
       //              "to" => trim($contactNo),
       //              "from" => "88434",
       //              "message" => "Your B4U Global OTP is : ". $this->randomNo
       //          );*/
       // if (substr(Auth::user()->phone_no, 0, 3) == '+92') {
       //          $url = "http://bsms.its.com.pk/api.php?";
       //          $contactNo = str_replace(' ','', $this->contactNo);
       //          $parameters = array(
       //              "key" => "753bfbf780eec18ed5ac7fbd0e2cbdec",
       //              "receiver" => trim($contactNo),
       //              "sender" => "88434",
       //              "msgdata" => "Your B4U Global OTP is : ". $this->randomNo,
       //              "response_type" => "json"
       //          );

       //          $ch = curl_init();
       //          $timeout  =  30;
       //          curl_setopt($ch, CURLOPT_URL, $url);
       //          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       //          curl_setopt($ch, CURLOPT_HEADER, 0);
       //          curl_setopt($ch, CURLOPT_POST, 1);
       //          curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
       //          curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
       //          $response = curl_exec($ch);
       //          curl_close($ch);
       //          $res = explode(":", $response);
       //          if ($res[0] != "OK") {
       //              Log::info("unable to send sms on " . trim($contactNo) . " using Lifetime SMS  " . $res[1]);
       //          }
       //      } else {

                $contactNo = str_replace(' ','', $this->contactNo);
                $twilioAccountSid   = env('TWILIO_SID', 'AC2d34c16c1be9c8933438780e4ea98935');
                $twilioAuthToken       = env('TWILIO_TOKEN', '8a19a5224c69cbf47ac952c778763a53');
                $twilioFromNumber   = env('TWILIO_NUMBER', '+16144125274');
                $client = new TwilioClient($twilioAccountSid, $twilioAuthToken);
                $clientMessageResponse = $client->messages->create(
                    // Where to send a text message (your cell phone?)
                    trim($contactNo),
                    array(
                        'from' => $twilioFromNumber,
                        'body' => 'Dear Customer, Your OTP is : ' . $this->randomNo
                    )
                );
    }
}
