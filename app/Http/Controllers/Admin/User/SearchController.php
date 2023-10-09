<?php

namespace App\Http\Controllers\Admin\User;

use App\User;
use App\users;
use App\UserWithdrawRule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller2;

class SearchController extends Controller2
{
    public function __construct()
    {
        // $this->middleware('admin');
    }
    public function index()
    {
        $settings         =  site_settings() ;

        return view('admin.user.search.index', compact('settings'));
    }
    public function search(Request $request)
    {
        if (empty($request->u_id)) {
            return back()->withMessage('Server Busy');
        }
        $user = User::where('email', $request->u_id)
            ->orWhere('u_id', $request->u_id)
            ->orWhere('account_no', $request->u_id)
            ->orWhere('btc_address', $request->u_id)
            ->orWhere('eth_address', $request->u_id)
            ->orWhere('bch_address', $request->u_id)
            ->orWhere('ltc_address', $request->u_id)
            ->orWhere('xrp_address', $request->u_id)
            ->orWhere('zec_address', $request->u_id)
            ->orWhere('dash_address', $request->u_id);


        if ($user->count() == 1) {
            $user1 = $user->first();
            $response = users::getUserDetailsFromCallCenter($user1->id);
            if($response && !is_null($response) && $response->status() == 200) {
                $userData = json_decode($response)->data;
                if (isset($userData) && $userData->is_first_call == 1 && $user1->is_first_call == 0) {
                    $user1->is_first_call = 1;
                    $user1->save();
                }
            }
            $UserWithdrawRule = UserWithdrawRule::where('user_id', $user1->id)->first();

            return view('admin.user.search.search', compact('user', 'UserWithdrawRule'));
        } else {
            return back()->withMessage('User not found!');
        }
    }
}
