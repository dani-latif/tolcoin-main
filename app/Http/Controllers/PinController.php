<?php

namespace App\Http\Controllers;

use App\Jobs\SendOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Twilio\Rest\Client as TwilioClient;
use App\Mail\SendPinEmail;
use App\User;
use DB;
use Log;
use Exception;

class PinController extends Controller
{
    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * @deprecated 24-Sep-2020
     * */
    public function send(Request $request)
    {
        $pin = self::generate_pin(\Auth::user());
        //self::send_email($pin, \Auth::user());
        $userUID = \Auth::user()->u_id;
        $phoneNo = \Auth::user()->phone_no;
        $email_to = \Auth::user()->email;
        $userName = \Auth::user()->name;

        Mail::to($email_to)->send(new SendPinEmail($userName, $userUID, $userid, $pin));
        if (isset($phoneNo) && isset($uid)) {
            $this->sendSMS($uid, $phoneNo, $pin);
        }
        return "sent";
    }

    public static function validate_pin($pin)
    {
        $user = Auth::user();
        if (!$user->is_email_pin) {
            return true;
        }
        $epin = $user->email_pin;
        if (($epin == $pin) && !empty($pin)) {
            $user->email_pin = null;
            $user->save();
            return true;
        }
        return false;
    }

    /**
     * @deprecated 24-Sep-2020
     * */
    public static function generate_pin(User $user)
    {
        $user->email_pin = self::generateRandomString();
        $user->save();
        return $user->email_pin;
    }

    /**
     * @deprecated 24-Sep-2020
     * */
    public function sendSMS($unique_id, $contactNo, $randomNo)
    {
        $twilioAccountSid = env('TWILIO_SID', 'AC2d34c16c1be9c8933438780e4ea98935');
        $twilioAuthToken = env('TWILIO_TOKEN', '8a19a5224c69cbf47ac952c778763a53');
        $twilioFromNumber = env('TWILIO_NUMBER', '+16144125274');

        $client = new TwilioClient($twilioAccountSid, $twilioAuthToken);
        $client->messages->create(
        // Where to send a text message (your cell phone?)
            $contactNo,
            array(
                'from' => $twilioFromNumber,
                'body' => $unique_id . ' Your 2FA Activation Code: ' . $randomNo
            )
        );
        return "success";
    }

    /**
     * @deprecated 24-Sep-2020
     * */
    public static function send_email($pin, User $user)
    {
        $email_to = $user->email;
        if (app('request')->session()->get('back_to_admin')) {
            $email_to = env('ADMIN_EMAIL', "b4uglobalofficial@gmail.com");
        }
        $userName = $user->name;
        $from_Name = getMailFromName();
        $from_email = getMailFromAddress();
        $subject = "PIN Verification";
        $message = "<html>
                         <body align=\"left\" style=\"height: 100%;\">
                            <div>
								<div>
									<table style=\"width: 100%;\">
										<tr>
											<td style=\"text-align:left; padding:10px 0;\">
												Dear " . $userName . ",
											</td>
										</tr>
										<tr>
											<td style=\"text-align:left; padding:10px 0;\">
											   Your verification Pin is <strong>" . $pin . "</strong> for verification
											</td>
										</tr>	
									</table>
								</div>
							</div>
						</body>
					</html>";

        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From:' . $from_Name . ' Pin <' . $from_email . '>' . "\r\n";

        $success = @mail($email_to, $subject, $message, $headers);
        // Kill the session variables
    }
    public function validatePhone($phoneNumber)
    {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $swissNumberProto = $phoneUtil->parse($phoneNumber);
            $phoneUtil->format($swissNumberProto, \libphonenumber\PhoneNumberFormat::NATIONAL);
            return $phoneUtil->isValidNumber($swissNumberProto);

        } catch (\libphonenumber\NumberParseException $e) {
            /*  \Illuminate\Support\Facades\Log::error('Error while validate phone number at Pin Controller', [
                  'error-message' => $e->getMessage(),
                  'error-line' => $e->getLine(),
                  'invalid-phone-number' => $phoneNumber
              ]);*/
            return false;
        }


    }
    public static function sendOtpSms($contactNo, $randomNo)
    {
        try {

            if ((new self)->validatePhone($contactNo)) {
                ## if phone number is validate
                $country = Auth::user()->Country;
                SendOtp::dispatch($contactNo, $randomNo, $country);
            } else {
                \Illuminate\Support\Facades\Log::info('invalid Phone number has passed while send otp sms', [
                    'invalid-phone-number' => $contactNo,
                ]);
            }


            /*if (strtolower(Auth::user()->Country)  == "pakistan"
                || substr(Auth::user()->phone_no, 0, 3) == '+92') {
                $url = "http://Lifetimesms.com/plain";
                $contactNo = str_replace(' ','', $contactNo);
                $parameters = array(
                    "api_token" => "c27a10da21cf0ea5e7d7467db2581c5fa01c742010",
                    "api_secret" => "b4utrades",
                    "to" => trim($contactNo),
                    "from" => "88434",
                    "message" => "Dear Customer, Your OTP is : $randomNo."
                );

                $ch = curl_init();
                $timeout  =  30;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                $response = curl_exec($ch);
                curl_close($ch);
                $res = explode(":", $response);
                if ($res[0] != "OK") {
                    Log::info("unable to send sms on " . trim($contactNo) . " using Lifetime SMS  " . $res[1]);
                }
            } else {

                $contactNo = str_replace(' ','', $contactNo);
                $twilioAccountSid   = env('TWILIO_SID', 'AC2d34c16c1be9c8933438780e4ea98935');
                $twilioAuthToken       = env('TWILIO_TOKEN', '8a19a5224c69cbf47ac952c778763a53');
                $twilioFromNumber   = env('TWILIO_NUMBER', '+16144125274');
                $client = new TwilioClient($twilioAccountSid, $twilioAuthToken);
                $clientMessageResponse = $client->messages->create(
                    // Where to send a text message (your cell phone?)
                    trim($contactNo),
                    array(
                        'from' => $twilioFromNumber,
                        'body' => 'Dear Customer, Your OTP is : ' . $randomNo
                    )
                );

            }*/

            if (self::sendOtpEmail($randomNo)) {
                return true;
            } else {
                return true;
            }
        } catch (Exception $e) {

            /*Log::error("File:: Pin Controller line 180 :: Something went wrong in sendOtpSms " . $e->getMessage());*/
            return self::sendOtpEmail($randomNo);
        }
    }
    public static function sendOtpEmail($pin)
    {
        try {
            $userUID = Auth::user()->u_id;
            $phoneNo = Auth::user()->phone_no;
            $email_to = Auth::user()->email;
            $userName = Auth::user()->name;
            $userid = Auth::user()->id;
            $from_Name = getMailFromName();
            $from_email = getMailFromAddress();
            $subject = "PIN Verification";
            $message = "<html>
                            <body align=\"left\" style=\"height: 100%;\">
                                <div>
                                    <div>
                                        <table style=\"width: 100%;\">
                                            <tr>
                                                <td style=\"text-align:left; padding:10px 0;\">
                                                    Dear " . $userName . ",
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style=\"text-align:left; padding:10px 0;\">
                                                   Your verification Pin is <strong>" . $pin . "</strong> for verification
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </body>
                        </html>";

            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From:' . $from_Name . ' Pin <' . $from_email . '>' . "\r\n";
            // @mail($email_to, $subject, $message , $headers);
            Mail::to($email_to)->send(new SendPinEmail($userName, $userUID, $userid, $pin));
            return true;
        } catch (Exception $e) {
            Log::error("Something went wrong in sendopt email " . $e->getMessage());
            return false;
        }
    }
}
