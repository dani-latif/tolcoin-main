<?php

namespace App\Http\Controllers\Admin;

use Config;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Facades\Validator;

class IpController extends Controller
{
    private $admin_pin;
    private $can_admin_login;
    private $user_types;

    public function __construct()
    {
        $this->admin_pin = Config::get('admin.admin_pin');
        $this->can_admin_login = Config::get('admin.can_admin_login');
        $this->user_types = [
            1 => 'admin',
            2 => 'manager'
        ];
        //   $this->middleware('auth');
    }
    //
    public static function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    public function getIp()
    {
        if (\Auth::user()!=null) {
            if (Auth::user()->type == 1  ||  Auth::user()->type == 2) {
                $client_ip = self::get_client_ip();
                return view('ip', ['client_ip'=>$client_ip]);
            }
        }
    }

    public function validator(array $data)
    {
        return Validator::make(
            $data,
            [
            //    'g-recaptcha-response' => 'required|captcha',

            ],
            [
            //  'g-recaptcha-response.required' => 'Invalid Google Captcha',
            ]
        );
    }


    //a
    public function whitelist_new1($ip, $type = 'admin')
    {
        $whitelist = json_decode(file_get_contents('uploads/whitlist.json'), true);
        $whitelist[$type][] = $ip;
        file_put_contents('uploads/whitlist.json', json_encode($whitelist));
    }

    public function postIp(\Illuminate\Http\Request $request)
    {
        $user = \Auth::user();
        if ($user != null) {
            if ($user->type == 1 || $user->type == 2) {
                $this->validator($request->all())->validate();
                //                  dd( $request->pin,$this->admin_pin);
                if ($request->pin != $this->admin_pin) {
                    return redirect()->back();
                }
                $client_ip = self::get_client_ip();
                $this->whitelist_new1($client_ip, $this->user_types[$user->type]);
                return redirect()->to('dashboard');
            }
        }
    }


    public function whitelist(Request $request)
    {
        //log user out if not approved
        if (Auth::user()->status != "active") {
            $request->session()->flush();
            $request->session()->put('reged', 'yes');
            return redirect()->route('dashboard');
        }
        //Check if the user is referred by someone after a successful registration

        if (Auth::user()->type == 1  ||  Auth::user()->type == 2) {
            $whitelist    = json_decode(file_get_contents('uploads/whitlist.json'), true);

            return view('whitelist')->with(['whitelist'=>$whitelist]);
        }
    }

    public function whitelist_delete(Request $request)
    {
        //log user out if not approved
        if (Auth::user()->status != "active") {
            $request->session()->flush();
            $request->session()->put('reged', 'yes');
            return redirect()->route('dashboard');
        }
        //Check if the user is referred by someone after a successful registration

        if (Auth::user()->type == 1  ||  Auth::user()->type == 2) {
            $whitelist    = json_decode(file_get_contents('uploads/whitlist.json'), true);
            $index = array_search($request->ip, $whitelist[$request->type]);
            if ($index !== false) {
                unset($whitelist[$request->type][$index]);
            }
            file_put_contents('uploads/whitlist.json', json_encode($whitelist));
            return redirect()->back();
        }
    }

    public function whitelist_new(Request $request)
    {
        //log user out if not approved
        if (Auth::user()->status != "active") {
            $request->session()->flush();
            $request->session()->put('reged', 'yes');
            return redirect()->route('dashboard');
        }
        //Check if the user is referred by someone after a successful registration
        if (Auth::user()->type == 1  ||  Auth::user()->type == 2) {
            $whitelist    = json_decode(file_get_contents('uploads/whitlist.json'), true);
            $whitelist[$request->type][] = $request->ip;
            file_put_contents('uploads/whitlist.json', json_encode($whitelist));
            return redirect()->back();
        }
    }

    public static function whitelistip()
    {
        try {
            $data = json_decode(file_get_contents("uploads/whitlist.json"), true);
            return $data;
        } catch (Exception $ex) {
            return  [
                "admin"=>[],
                "manager"=>[],
            ];
        }
    }

    public static function ip_security($type)
    {
        $ip = self::get_client_ip();
        $canLogin = Config::get('admin.can_admin_login');
        $type = $type == 1 ? 'admin' : 'manager';
        if ($canLogin) {
            return in_array($ip, self::whitelistip()[$type]);
        }
        return false;
    }
}
