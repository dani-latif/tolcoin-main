<?php

namespace App\Http\Controllers;

use App\currency_rates;
use App\current_rate;
use App\UserAccounts;
use App\withdrawals;
use Illuminate\Support\Facades\Auth;
use App\daily_profit_bonus;
use App\daily_investment_bonus;
use App\referal_profit_bonus_rules;
use Illuminate\Support\Facades\DB;

class ProfitBonusController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth');
        set_time_limit(0);
        // GET EMAIL TEMPLATE DATA SENT IN EMAILS
    }

    public function lastWeekProfitBonus()
    {
        $currentDateTime = date("Y-m-d 23:59:00");
        //$Today                    =    date("Y-m-d h:i:s", strtotime($currentDateTime));
        $dateBefore1Week = date("Y-m-d h:i:s", strtotime($currentDateTime . "-1 Week"));
    
        // $profit_list = DB::table('daily_profit_bonus')
        //     ->select('daily_profit_bonus.*')
        //     ->whereBetween('daily_profit_bonus.created_at', [$dateBefore1Week, $currentDateTime])
        //     ->orderby('daily_profit_bonus.created_at', 'DESC')->get();

        $profit_list = daily_profit_bonus::whereBetween(
            'daily_profit_bonus.created_at',
            [$dateBefore1Week, $currentDateTime]
        )
            ->orderby('daily_profit_bonus.created_at', 'DESC')->get();

        // $bonus_list = DB::table('daily_investment_bonus')
        //     ->select('daily_investment_bonus.*')
        //     ->whereBetween('daily_investment_bonus.created_at', [$dateBefore1Week, $currentDateTime])
        //     ->orderby('daily_investment_bonus.created_at', 'DESC')->get();
        $bonus_list = daily_investment_bonus::with(['parent'])
            ->whereBetween('daily_investment_bonus.created_at', [$dateBefore1Week, $currentDateTime])
            ->orderby('daily_investment_bonus.created_at', 'DESC')->get();

        $title = 'Daily Profit';

        if (Auth::user()->type == 1 || Auth::user()->type == '1') {
            return view(
                'currentWeekProfitBonus',
                ['title' => $title, 'profit_list' => $profit_list, 'bonus_list' => $bonus_list]
            );
        } else {
            return redirect()->back()->with('Errormsg', 'Invalid Link!');
        }
    }

    

    public function daily_profit()
    {  
       $title = 'Daily Profit';
       if (app('request')->session()->get('back_to_admin')) {

       $userId = Auth::user()->id;
       $dailyProfit =   daily_profit_bonus::whereUser($userId);
       $totalProfitBonus = round($dailyProfit->sum('today_profit'), 2);
       $ratesQueryResults = current_rate::first();

        $totalProfitBonusUSD = $dailyProfit->where('currency', '=', "USD")->sum('today_profit');
        $totalProfitBonusBTC = $dailyProfit->where('currency', '=', "BTC")->sum('crypto_profit');
        $totalProfitBonusETH = $dailyProfit->where('currency', '=', "ETH")->sum('crypto_profit');


        $old_profit_data = UserAccounts::where('user_id',$userId)->first();
        $old_profit_usd = $old_profit_data->old_profit_usd;
        $old_profit_btc = $old_profit_data->old_profit_btc;
        $old_profit_eth = $old_profit_data->old_profit_eth;


        $totalOld = $old_profit_usd + ($old_profit_btc * $ratesQueryResults->rate_btc) + ($old_profit_eth * $ratesQueryResults->rate_eth);

        $total_USD = $totalProfitBonus + $totalOld;

          $withdrawalsProfitQuery = withdrawals::whereUser($userId)->where('payment_mode', '=', "Profit")->where('status', '=', "Approved");
          $withdrawalsProfitQueryUSD = $withdrawalsProfitQuery->where('currency', '=', "USD");
          $withdrawalsProfitQueryBTC = $withdrawalsProfitQuery->where('currency', '=', "BTC");
          $withdrawalsProfitQueryETH = $withdrawalsProfitQuery->where('currency', '=', "ETH");
        if($userId < 15000) {
            $withdrawn_usd = $withdrawalsProfitQueryUSD->where('created_at', '>=', "2018-12-15")->sum('amount');
            $withdrawn_btc = $withdrawalsProfitQueryBTC->where('created_at', '>=', "2018-12-15")->sum('amount');
            $withdrawn_eth = $withdrawalsProfitQueryETH->where('created_at', '>=', "2018-12-15")->sum('amount');

            $pre_withdrawn_usd = $withdrawalsProfitQueryUSD->where('created_at', '<', "2018-12-15")->sum('amount');
            $pre_withdrawn_btc = $withdrawalsProfitQueryBTC->where('created_at', '<', "2018-12-15")->sum('amount');
            $pre_withdrawn_eth = $withdrawalsProfitQueryETH->where('created_at', '<', "2018-12-15")->sum('amount');
        }else{
            $withdrawn_usd = $withdrawalsProfitQueryUSD->sum('amount');
            $withdrawn_btc = $withdrawalsProfitQueryBTC->sum('amount');
            $withdrawn_eth = $withdrawalsProfitQueryETH->sum('amount');

            $pre_withdrawn_usd = 0;
            $pre_withdrawn_btc = 0;
            $pre_withdrawn_eth = 0;
        }
        return view(
            'daily_profit',
            ['title' => $title,
             'total_USD' => $total_USD ,'totalProfitBonus' => $totalProfitBonus,
             'totalProfitBonusUSD' => $totalProfitBonusUSD,'totalProfitBonusBTC' => $totalProfitBonusBTC,'totalProfitBonusETH' => $totalProfitBonusETH,
             'old_profit_usd' => $old_profit_usd,'old_profit_btc' => $old_profit_btc,'old_profit_eth' => $old_profit_eth,
             'withdrawn_usd' => $withdrawn_usd, 'withdrawn_btc' => $withdrawn_btc, 'withdrawn_eth' => $withdrawn_eth,
             'pre_withdrawn_usd' => $pre_withdrawn_usd, 'pre_withdrawn_btc' => $pre_withdrawn_btc, 'pre_withdrawn_eth' => $pre_withdrawn_eth 
            ]);
        }else{
            return view('daily_profit',['title' => $title]);
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     * Purpose: Daily Profit data in json.
     */
    public function daily_profit_json()
    {
       $daily_profit_list = daily_profit_bonus::whereUser(Auth::user()->id);
        return datatables()->of($daily_profit_list)->toJson();
    }

    //Return Daily Bonus list

    public function daily_bonus()
    {

        $title = 'Daily Bonus';
       
        return view('daily_bonus', ['title' => $title]);
    }


    public function daily_bonus_json()
    {
        $logeduserId = Auth::user()->id;

        $daily_bonus_list = DB::table('daily_investment_bonus')
                          ->where('parent_user_id', $logeduserId)
                          ->where('trade_id', '!=', "")
                          ->orderby('created_at', 'DESC');

        return datatables()->of($daily_bonus_list)->editColumn('user_id', function($daily_bonus_list)
        {
            return str_replace('B4U','SRG',$daily_bonus_list->user_id);
        })->addIndexColumn()->toJson();
    }


    // New Function Get Rates From Coin Market Cap
}
