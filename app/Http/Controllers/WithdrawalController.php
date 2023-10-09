<?php

namespace App\Http\Controllers;

use App\CBCredits;
use App\current_rate;
use App\Http\Requests\WithDrawals\ApproveWithDrawlRequest;
use App\Http\Requests\WithDrawals\BigAmountWithDrawlRequest;
use App\Http\Requests\WithDrawals\DepositProofHTMLRequest;
use App\Http\Requests\WithDrawals\DepositProofRequest;
use App\Http\Requests\WithDrawals\HoldWithDrawalRequest;
use App\Http\Requests\WithDrawals\RequestWithDrawls;
use App\Http\Requests\WithDrawals\UpdateWithDrawalRequest;
use App\Http\Requests\WithDrawals\UpdateWIthDrawalStatusRequest;
use App\Http\Requests\WithDrawals\VerifiedWithDrawlRequest;
use App\Http\Requests\WithDrawals\ViewWithdrawalDetailRequest;
use App\Model\Deposit;
use App\settings;
use Carbon\Carbon;
use Dompdf\Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\fund_beneficiary;
use App\daily_investment_bonus;
use App\users;
use App\ph;
use App\gh;
use App\withdrawals;
use App\deposits;
use App\currency_rates;
use App\deposit_investment_bonus;
use App\UserAccounts;
use App\Currencies;
use App\OTPToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Session;

/**
 * Class WithdrawalController
 * @package App\Http\Controllers
 */
class WithdrawalController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
//        parent::__construct();
        $this->middleware('auth');
        $this->settings = settings::getSettings();
        // GET EMAIL TEMPLATE DATA SENT IN EMAILS
//        function getEmailTemplate($event)
//        {
//            $emailDetails = email_templates::where('title', 'Like', $event)->first();
//
//            return $emailDetails;
//        }
    }

    /**
     * Show the application dashboard.
     * @return Response
     */

    /*  $rules = [
         'trans_id' => 'required|string|max:255',
          'proof' => 'mimes:jpg,jpeg,png|max:4000',
          //|digits_between:10,10
        ];

        $customMessages = [
                'trans_id.required' => 'Transaction ID field is required.'
            ];

        $validator =  $this->validate($request, $rules, $customMessages);
    */

    //Return withdrawals route
    public function withdrawals()
    {
        $title = 'withdrawals';
        $widrawalQry = withdrawals::where('user', Auth::user()->id)
            ->where('status', '!=', 'Cancelled')
            ->where('status', '!=', 'Deleted')
            ->where('is_manual_cancel', '!=', 2)
            ->orderby('created_at', 'DESC')->get();
        $fund_beneficiaries = fund_beneficiary::where('user_id', Auth::user()->id)
            ->where('status', 0)
            ->orderby('created_at', 'DESC')->get();

        $UserAccountsQry = UserAccounts::where('id', Auth::user()->id)->first();
        //     $currecy_RatesQry = currecy_rates::orderby('created_at', 'DESC')->first();
        $ratesQry = current_rate::first();

        $currencies = Currencies::distinct('code')->where('status', 'Active')->get();
        //DB::enableQueryLog();
        //dd($UserAccountsQry);

        $uid = Auth::user()->u_id;
        $parentid = Auth::user()->parent_id;
        $userdonation = $this->userdonations();

        $related_users = users::where('users.parent_id', '=', $uid)->orwhere('users.u_id', '=', $parentid)
            ->select('users.id', 'users.u_id', 'users.parent_id', 'users.name', 'users.email')->get();

        return view('withdrawals')->with(array('title' => $title, 'withdrawals' => $widrawalQry, 'usersList' => $related_users, 'accountsInfo' => $UserAccountsQry, 'ratesQuery' => $ratesQry, 'currencies' => $currencies, 'userdonation' => $userdonation, 'beneficiaries' => $fund_beneficiaries));
    }

    public function userdonations()
    {
        $exrate = DB::table('current_rate')->where('id', '=', 1)->first();
        $user_id = Auth::user()->id;

        $DonationUSD = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'USD')->sum('donation');

        $DonationBTC = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'BTC')->sum('donation');
        $BTCrate = $exrate->rate_btc;
        $DonationBTC = $DonationBTC * $BTCrate;

        $DonationETH = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'ETH')->sum('donation');
        $ETHrate = $exrate->rate_eth;
        $DonationETH = $DonationETH * $ETHrate;

        $DonationBCH = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'BCH')->sum('donation');
        $BCHrate = $exrate->rate_bch;
        $DonationBCH = $DonationBCH * $BCHrate;

        $DonationLTC = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'LTC')->sum('donation');
        $LTCrate = $exrate->rate_ltc;
        $DonationLTC = $DonationLTC * $LTCrate;

        $DonationDASH = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'DASH')->sum('donation');
        $DASHrate = $exrate->rate_dash;
        $DonationDASH = $DonationDASH * $DASHrate;

        $DonationZEC = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'ZEC')->sum('donation');
        $ZECrate = $exrate->rate_zec;
        $DonationZEC = $DonationZEC * $ZECrate;

        $DonationXRP = withdrawals::where('user', $user_id)->where(
            function ($q) {
                $q->where('status', 'Approved')
                    ->orwhere('status', 'Pending');
            }
        )->where('currency', 'XRP')->sum('donation');
        $XRPrate = $exrate->rate_xrp;
        $DonationXRP = $DonationXRP * $XRPrate;

        $Donation = $DonationUSD + $DonationBTC + $DonationETH + $DonationBCH + $DonationLTC + $DonationDASH + $DonationZEC + $DonationXRP;

        return $Donation;
    }

    //Return ADMIN manage withdrawals route

    public function mwithdrawals()
    {
        if (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER) || Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $title = 'Manage users withdrawals';

            //$UserAccountsQry     =     UserAccounts::where('id', Auth::user()->id)->first();
            //$withdrawals     =     withdrawals::where('status','Approved')->orwhere('status','Pending')->orderby('created_at','DESC')->get();
            /*    $withdrawals     =     DB::table('withdrawals')
                                ->join('user_accounts AS accounts','withdrawals.user','=','accounts.user_id')
                                ->select('withdrawals.*','accounts.total_deduct')
                                ->where('withdrawals.status','Approved')
                                ->orwhere('withdrawals.status','Pending')
                                ->orderby('created_at','DESC')
                                ->get(); */

            return view('admin/mwithdrawals')->with(array('title' => $title));
        } else {
            return redirect()->intended('dashboard/withdrawals')->with('message', 'You are not allowed!.');
        }
    }

    public function mwithdrawals_json()
    {
        if (Auth::user()->type == 5) {
            if (Auth::user()->id == 6774) {
                $country = config('country.asia');
                $withdrawals2 = DB::table('withdrawals')
                    ->join('users', 'withdrawals.user', '=', 'users.id')
                    ->select('withdrawals.*', 'users.Country')
                    ->where('users.Country', $country)
                    ->where('withdrawals.status', 'Pending')
                    ->orwhere('withdrawals.status', 'Approved')
                    ->orderby('created_at', 'DESC')->get();
            } elseif (Auth::user()->id == 8416) {
                $country = config('country.europe');
                $withdrawals2 = DB::table('withdrawals')
                    ->join('users', 'withdrawals.user', '=', 'users.id')
                    ->select('withdrawals.*', 'users.Country')
                    ->where('users.Country', $country)
                    ->where('withdrawals.status', 'Pending')
                    ->orwhere('withdrawals.status', 'Approved')
                    ->orderby('created_at', 'DESC')->get();
            }
        } elseif (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER)) {
            $withdrawals2 = DB::table('withdrawals')->where('status', 'Approved')->orwhere('withdrawals.status', 'Pending')->get();
        }
        return datatables()->of($withdrawals2)->toJson();
    }

    //Return Cancel withdrawals route

    public function mcwithdrawal()
    {
        if (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER) || Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $title = 'Manage Trashed withdrawals';
            return view('admin/mcwithdrawal')->with(array('title' => $title));
        } else {
            return redirect()->intended('dashboard/withdrawals')->with('message', 'You are not allowed!.');
        }
    }

    public function mcwithdrawal_json()
    {
        $withdrawals = [];
        if (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER) || Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            if (
                Auth::user()->is_user(SITE_ADMIN) ||
                Auth::user()->is_user(SITE_SUPER_ADMIN) ||
                Auth::user()->is_user(SITE_MANAGER) ||
                Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {

                $withdrawals = \Illuminate\Support\Facades\DB::table('withdrawalView')->where(function ($query) {
                    $query->where('withdrawalView.status', 'Cancelled')->orwhere('withdrawalView.status', 'Deleted');
                });

                if (Auth::user()->id == 6774) {
                    $withdrawals = $withdrawals->where('users.Country', config('country.asia'));
                } elseif (Auth::user()->id == 8416) {
                    $withdrawals = $withdrawals->where('users.Country', config('country.europe'));
                }

            }


            return datatables()->of($withdrawals)->toJson();
        } else {
            return redirect()->intended('dashboard/withdrawals')->with('message', 'You are not allowed!.');
        }
    }


    /**
     * @deprecated 14-07-2020
     * Remove view file as well.
     * */
    public function mcwithdrawalBackup()
    {
        if (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER) || Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $title = 'Manage Trashed withdrawals';
            if (Auth::user()->type == 1 || Auth::user()->type == 2) {
                $withdrawals = withdrawals::where('status', 'Cancelled')->orwhere('status', 'Deleted')->orderby('created_at', 'DESC')->get();
            } else {
                if (Auth::user()->is_user(SITE_COUNTRY_MANAGER) && Auth::user()->id == 6774) {
                    $country = config('country.asia');
                    $withdrawals = withdrawals::join('users', 'withdrawals.user', '=', 'users.id')
                        ->select('withdrawals.*', 'users.Country')
                        ->where('users.Country', $country)
                        ->where('withdrawals.status', 'Cancelled')
                        ->orwhere('withdrawals.status', 'Deleted')
                        ->orderby('withdrawals.created_at', 'DESC')
                        ->get();
                } elseif (Auth::user()->is_user(SITE_COUNTRY_MANAGER) && Auth::user()->id == 8416) {
                    $country = config('country.europe');
                    $withdrawals = withdrawals::join('users', 'withdrawals.user', '=', 'users.id')
                        ->select('withdrawals.*', 'users.Country')
                        ->where('users.Country', $country)
                        ->where('withdrawals.status', 'Cancelled')
                        ->orwhere('withdrawals.status', 'Deleted')
                        ->orderby('withdrawals.created_at', 'DESC')
                        ->get();
                }
            }
            //$UserAccountsQry     =     UserAccounts::where('id', Auth::user()->id)->first();
            return view('admin/mcwithdrawalBackup')->with(array('title' => $title, 'withdrawals' => $withdrawals));
        } else {
            return redirect()->intended('dashboard/withdrawals')->with('message', 'You are not allowed!.');
        }
    }

    public function vwithdrawals()
    {
        $title = 'Verified withdrawals';
        $beneficiaries = fund_beneficiary::where('user_id', Auth::user()->id)
            ->where('status', 0)
            ->orderby('created_at', 'DESC')->get();
        $currencies = Currencies::all();
        return view('admin/vwithdrawals')->with(array('title' => $title, 'currencies' => $currencies, 'beneficiaries' => $beneficiaries));
    }


    /**
     * @deprecated 14-07-2020
     * Remove view file as well.
     * */
    public function vwithdrawalsBackup()
    {
        $title = 'Verified withdrawals';
        $beneficiaries = fund_beneficiary::where('user_id', Auth::user()->id)
            ->where('status', 0)
            ->orderby('created_at', 'DESC')->get();
        $currencies = Currencies::all();

        if (Auth::user()->id == 6774) {
            $country = config('country.asia');
            $withdrawals = DB::table('withdrawals')
                ->join('user_accounts AS accounts', 'withdrawals.user', '=', 'accounts.user_id')
                ->join('users', 'withdrawals.user', '=', 'users.id')
                ->select('withdrawals.*', 'accounts.total_deduct', 'users.Country')
                ->where('withdrawals.status', 'Pending')
                ->where('withdrawals.is_verify', 1)
                ->whereIn('users.Country', $country)
                ->where('withdrawals.is_manual_cancel', '!=', 3)
                ->orderby('created_at', 'DESC')
                ->get();
        } elseif (Auth::user()->id == 8416) {
            $country = config('country.europe');
            $withdrawals = DB::table('withdrawals')
                ->join('user_accounts AS accounts', 'withdrawals.user', '=', 'accounts.user_id')
                ->join('users', 'withdrawals.user', '=', 'users.id')
                ->select('withdrawals.*', 'accounts.total_deduct', 'users.Country')
                ->where('withdrawals.status', 'Pending')
                ->where('withdrawals.is_verify', 1)
                ->whereIn('users.Country', $country)
                ->where('withdrawals.is_manual_cancel', '!=', 3)
                ->orderby('created_at', 'DESC')
                ->get();
        } else {
            $withdrawals = DB::table('withdrawals')
                ->join('user_accounts AS accounts', 'withdrawals.user', '=', 'accounts.user_id')
                ->join('users', 'withdrawals.user', '=', 'users.id')
                ->select('withdrawals.*', 'accounts.total_deduct', 'users.Country')
                ->where('withdrawals.status', 'Pending')
                ->where('withdrawals.is_verify', 1)
                ->where('withdrawals.is_manual_cancel', '!=', 3)
                ->orderby('created_at', 'DESC')
                ->get();
        }
        return view('admin/vwithdrawalsBackup')->with(array('title' => $title, 'withdrawals' => $withdrawals, 'currencies' => $currencies, 'beneficiaries' => $beneficiaries));
    }

    public function bawithdrawals()
    {
        $title = 'Big Amount withdrawals';
        $withdrawals = DB::table('withdrawals')
            ->join('user_accounts AS accounts', 'withdrawals.user', '=', 'accounts.user_id')
            ->join('users', 'withdrawals.user', '=', 'users.id')
            ->select('withdrawals.*', 'accounts.total_deduct', 'users.name')
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('withdrawals.status', 'Pending')
            ->whereIn('withdrawals.is_verify', [1, 2])
            ->where('withdrawals.amount', '>=', 1333)
            ->where('withdrawals.is_manual_cancel', '!=', 3)
            ->orderby('created_at', 'DESC')
            ->get();

        $beneficiaries = fund_beneficiary::where('user_id', Auth::user()->id)
            ->where('status', 0)
            ->orderby('created_at', 'DESC')->get();
        $currencies = Currencies::all();
        //dd(DB::getQueryLog());
        return view('admin/bawithdrawals')
            ->with([
                'title' => $title,
                'withdrawals' => $withdrawals,
                'currencies' => $currencies,
                'beneficiaries' => $beneficiaries
            ]);
    }

    public function pwithdrawals_json()
    {
        $withdrawals = withdrawals::select(['id', 'unique_id', 'status', 'amount', 'currency', 'adminid', 'payment_mode', 'fund_type', 'created_at', 'flag_dummy', 'is_paid', 'is_verify'])
            ->where('is_verify', 0)->where('status', 'Pending');

        if (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $withdrawals = withdrawals::leftJoin('users', 'withdrawals.user', '=', 'users.id')
                ->select('withdrawals.*', 'users.Country')
                ->where('withdrawals.is_verify', 0)
                ->where('withdrawals.status', 'Pending')
                ->where('users.Country', '=', Auth::user()->Country);
        }
        return datatables()->of($withdrawals)->toJson();
    }

    /**
     * @return Factory|View
     * @deprecated  14-07-2020 This view file is also deprecated
     */
    public function pwithdrawalsBackup()
    {
        $title = 'Manage Pending withdrawals';

        /*$withdrawals     =     DB::table('withdrawals')
                            ->join('user_accounts AS accounts','withdrawals.user','=','accounts.user_id')
                            ->join('users AS user','user.id','=','withdrawals.user')
                            ->join('admin_logs AS logs','logs.user_id','=','withdrawals.unique_id')
                            ->select('withdrawals.*','accounts.total_deduct','user.awarded_flag','logs.admin_id')
                            ->where('logs.event', 'User Verified')
                            ->where('user.status','active')
                            ->where('withdrawals.status','Pending')
                              ->where('withdrawals.is_verify', 0)
                            ->orderby('created_at','DESC')
                            ->get(); */
        // DB::enableQueryLog();
        //  $withdrawals     =     DB::table('withdrawals')
        //                     ->join('user_accounts AS accounts','withdrawals.user','=','accounts.user_id')
        //                     ->join('users AS user','user.id','=','withdrawals.user')
        //                   //  ->join('admin_logs AS logs','logs.user_id','=','withdrawals.unique_id')
        //                     ->select('withdrawals.*','accounts.total_deduct','user.awarded_flag')
        //                     ->select(DB::raw('(select admin_id from admin_logs WHERE admin_logs.user_id=withdrawals.unique_id and admin_logs.event="User Verified" ORDER BY admin_logs.id DESC LIMIT 1) as adminid'))
        //                   //  ->where('logs.event', 'User Verified')
        //                     ->where('user.status','active')
        //                     ->where('withdrawals.status','Pending')
        //                       ->where('withdrawals.is_verify', 0)
        //                     ->orderby('created_at','DESC')
        //                     ->get();
        if (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            if (Auth::user()->id == 6774) {
                $country = config('country.asia');
                $withdrawals = DB::select(
                    DB::raw(
                        "select `withdrawals`.*, `accounts`.`total_deduct`,`accounts`.`is_manual_verified`,  `user`.`awarded_flag`,`user`.`Country`,(select admin_logs.admin_id from admin_logs WHERE admin_logs.user_id=withdrawals.unique_id and admin_logs.event='User Verified' ORDER BY admin_logs.id DESC LIMIT 1) as adminid
            			from `withdrawals` 
            			inner join `user_accounts` as `accounts` on `withdrawals`.`user` = `accounts`.`user_id` 
            			inner join `users` as `user` on `user`.`id` = `withdrawals`.`user`
            			and `user`.`status` = 'active' 
            			and `withdrawals`.`status` = 'Pending' 
            			and `user`.`Country` = 'Malaysia'
            			and `withdrawals`.`is_verify` = 0 order by `created_at` DESC"
                    )
                );
            } elseif (Auth::user()->id == 8416) {
                $country = config('country.europe');
                $withdrawals = DB::select(
                    DB::raw(
                        "select `withdrawals`.*, `accounts`.`total_deduct`,`accounts`.`is_manual_verified`,  `user`.`awarded_flag`,`user`.`Country`,(select admin_logs.admin_id from admin_logs WHERE admin_logs.user_id=withdrawals.unique_id and admin_logs.event='User Verified' ORDER BY admin_logs.id DESC LIMIT 1) as adminid
                        from `withdrawals` 
                        inner join `user_accounts` as `accounts` on `withdrawals`.`user` = `accounts`.`user_id` 
                        inner join `users` as `user` on `user`.`id` = `withdrawals`.`user`
                        and `user`.`status` = 'active' 
                        and `withdrawals`.`status` = 'Pending' 
                        and `user`.`Country` = 'Spain'
                        and `withdrawals`.`is_verify` = 0 order by `created_at` DESC"
                    )
                );
            } else {
                $withdrawals = DB::select(
                    DB::raw(
                        "select `withdrawals`.*, `accounts`.`total_deduct`,`accounts`.`is_manual_verified`,  `user`.`awarded_flag`,`user`.`Country`,(select admin_logs.admin_id from admin_logs WHERE admin_logs.user_id=withdrawals.unique_id and admin_logs.event='User Verified' ORDER BY admin_logs.id DESC LIMIT 1) as adminid
                from `withdrawals` 
                inner join `user_accounts` as `accounts` on `withdrawals`.`user` = `accounts`.`user_id` 
                inner join `users` as `user` on `user`.`id` = `withdrawals`.`user`
                and `user`.`status` = 'active' 
                and `withdrawals`.`status` = 'Pending' 
                and `user`.`Country` = '" . Auth::user()->Country . "'
                and `withdrawals`.`is_verify` = 0 order by `created_at` DESC"
                    )
                );
            }
        } else {
            $withdrawals = DB::select(
                DB::raw(
                    "select `withdrawals`.*, `accounts`.`total_deduct`,`accounts`.`is_manual_verified`,  `user`.`awarded_flag`,(select admin_logs.admin_id from admin_logs WHERE admin_logs.user_id=withdrawals.unique_id and admin_logs.event='User Verified' ORDER BY admin_logs.id DESC LIMIT 1) as adminid
        			from `withdrawals` 
        			inner join `user_accounts` as `accounts` on `withdrawals`.`user` = `accounts`.`user_id` 
        			inner join `users` as `user` on `user`.`id` = `withdrawals`.`user`
        			and `user`.`status` = 'active' 
        			and `withdrawals`.`status` = 'Pending' 
        			and `withdrawals`.`is_verify` = 0 order by `created_at` DESC"
                )
            );
        }
        $currencies = Currencies::all();
        // dd($withdrawals->verifiedUser);
        return view('admin/pwithdrawalsBackup')->with(array('title' => $title, 'withdrawals' => $withdrawals, 'currencies' => $currencies));
    }

    public function pwithdrawals()
    {
        $title = 'Manage Pending withdrawals';
        $currencies = Currencies::all();
        return view('admin/pwithdrawals')->with(array('title' => $title, 'currencies' => $currencies));
    }

    public function holdingwithdrawals()
    {
        if (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER)) {
            $title = 'Manage Pending withdrawals';
            $withdrawals = DB::table('withdrawals')
                ->join('user_accounts AS accounts', 'withdrawals.user', '=', 'accounts.user_id')
                ->join('users AS user', 'user.id', '=', 'withdrawals.user')
                ->select('withdrawals.*', 'accounts.total_deduct', 'user.awarded_flag')
                ->where('user.status', 'active')
                ->where('withdrawals.status', 'Pending')
                ->where('withdrawals.is_verify', 1)
                ->where('withdrawals.is_manual_cancel', 3)
                ->orderby('created_at', 'DESC')
                ->get();

            $currencies = Currencies::all();

            return view('admin/hwithdrawals')->with(array('title' => $title, 'withdrawals' => $withdrawals, 'currencies' => $currencies));
        } else {
            return redirect()->intended('dashboard/withdrawals')->with('message', 'You are not allowed!.');
        }
    }

    public function awithdrawals()
    {
        $title = 'Manage Approved withdrawals';
        $currencies = Currencies::all();
        return view('admin/awithdrawals')->with(array('title' => $title, 'currencies' => $currencies));
    }

    public function awithdrawals_json()
    {

        $country = null;
        if (Auth::user()->id == 6774) {
            $country = config('country.asia');
        } elseif (Auth::user()->id == 8416) {
            $country = config('country.europe');
        }

        $withdrawals2 = \Illuminate\Support\Facades\DB::table('withdrawalView')
            ->where('withdrawalView.status', 'Approved');

        if ($country) {
            $withdrawals2 = $withdrawals2->whereIn('users.Country', $country);
        }

        return datatables()->of($withdrawals2)->toJson();
    }

    public function cbWithdrawals()
    {
        return view('admin/cbwithdrawals')->with(array('title' => 'Manage CashBox withdrawals', 'currencies' => Currencies::all()));
    }

    public function cbWithdrawals_json()
    {
        $country = null;
        if (Auth::user()->id == 6774) {
            $country = config('country.asia');
        } elseif (Auth::user()->id == 8416) {
            $country = config('country.europe');
        }
        $withdrawals = \Illuminate\Support\Facades\DB::table('withdrawalView')
            ->where('withdrawalView.fund_type', 'cashbox');
        if ($country) {
            $withdrawals = $withdrawals->whereIn('users.Country', $country);
        }
        return datatables()->of($withdrawals)->toJson();
    }


    public function vwithdrawals_json()
    {
        $country = null;

        if (Auth::user()->id == 6774) {
            $country = config('country.asia');
        } elseif (Auth::user()->id == 8416) {
            $country = config('country.europe');
        }

        $withdrawals = DB::table('withdrawalView')
            ->where('withdrawalView.status', 'Pending')
            ->where('withdrawalView.is_verify', 1)
            ->where('withdrawalView.is_manual_cancel', '!=', 3);


        if ($country) {
            $withdrawals = $withdrawals->whereIn('withdrawalView.Country', $country);
        }

        return datatables()->of($withdrawals)->toJson();
    }

    //Return relatedUsers Ajax Search

    public function relatedUsers(Request $request)
    {
        $searchData = $request['search'];

        $uid = Auth::user()->u_id;

        $parentid = Auth::user()->parent_id;

        $related_users = users::where('users.parent_id', '=', $uid)->where('users.u_id', '=', $parentid)
            ->where('users.name', 'Like', '%' . $searchData . '%')->orwhere('users.u_id', 'Like', '%' . $searchData . '%')
            ->select('users.id', 'users.u_id', 'users.parent_id', 'users.name', 'users.email')->take(5);

        echo json_encode($related_users);
        //echo $related_users;
    }

    public function updateWStatus(UpdateWIthDrawalStatusRequest $request)
    {

        $newstatus = $request->newstatus;
        $id = $request->id;

        $withdraw = withdrawals::where('id', $id)->first();
        $currency = $withdraw->currency;
        $payment_mode = $withdraw->payment_mode;
        $total_amount = $withdraw->amount;
        $pre_amount = $withdraw->pre_amount;
        $withdrawal_fee = $withdraw->withdrawal_fee;
        $userid = $withdraw->user;
        $status = $withdraw->status;
        $pre_status = $withdraw->pre_status;

        $user = users::find($withdraw->user);
        $user_id = $user->id;
        $user_Uid = $user->u_id;

        // Accounts Info
        $userAccInfo = UserAccounts::where('user_id', $user_id)->first();
        $deductAmount = $userAccInfo->total_deduct;

        $smallCurrency = strtolower($currency);
        if ($currency != "USD") {
            $rateVal = "rate_" . $smallCurrency;
            $profitval = "profit_" . $smallCurrency;

            //  $currency_RatesQry = currecy_rates::orderby('created_at', 'DESC')->first();
            $currency_RatesQry = current_rate::first();
            $rate = $currency_RatesQry->$rateVal;
        } else {
            $rate = 1;
            $rateVal = "rate_usd";
            $profitval = "profit_usd";
        }

        ## event details...

        $event = "User Withdrawal " . $newstatus;
        $trade_id = $id;
        $admin_id = Auth::user()->u_id;
        $withDrawlUpdateArray = ['status' => $newstatus, 'pre_status' => $status];

        if ($newstatus == "Cancelled") {
            if ($deductAmount > 0) {
                $finalamount = $pre_amount * $rate;
                $finalamount2 = $finalamount - $deductAmount;
                if ($finalamount2 >= 0) {
                    \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update(['total_deduct' => $finalamount2,]);
                } elseif ($finalamount2 < 0) {
                    $finalamount2 = abs(floatval($finalamount2));
                    \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update(['total_deduct' => 0, $profitval => $finalamount2]);
                }
                $title = "Account Updated By Admin :" . Auth::user()->u_id . " Due to Deduction";
                $details = $user_id . " withdrawal has cancelled And deduct amount is updated from $" . $deductAmount . " to $" . $finalamount2;
                $pre_amt = $deductAmount;
                $curr_amt = $finalamount2;
                $approvedby = "";
                // Save Logs
                $this->saveLogs($title, $details, $user_id, $user_Uid, $currency, $finalamount2, $pre_amt, $curr_amt, $approvedby);
            }
        } elseif ($newstatus == "Recover") {
            $withDrawlUpdateArray = ['status' => $pre_status, 'pre_status' => $status];
            $event = "User Withdrawal Recovered from Dummy";
        } elseif ($newstatus == "Dummy1" || $newstatus == "Dummy0") {
            $flag_dummy = substr($newstatus, 5);
            $withDrawlUpdateArray = ['flag_dummy' => $flag_dummy, 'pre_status' => $status];
            $event = "User Withdrawal updated Dummy state";
        } else if ($newstatus === 'onHold') {
            $withDrawlUpdateArray = [
                'is_manual_cancel' => 3,
            ];
        } elseif ($newstatus === 'hideWithdrawal') {
            $withDrawlUpdateArray = [
                'is_manual_cancel' => 2,
            ];
        } elseif ($newstatus === 'showWithdrawal') {
            $withDrawlUpdateArray = [
                'is_manual_cancel' => 0,
            ];
        }

        \Illuminate\Support\Facades\DB::table('withdrawals')->where('id', $id)->update($withDrawlUpdateArray);

        // create Logs
        $this->adminLogs($user_Uid, $admin_id, $trade_id, $event);
        return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');


    }

    ///Paid Deposited///
    public function paidwithdrawal(ViewWithdrawalDetailRequest $viewWithdrawalDetailRequest)
    {
        \Illuminate\Support\Facades\DB::table('withdrawals')->where('id', $viewWithdrawalDetailRequest->id)->update(['is_paid' => 1,]);
        return redirect()->back()->with('successmsg', 'Status Updated Successfully.');
    }

    // send EMail
    public function sendWithdrawalSuccessMail($wid, $amount, $finalAmount, $currency)
    {
        if (Auth::user()->email != "") {
            //$withdrawal_id     = $wid;

            $email_to = Auth::user()->email;
            $userName = Auth::user()->name;
            $from_Name = $this->mail_from_name;
            $from_email = $this->mail_from_address;
            $subject = "Your Widrawal Request is Successful";
            $message =
                "<html>
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
														Your requested withdrawal is successful, to " . $from_Name . ", your withdrawal id is W-" . $wid . ".
															
													</td>
												</tr>";
            /*    <tr>
                    <td style=\"text-align:left; padding:10px 0;\">
                            Your withdrawal amount is ".$amount."(".$currency."),after deduction of withdrawal fee you will recieve ".$finalAmount." (".$currency.").
                    </td></tr> */


            $message .= "<tr>
													<td style=\"text-align:left; padding:10px 0;\">
															Note: (if withdrawal amount <= 500 than $6 fee will be charged. and if withdrawal amount greater than $500 than $17 fee will be charged.)
													</td>
												</tr>
												<tr>
													<td style=\"text-align:left; padding:10px 0;\">
														You will get your withdraw amount, as soon as Admin approved your request.
													</td>
												</tr>
													
												<tr>
													<td style=\"text-align:left; padding:10px 0;\">
														Thanks for using  " . $from_Name . ".
													</td>
												</tr>
												<tr>
													<td style=\"padding:10px 0; text-align:left;\">
														Your Sincerely,
													</td>
												</tr>
												<tr>
													<td style=\"padding:10px 0; text-align:left;\">
														Team " . $from_Name . "
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
            $headers .= 'From:B4U Global Withdrawals<' . $from_email . '>' . "\r\n";
            // More headers info
            //$headers .= 'Cc: myboss@example.com' . "\r\n";

            $success = @mail($email_to, $subject, $message, $headers);

            return "Email send successfully";
        }
    }

    public function calculateDate($created_at)
    {
        /*
        $carbonCreatedAt = \Carbon\Carbon::parse($created_at);
        $carbonNow = \Carbon\Carbon::now();
        if($carbonNow->greaterThan($carbonCreatedAt) && $carbonNow->diffInMonths($created_at) > 1){
            return 1;
        }else{
            return 0;
        }
        */
        // Update lastest Profit for users
        $todayDate = date("Y-m-d");
        $creationDate = date("Y-m-d", strtotime($created_at));
        $date1MonthAfterCreate = date("Y-m-d", strtotime($creationDate . "+1 Month")); //Date After 1 month of Approved
        $date1 = date_create($creationDate);
        $date = date_create($todayDate);

        // Calculate Dates Differance1 in days
        $diff = date_diff($date1, $date);
        $diff_in_days = $diff->days;
        $diff_in_days2 = $diff->format("%R%a Days");
        $dateDifferance = date("Y-m-d", strtotime($created_at . $diff_in_days2));

        if ($dateDifferance >= $date1MonthAfterCreate) {
            return 1;
        } else {
            return 0;
        }
    }

    // Add new Withdrawal Process
    public function withdrawal(RequestWithDrawls $request)
    {
        if (!(app('request')->session()->get('back_to_admin'))) {

            if (\Illuminate\Support\Facades\Session::has('withdrawal') && !$request->exists("_key")) {
                $data = \Illuminate\Support\Facades\Session::pull('withdrawal');
                $request = $request->merge($data);
                \Illuminate\Support\Facades\Session::forget('withdrawal');
            } else {
                $otp = new OTPToken();
                $random_string = $otp->generateCode();
                if (PinController::sendOtpSms(Auth::user()->phone_no, $random_string)) {
                    ///save otp into database
                    $otp->code = $random_string;
                    $otp->user_id = Auth::user()->id;
                    $otp->otp_type = "withdrawal";
                    $otp->save();

                    ## remove key
                    $request->request->remove('_key');

                    ///put request into session for later use
                    \Illuminate\Support\Facades\Session::forget('withdrawal');
                    \Illuminate\Support\Facades\Session::put('withdrawal', $request->request->all());
                    return redirect()->route('getOtp', [$otp->id]);
                } else {
                    return redirect()->back()->with('successmsg', 'Unable to send OTP . Please try again later.!');
                }
            }
        }

        if (isset($request['withdraw_mode'])) {
            $withdraw_mode = $request['withdraw_mode'];
        } else {
            $withdraw_mode = "";
        }


        if (isset($request['amount'])) {
            $amount = $request['amount'];
        } else {
            $amount = 0;
        }

        if (isset($request['donation'])) {
            $donation = $request['donation'];
        } else {
            $donation = 0;
        }

        if (isset($request['donation2'])) {
            $donation2 = $request['donation2'];
        } else {
            $donation2 = 0;
        }

        if (isset($request['currency'])) {
            $currency = $request['currency'];
        } else {
            $currency = "";
        }

        $user_id = Auth::user()->id;
        $user_uid = Auth::user()->u_id;

        // Accounts Info
        $userAccInfo = UserAccounts::where('user_id', $user_id)->first();

        // Currency Rates Query
        // $ratesQuery = currecy_rates::orderBy('created_at', 'desc')->first();
        $ratesQuery = current_rate::first();

        $activeDeposits = deposits::where('user_id', Auth::user()->id)->where('status', 'Approved')->get();
        $totalActiveDeposits = count($activeDeposits);
        $createdAt = "";
        $previousWithdrawal = "";
        $dateDifferece = 0;

        if ($withdraw_mode == "Profit") {

            //Only one withdrawal allowed with in 1 month against a specific currency
            $previousWithdrawal = withdrawals::where('user', Auth::user()->id)
                ->where('payment_mode', 'Profit')
                ->where('currency', $currency)
                ->where('amount', '>', 0)
                ->where(function ($q) {
                    $q->where('status', 'Approved')
                        ->orwhere('status', 'Pending');
                })
                ->orderBy('created_at', 'DESC')
                ->first();

            if (isset($previousWithdrawal) && isset($previousWithdrawal->created_at)) {
                $createdAt = $previousWithdrawal->created_at;
                $dateDifferece = $this->calculateDate($createdAt);
            } else {
                $dateDifferece = 1;
            }
        }


        if ((app('request')->session()->get('back_to_admin')) || ($withdraw_mode != "Profit" || $dateDifferece == 1)) {
            if ($withdraw_mode != "" && isset($userAccInfo) && isset($ratesQuery)) {
                $reference_bonus = $userAccInfo->reference_bonus;
                $reference_bonus2 = $userAccInfo->reference_bonus;
                //exit;
                if (isset($currency)) {
                    $curr = strtolower($currency);

                    $rateVal = "rate_" . $curr;
                    $currencyRate = $ratesQuery->$rateVal;

                    $accProfit = "profit_" . $curr;
                    $userProfit = $userAccInfo->$accProfit;
                    $userProfit2 = $userAccInfo->$accProfit;

                    $accBalSold = "sold_bal_" . $curr;
                    $userbalanceSold = $userAccInfo->$accBalSold;
                    $userbalanceSold2 = $userAccInfo->$accBalSold;

                    $accBal = "balance_" . $curr;
                    $userbalance = $userAccInfo->$accBal;
                    $userbalance2 = $userAccInfo->$accBal;
                    $totalUsd = $amount * $currencyRate;
                    //B4U Foundation Donation
                    if ($donation > 0) {
                        $donationUsd = $donation * $currencyRate;
                    } else {
                        $donationUsd = 0;
                    }

                    //B4U Foundation Donation
                    if ($donation2 > 0) {
                        $donationUsd = $donation2 * $currencyRate;
                    } else {
                        $donationUsd = 0;
                    }

                    // Set Withdrawal Deduct Fee
                    if ($request['fund_type'] != "fundtransfer") {
                        if ($totalUsd <= 500) {
                            if ($currency != "USD") {
                                $withdrawal_fee = 6 / $currencyRate;
                            } else {
                                $withdrawal_fee = 6;
                            }
                            $finalAmount = $amount - $withdrawal_fee;
                        } elseif ($totalUsd > 500) {
                            if ($currency != "USD") {
                                $withdrawal_fee = 17 / $currencyRate;
                            } else {
                                $withdrawal_fee = 17;
                            }
                            $finalAmount = $amount - $withdrawal_fee;
                        }
                    } else {
                        $finalAmount = $amount;
                        $withdrawal_fee = 0;
                    }

                    if ($currency == "USD" && $withdraw_mode == "Bonus") {
                        if ($totalUsd < site_settings()->withdraw_limit || $amount > $reference_bonus) {
                            return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Amount is less than $' . site_settings()->withdraw_limit . '! Or You have insufficient balance for this request!');
                        } elseif ($totalActiveDeposits == 0) {
                            return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Bonus Withdrawal Not Allowed ! You have no approved deposits in your deposits account.');
                        }
                    } elseif ($currency != "USD" && $withdraw_mode == "Bonus") {
                        return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Invalid request! selected currency not allowed for reinvest Bonus');
                    } elseif ($withdraw_mode == "Profit" && ($totalUsd < site_settings()->withdraw_limit || $amount > $userProfit)) {
                        return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Amount is less than $' . site_settings()->withdraw_limit . '! Or You have insufficient balance for this request!');
                    } elseif ($withdraw_mode == "Sold" && ($totalUsd < site_settings()->withdraw_limit || $amount > $userbalanceSold)) {
                        return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Amount is less than $' . site_settings()->withdraw_limit . '! Or You have insufficient balance for this request!');
                    }

                    $fundRecieverID = "";

                    if (isset($request['fund_type']) && isset($request['fund_receivers_id'])) {
                        if (isset($request['fund_receivers_id2']) && $request['fund_receivers_id'] != "notexist") {
                            $fundRecieverID = strtoupper($request['fund_receivers_id']);
                        } elseif (isset($request['fund_receivers_id2']) && $request['fund_receivers_id2'] != "") {
                            $fundRecieverID = strtoupper($request['fund_receivers_id2']);
                        }

                        if ($fundRecieverID != "") {
                            $fundBeneficiary = fund_beneficiary::where('user_id', $user_id)->where('beneficiary_uid', $fundRecieverID)->first();
                            $validuser = users::where('u_id', $fundRecieverID)->where('status', 'LIKE', 'active')->first();

                            if (!isset($fundBeneficiary) && $fundRecieverID !== Auth::user()->u_id && isset($validuser)) {
                                $bene_details = users::where('u_id', $fundRecieverID)->first();

                                $nb = new fund_beneficiary();
                                $nb->user_id = $user_id;
                                $nb->user_uid = $user_uid;
                                $nb->beneficiary_id = $bene_details->id;
                                $nb->beneficiary_uid = $fundRecieverID;
                                $nb->save();
                            }

                            $fundUserAcc = users::where('u_id', $fundRecieverID)->where('status', 'LIKE', 'active')->first();


                            if (!isset($fundUserAcc) || $fundRecieverID == Auth::user()->u_id) {
                                return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Invalid beneficiary, Please add valid beneficiary user id for fundtransfer!');
                                //return redirect()->back()->with('errormsg', 'Invalid beneficiary, Please add valid beneficiary user id for fundtransfer.');
                            }
                        }
                    }
                    if (($withdraw_mode != "FundTransfer") && $totalUsd >= site_settings()->withdraw_limit) {
                        $balance = 0;
                        //$last_withdrawal_id =  "W-".$wd->id;
                        $dateTime = date("Y-m-d H:i:s");
                        $title = $withdraw_mode . " Withdraw by " . $user_uid;
                        $details = "New " . $withdraw_mode . " Withdrawal added by" . $user_id;
                        $approvedby = "";
                        if ($withdraw_mode == "Bonus") {
                            $balance = $reference_bonus;
                            $reference_bonus = $reference_bonus - $amount;

                            // B4U Foundation Donations
                            if ($donation > 0 && $reference_bonus >= $donation) {
                                $reference_bonus = $reference_bonus - $donation;
                            } else {
                                $donation = 0;
                            }

                            // Crona Donations
                            if ($donation2 > 0 && $reference_bonus >= $donation2) {
                                $reference_bonus = $reference_bonus - $donation2;
                            } else {
                                $donation2 = 0;
                            }

                            \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update(['reference_bonus' => $reference_bonus,]);
                            $pre_amt = $reference_bonus2;
                            $curr_amt = $reference_bonus;
                            // Save Logs
                            $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                        } elseif ($withdraw_mode == "Profit") {
                            $balance = $userProfit;
                            $userProfit = $userProfit - $amount;
                            // B4U Foundation Donation
                            if ($donation > 0 && $userProfit >= $donation) {
                                $userProfit = $userProfit - $donation;
                            } else {
                                $donation = 0;
                            }

                            // Crona Donation
                            if ($donation2 > 0 && $userProfit >= $donation2) {
                                $userProfit = $userProfit - $donation2;
                            } else {
                                $donation2 = 0;
                            }

                            \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update([$accProfit => $userProfit]);

                            $pre_amt = $userProfit2;
                            $curr_amt = $userProfit;
                            // Save Logs
                            $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                        } elseif ($withdraw_mode == "Sold") {
                            if (Auth::user()->u_id == 'B4U0720') {
                                return redirect()->back()->with('errormsg', 'You are not allowed!');
                            } else {
                                $balance = $userbalanceSold;
                                $userbalanceSold = $userbalanceSold - $amount;
                                //B4U Foundation Donation
                                if ($donation > 0 && $userbalanceSold >= $donation) {
                                    $userbalanceSold = $userbalanceSold - $donation;
                                } else {
                                    $donation = 0;
                                }

                                //Crona Donation
                                if ($donation2 > 0 && $userbalanceSold >= $donation2) {
                                    $userbalanceSold = $userbalanceSold - $donation2;
                                } else {
                                    $donation2 = 0;
                                }

                                \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update([$accBalSold => $userbalanceSold]);

                                $pre_amt = $userbalanceSold2;
                                $curr_amt = $userbalanceSold;
                                // Save Logs
                                $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                            }
                        }
                        // save New Withdrawal
                        $wd = new withdrawals();
                        $wd->user = $user_id;

                        $wd->amount = $finalAmount;  // withdrawal Amount
                        $wd->pre_amount = $balance; // Previous Acc Balance
                        if ($currency != "USD") {
                            $wd->crypto_amount = $amount;
                        }
                        $wd->usd_amount = $totalUsd;
                        $wd->donation = $donation + $donation2;
                        $wd->currency = $currency;
                        $wd->unique_id = $user_uid;
                        $wd->payment_mode = $withdraw_mode;
                        $wd->status = 'Pending';
                        $wd->pre_status = 'New';
                        if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                            $wd->flag_dummy = 1;
                        }

                        $wd->withdrawal_fee = $withdrawal_fee; // Previous Acc Balance

                        if (isset($request['fund_type'])) {
                            $wd->fund_type = $request['fund_type'];

                            $wd->fund_receivers_id = $fundRecieverID;
                        }

                        // save the created by id for withdrawls
                        $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                        $wd->created_by = $createdby;

                        // dd($wd);
                        $wd->save();

                        $last_withdrawal_id = $wd->id;

                        // send email functions
                        $this->sendWithdrawalSuccessMail($last_withdrawal_id, $amount, $finalAmount, $currency);

                        $successmsg = 'Action Successful! Please wait for system to approve your withdrawal request.';
                        return redirect("dashboard/withdrawals")->with('successmsg', $successmsg);
                    }
                } // end currenc if
            } // end mode if

            // end of top if
        } elseif ($dateDifferece != 1 && $withdraw_mode == "Profit") {
            return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Withdrawal Not Allowed ! You can create only 1 profit-withdrawal against a same currency within 1 Month');
        }

        /*}else{
              return redirect()->back()->with('errormsg', 'You are not allowed!');
        }*/
        /*else if($dateDifferece1 != 1 && $withdraw_mode != "Profit"){
            return redirect()->intended('dashboard/withdrawals')->with('errormsg', 'Withdrawal Not Allowed ! You can create only 1 withdrawal against the same currency within 1 Month.');
        }*/
    }// end of function

    //process withdrawals
    public function pwithdrawal(ApproveWithDrawlRequest $request)
    {
        $id = $request->id;
        try {
            $withdrawal = withdrawals::where('id', '=', $id);
            if (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
                $withdrawal = $withdrawal->where('is_verify', 1);
            }
            $withdrawal = $withdrawal->firstOrFail();
            $fund_receivers_id = $withdrawal->fund_receivers_id;
            // User info
            $user = users::where('id', $withdrawal->user)->first();
            $approvedDate = date("Y-m-d H:i:s");
            $admin_id = Auth::user()->u_id;
            if ($withdrawal->status != 'Approved' && strtolower($withdrawal->status) != 'cancelled') {
                if (!empty($fund_receivers_id) && $withdrawal->fund_type == 'fundtransfer') {
                    $this->fundTransfer($id);
                }elseif(!empty($fund_receivers_id) && $withdrawal->fund_type != 'fundtransfer'){
                    return redirect()->back()->with('errormsg', 'Withdrawal is not fundtransfer');
                }
                else {
                    DB::table('withdrawals')->where('id', $id)->update(['status' => 'Approved', 'approved_by' => $admin_id, 'approved_at' => $approvedDate, 'paid_flag' => 1,]);
                }
                $event = $withdrawal->payment_mode . " Withdrawal Approved for " . $user->u_id;
                $this->adminLogs($user->u_id, $admin_id, $id, $event);
                $message = "Action Successful!";
            } else {
                $message = "Something went wrong";
            }
            return redirect()->back()->with('successmsg', $message);
        } catch (\Exception $exception) {
            Log::error('p withdrawal error ', [
                'errorMessage' => $exception->getMessage(),
                'pwithdrawal of' => $id
            ]);
            return redirect()->back()->with('errormsg', 'Something went wrong');
        }
    }

    public function fundTransfer($withdrawID)
    {
        DB::transaction(function () use ($withdrawID) {
            $approvedDate = date("Y-m-d H:i:s");
            DB::table('withdrawals')->where('id', $withdrawID)->update(['status' => 'Approved', 'approved_by' => Auth::user()->u_id, 'approved_at' => $approvedDate, 'paid_flag' => 1,]);
            $withdrawal = withdrawals::find($withdrawID);
            $deposit = Deposit::where('withdrawal_id', $withdrawID)->first();
            if (!$deposit && $withdrawal->fund_receivers_id != "") {
                $unique_id = $withdrawal->unique_id;
                $amount = $withdrawal->amount;
                $currency = $withdrawal->currency;
                $fund_receivers_id = $withdrawal->fund_receivers_id;
                // trade save
                $trans_id = "Fund transfer by : $unique_id , and withdrawal id : w-$withdrawID";
                $trans_type = "NewInvestment";

                $userinfo = users::where('u_id', $fund_receivers_id)->first();
                $fundUserId = $userinfo->id;
                $planid = $userinfo->plan;

                //  $ratesQuery = currecy_rates::orderby('created_at', 'DESC')->first();
                $ratesQuery = current_rate::first();
                $curr = strtolower($currency);
                $rateVal = "rate_" . $curr;
                $rate = $ratesQuery->$rateVal;

                // get settings table data
                $fee_deduct = explode(",", site_settings()->deposit_fee);
                $fee = 0;

                $totalAmount = floatval($amount) * floatval($rate);

                if (feeDeductOnDailyDeposit($totalAmount, $fundUserId) <= 300) {
                    $totalAmount = ($totalAmount - $fee_deduct[0]);
                    $fee = $fee_deduct[0];
                    $amount = floatval($totalAmount) / floatval($rate);
                } elseif (feeDeductOnDailyDeposit($totalAmount, $fundUserId) > 300 && feeDeductOnDailyDeposit($totalAmount, $fundUserId) <= 1000) {
                    $totalAmount = ($totalAmount - $fee_deduct[1]);
                    $fee = $fee_deduct[1];
                    $amount = floatval($totalAmount) / floatval($rate);
                } elseif (feeDeductOnDailyDeposit($totalAmount, $fundUserId) > 1000) {
                    $totalAmount = ($totalAmount - $fee_deduct[2]);
                    $fee = $fee_deduct[2];
                    $amount = floatval($totalAmount) / floatval($rate);
                }

                $dp = new deposits();
                $dp->amount = $amount;
                $dp->payment_mode = "FundTransfer";
                $dp->currency = $currency;
                $dp->rate = $rate;
                $dp->total_amount = $totalAmount;
                $dp->reinvest_type = "FundTransfer";
                $dp->plan = $planid;
                $dp->user_id = $fundUserId;
                $dp->unique_id = $fund_receivers_id;
                $dp->pre_status = 'Pending';
                $dp->flag_dummy = 0;
                $dp->profit_take = 0;
                $dp->status = 'FundReceived';
                $dp->approved_at = date('Y-m-d');
                $dp->trans_id = $trans_id;
                $dp->trans_type = $trans_type;
                $dp->withdrawal_id = $withdrawal->id;
                $dp->fee_deducted = $fee;
                $dp->save();
                DB::select('CALL approve_deposit(' . $dp->id . ',' . Auth::user()->id . ',0,' . date('Y-m-d') . ')');
                $result = "FundTranffered successfull";
                return $result;
            } else {
                $result = "Already Transferred successfully";
                return $result;
            }
        }, 5);
        /* $newDeposit = Deposit::where('withdrawal_id', $withdrawID)->first();
         if ($newDeposit && $newDeposit->status == 'FundReceived') {*/
    }

    public function viewWithdrawDetailsPost(ViewWithdrawalDetailRequest $request)
    {
        $withdrawal = withdrawals::where('id', $request->id)->first();
        $user_id = $withdrawal->user;
        $unique_id = "W-" . $withdrawal->id;
        $amount = $withdrawal->amount;

        $pre_status = $withdrawal->pre_status;

        $status = $withdrawal->status;

        $payment_mode = $withdrawal->payment_mode;

        $pre_amount = $withdrawal->pre_amount;
        $new_amount = $withdrawal->new_amount;

        $currency = $withdrawal->currency;

        $withdrawal_fee = $withdrawal->withdrawal_fee;

        $bank_reference_id = $withdrawal->bank_reference_id;

        $new_type = $withdrawal->new_type;
        $fund_type = $withdrawal->fund_type;
        $fund_receivers_id = $withdrawal->fund_receivers_id;

        $paid_flag = $withdrawal->paid_flag;

        $flag_dummy = $withdrawal->flag_dummy;

        $created_at = $withdrawal->created_at;

        $updated_at = $withdrawal->updated_at;

        $createdby = ($withdrawal->createdBy) ? $withdrawal->createdBy->name : "NA";

        $userinfo = users::where('id', $user_id)->first();

        if ($withdrawal->is_verify == 1) {
            $userverifyname = users::where('u_id', $withdrawal->verified_by)->first();
            if (isset($userverifyname->name)) {
                $verifyName = $userverifyname->name;
            } else {
                $verifyName = $withdrawal->verified_by;
            }
        }
        if (isset($withdrawal->approved_by)) {
            $userApprovename = users::where('u_id', $withdrawal->approved_by)->first();
            if (isset($userApprovename->name)) {
                $ApproveName = $userApprovename->name;
            } else {
                $ApproveName = $withdrawal->approved_by;
            }
        }
        if ($withdrawal->is_paid == 1) {
            $userPaidname = users::where('u_id', $withdrawal->paid_by)->first();
            if (isset($userPaidname->name)) {
                $PaidName = $userPaidname->name;
            } else {
                $PaidName = $withdrawal->paid_by;
            }
        }
        //print_r($userinffsdo);
        //exit;
        $userUID = $userinfo->u_id;
        $totalAmount = $amount + $withdrawal_fee;

        $USERTYPE = Auth::user()->type;
        $USERID = Auth::user()->id;

        if (($USERTYPE == 0 && $USERID == $user_id) || $USERTYPE == 1 || $USERTYPE == 2 || $USERTYPE == 5) {
            $details = '<div class="modal-header">

			        <button type="button" class="close" data-dismiss="modal">&times;</button>

			        <h4 class="modal-title" style="text-align:center;">Withdraw Details</h4>

			      </div>

			      <div class="modal-body">

					<table width="90%" align="center" style="padding-bottom: 15px !important;">

						<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Withdrawal Info: </h4></td></tr>

						<tr><td width="25%"> <strong>Withdraw ID : </strong></td><td width="25%">' . $unique_id . ' </td><td width="25%"> <strong>User ID : </strong></td><td width="25%">' . $userUID . '</td> </tr>

						<tr><td width="25%"><strong>Current Status : </strong></td><td width="20%">' . $status . '</td><td width="35%">	<strong>Pre Status : </strong></td><td width="20%">' . $pre_status . ' </td></tr>

						<tr><td width="25%"><strong>Payment Mode : </strong></td><td width="25%">' . $payment_mode . '</td>';
            if ($USERTYPE == 1 || $USERTYPE == 2) {
                if (isset($bank_reference_id)) {
                    $details .= '<td width="15%"><strong>Bank Ref.Id : </strong></td><td width="15%">' .
                        $bank_reference_id . '</td></tr>';
                } else {
                    $details .= '<td></td></tr>';
                }

                if ($withdrawal->is_verify == 1 && isset($verifyName)) {
                    $verified_at = date("Y-m-d", strtotime($withdrawal->verified_at));

                    $details .= '<tr><td width="25%"><strong>Verify By : </strong></td><td width="25%">' .
                        $verifyName . '</td>
							<td width="25%"><strong>Verified at : </strong></td><td width="25%">' . $verified_at . '</td></tr>';
                }

                if (isset($withdrawal->approved_by) && isset($ApproveName)) {
                    $approved_at = date("Y-m-d", strtotime($withdrawal->approved_at));
                    $details .= '<tr><td width="25%"><strong>Approved By : </strong></td><td width="25%">' .
                        $ApproveName . '</td>
							<td width="25%"><strong>Approved at : </strong></td><td width="25%">' . $approved_at . '</td></tr>';
                }
                //withdrawls created by
                $details .= '<tr><td width="25%"><strong>Created By: </strong></td><td>' . $createdby . '</td></tr>';

                if ($withdrawal->is_paid == 1 && isset($PaidName)) {
                    $paid_at = date("Y-m-d", strtotime($withdrawal->paid_at));

                    $details .= '<tr><td width="25%"><strong>Paid By : </strong></td><td width="25%">' . $PaidName . '</td>
							<td width="25%"><strong>Paid at : </strong></td><td width="25%">' . $paid_at . '</td></tr>';
                }
            }
            if ($payment_mode == "FundTransfer") {
                $details .= '<tr><td width="25%"><strong>Total Amount: </strong></td><td>' . number_format($totalAmount, 2) . '</td><td width="25%"><strong>Fund Reciever ID : </strong></td><td width="25%">' . $fund_receivers_id . '</td width="25%"></tr>';
            }

            if ($currency == "USD") {
                $details .= '<tr><td colspan="4"></td></tr>

						<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Amount Info: </h4></td></tr>

						<tr><td colspan="2"><strong>User Recieved Amount: </strong></td><td colspan="2">' . number_format($amount, 2) . ' (' . $currency . ')</td></tr>
						<tr><td colspan="2"> <strong>Deduction Fee: </strong></td><td colspan="2">' . number_format($withdrawal_fee, 2) . ' (' . $currency . ')</td></tr>

						<tr><td colspan="4"></td></tr>';
            } else {
                $details .= '<tr><td colspan="4"></td></tr>

						<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Amount Info: </h4></td></tr>

						<tr><td colspan="2"><strong>User Recieved Amount: </strong></td><td colspan="2">' . number_format($amount, 5) . ' (' . $currency . ')</td></tr>
						<tr><td colspan="2"> <strong>Deduction Fee: </strong></td><td colspan="2">' . number_format($withdrawal_fee, 5) . ' (' . $currency . ')</td></tr>

						<tr><td colspan="4"></td></tr>';
            }

            $details .= '</table></div>';

            echo $details;
        } else {
            echo "You are not allowed to view the details";
        }
        //exit;
    }

    public function depositprofpic(DepositProofRequest $request)
    {

        $errorMessage = 'Deposit file missing,Please upload again';
        $successMessage = 'Image Update Successfully!';


        $withdrawal_id = $request['id'];
        $withdrawals = withdrawals::find($withdrawal_id);

        ## upload file as deposit proof
        $uploadedFile = SignedUrlUploadController::uploadImageToGoogleCloud($request->get('fileurl'), 'paiduploads');

        if ($uploadedFile['status']) {
            $image = !empty($uploadedFile['result']['imagePath']) ? $uploadedFile['result']['imagePath'] : null;
        } else {
            \Illuminate\Support\Facades\Log::error('Error while uploading file at Deposit',
                [
                    'aws-file-upload-url' => $request->get('fileurl')
                ]);
            return redirect()->back()->with('errormsg', $errorMessage);
        }

        $withdrawals->paid_img = $image;
        $withdrawals->is_paid = 1;
        $withdrawals->paid_at = Carbon::now();
        $withdrawals->paid_by = Auth::user()->u_id;
        $withdrawals->save();


        if ($withdrawals->paid_img === $image) {
            return redirect()->back()->with('Successmsg', $successMessage);
        } else {
            return redirect()->back()->with('errormsg', $errorMessage);
        }

    }

    /** @param DepositProofHTMLRequest $request
     * @return Application|Factory|View
     * @deprecated 02-sep-2020 - delete file as well and the request as well.
     */
    public function depositprofdiv(DepositProofHTMLRequest $request)
    {
        return \view('deposit.proofBlock', [
            'withdrawalId' => $request->id
        ]);
    }

    /**
     * @param ViewWithdrawalDetailRequest $request
     * @return string
     * @Purpose:: View Withdrawal deposit proof.
     */
    public function viewProofPost1(ViewWithdrawalDetailRequest $request)
    {
        $withdrawal = withdrawals::find($request->id);
        if ($withdrawal->paid_img) {
            ## upload Image proof, before aws file upload, these deposit proofs are uploading in local system storage
            ## so make it functional, I mean to view old proof of deposits, we have these codes, for the old deposits we will
            ## take deposit proof from the local storage and for the new deposit proof we will have from google cloud,
            $previousFileUpload = $image = "uploads/paiduploads/" . $withdrawal->paid_img;
            $fileUrl = null;
            if (is_file($previousFileUpload)) {

                if (strtolower(pathinfo($withdrawal->paid_img, PATHINFO_EXTENSION)) == 'pdf') {
                    $details = '<a  target="_blank" href="' . url($image) . '">Download Proof Pdf</a>';
                } else {
                    $details = '<img width="80%" src="' . url($image) . '">';
                }
            } else {
                $details = '<img width="80%" src="' . Storage::disk('gcs')->url($withdrawal->paid_img) . '">';
            }

            echo '<p style="text-align:center;">' . $details . '</p> <br/>';
        } else {
            echo '<p style="text-align:center;">Proof Not Found! </p> <br/>';
        }
        exit();
    }

    /**
     * @param VerifiedWithDrawlRequest $request
     * @return RedirectResponse
     * @Purpose: Verified withdrawal
     */
    public function vwithdrawal(VerifiedWithDrawlRequest $request)
    {
        $id = $request->id;
        if (app('request')->session()->get('back_to_admin')) {
            $admin_id = app('request')->session()->get('Admin_Id');
            $admin = users::find($admin_id);
            $verifyby = $admin->u_id;
        } else {
            $verifyby = Auth::user()->u_id;
        }
        $verifiedDate = Carbon::now();

        $withDrawl = withdrawals::find($id);
        $withDrawl->verified_by = $verifyby;
        $withDrawl->verified_at = $verifiedDate;
        $withDrawl->is_verify = 1;
        $withDrawl->save();

        $u_id = $withDrawl->user;
        $user = users::find($u_id);
        $post_check = $user->post_check;
        if ($post_check == 1 && $withDrawl->payment_mode == 'Profit') {
            $this->pwithdrawal($request);
        }
        return redirect()->back()->with('successmsg', 'Status Updated Successfully.');
    }

    /**
     * @param BigAmountWithDrawlRequest $request
     * @return RedirectResponse
     * @deprecated
     */
    public function authowithdrawal(BigAmountWithDrawlRequest $request)
    {
        try {
            $withdrawal = withdrawals::find($request->id);
            $withdrawal->is_verify = 2;
            $withdrawal->save();
            $this->adminLogs($withdrawal->unique_id, Auth::user()->u_id, $request->id, "Withdrawal Authorised");
            return redirect()->back()->with('successmsg', 'Status Updated Successfully.');
        } catch (\Exception $exception) {
            Log::error('BigAmountWithDrawlVerifiedError', [
                'withdrawal id' => $request->id,
                'action doing by' => Auth::user(),
                'errorMessage' => $exception->getMessage()
            ]);

            return redirect()->back()->with('errormsg', 'Verification failed, It seems system is under maintenance, We apologise for inconvenience');

        }


    }

    /**
     * @param BigAmountWithDrawlRequest $request
     * @return RedirectResponse
     * @Purpose Approve Big Amount WithDrawl in Bulk
     */
    public function authoallwithdrawals(BigAmountWithDrawlRequest $request)
    {
        try {
            if (empty($request->input('Ids'))) {
                $Ids = [$request->input('id')];
            } else {
                $Ids = $request->input('Ids');
            }
            \Illuminate\Support\Facades\DB::table('withdrawals')->whereIn('id', $Ids)->update(
                [
                    'is_verify' => 2,
                ]
            );
            $withdrawal = withdrawals::whereIn('id', $Ids)->get();
            foreach ($withdrawal as $wid) {
                $this->adminLogs($wid->unique_id, Auth::user()->u_id, $wid->id, "Withdrawal Authorised");
            }
            return redirect()->back()->with('successmsg', 'Status Updated Successfully.');


        } catch (\Exception $exception) {
            Log::error('BigAmountWithDrawlVerifiedError', [
                'withdrawal id' => $request->id,
                'action doing by' => Auth::user(),
                'errorMessage' => $exception->getMessage()
            ]);

            return redirect()->back()->with('errormsg', 'Verification failed, It seems system is under maintenance, We apologise for inconvenience');

        }


    }

    public function updatewithdrwldetails(UpdateWithDrawalRequest $request)
    {
        try {
            $w_id = $request->get('id');
            $wd_details = withdrawals::find($w_id);
            $wd_details->amount = $request->get('amount');
            $wd_details->withdrawal_fee = $request->get('withdrawal_fee');
            $wd_details->status = $request->get('status');
            $wd_details->save();
            $this->adminLogs($wd_details->unique_id, app('request')->session()->get('Admin_Id'), $w_id, 'User Withdrawal Amount Updated');
            return redirect()->back()->with('Successmsg', 'Withdrawal Update Successfully!');
        } catch (\Exception $exception) {
            return redirect()->back()->with('errormsg', $exception->getMessage())->withInput();
        }

    }

    public function withdrwldetails(Request $request)
    {
        $wd_id = $request['id'];

        $wd_details = withdrawals::where('id', $wd_id)->first();

        $details = ' <div class="form-group">
                            <label for="amount" class="col-md-4 control-label">Amount</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control" name="amount" value="' . $wd_details->amount . '"> </div>
                        </div>

                        <div class="form-group">
                            <label for="withdrawal_fee" class="col-md-4 control-label">Withdrawal Fee</label>

                            <div class="col-md-6">
                                <input id="description" type="text" class="form-control" name="withdrawal_fee" value="' . number_format($wd_details->withdrawal_fee, 8) . '">

                               
                            </div>
                        </div>

                         <div class="form-group">
                            <label for="status" class="col-md-4 control-label">Status</label>

                          
                            <div class="col-md-6">

                            <select name="status" id="status" class="form-control amount_type withdrawmode" required>
											<option value="">--Select--</option>
											<option value="Pending">Pending</option>
											<option value="Cancelled">Cancelled</option>
												
							</select>
								
                            </div>
                        </div>


                        

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                            <input type="hidden" name="id" value="' . $wd_id . '">
								<input type="hidden" name="_token" value="' . csrf_token() . '">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-user"></i> Save
                                </button>
                            </div>
                        </div>';


        echo $details;
    }


    public function calculateParentBonus($deposit_id)
    {
        $deposit = deposits::where('id', $deposit_id)->first();
        $total_amount = $deposit->total_amount;
        $userid = $deposit->user_id;
        $flag_dummy = $deposit->flag_dummy;
        $profit_take = $deposit->profit_take;
        $trans_Type = $deposit->trans_type;
        $currency = $deposit->currency;
        $amount = $deposit->amount;

        $deposit_user = users::where('id', $userid)->first();
        $plan_id = $deposit_user->plan;
        $user_uid = $deposit_user->u_id;
        $parent_id = $deposit_user->parent_id;
        $user_awarded_flag = $deposit_user->awarded_flag;

        if (($profit_take == 0 || $profit_take == "0") && ($flag_dummy == 0 && $user_awarded_flag == 0 && $trans_Type != "Reinvestment")) {
            $exitsBonuses = daily_investment_bonus::where('trade_id', $deposit_id)->first();
            $exitsBonuses1 = daily_investment_bonus::where('trade_id', $deposit_id)->where('details', 'NOT LIKE', 'Profit Bonus')->first();
            // Now Get latest Accounts Balances and rates and update Plans of Users
            if ((!isset($exitsBonuses) || !isset($exitsBonuses1)) && ($deposit_id != "" && $parent_id != "0" && $parent_id != "B4U0001")) {
                $count = 1;
                $calculatedBonus = 0;
                for ($i = 0; $i < 5; $i++) {
                    $parentDetails = DB::table('users')->select('id', 'u_id', 'parent_id', 'plan')->where('u_id', $parent_id)->first();

                    $Parent_userID = $parentDetails->id;
                    $parentPlanid = $parentDetails->plan;
                    $parentNewId = $parentDetails->parent_id;
                    $parent_uid = $parentDetails->u_id;

                    //$parent_uid     = $parentDetails->u_id;
                    $plansDetailsQuery = DB::table('plans')
                        ->join('referal_investment_bonus_rules AS refinvbonus', 'plans.id', '=', 'refinvbonus.plan_id')
                        ->select('refinvbonus.first_line', 'refinvbonus.second_line', 'refinvbonus.third_line', 'refinvbonus.fourth_line', 'refinvbonus.fifth_line')
                        ->where('plans.id', $parentPlanid)->first();

                    $investment_bonus_line1 = $plansDetailsQuery->first_line;
                    $investment_bonus_line2 = $plansDetailsQuery->second_line;
                    $investment_bonus_line3 = $plansDetailsQuery->third_line;
                    $investment_bonus_line4 = $plansDetailsQuery->fourth_line;
                    $investment_bonus_line5 = $plansDetailsQuery->fifth_line;

                    if (floatval($investment_bonus_line1) > 0 && $count == 1) {
                        $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line1)) / 100;
                        $percentage = $investment_bonus_line1;
                    } elseif (floatval($investment_bonus_line2) > 0 && $count == 2) {
                        $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line2)) / 100;
                        $percentage = $investment_bonus_line2;
                    } elseif (floatval($investment_bonus_line3) > 0 && $count == 3) {
                        $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line3)) / 100;
                        $percentage = $investment_bonus_line3;
                    } elseif (floatval($investment_bonus_line4) > 0 && $count == 4) {
                        $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line4)) / 100;
                        $percentage = $investment_bonus_line4;
                    } elseif (floatval($investment_bonus_line5) > 0 && $count == 5) {
                        $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line5)) / 100;
                        $percentage = $investment_bonus_line5;
                    }
                    //$bonus = $calculatedBonus;

                    if ($calculatedBonus > 0 && $user_uid != "B4U0001" && $parent_id != "B4U0001") {
                        $daily_ibonus = new daily_investment_bonus();

                        $daily_ibonus->trade_id = $deposit_id;

                        $daily_ibonus->user_id = $user_uid;

                        $daily_ibonus->parent_id = $parent_id;
                        //    $daily_ibonus->parent_plan    =     $parentPlanid;
                        $daily_ibonus->parent_user_id = $Parent_userID;
                        $daily_ibonus->bonus = $calculatedBonus;
                        //$daily_ibonus->bonus        =     $bonus;
                        $daily_ibonus->details = "Investment Bonus with percentage of " . $percentage;

                        $daily_ibonus->save();

                        $savedb = $daily_ibonus->id;

                        $new_user_id = $daily_ibonus->user_id;
                    }
                    // Update Account will be on Update status

                    $parent_id = $parentNewId;
                    //echo "save data".$count." ids =".$savedid;
                    if ($parent_id == '0' || $parent_uid == "B4U0001") {
                        //echo $parent_id;
                        break;
                    }
                    $calculatedBonus = 0;
                    $count++;
                } // end of loop
            }
            $daily_investment_bonus = daily_investment_bonus::where('trade_id', $deposit_id)->get();

            foreach ($daily_investment_bonus as $investmentbonus) {
                $current_bonus = $investmentbonus->bonus;
                $user_uid = $investmentbonus->parent_id;
                if ($user_uid != '') {
                    $parent_user = users::where('u_id', $user_uid)->first();
                    $ref_user_id = $parent_user->id;
                    $ref_user_uid = $parent_user->u_id;

                    $userAccDetails = UserAccounts::where('user_id', $ref_user_id)->first();
                    $reference_bonus = $userAccDetails->reference_bonus;
                    $reference_bonus2 = $userAccDetails->reference_bonus;
                    if ($current_bonus > 0) {
                        $reference_bonus = $reference_bonus + $current_bonus;
                        UserAccounts::where('user_id', $ref_user_id)->update(['reference_bonus' => $reference_bonus, 'latest_bonus' => $current_bonus]);
                        $title = "Reference Bonus Updated";
                        $details = $ref_user_id . " has updated bonus to " . $reference_bonus;
                        $pre_amt = $reference_bonus2;
                        $curr_amt = $reference_bonus;
                        $currency = "";
                        $approvedby = "";
                        // Save Logs
                        $this->saveLogs($title, $details, $ref_user_id, $user_uid, $currency, $current_bonus, $pre_amt, $curr_amt, $approvedby);
                        $event = "User Deposit Approved";
                        $admin_id = Auth::user()->u_id;
                        $user_id = $user_uid;
                        $this->adminLogs($user_id, $admin_id, $deposit_id, $event);
                    }
                }
            }// end foreach loop
            deposits::where('id', $deposit_id)->update(['profit_take' => 1]);
            return "successful";
        }
        return "unsuccessful";
    }

    public function beneficiaryDetails(Request $request)
    {
        $benficiaryuid = strtoupper(trim($request['benficiaryid']));
        $userid = Auth::user()->id;
        $useruid = Auth::user()->u_id;

        if (isset($benficiaryuid)) {
            $bene_details = users::where('u_id', $benficiaryuid)->where('status', 'LIKE', 'active')->first();

            if (isset($bene_details) && $bene_details->id != "" && $bene_details->id != $userid) {
                //exit($benficiaryuid ."  " .$beneid);&& $bene_details->id != $userid
                $fundBeneficiary = fund_beneficiary::where('user_id', $userid)->where('beneficiary_uid', $benficiaryuid)->first();

                if (!isset($fundBeneficiary)) {
                    /*

                    $nb                        = new fund_beneficiary();
                    $nb->user_id            = $userid;
                    $nb->user_uid            = $useruid;
                    $nb->beneficiary_id        = $bene_details->id;
                    $nb->beneficiary_uid    = $benficiaryuid;
                    $nb->save();

                    */

                    if (isset($bene_details->name) && $bene_details->name != "") {
                        $name = $bene_details->name;
                    } else {
                        $name = $benficiaryuid;
                    }

                    if ($name != "") {
                        $name = $name;
                    }
                    $email = $bene_details->email;

                    echo '<strong style="color:green;">UserName: ' . $name . '</strong><br>Alert: Be careful Fund Transfer cannot be reverse.';
                } else {
                    echo '<strong style="color:red;">Beneficiary already exists in list!</strong>';
                }
            } else {
                echo '<strong style="color:red;">Beneficiary not valid or blocked. Please add correct Beneficiary !(Note: You cannot enter your own User_id) </strong>';
            }
        } else {
            echo "nodata";
        }
        //exit;
    }

    public function convertToCb(ApproveWithDrawlRequest $request)
    {
        $withdrawal = withdrawals::find($request->id);
        if ($withdrawal->status == 'Pending' && $withdrawal->is_verify == 1) {
            $withdrawal->status = 'Approved';
            $withdrawal->fund_type = 'cashbox';
            $withdrawal->approved_at = date("Y-m-d H:i:s");
            $withdrawal->approved_by = Auth::user()->u_id;
            if ($withdrawal->withdrawal_fee == 10) {
                if ($withdrawal->payment_mode == 'Bonus'){
                    $withdrawal->usd_amount =    $withdrawal->usd_amount - 4;
            }
                $withdrawal->withdrawal_fee = 6;
            }elseif ($withdrawal->withdrawal_fee == 20) {
                $withdrawal->withdrawal_fee = 17;
                if ($withdrawal->payment_mode == 'Bonus'){
                    $withdrawal->usd_amount =    $withdrawal->usd_amount - 3;
                }
            }
            $withdrawal->save();

            return redirect()->back()->with('message','Withdrawal updated successfully');
        }
        return redirect()->back()->with('errormsg','Something went wrong.');
    }
}