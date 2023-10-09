<?php

namespace App\Http\Controllers;


use App\Http\Requests\Donation\MakeDonation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;

use App\fund_beneficiary;
use App\Donation;
use App\users;
use App\settings;
use App\ph;
use App\gh;
use App\withdrawals;
use App\deposits;
use App\currency_rates;
use App\daily_profit_bonus;
use App\UserAccounts;
use App\Currencies;
use Session;
use DB;

class DonationController extends Controller
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

    }

    public function donations()
    {
        $title = 'Donations';
        $fund_beneficiaries = fund_beneficiary::where('user_id', Auth::user()->id)
            ->where('status', 0)
            ->orderby('created_at', 'DESC')->get();

        $UserAccountsQry = UserAccounts::where('id', Auth::user()->id)->first();
        $currency_RatesQry = currency_rates::orderby('created_at', 'DESC')->first();



        $currencies = Currencies::distinct('code')->where('status', 'Active')->get();
        $donations = Donation::where('user_id', Auth::user()->id)->orderBy('created_at', 'Desc')->get();

        $uid = Auth::user()->u_id;



        $userdonation = $this->totalDonations(Auth::user()->id);


        //dd($donations);

        return view('donations/index')->with(array('title' => $title, 'donations' => $donations, 'accountsInfo' => $UserAccountsQry, 'ratesQuery' => $currency_RatesQry, 'currencies' => $currencies, 'userdonation' => $userdonation, 'beneficiaries' => $fund_beneficiaries));
    }


    //////////////////////////////


    public function donations_json()
    {
        $donations = Donation::where('user_id', Auth::user()->id)->orderBy('created_at', 'Desc')->get();
        return datatables()->of($donations)->toJson();
    }


    public function createDonation(MakeDonation $request)
    {
        $payment_mode = $request->payment_mode;
        $donation_type = $request->donation_type;
        $amount = $request->amount;
        $currency = $request->currency;

        $user_id = Auth::user()->id;
        $user_uid = Auth::user()->u_id;

        // Accounts Info
        $userAccInfo = UserAccounts::where('user_id', $user_id)->first();

        // Currency Rates Query
        $ratesQuery = currency_rates::orderBy('created_at', 'desc')->first();
        $activeDeposits = deposits::where('user_id', Auth::user()->id)->where('status', 'Approved')->get();
        $totalActiveDeposits = count($activeDeposits);



        if ($payment_mode != "" && isset($userAccInfo) && isset($ratesQuery)) {
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
                //$accWaitingProfit     = "waiting_profit_".$curr;
                //$userwaitingProfit    = $userAccInfo->$accWaitingProfit;
                $totalUsd = $amount * $currencyRate;
                $donation_limit = 10;

                if ($currency == "USD" && $payment_mode == "Bonus") {
                    if ($totalUsd < $donation_limit || $amount > $reference_bonus) {
                        return redirect()->intended('dashboard/donations')->with('errormsg', 'Amount is less than $' . $donation_limit . '! Or You have insufficient balance for this request!');
                    } elseif ($totalActiveDeposits == 0) {
                        return redirect()->intended('dashboard/donations')->with('errormsg', 'Bonus Withdrawal Not Allowed ! You have no approved deposits in your deposits account.');
                    }
                } elseif ($currency != "USD" && $payment_mode == "Bonus") {
                    return redirect()->intended('dashboard/donations')->with('errormsg', 'Invalid request! selected currency not allowed for reinvest Bonus');
                } elseif ($payment_mode == "Profit" && ($totalUsd < $donation_limit || $amount > $userProfit)) {
                    return redirect()->intended('dashboard/donations')->with('errormsg', 'Amount is less than $' . $donation_limit . '! Or You have insufficient balance for this request!');
                } elseif ($payment_mode == "Sold" && ($totalUsd < $donation_limit || $amount > $userbalanceSold)) {
                    return redirect()->intended('dashboard/donations')->with('errormsg', 'Amount is less than $' . $donation_limit . '! Or You have insufficient balance for this request!');
                }


                if ($totalUsd >= $donation_limit) {
                    $balance = 0;
                    //$last_withdrawal_id =  "W-".$wd->id;
                    $dateTime = date("Y-m-d H:i:s");
                    $title = $payment_mode . " Donate by " . $user_uid;
                    $details = "New " . $payment_mode . " Donation added by" . $user_id;
                    $approvedby = "";
                    if ($payment_mode == "Bonus") {
                        $balance = $reference_bonus;
                        $reference_bonus = $reference_bonus - $amount;


                        \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update(['reference_bonus' => $reference_bonus]);

                        $pre_amt = $reference_bonus2;
                        $curr_amt = $reference_bonus;
                        // Save Logs
                        $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                    } elseif ($payment_mode == "Profit") {
                        $balance = $userProfit;
                        $userProfit = $userProfit - $amount;


                        \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update([$accProfit => $userProfit]);
                        $pre_amt = $userProfit2;
                        $curr_amt = $userProfit;
                        // Save Logs
                        $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                    } elseif ($payment_mode == "Sold") {
                        if (Auth::user()->u_id == 'B4U0720') {
                            return redirect()->back()->with('errormsg', 'You are not allowed!');
                        } else {
                            $balance = $userbalanceSold;
                            $userbalanceSold = $userbalanceSold - $amount;

                            \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update([$accBalSold => $userbalanceSold]);

                            $pre_amt = $userbalanceSold2;
                            $curr_amt = $userbalanceSold;
                            // Save Logs
                            $this->saveLogs($title, $details, $user_id, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                        }
                    }


                    // save New Donation
                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $user_uid;
                    $don->amount = $amount;  // Donation Amount
                    $don->usd_amount = $totalUsd;
                    $don->donation_type = $donation_type;
                    $don->currency = $currency;
                    $don->payment_mode = $payment_mode;
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    if ($currency != "USD") {
                        $wd->crypto_amount = 0;
                    }
                    $wd->usd_amount = 0;
                    $wd->donation = $amount;
                    $wd->currency = $currency;
                    $wd->unique_id = $user_uid;
                    $wd->payment_mode = $payment_mode;
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')
                        ->session()
                        ->get("Admin_Id")) ?
                        app('request')->session()->get("Admin_Id") :
                        Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();

                 //   $donation_type = \App\Donation::$donation_types($donation_type);

                    $successmsg = 'Action Successful! Your donation is submitted for '. $don->donation_type;
                    return redirect()->intended('dashboard/donations')->with('successmsg', $successmsg);
                } else {
                    $error = 'Total USD is not enough.';
                    return redirect()->intended('dashboard/donations')->with('errormsg', $error);
                }
            } // end currenc if
        } // end mode if

        // end of top if
    }

    public function donateOdp(Request $request)
    {
        $user = Auth::user();
        $user->show_donation_popup = true;
        $user->donate_odp = true;
        $user->save();
        $successmsg = 'Action Successful! You donate your one day profit thank you.';
        return redirect(route('dashboard'))->with('successmsg', $successmsg);
    }

    public function canceldonateOdp()
    {
        \Illuminate\Support\Facades\DB::table('users')->where('id', Auth::user()->id)->update([
            'show_donation_popup' => 1,
        ]);
        return redirect()->back();
    }

    public function totalDonations($user)
    {
        $total_donations = Donation::where('user_id', Auth::user()->id)->sum('amount');
        return $total_donations;
    }


    public function oneday_profitDonation()
    {
        $user_id = Auth::user()->id;
        $unique_id = Auth::user()->u_id;

        $profit_date = daily_profit_bonus::where('user_id', $unique_id)->orderby('created_at', 'DESC')->first();

        if ($profit_date) {
            $profit_date = date("Y-m-d", strtotime($profit_date->created_at));


            // Accounts Info
            $userAccInfo = UserAccounts::where('user_id', $user_id)->first();

            $ratesQuery = currency_rates::orderBy('created_at', 'desc')->first();


            $userProfitUSD = $userAccInfo->profit_usd;
            $userProfitBTC = $userAccInfo->profit_btc;
            $userProfiteth = $userAccInfo->profit_eth;
            $userProfitbch = $userAccInfo->profit_bch;
            $userProfitltc = $userAccInfo->profit_ltc;
            $userProfitxrp = $userAccInfo->profit_xrp;
            $userProfitzec = $userAccInfo->profit_zec;
            $userProfitdash = $userAccInfo->profit_dash;

            $profit_usd = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'USD')->sum('today_profit');
            $profit_btc = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'BTC')->sum('crypto_profit');
            $profit_eth = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'ETH')->sum('crypto_profit');
            $profit_bch = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'BCH')->sum('crypto_profit');
            $profit_ltc = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'LTC')->sum('crypto_profit');
            $profit_xrp = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'XRP')->sum('crypto_profit');
            $profit_dash = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'DASH')->sum('crypto_profit');
            $profit_zec = daily_profit_bonus::where('user_id', $unique_id)->where('created_at', 'LIKE', '%' . $profit_date . '%')->where('currency', 'LIKE', 'ZEC')->sum('crypto_profit');


            if (($profit_usd > 0 && $userProfitUSD >= $profit_usd)
                || ($profit_btc > 0 && $userProfitBTC >= $profit_btc)
                || ($profit_eth > 0 && $userProfiteth >= $profit_eth)
                || ($profit_bch > 0 && $userProfitbch >= $profit_bch)
                || ($profit_ltc > 0 && $userProfitltc >= $profit_ltc)
                || ($profit_xrp > 0 && $userProfitxrp >= $profit_xrp)
                || ($profit_zec > 0 && $userProfitzec >= $profit_zec)
                || ($profit_dash > 0 && $userProfitdash >= $profit_dash)
            ) {
                if ($profit_usd > 0 && $userProfitUSD >= $profit_usd) {
                    $balance = $userProfitUSD;
                    $userProfitUSD = $userProfitUSD - $profit_usd;
                    $totalUsd = $profit_usd * $ratesQuery->rate_usd;

                    UserAccounts::where('user_id', $user_id)->update(['profit_usd' => $userProfitUSD,]);
                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_usd;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'USD';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    $wd->crypto_amount = 0;
                    $wd->usd_amount = 0;
                    $wd->donation = $profit_usd;
                    $wd->currency = 'USD';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_btc > 0 && $userProfitBTC >= $profit_btc) {
                    $balance = $userProfitBTC;
                    $userProfitBTC = $userProfitBTC - $profit_btc;
                    $totalUsd = $profit_btc * $ratesQuery->rate_btc;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_btc;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'BTC';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    $wd->crypto_amount = 0;
                    $wd->usd_amount = 0;
                    $wd->donation = $profit_btc;
                    $wd->currency = 'BTC';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_eth > 0 && $userProfiteth >= $profit_eth) {
                    $balance = $userProfiteth;
                    $userProfiteth = $userProfiteth - $profit_eth;
                    $totalUsd = $profit_eth * $ratesQuery->rate_eth;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_eth;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'ETH';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    $wd->crypto_amount = 0;
                    $wd->usd_amount = 0;
                    $wd->donation = $profit_eth;
                    $wd->currency = 'ETH';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_bch > 0 && $userProfitbch >= $profit_bch) {
                    $balance = $userProfitbch;
                    $userProfitbch = $userProfitbch - $profit_bch;
                    $totalUsd = $profit_bch * $ratesQuery->rate_bch;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_bch;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'BCH';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    $wd->crypto_amount = 0;
                    $wd->usd_amount = 0;
                    $wd->donation = $profit_bch;
                    $wd->currency = 'BCH';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_ltc > 0 && $userProfitltc >= $profit_ltc) {
                    $balance = $userProfitltc;
                    $userProfitltc = $userProfitltc - $profit_ltc;
                    $totalUsd = $profit_ltc * $ratesQuery->rate_ltc;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_ltc;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'LTC';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance
                    $wd->crypto_amount = 0;
                    $wd->usd_amount = 0;
                    $wd->donation = $profit_ltc;
                    $wd->currency = 'LTC';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_xrp > 0 && $userProfitxrp >= $profit_xrp) {
                    $balance = $userProfitxrp;
                    $userProfitxrp = $userProfitxrp - $profit_xrp;
                    $totalUsd = $profit_xrp * $ratesQuery->rate_xrp;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_xrp;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'XRP';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();
                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance

                    $wd->crypto_amount = 0;

                    $wd->usd_amount = 0;
                    $wd->donation = $profit_xrp;
                    $wd->currency = 'XRP';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_dash > 0 && $userProfitdash >= $profit_dash) {
                    $balance = $userProfitdash;
                    $userProfitdash = $userProfitdash - $profit_dash;
                    $totalUsd = $profit_dash * $ratesQuery->rate_dash;

                    $don = new Donation();
                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_dash;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'DASH';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();

                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance

                    $wd->crypto_amount = 0;

                    $wd->usd_amount = 0;
                    $wd->donation = $profit_dash;
                    $wd->currency = 'DASH';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')
                        ->session()
                        ->get("Admin_Id")) ?
                        app('request')->session()->get("Admin_Id") :
                        Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }

                if ($profit_zec > 0 && $userProfitzec >= $profit_zec) {
                    $balance = $userProfitzec;
                    $userProfitzec = $userProfitzec - $profit_zec;
                    $totalUsd = $profit_zec * $ratesQuery->rate_zec;

                    $don = new Donation();

                    $don->user_id = $user_id;
                    $don->user_u_id = $unique_id;
                    $don->amount = $profit_zec;
                    $don->usd_amount = $totalUsd;  // Donation Amount
                    $don->donation_type = 0;
                    $don->currency = 'ZEC';
                    $don->payment_mode = 'Profit';
                    $don->status = 'Approved';

                    $don->save();


                    $wd = new withdrawals();

                    $wd->user = $user_id;

                    $wd->amount = 0;  // withdrawal Amount
                    $wd->pre_amount = $balance; // Previous Acc Balance

                    $wd->crypto_amount = 0;

                    $wd->usd_amount = 0;
                    $wd->donation = $profit_zec;
                    $wd->currency = 'ZEC';
                    $wd->unique_id = $unique_id;
                    $wd->payment_mode = 'Profit';
                    $wd->status = 'Approved';
                    $wd->pre_status = 'New';
                    if (Auth::user()->awarded_flag == 2 || Auth::user()->awarded_flag == '2') {
                        $wd->flag_dummy = 1;
                    }

                    // save the created by id for withdrawls
                    $createdby = (app('request')
                        ->session()
                        ->get("Admin_Id")) ?
                        app('request')->session()->get("Admin_Id") :
                        Auth::user()->id;
                    $wd->created_by = $createdby;

                    // dd($wd);
                    $wd->save();
                }


                \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $user_id)->update(
                    [
                        'profit_usd' => $userProfitUSD,
                        'profit_btc' => $userProfitBTC,
                        'profit_eth' => $userProfiteth,
                        'profit_bch' => $userProfitbch,
                        'profit_ltc' => $userProfitltc,
                        'profit_xrp' => $userProfitxrp,
                        'profit_zec' => $userProfitzec,
                        'profit_dash' => $userProfitdash,

                    ]
                );


                \Illuminate\Support\Facades\DB::table('users')->where('id', Auth::user()->id)->update([
                    'show_donation_popup' => 1,
                    'donate_odp' => 1,
                ]);

                return redirect()
                    ->back()
                    ->with(
                        'successmsg',
                        'You have successfully donated your oneday profit for COVID-19.Thank You..!'
                    );
            }
        }
        return redirect()->back()->with('errormsg', 'You have no profit for donation..!');
    }


    //Return ADMIN manage donations route

    public function mdonations()
    {
        if (Auth::user()->type == 1 || Auth::user()->type == 2 || Auth::user()->type == 5) {
            $title = 'Manage users withdrawals';
            $donations = Donation::where('status', 'Approved')
                ->orwhere('status', 'Pending')
                ->orderby('created_at', 'DESC')
                ->get();
           

            return view('admin/mdonations')->with(
                array('title' => $title, 'donations' => $donations)
            );
        } else {
            return redirect()->intended('dashboard/donations')->with('message', 'You are not allowed!.');
        }
    }
}
