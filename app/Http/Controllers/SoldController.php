<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserAccounts;
use App\withdrawals;
use App\deposits;
use App\solds;
use App\Model\Referral;
use DB;

class SoldController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        set_time_limit(0);
    }
    
    
    //Return sold route for customers

    public function sold()
    {
        $soldQuery     = solds::where('user_id', Auth::user()->id)->orderby('created_at', 'DESC')->get();
        $title        = 'Sold Trades';
        return view('sold', ['title'=>$title,'solds'=>$soldQuery]);
    }



    //Return manage deposits route for admin

    public function msold()
    {
        if (Auth::user()->type == 1 || Auth::user()->type == 2 || Auth::user()->type == 5) {
            //$deposits = deposits::where('status','Processed')->orwhere('status','Pending')->orderby('created_at','DESC')->get();
            $soldQuery = DB::table('solds')
                ->where('status', 'Approved')
                ->orderby('created_at', 'DESC')
                ->get();

            $title= 'Manage Customers Sold Trades';

            return view('admin/msold', ['title' => $title, 'solds' => $soldQuery]);
        } else {
            return redirect()->intended('dashboard/sold')->with('errormsg', 'You are not allowed!.');
        }
    }

    public function msold_json()
    {
        if (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            if (Auth::user()->id == 6774) {
                $country = config('country.asia');
                $soldQuery2 = DB::table('solds')
                    ->leftJoin('users', 'solds.user_id', '=', 'users.id')
                    ->select('solds.*', 'users.u_id')
                    ->where('solds.status', 'Approved')
                    ->whereIn('users.Country', $country)
                    ->orderby('solds.created_at', 'DESC');
            } elseif (Auth::user()->id == 8416) {
                $country = config('country.europe');
                $soldQuery2 = DB::table('solds')
                    ->leftJoin('users', 'solds.user_id', '=', 'users.id')
                    ->select('solds.*', 'users.u_id')
                    ->where('solds.status', 'Approved')
                    ->whereIn('users.Country', $country)
                    ->orderby('solds.created_at', 'DESC');
            }
        } elseif (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_MANAGER)) {
            $soldQuery2 = DB::table('solds')
                ->leftJoin('users', 'solds.user_id', '=', 'users.id')
                ->select('solds.*', 'users.u_id')
                ->where('solds.status', 'Approved')
                ->orderby('solds.created_at', 'DESC');
        }

        return datatables()->of($soldQuery2)->toJson();
    }
    
    // Change Status to Processed
    public function partialSold(Request $request)
    {
        $withdrawAmount = $request['withdrawalAmount'];
        
        $deduct         = $request['soldDeduct'];
        $tradeID         = $request['deposit_id'];
        $details         = $this->partialSaleTrade($tradeID, $deduct, $withdrawAmount);
        return redirect()->intended('dashboard/sold')->with('successmsg', 'Deposit Successfully Partially Sold and New Withdrawal is also Created.');
        
        //$fee             = $request['soldFee'];
        //$currency         = $request['curr_id'];
        //$deposit_amount = $request['depositAmount'];
        
        //echo  "Deposit Amount : $deposit_amount ,Withdrawa Amount: $withdrawAmount ,Fee :$fee ,Deduct: $deduct  ,DepositID: $depositID,Currency: $currency";
        //exit;
    }
    
    public function partialSaleTrade($tradeID, $deduct, $withdrawAmount)
    {
        $logeduser_id     =   Auth::user()->id;
        $logeduser_uid     =   Auth::user()->u_id;
                
        $deposit         =      deposits::where('id', $tradeID)->first();
        $amount            =    $deposit->amount;
        $total_amount    =    $deposit->total_amount;
        $trade_id        =    "D-".$deposit->id;
        $payment_mode     =     $deposit->payment_mode;
        $userid            =    $deposit->user_id;
        $status            =    $deposit->status;
        $currency        =    $deposit->currency;
        $trade_profit    =    $deposit->trade_profit;
        $crypto_profit    =    $deposit->crypto_profit;
        $created_at        =    $deposit->created_at;
        $approved_at     =     $deposit->approved_at;
        $profit_take     =     $deposit->profit_take;
        $trans_type     =     $deposit->trans_type;
        $plan             =     $deposit->plan;
        $rate            =   $deposit->rate;
        $is_partial_sold=   $deposit->is_partial_sold;
        
        $newAmount         =  $withdrawAmount;
        $deductedAmount =  0;
        if ($status == "Approved" && $is_partial_sold != 1) {
            $total_amount2       = $rate * $withdrawAmount;
            
            $dp                    = new deposits();
            $dp->amount            = $withdrawAmount;
            $dp->payment_mode    = $payment_mode;
            $dp->currency        = $currency;
            $dp->rate            = $rate;
            $dp->total_amount    = $total_amount2;
            $dp->plan            = $plan;
            $dp->sold_at        = date("Y-m-d");
            $dp->user_id        = $logeduser_id;
            $dp->unique_id        = $logeduser_uid;
            $dp->status            = 'Sold';
            $dp->pre_status        = 'Partial';
            $dp->profit_take     = 2;
            $dp->trans_id         = "Partial sold of Trade D-".$tradeID;
            $dp->trans_type     = $trans_type;
            $dp->partial_tradeid= $tradeID;
            $dp->is_partial_sold     = 1;
            $dp->save();
            $new_deposit_id     =     $dp->id;
           
            $amount                =     $amount - $withdrawAmount;
            $total_amount        =   $total_amount - $total_amount2;
            //Update Main Deposit AMOUNT
            deposits::where('id', $tradeID)->update(['total_amount'=>$total_amount,'amount'=>$amount,'partial_tradeid'=>$new_deposit_id,'is_partial_sold'=>1]);
        
            $curr                 =     strtolower($currency);
            $userAccInfo         =     UserAccounts::where('user_id', $userid)->first();
            $accBal             =     "balance_" . $curr;
            $userbalance         =     $userAccInfo->$accBal;
            $userbalance        =   $userbalance - $withdrawAmount;
            // Update User Accounts Table
            UserAccounts::where('user_id', $logeduser_id)->update([$accBal => $userbalance]);
            Referral::sync($userid);
            
            
            if (($deduct == 1 || $deduct == "1") && $withdrawAmount > 0) {
                $deductedAmount =  $this->calculateDeduction($approved_at, $withdrawAmount);
                if ($deductedAmount > 0) {
                    $newAmount         =  $newAmount - $deductedAmount;
                }
            }
            
            if ($newAmount > 0) {
                $sd                     = new solds();
                $sd->user_id             = $logeduser_id;
                $sd->unique_id             = $logeduser_uid;
                $sd->amount             = $newAmount;
                $sd->trade_id             = $tradeID;
                $sd->currency             = $currency;
                $sd->payment_mode         = "Sold";
                $sd->status             = 'Approved';
                $sd->pre_status         = 'Partial';
                $sd->pre_amount         = $withdrawAmount;
                $sd->new_amount         = $newAmount;
                $sd->new_type             = "D-".$new_deposit_id;
                $sd->sale_deduct_amount = $deductedAmount;
                $sd->save();
                $lastSoldID             = $sd->id;
                
                //$sd->waiting_profit     = $userwaitingProfit2;
                //$sd->sold_profit         = $sold_profit;
            }
            
            // Total Amount in dollers
            $ratesQuery     = DB::table('currency_rates')->orderby('created_at', 'DESC')->first();
            $rate           = 1;
            if (isset($currency) && isset($ratesQuery)) {
                $curr         = strtolower($currency);
                $rateVal    = "rate_".$curr;
                $rate        = $ratesQuery->$rateVal;
            }
            // Add New Withdrawal
            $sold  = $this->soldWithdrawal($newAmount, $currency, $rate);
            $success = "Success";
            return  $success;
        } else {
            $error = "Already Partial Sold";
            return  $error;
        }
    }

    /*
        $userbalance2         = $userbalance;
        // Save Logs
        $title                 = "Deposit Sold";
        $details            = "The Balance of user : $logeduser_id has been updated" ;
        $pre_amt             = $userbalanceSold2;
        $curr_amt           = $userbalanceSold;
        $approvedby         = $logeduser_uid;;
        // Save Logs
        $this->saveLogs($title,$details,$logeduser_id,$logeduser_uid,$currency,$amount,$pre_amt,$curr_amt,$approvedby);

    */
  
 
    // Change Status to Processed
    public function calculateDeduction($approved_at, $amount)
    {
        $approvedDate         =     date("Y-m-d", strtotime($approved_at));
        $todayDate            =     date("Y-m-d");
        $date1                 =   date_create($approvedDate);
        $date2                =    date_create($todayDate);
        $diff                =    date_diff($date1, $date2);
        $diff_in_days         =     $diff->days;
        $diff_in_days2         =     $diff->format("%R%a Days");
        $dateDifferance     =     date("Y-m-d", strtotime($approvedDate . $diff_in_days2));
        // $dateAfter15Days    =    date("Y-m-d", strtotime($approvedDate."+15 Days"));
        $dateAfter1Month     =     date("Y-m-d", strtotime($approvedDate . "+1 Month"));
        $dateAfter2Months     =     date("Y-m-d", strtotime($approvedDate . "+2 Months"));
        $dateAfter4Months     =     date("Y-m-d", strtotime($approvedDate . "+4 Months"));
        $dateAfter6Months     =     date("Y-m-d", strtotime($approvedDate . "+6 Months"));
        // New Deduction formula
        if ($dateDifferance < $dateAfter2Months) {
            $deductionPercentage = 35;
            $deductedAmount = (floatval($amount)*35)/100;
        } elseif ($dateDifferance > $dateAfter2Months && $dateDifferance < $dateAfter4Months) {
            $deductionPercentage = 20;
            $deductedAmount = (floatval($amount)*20)/100;
        } elseif ($dateDifferance > $dateAfter4Months && $dateDifferance < $dateAfter6Months) {
            $deductionPercentage = 10;
            $deductedAmount = (floatval($amount)*10)/100;
        } elseif ($dateDifferance >= $dateAfter6Months) {
            $deductionPercentage = 0;
            $deductedAmount = 0;
        } else {
            $deductedAmount = 0;
        }
            
        return $deductedAmount;
    }
    
    public function soldWithdrawal($amount, $currency, $rate)
    {
        $logeduser_id     =   Auth::user()->id;
        $logeduser_uid     =   Auth::user()->u_id;
        $totalUsd       =     $amount * $rate;
        $withdrawAmount =   $amount ;
        // Set Withdrawal Deduct Fee
        if ($totalUsd <= 500) {
            if ($currency != "USD") {
                $withdrawal_fee     = 5 / $rate;
            } else {
                $withdrawal_fee     = 5;
            }
        } elseif ($totalUsd > 500) {
            if ($currency != "USD") {
                $withdrawal_fee     = 15 / $rate;
            } else {
                $withdrawal_fee     = 15;
            }
        }
        
        $withdrawAmount  =  $withdrawAmount - $withdrawal_fee;
        
        
        // save New Withdrawal
        $wd                    = new withdrawals();
        $wd->user            = Auth::user()->id;
        $wd->unique_id        = Auth::user()->u_id;
        $wd->amount            = $withdrawAmount;  // withdrawalAmount
        $wd->currency        = $currency;
        $wd->pre_amount        = $amount;
        
        if ($currency != "USD") {
            $wd->crypto_amount = $withdrawAmount;
        }
        $wd->usd_amount        = $totalUsd;
        $wd->payment_mode    = "Sold";
        $wd->status            = 'Pending';
        $wd->pre_status        = 'New';
        $wd->withdrawal_fee    = $withdrawal_fee; // Previous Acc Balance
        $wd->new_type        = "Partial";
        $wd->save();
        $last_withdrawal_id =  $wd->id;
   
        return $last_withdrawal_id;
    }
}
