<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\users;
use App\deposits;
use App\UserAccounts;
use App\currency_rates;
use App\daily_investment_bonus;
use App\daily_profit_bonus;
use App\Event;
use DB;
use Mail;

class ProfitCalculateController extends Controller
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


    public function payprofitbonus()
    {
        return view('home.payprofitbonus')->with(
            array(
                'title' => 'CalculateTotal Profit',
            )
        );
    }

    public function updateRates()
    {
        $currentdate = date('Y-m-d');

        
        $currentRates = DB::table('current_rate')->orderby('created_at', 'DESC')->first();
        
        if (isset($currentRates)) {
            $currencyRates = DB::table('currency_rates')->orderby('created_at', 'DESC')->first();
            
            $created_at = date('Y-m-d', strtotime($currencyRates->created_at));
            
            /*echo $currencyRates->created_at . " " ;
            echo "Create" . $created_at ;
            echo " Current". $currentdate;*/

            
            if (isset($currencyRates) && $created_at != $currentdate) {//save rates info
                $rquery = new currency_rates();
                $rquery->rate_usd         = 1;
                $rquery->rate_btc         = $currentRates->rate_btc;
                $rquery->rate_eth         = $currentRates->rate_eth;
                $rquery->rate_bch         = $currentRates->rate_bch;
                $rquery->rate_ltc         = $currentRates->rate_ltc;
                $rquery->rate_xrp         = $currentRates->rate_xrp;
                $rquery->rate_zec         = $currentRates->rate_zec;
                $rquery->rate_dash         = $currentRates->rate_dash;
                $rquery->created_at     = $currentdate;
                //        $rquery->today_profit     = $currentRates->today_profit;
                $rquery->today_profit     = 0;
                $rquery->save();

                return redirect()->back()->with('message', 'Rates updated successfully!');
            } else {
                return redirect()->back()->with('errormsg', 'Today`s Rates already updated');
            }
        }
    }


  
    
    public function calculateProfit(Request $request)
    {
        
            
        //$todayDay             = date('l');
            
        $currentCronDate     = date('Y-m-d', strtotime($request['date']));
        $trade_id     =  $request['trade_id'];
        $lastProfitRate     =  $request['per_rate'];



        //$nextDate         = date('Y-m-d',strtotime("+1 days"));
        //$previousDate     = date('Y-m-d',strtotime("-1 days"));
        //$currentCronDate     = "2019-03-22";
        //$currentCronDate     = $nextDate;
            
        $currentDateCron     = $currentCronDate." 00:00:00";
        //$rquery             = DB::table('currency_rates')->orderby('created_at','DESC')->first();
        //    $rquery         = DB::table('currency_rates')->where('created_at',$currentDateCron)->first();
                
        if (isset($lastProfitRate)) {
            //$deposits = DB::table('deposits')->where('status','Approved')->where('approved_at','<=',$currentCronDate)->orderby('created_at','DESC')->get();
            $deposits = DB::table('deposits')->where('status', 'Approved')->where('id', '=', $trade_id)->get();
            //DB::enableQueryLog();
            //dd(DB::getQueryLog());
                
            //$deposits     = DB::table('deposits')->where('status','Approved')->where('approved_at','2019-03-22')->orderby('deposits.created_at','ASC')->get();
                        
            //    $deposits     = DB::table('deposits')->where('status','Approved')->whereBetween('approved_at', ['2019-03-18', '2019-03-22'])->get();
            if (isset($deposits)) {
                foreach ($deposits as $trade) {
                    $depositId               = $trade->id;
                    $user_id                   = $trade->user_id;
                    $unique_id               = "D-".$trade->id;
                    $amount                   = $trade->amount;
                    $payment_mode           = $trade->payment_mode;
                    $currency               = $trade->currency;
                    $rate                   = $trade->rate;
                    $total_amount           = $trade->total_amount;
                    $status                   = $trade->status;
                    $latest_profit           = $trade->latest_profit;
                    $trade_waiting_profit   = $trade->trade_profit;
                    $profit_total           = $trade->profit_total;
                    $flag_dummy               = $trade->flag_dummy;
                    $latest_crypto_profit   = $trade->latest_crypto_profit;
                    $crypto_profit          = $trade->crypto_profit;
                    $crypto_profit_total      = $trade->crypto_profit_total;
                    $lastProfit_update_date = $trade->lastProfit_update_date;
                    $waiting_profit_flag      = $trade->waiting_profit_flag;
                    $created_at               = $trade->created_at;
                    $approved_at               = $trade->approved_at;
                    $trans_type               = $trade->trans_type;
                        
                    
                        
                    //$currentDateCron         = date('Y-m-d')." 00:00:00";
                    //DB::enableQueryLog();
                    //$dailyprofitQuery     = DB::table('daily_profit_bonus')->where('trade_id',$depositId)->where('created_at',"2019-02-27 00:00:00")->first();
                    //dd(DB::getQueryLog());
                    $today_profit = 0;
                    $today_crypto_profit=0;
                    //$dailyprofitQuery         = DB::table('daily_profit_bonus')->where('trade_id',$depositId)->where('created_at',$currentDateCron)->first();
                    $dailyprofitQuery         = DB::table('daily_profit_bonus')->where('trade_id', $trade_id)->where('created_at', $currentDateCron)->first();
                        
                    if (!isset($dailyprofitQuery)) {
                        // Calculate Todays Profit
                        $today_profit     = (floatval($total_amount)*$lastProfitRate)/100;
                                
                        if ($currency != "USD") {
                            $today_crypto_profit    = (floatval($amount)*$lastProfitRate)/100;
                        } else {
                            $today_crypto_profit    = 0;
                        }
                            
                        // Get user Info
                        //$userPlansInfo                 = users::where('id',$user_id)->first();
                        //Calculate user plans
                        //PlansCron::process2($userPlansInfo);
                            
                        // Get User Accounts Info
                        $userAccInfo                 = UserAccounts::where('user_id', $user_id)->first();
                        $latest_bonus                = $userAccInfo->latest_bonus;
                        $latest_profit                = $userAccInfo->latest_profit;
                        $reference_bonus            = $userAccInfo->reference_bonus;
                                
                        $cur                         = strtolower($currency);
                        $accProfitVal                = "profit_".$cur;
                        $lastAvailProfit            = $userAccInfo->$accProfitVal;
                                
                        // Get User Info
                        $userInfo                     = users::where('id', $user_id)->first();
                        $parent_id                    = $userInfo->parent_id;
                        $user_uid                    = $userInfo->u_id;
                        $user_name                    = $userInfo->name;
                        $user_email                    = $userInfo->email;
                        $user_plan                    = $userInfo->plan;
                        $user_awarded_flag             = $userInfo->awarded_flag ;
                                
                        // Save Calculated Profit
                                
                        $profit_bonus                =    new daily_profit_bonus();
                        //$profit_bonus->trade_id        =     $depositId;
                        $profit_bonus->trade_id        =     $trade_id;
                        $profit_bonus->user_id        =     $user_uid;
                        $profit_bonus->currency        =     $currency;
                        $profit_bonus->trade_amount    =     $total_amount;
                        $profit_bonus->last_profit    =     $lastAvailProfit;
                        $profit_bonus->percetage    =   $lastProfitRate;
                        $profit_bonus->today_profit    =     $today_profit;
                        $profit_bonus->created_at    =     $currentCronDate;
                                
                        if ($currency != "USD") {
                            $profit_bonus->crypto_amount =     $amount;
                            $profit_bonus->crypto_profit =     $today_crypto_profit;
                        }
                                
                        $profit_bonus->save();
                            
                        // Update lastest Profit for users
                        $currentDateTime        = date("Y-m-d h:i:s");
                        $todayDate                = date("Y-m-d");
                        //$todayDate                = $currentCronDate;
                                
                        $approvedDate            = date("Y-m-d", strtotime($approved_at));
                        $date1MonthAfterApprove    = date("Y-m-d", strtotime($approvedDate."+1 Month")); //Date After 1 month of Approved
                        $date1                    = date_create($approvedDate);
                        $date                    = date_create($todayDate);
                            
                        $dateDifferance         = "";
                        if ($approvedDate != "") {
                            // Calculate Dates Differance1 in days
                            $diff                = date_diff($date1, $date);
                            $diff_in_days         = $diff->days;
                            $diff_in_days2         = $diff->format("%R%a Days");
                            $dateDifferance        = date("Y-m-d", strtotime($approved_at.$diff_in_days2));
                                
                            //$dateDifferance        = date("Y-m-d", strtotime($created_at.$diff_in_days2));
                        }
                        if ($currency != "USD") {
                            $crypto_profit            = $crypto_profit         + $today_crypto_profit;//deposits
                            $crypto_profit_total    = $crypto_profit_total     + $today_crypto_profit;//deposits
                        } else {
                            $crypto_profit            = 0;//deposits
                            $crypto_profit_total    = 0;//deposits
                        }
                            
                        if (isset($currency)) {
                            $curr                     = strtolower($currency);
                            $accBal                    = "balance_".$curr;
                            $userbalance            = $userAccInfo->$accBal;
                            $accBalSold                = "sold_bal_".$curr;
                            $userbalanceSold        = $userAccInfo->$accBalSold;
                            $accProfit                = "profit_".$curr;
                            $userProfit                = $userAccInfo->$accProfit;
                            $accWaitingProfit        = "waiting_profit_".$curr;
                            $userwaitingProfit        = $userAccInfo->$accWaitingProfit;
                            
                            $trade_waiting_profit     = $trade_waiting_profit + $today_profit;
                                
                                 
                            $difference             = "Not Set";
                                
                            if ($dateDifferance < $date1MonthAfterApprove) {
                                $difference = "Less than 1 Month";
                            }
                            if ($dateDifferance >= $date1MonthAfterApprove) {
                                $difference = "Greater than equal to 1 Month";
                            }
                            
                            //Update Daily Profit On Trades
                            if ($waiting_profit_flag == 0 && $trade_waiting_profit >= 0 && $dateDifferance < $date1MonthAfterApprove) {
                                if ($currency == "USD") {
                                    $latest_profit         = $today_profit;
                                    $userwaitingProfit     = $userwaitingProfit + $today_profit;
                                } else {
                                    $latest_profit         = $today_crypto_profit;
                                    $userwaitingProfit     = $userwaitingProfit + $today_crypto_profit;
                                }
                                if ($userwaitingProfit < 0) {
                                    $userwaitingProfit = 0;
                                }
                                    
                                UserAccounts::where('user_id', $user_id)->update([$accWaitingProfit=> $userwaitingProfit,'latest_profit' =>$latest_profit]);
                                $trade_waiting_profit      =    $trade_waiting_profit;
                            } elseif ($waiting_profit_flag == 0 && $trade_waiting_profit > 0 && $dateDifferance >= $date1MonthAfterApprove) {
                                if ($currency == "USD") {
                                    $latest_profit         = $today_profit;
                                    $userProfit         = $userProfit + $trade_waiting_profit;
                                    $userwaitingProfit     = $userwaitingProfit - $trade_waiting_profit;
                                } else {
                                    $latest_profit         = $today_crypto_profit;
                                    $userProfit         = $userProfit + $crypto_profit;
                                    $userwaitingProfit     = $userwaitingProfit - $crypto_profit;
                                }
                                    
                                if ($userwaitingProfit < 0) {
                                    $userwaitingProfit = 0;
                                }
                                    
                                UserAccounts::where('user_id', $user_id)->update([$accProfit=> $userProfit,$accWaitingProfit=> $userwaitingProfit,'latest_profit' =>$latest_profit]);
                                        
                                $trade_waiting_profit     = 0;
                                $waiting_profit_flag     = 1;
                            } elseif ($waiting_profit_flag == 1 && $dateDifferance > $date1MonthAfterApprove) {
                                if ($currency == "USD") {
                                    $latest_profit         = $today_profit;
                                    $userProfit         = $userProfit + $today_profit;
                                } else {
                                    $latest_profit         = $today_crypto_profit;
                                    $userProfit         = $userProfit + $today_crypto_profit;
                                }
                                            
                                UserAccounts::where('user_id', $user_id)->update([$accProfit=> $userProfit,'latest_profit' =>$latest_profit]);
                                $trade_waiting_profit     = 0;
                            }
                                
                            //$this->info('Today Cron Started with Profit Percentage : '.$lastProfitRate);
                            $profit_total             = $profit_total         + $today_profit;
                            $newTotal                 = $amount                 + $profit_total;
                            $arr =
                                   [
                                   'latest_profit'     => $today_profit,
                                   'trade_profit'         => $trade_waiting_profit,
                                   'profit_total'        => $profit_total,
                                   'new_total'         => $newTotal,
                                   ];
                                
                            if ($currency != "USD") {
                                $arr['latest_crypto_profit']     = $today_crypto_profit;
                                $arr['crypto_profit']             = $crypto_profit;
                                $arr['crypto_profit_total']     = $crypto_profit_total;
                            }
                            if ($lastProfit_update_date == "") {
                                $arr['lastProfit_update_date']    = $date1MonthAfterApprove;
                            }
                            if ($waiting_profit_flag == 1) {
                                $arr['waiting_profit_flag']     = $waiting_profit_flag;
                            }
                            // update Deposit
                            deposits::where('id', $depositId)->update($arr);
                        }
                        
                        // update Parent Bonus According to Rules of Profit
                            
                        if ($today_profit > 0 && $trans_type != "Reinvestment" && $parent_id != "0" && $parent_id != "B4U0001" && $flag_dummy == 0 && $user_awarded_flag == 0) {
                            $dailyBonusQuery = DB::table('daily_investment_bonus')->where('trade_id', $depositId)->where('details', "Profit Bonus")->where('created_at', $currentDateCron)->first();
                            if (!isset($dailyBonusQuery)) {
                                $this->updateParentsBonus($parent_id, $today_profit, $currentCronDate, $depositId, $user_uid);
                            }
                        }
                    }//end if
                }
            }
            echo "Success";
            exit;// end of foreach loop
        }
    }

    //Update Parent Bonuses
    public function updateParentsBonus($parent_id, $today_profit, $currentCronDate, $depositId, $user_uid)
    {
        $count=1;
        $calculatedBonus  = 0;
        for ($i=0; $i<5; $i++) {
            $parentDetails     = DB::table('users')
                ->select('id', 'u_id', 'parent_id', 'plan')
                ->where('u_id', $parent_id)
                ->first();
            if (isset($parentDetails)) {
                $Parent_userID         = $parentDetails->id;
                $parentPlanid         = $parentDetails->plan;
                $parentNewId         = $parentDetails->parent_id;
                $parent_uid         = $parentDetails->u_id;
                
                
                if ($parent_id == '0' || $parent_uid == "B4U0001") {
                    break;
                }
                //Getting Rules of Profit
                $plansDetailsQuery = DB::table('plans')
                    ->join('referal_profit_bonus_rules AS refprofit', 'plans.id', '=', 'refprofit.plan_id')
                    ->select('refprofit.first_pline', 'refprofit.second_pline', 'refprofit.third_pline', 'refprofit.fourth_pline', 'refprofit.fifth_pline')
                    ->where('plans.id', $parentPlanid)->first();
                    
                $profit_line1     = $plansDetailsQuery->first_pline;
                $profit_line2     = $plansDetailsQuery->second_pline;
                $profit_line3     = $plansDetailsQuery->third_pline;
                $profit_line4     = $plansDetailsQuery->fourth_pline;
                $profit_line5     = $plansDetailsQuery->fifth_pline;
                                        
                if (floatval($profit_line1) > 0 && $count==1) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line1))/100;
                    $percentage      =  $profit_line1;
                } elseif (floatval($profit_line2) > 0 && $count==2) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line2))/100;
                    $percentage      =  $profit_line2;
                } elseif (floatval($profit_line3) > 0 && $count==3) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line3))/100;
                    $percentage      =  $profit_line3;
                } elseif (floatval($profit_line4) > 0 && $count==4) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line4))/100;
                    $percentage      =  $profit_line4;
                } elseif (floatval($profit_line5) > 0 && $count==5) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line5))/100;
                    $percentage      =  $profit_line5;
                }
                                        
                $bonus                  = floatval($calculatedBonus);
                $parentAccInfo          = UserAccounts::where('user_id', $Parent_userID)->first();
                                    
                $referenceBonus      = $parentAccInfo->reference_bonus;
                $pre_bonus_amt          = $parentAccInfo->reference_bonus;
                                    
                $referenceBonus      = floatval($referenceBonus) + floatval($bonus);  // Accounts Table
                $new_bonus_amt       = $referenceBonus;
                
                
                if ($bonus > 0) {
                    $daily_ibonus                    =    new daily_investment_bonus();
                    $daily_ibonus->trade_id            =     $depositId;
                    $daily_ibonus->user_id            =     $user_uid;
                    $daily_ibonus->parent_id        =     $parent_id;
                    $daily_ibonus->parent_plan        =     $parentPlanid;
                    $daily_ibonus->bonus            =     $bonus;
                    $daily_ibonus->pre_bonus_amt    =     $pre_bonus_amt;
                    $daily_ibonus->new_bonus_amt    =     $new_bonus_amt;
                    $daily_ibonus->created_at        =     $currentCronDate;
                    $daily_ibonus->details            =     "Profit Bonus";
                    $daily_ibonus->save();
                    
                    //$this->info('Update On 607 Trade ='.$depositId);
                    // update bonus for parents
                                        
                    if ($referenceBonus >= 0) {
                        UserAccounts::where('user_id', $Parent_userID)->update(['reference_bonus'=>$referenceBonus,'latest_bonus'=> $bonus]);
                    }
                }
                $parent_id = $parentNewId;
                
                $count++;
                $calculatedBonus = 0;
            }//end of if
        } // end of for loop
    }

    
    
    // Send Email To Admin
    /*function sendEmailToMe($user_email, $percentage, $date, $startEnd)
    {
    // Send Emails to users
    if(isset($user_email) && isset($percentage) && isset($date))
    {
            //$email_to         = $user_email;
            //$userName        = $user_name;

            $from_Name        = getMailFromName();
            $from_email        = getSupportMailFromAddress();
    if($startEnd =="Successful")
    {
                $subject    = "B4U Global Profit Cron Job completed successfully Runs for Date ($date) and profit percentage: $percentage %";
    }else if($startEnd=="Started")
    {
                $subject    = "B4U Global Profit Cron Job Started successfully for Date ($date) from B4U Global";
    }else{
                $subject    = "B4U Global Profit Cron Not Successfull! for Date ($date)";
    }
            $message         =
                                 "<html>
                                     <body align=\"left\" style=\"height: 100%;\">
                                        <div>

                                            <div>
                                                <table style=\"width: 100%;\">
                                                    <tr>
                                                        <td style=\"text-align:left; padding:10px 0;\">
                                                            <h1>Dear Member B4U Global</h1>
                                                        </td>
                                                    </tr>";
                                if($startEnd=="Started")
                                {
                                    $message     .="<tr>
                                                        <td style=\"text-align:left; padding:10px 0;\">
                                                            <h1>This Email is to inform you about today Profit Cron Job Runs successfully and date is ".$date.".</h1>
                                                        </td>
                                                    </tr>";
                                }else if($startEnd=="Successful")
                                {
                                    $message     .="<tr>
                                                        <td style=\"text-align:left; padding:10px 0;\">
                                                            <h1>This Email is to inform you about today Profit Cron Job Completed successfully, and today Profit Percentage is ".$percentage." % and date is ".$date.".</h1>
                                                        </td>
                                                </tr>";
                                }else{
                                    $message     .="<tr>
                                                        <td style=\"text-align:left; padding:10px 0;\">
                                                            <h1>This Email is to inform you about today Profit Cron Job not successfull, </h1>
                                                            <p> Due to haveing Error : ".$startEnd."</p>
                                                        </td>
                                                </tr>";
                                }

                                    $message     .="<tr>
                                                        <td style=\"text-align:left; padding:10px 0;\">
                                                            Thanks
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style=\"padding:10px 0; text-align:left;\">
                                                            Your Sincerely,
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style=\"padding:10px 0; text-align:left;\">
                                                            Team B4U Global

                                                        </td>
                                                    </tr>


                                                </table>
                                            </div>
                                        </div>
                                    </body>
                                </html>";
                        //echo $message;
                        //exit;
                        // Always set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From:B4U Global Support <'.$from_email.'>' . "\r\n";
                            // More headers info
                //$headers .= 'From: Noreply <noreply@b4uinvestors.cf>' . "\r\n";
                $headers .= 'Cc: mudassar.mscit@gmail.com' . "\r\n";

                // Send Emails to users
                $success = @mail($user_email, $subject, $message , $headers);

    }

    }*/
    
    //Update Events
    public function updateEvents($currentDate, $profitRate)
    {
        $events  =  Event::where('start_date', 'Like', $currentDate)->where('end_date', 'Like', $currentDate)->first();
        if (!isset($events)) {
            $equery             =  new Event();
            $equery->title      =  $profitRate."%";
            $equery->start_date =  $currentDate;
            $equery->end_date   =  $currentDate;
            $equery->save();
        }
    }
}
