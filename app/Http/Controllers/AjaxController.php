<?php

namespace App\Http\Controllers;

use App\Currencies;
use App\Http\Requests\Deposits\ViewDepositDetailsRequest;
use App\withdrawals;
use App\deposits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function __construct()
    {
        // $this->settings = \App\settings::whereId(1)->first();
    }

    // get user withdrawls on dashboard
    public function getWithDrawals()
    {
        $wAmount = 0;
        $currenciesAll = Currencies::where('status', 'Active')->get();
        if (isset($currenciesAll)) {
            foreach ($currenciesAll as $curr) {
                $curruncy = $curr->code;
                $withdrawAmount = withdrawals::where('user', Auth::user()->id)
                    ->where('currency', $curruncy)
                    ->where('status', 'Approved')
                    ->value(DB::raw('SUM(amount + donation)'));

                if ('USD' == $curruncy) {
                    $wAmount = $wAmount + $withdrawAmount;
                } else {
                    $curr_small = $curr->small_code;
                    $rateval = 'rate_' . $curr_small;
                    $erates = DB::table('currency_rates')
                        ->select($rateval)
                        ->orderBy('id', 'desc')
                        ->first();
                    $exrate = $erates->$rateval;
                    $wAmount = $wAmount + ($withdrawAmount * $exrate);
                }
            }
        }

        return site_settings()->currency .
            number_format($wAmount, 2) .
            '<i class="fa fa-arrow-up" style="color:green; font-size:12px;"></i>';
    }

    // get total bonus for the dashboard
    public function getTotalBonus()
    {
        $totalInvestmentBonus = 0;
        // calling store procedure
        $totalInvestmentBonus = DB::select(
            'CALL calculateOverallBonus("' . Auth::user()->u_id . '",@payout)'
        )[0]->total_investment;

        return site_settings()->currency .
            $totalInvestmentBonus .
            '<i class="fa fa-arrow-up" style="color:green; font-size:12px;"></i>';
    }

    // get repoort of 2019 and greater then 2019
    public function getReport()
    {
        set_time_limit(0);
        $previous['deposits'] = DB::select(
            "select sum(d.total_amount) as total 
                    from deposits as d 
                    INNER JOIN users as u on u.id = d.user_id 
                    WHERE d.approved_at <= '2019-12-31' 
                    AND u.status ='active' 
                    AND d.status='Approved' 
                    AND u.type=0 "
        )[0]->total;

        $previous['profit'] = DB::select(
            'select SUM(today_profit) as total 
                    FROM daily_profit_bonus 
                    WHERE created_at <= "2019-12-31"'
        )[0]->total;

        $previous['investment_bonus'] = DB::select(
            "Call calculateInvestmentBonus('2000-12-12','2019-12-31',@payout)"
        )[0]->total_investment_bonus;

        $previous['withdrawals'] = DB::select(
            'select SUM(usd_amount) as total 
                    FROM withdrawals 
                    WHERE created_at <= "2019-12-31" 
                    AND status="Approved"'
        )[0]->total;

        $latest['deposits'] = DB::select(
            'select sum(d.total_amount) as total 
                    from deposits as d 
                    INNER JOIN users as u on u.id = d.user_id 
                    WHERE d.approved_at > "2019-12-31" 
                    AND u.status ="active" 
                    AND d.status="Approved" 
                    AND u.type=0 '
        )[0]->total;
        $latest['profit'] = DB::select(
            'select SUM(today_profit) as total 
                    FROM daily_profit_bonus 
                    WHERE created_at > "2019-12-31"'
        )[0]->total;
        $latest['investment_bonus'] = DB::select(
            "Call calculateInvestmentBonus('2020-01-01','2025-01-01',@payout)"
        )[0]->total_investment_bonus;
        $latest['withdrawals'] = DB::select(
            'select SUM(usd_amount) as total 
                    FROM withdrawals 
                    WHERE created_at > "2019-12-31" 
                    AND status="Approved"'
        )[0]->total;
        return view('admin.partials.report_card_admin_dashboard', compact('previous', 'latest'));
    }

    ////get details of deposit using id

    public function mdepositsEdit(ViewDepositDetailsRequest $request)
    {
        try {
            $deposit = deposits::find($request->id);
            if ($deposit) {
                return view("admin.partials.edit_deposit", compact('deposit'));
            }
        } catch (Exception $e) {
            Log::error("something went wrong in mdepositsEdit " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }
}
