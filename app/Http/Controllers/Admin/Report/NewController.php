<?php

namespace App\Http\Controllers\Admin\Report;

use App;
use App\Currencies;
use App\deposits;
use App\Model\Deposit;
use App\Model\CSDeposit;
use App\settings;
use App\User;
use App\UserAccounts;
use App\withdrawals;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NewController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $user = \App\User::where('u_id', $request->b4uid);
        if ($user->count()) {
            $user = $user->first();
            $user_account = \App\UserAccounts::updateNewForSpecific($user->id);

            $investments = CSDeposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('reinvest_type', '!=', 'profit')
                ->where('reinvest_type', '!=', 'bonus')
                ->where('reinvest_type', '!=', 'sold')
                ->where(function ($q) {
                    $q->whereNull('reinvest_type')
                        ->orwhere('reinvest_type', '=','')
                        ->orwhere('reinvest_type', '=','FundTransfer');
                })
                ->where('status', '!=', 'Deleted')->get();

            $soldReinvestment = CSDeposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'sold')
                ->where('trans_type', 'reinvestment')
                ->get();

            $rProfit = CSDeposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'profit')
                ->where('trans_type', 'reinvestment')
                ->get();
            $rBonus = CSDeposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'bonus')
                ->where('trans_type', 'reinvestment')
                ->get();

            $withdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', '!=', 'profit')
                ->where('payment_mode', '!=', 'bonus')
                ->where('payment_mode', '!=', 'sold')
                ->where('created_at', '>=', "2018-12-15")
                ->get();


            $soldWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', 'sold')
                ->where('created_at', '>=', "2018-12-15")

                ->get();
            $profitWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', 'profit')
                ->where('created_at', '>=', "2018-12-15")

                ->get();

            $currencies = Currencies::all();

            $user_account = UserAccounts::where('user_id', $user->id);
            if ($user_account->count() == 0) {
                return "User Account not found";
            }
            $user_account = $user_account->first();
            $is_complete = false;
            return view('admin.report.complete', compact('is_complete', 'investments', 'soldReinvestment', 'rProfit', 'rBonus', 'withdrawals', 'soldWithdrawals', 'profitWithdrawals', 'currencies', 'user_account'));
        }
    }

    public function complete(Request $request)
    {
        $user = \App\User::where('u_id', $request->b4uid);
        if ($user->count()) {
            $user = $user->first();
            return self::completereportuser($user, $request->has('iframe'));
        }
    }

    public function complete_pdf(Request $request)
    {
        $user = \App\User::where('u_id', $request->b4uid);
        if ($user->count()) {
            $user = $user->first();
            $html = self::completereportuser($user, false);
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML('<h1>Test</h1>');
            return $pdf->stream();
        }
    }

    public static function completeAllUser()
    {
        UserAccounts::where('is_report_change', 0)->where('is_manual_verified', 0)->orderBy('id', 'desc')->chunk(
            1000,
            function ($users) {
                foreach ($users as $user) {
                    $user = User::find($user->user_id);
                    echo  self::completereportuser($user);
                    echo "\ndone user(".$user->id.")\n";
                }
            }
        );
    }

    public static function completereportuser($user, $is_iframe = false)
    {
        $user_account = \App\UserAccounts::updateNewForSpecific($user->id);

        $investments = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('reinvest_type', '!=', 'profit')
                ->where('reinvest_type', '!=', 'bonus')
                ->where('reinvest_type', '!=', 'sold')
                ->where(function ($q) {
                    $q->whereNull('reinvest_type')
                        ->orwhere('reinvest_type', '=','')
                        ->orwhere('reinvest_type', '=','FundTransfer');
                })->where('status', '!=', 'Deleted')->get();

        $rProfit = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'profit')
                ->where('trans_type', 'reinvestment')
                ->get();
        $rBonus = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'bonus')
                ->where('trans_type', 'reinvestment')
                ->get();
        $soldReinvestment = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'sold')
                ->where('trans_type', 'reinvestment')
                ->get();
        $withdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', '!=', 'profit')
                ->where('payment_mode', '!=', 'bonus')
                ->where('payment_mode', '!=', 'sold')

                ->get();


        $soldWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', 'sold')
                ->get();
        $profitWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where(function ($q){
                       $q->where('status', 'Approved')->orwhere('status', 'Pending');
                   })
                ->where('payment_mode', 'profit')
                ->get();

        $currencies = Currencies::all();

        $user_account = UserAccounts::where('user_id', $user->id);
        if ($user_account->count() == 0) {
            return "User Account not found";
        }
        $user_account = $user_account->first();
        $is_complete = true;
        return view('admin.report.complete', compact('is_iframe', 'user', 'is_complete', 'investments', 'soldReinvestment', 'rProfit', 'rBonus', 'withdrawals', 'soldWithdrawals', 'profitWithdrawals', 'currencies', 'user_account'));
    }

    public function finalProfitReport(Request $request)
    {
        $user = \App\User::where('u_id', $request->b4uid);
        if ($user->count()) {
            $user = $user->first();
            $user_account = \App\UserAccounts::updateNewForSpecific($user->id);

            $investments = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('reinvest_type', '!=', 'profit')
                ->where('reinvest_type', '!=', 'bonus')
                ->where('reinvest_type', '!=', 'sold')
                ->where(function ($q) {
                    $q->whereNull('reinvest_type')
                        ->orwhere('reinvest_type', '=','')
                        ->orwhere('reinvest_type', '=','FundTransfer');
                })
                ->where('status', '!=', 'Deleted')->get();

            $rProfit = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'profit')
                ->where('trans_type', 'reinvestment')
                ->get();
            $rBonus = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'bonus')
                ->where('trans_type', 'reinvestment')
                ->get();
            $soldReinvestment = Deposit::orderBy('created_at', 'asc')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Pending')
                ->where('status', '!=', 'Deleted')
                ->where('reinvest_type', 'sold')
                ->where('trans_type', 'reinvestment')
                ->get();
            $withdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', '!=', 'profit')
                ->where('payment_mode', '!=', 'bonus')
                ->where('payment_mode', '!=', 'sold')

                ->get();


            $soldWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', 'sold')
                ->get();
            $profitWithdrawals = withdrawals::orderBy('created_at', 'asc')
                ->where('user', $user->id)
                ->where('status', 'Approved')
                ->where('payment_mode', 'profit')
                ->get();

            $currencies = Currencies::all();

            $user_account = UserAccounts::where('user_id', $user->id);
            if ($user_account->count() == 0) {
                return "User Account not found";
            }
            $settings = settings::getSettings();
            $user_account = $user_account->first();
            $is_complete = true;
            return view('admin.report.finalProfitReport', compact('is_complete', 'investments', 'soldReinvestment', 'rProfit', 'rBonus', 'withdrawals', 'soldWithdrawals', 'profitWithdrawals', 'currencies', 'user', 'user_account', 'settings'));
        }
    }
}
