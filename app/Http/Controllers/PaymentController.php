<?php

namespace App\Http\Controllers;

use App\BankAccountModel;
use App\CBDebits;
use App\Console\Commands\PlansCron;
use App\current_rate;
use App\Http\Requests\Deposits\ApproveDepositRequest;
use App\Http\Requests\Deposits\MakeDepositRequest;
use App\Http\Requests\Deposits\UpdateDepositStatus;
use App\Http\Requests\Deposits\ViewDepositDetailsRequest;
use App\Http\Requests\Solds\MakeSoldRequest;
use App\settings;
use Illuminate\Support\Facades\DB;
use Log;
use Mail;
use Session;
use App\solds;
use App\users;
use App\Account;
use App\deposits;
use App\OTPToken;
use Carbon\Carbon;
use App\admin_logs;
use App\Currencies;
use App\UserAccounts;
use App\currency_rates;
use App\Model\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\daily_investment_bonus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        set_time_limit(0);
        $this->settings = settings::getSettings();
        // $this->middleware('auth');
        //saveLogs($title,$details,$userid,$curr,$amt,$pre_amt,$approvedby)
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|Response|\Illuminate\View\View
     */
    //payment route
    public function payment(Request $request)
    {
        // redirect user to new deposite create screen if users country is not selected
        if (Auth::user() && !Auth::user()->Country) {
            return redirect(url('dashboard/deposits'));
        }


        $amount = $request->session()->get('amount');
        $payment_mode = $request->session()->get('payment_mode');
        $plan_id = $request->session()->get('plan_id') ? $request->session()->get('plan_id') : Auth::user()->plan;
        $deposit_mode = $request->session()->get('deposit_mode');
        $currency = $request->session()->get('currency');
        if ($request->session()->get('reinvest_type')) {
            $reinvest_type = $request->session()->get('reinvest_type');
        } else {
            $reinvest_type = '';
        }

        $userAccInfo = UserAccounts::where('user_id', Auth::user()->id)->first();
        //$ratesQuery = currecy_rates::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();
        $activeDeposits = deposits::where('user_id', Auth::user()->id)->where('status', 'Approved')->get();
        $totalActiveDeposits = count($activeDeposits);

        $prevProfitReinvest =
            deposits::where('user_id', Auth::user()->id)->where('reinvest_type', 'Profit')->where('currency', $currency)->orderBy('created_at', 'DESC')->first();

        if (isset($prevProfitReinvest) && isset($prevProfitReinvest->created_at)) {
            $createdAt = $prevProfitReinvest->created_at;
            $dateDifferece = $this->calculateDate($createdAt);
        } else {
            $dateDifferece = 1;
        }

        if ($deposit_mode == 'reinvest') {
            if (isset($currency) && isset($ratesQuery)) {
                $curr = strtolower($currency);
                $rateVal = 'rate_' . $curr;
                $currencyRate = $ratesQuery->$rateVal;
                $accProfit = 'profit_' . $curr;
                $userProfit = $userAccInfo->$accProfit;
                $accBalSold = 'sold_bal_' . $curr;
                $userbalanceSold = $userAccInfo->$accBalSold;
                $reference_bonus = $userAccInfo->reference_bonus;

                $totalUsd = $amount * $currencyRate;
                if ($currency == 'USD' && $reinvest_type == 'Bonus') {
                    if ($totalUsd < site_settings()->reinvest_limit || $amount > $reference_bonus) {
                        return redirect()->intended('dashboard/deposits')->with('errormsg', 'Amount is less than ' . site_settings()->reinvest_limit . ' Or You have insufficient balance for this request!');
                    } elseif ($totalActiveDeposits == 0) {
                        return redirect()->intended('dashboard/deposits')->with('errormsg', 'Bonus Reinvestment Not Allowed ! You have no approved deposits in your deposits account.');
                    }
                } elseif ($currency != 'USD' && $reinvest_type == 'Bonus') {
                    return redirect()->intended('dashboard/deposits')->with('errormsg', 'Invalid request! selected currency not allowed for reinvest Bonus');
                } elseif ($reinvest_type == 'Profit' && ($totalUsd < site_settings()->reinvest_limit || $amount > $userProfit)) {
                    return redirect()->intended('dashboard/deposits')->with('errormsg', 'Amount is less than ' . site_settings()->reinvest_limit . '! Or You have insufficient balance for this request!');
                } elseif ($reinvest_type == 'Profit' && $dateDifferece != 1) {
                    return redirect()->intended('dashboard/deposits')->with('errormsg', 'Profit Reinvest Not Allowed ! You can create only 1 Profit Reinvest against a Currency within 1 Month.');
                } elseif ($reinvest_type == 'Sold' && ($totalUsd < site_settings()->reinvest_limit || $amount > $userbalanceSold)) {
                    return redirect()->intended('dashboard/deposits')->with('errormsg', 'Amount is less than ' . site_settings()->reinvest_limit . '! Or You have insufficient balance for this request!');
                }
            } else {
                return redirect()->intended('dashboard/deposits')->with('errormsg', 'Invalid Currency or Rates');
            }
        }
        $title = 'Make deposit';
        //Return payment page
        return view('payment', ['currencies' => Currencies::getCurrencies(), 'amount' => $amount, 'currency' => $currency, 'payment_mode' => $payment_mode, 'reinvest_type' => $reinvest_type, 'deposit_mode' => $deposit_mode, 'plan_id' => $plan_id, 'title' => $title]);
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
        $todayDate = date('Y-m-d');
        $creationDate = date('Y-m-d', strtotime($created_at));
        $date1MonthAfterCreate =
            date('Y-m-d', strtotime($creationDate . '+1 Month')); //Date After 1 month of Approved
        $date1 = date_create($creationDate);
        $date = date_create($todayDate);

        // Calculate Dates Differance1 in days
        $diff = date_diff($date1, $date);
        $diff_in_days = $diff->days;
        $diff_in_days2 = $diff->format('%R%a Days');
        $dateDifferance = date('Y-m-d', strtotime($created_at . $diff_in_days2));

        if ($dateDifferance >= $date1MonthAfterCreate) {
            return 1;
        } else {
            return 0;
        }
    }

    public function amountLimitCheck(Request $request)
    {
        //Investment amount info
        $amount = $request['amount'];
        $curr = $request['curr'];
        $currencies = Currencies::distinct('code')->where('status', 'Active')->get();
     //   $ratesQuery = ::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();
        if ($curr != 'USD' || $curr != 'usd') {
            $currency = strtolower($curr);
            $rateVal = 'rate_' . $currency;
            $rate = $ratesQuery->$rateVal;
            $totalUsd = $amount * $rate;
        } else {
            $totalUsd = $amount;
        }
        echo $totalUsd;
        exit;
    }

    public function deposit(Request $request)
    {
        if ($request['deposit_mode'] == 'new') {
            \Illuminate\Support\Facades\Session::forget('re-invest');
        }

        if ($request['deposit_mode'] == 'reinvest' || \Illuminate\Support\Facades\Session::has('re-invest')) {
            /////////////////////send otp on account change////////////////////////////////////

            if (!app('request')->session()->get('back_to_admin')) {
                if (\Illuminate\Support\Facades\Session::has('re-invest') && !$request->exists('_key')) {
                    $data = \Illuminate\Support\Facades\Session::pull('re-invest');
                    $request = $request->merge($data);
                    \Illuminate\Support\Facades\Session::forget('re-invest');
                } else {
                    $otp = new OTPToken();
                    $random_string = $otp->generateCode();
                    if (PinController::sendOtpSms(Auth::user()->phone_no, $random_string)) {
                        ///save otp into database
                        $otp->code = $random_string;
                        $otp->user_id = Auth::user()->id;
                        $otp->otp_type = 're-invest';
                        $otp->save();
                        ///put request into session for later use
                        \Illuminate\Support\Facades\Session::forget('re-invest');
                        \Illuminate\Support\Facades\Session::put('re-invest', $request->all());

                        return redirect()->route('getOtp', [$otp->id]);
                    } else {
                        return redirect()->back()->with('successmsg', 'Unable to send OTP . Please try again later.!');
                    }
                }
            }
        }

        //store payment info in session
        $request->session()->put('amount', $request['amount']);
        $request->session()->put('payment_mode', $request['payment_mode']);
        $request->session()->put('deposit_mode', $request['deposit_mode']);
        $request->session()->put('currency', $request['currency']);
        if (isset($request['reinvest_type'])) {
            $request->session()->put('reinvest_type', $request['reinvest_type']);
        }
        if (isset($request['pay_type'])) {
            $request->session()->put('pay_type', $request['pay_type']);
            $request->session()->put('plan_id', $request['plan_id']);
        }
        $title = 'Make deposit';
        $userAccInfo = UserAccounts::where('id', Auth::user()->id)->first();
     //   $ratesQuery = currecy_rates::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();

        return redirect()->route('payment', ['title' => $title, 'accountsInfo' => $userAccInfo, 'ratesQuery' => $ratesQuery]);
    }

    //Return deposit route for customers
    public function deposits()
    {
        $deposits = deposits::where('user_id', Auth::user()->id)
            ->where('status', '!=', 'Cancelled')
            ->where('status', '!=', 'Deleted')
            ->orderby('created_at', 'DESC')->get();

        $title = 'Deposits';
        $userAccInfo = UserAccounts::where('id', Auth::user()->id)->first();
        //echo "<pre>";
        $currencies = Currencies::distinct('code')->where('status', 'Active')->get();
        //print_r($currencies);
        //exit;
       // $ratesQuery = currecy_rates::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();

        return view('deposits', ['title' => $title, 'deposits' => $deposits, 'accountsInfo' => $userAccInfo, 'ratesQuery' => $ratesQuery, 'currencies' => $currencies]);
    }

    //Save deposit requests
    public function savedeposit(MakeDepositRequest $request)
    {
        if (deposits::where('user_id', \Auth::user()->id)->where('created_at', '>', \Carbon\Carbon::now()->subMinutes(5))->count()) {
            return redirect()->intended('dashboard/deposits')->with('errormsg', 'You can make only 1 deposit every 5 mins. please wait 5 mins to make new deposit');
        }

        //$parent_id=Auth::user()->ref_by;
        $logeduser_id = Auth::user()->id;
        $plan_id = Auth::user()->plan;
        $logeduser_uid = Auth::user()->u_id;

        $invested_amount = $request['amount'];
        $payment_mode = $request['payment_mode'];
        $currency = $request['currency'];

        $image = null;

        if (isset($request['trans_id'])) {
            $trans_id = $request['trans_id'];
            $trans_type = 'NewInvestment';
        } elseif ($request->deposit_mode == 'reinvest') {
            $trans_id = 'reinvest';
            $trans_type = 'Reinvestment';
        } else {
            $trans_id = '';
            $trans_type = 'NewInvestment';
        }

        if ($trans_type == 'NewInvestment') {
            ## upload file only and only if deposit type is re-invenst
            $uploadedFile = SignedUrlUploadController::uploadImageToGoogleCloud($request->get('fileurl'));
            if ($uploadedFile['status']) {
                $image = !empty($uploadedFile['result']['imageName']) ? $uploadedFile['result']['imageName'] : null;
            } else {
                \Illuminate\Support\Facades\Log::error('Error while uploading file at Deposit',
                    [
                        'aws-file-upload-url' => $request->get('fileurl')
                    ]);
                return back();
            }
        }


        if (isset($request['reinvest_type'])) {
            $reinvest_type = $request['reinvest_type'];
        } else {
            $reinvest_type = '';
        }

        if (isset($request['deposit_mode'])) {
            $deposit_mode = $request['deposit_mode'];
        } else {
            $deposit_mode = '';
        }

        $total_amount = '';
        $trade_profit = 0;
        $currencyRate = '';

        // Total Amount in dollers
   //     $ratesQuery = \Illuminate\Support\Facades\DB::table('currecy_rates')->orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();

        if (isset($currency) && isset($ratesQuery)) {
            $curr = strtolower($currency);
            $rateVal = 'rate_' . $curr;
            $currencyRate = $ratesQuery->$rateVal;
        }

        $fee_deduct = explode(',', site_settings()->deposit_fee);
        $fee = 0;
        //deduct fee from the deposit if depost amount less then equal to 700 usd then 10 usd else 30 usd
        $total_amount = floatval($invested_amount) * floatval($currencyRate);
        if ($deposit_mode == 'new') {
            if (feeDeductOnDailyDeposit($total_amount, $logeduser_id) <= 300) {
                $total_amount = ($total_amount - $fee_deduct[0]);
                $fee = $fee_deduct[0];
                $invested_amount = floatval($total_amount) / floatval($currencyRate);
            } elseif (feeDeductOnDailyDeposit($total_amount, $logeduser_id) > 300 && feeDeductOnDailyDeposit($total_amount, $logeduser_id) <= 1000) {
                $total_amount = ($total_amount - $fee_deduct[1]);
                $fee = $fee_deduct[1];
                $invested_amount = floatval($total_amount) / floatval($currencyRate);
            } elseif (feeDeductOnDailyDeposit($total_amount, $logeduser_id) > 1000) {
                $total_amount = ($total_amount - $fee_deduct[2]);
                $fee = $fee_deduct[2];
                $invested_amount = floatval($total_amount) / floatval($currencyRate);
            }
        }

        $plan_id2 = Auth::user()->plan;

        $flag = 0;

        $balance = 0;

        $userAccInfo = UserAccounts::where('user_id', $logeduser_id)->first();
        if (!isset($userAccInfo)) {
            $userAccs = new UserAccounts();
            $userAccs->user_id = Auth::user()->id;
            $userAccs->save();
            $userAccInfo = UserAccounts::where('id', Auth::user()->id)->first();
        }
        $reference_bonus = $userAccInfo->reference_bonus;
        $reference_bonus2 = $userAccInfo->reference_bonus;
        if (isset($currency)) {
            $curr = strtolower($currency);
            $accBal = 'balance_' . $curr;
            $userbalance = $userAccInfo->$accBal;
            $accBalSold = 'sold_bal_' . $curr;
            $userbalanceSold = $userAccInfo->$accBalSold;
            $userbalanceSold2 = $userAccInfo->$accBalSold;
            $accProfit = 'profit_' . $curr;
            $userProfit = $userAccInfo->$accProfit;
            $userProfit2 = $userAccInfo->$accProfit;
            $accWaitingProfit = 'waiting_profit_' . $curr;
            $userwaitingProfit = $userAccInfo->$accWaitingProfit;
        }
        if ($deposit_mode == 'reinvest') {
            if ($reinvest_type == 'Bonus') {
                $reference_bonus = $reference_bonus - $invested_amount;
                if ($reference_bonus >= 0) {
                    \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $logeduser_id)->update(['reference_bonus' => $reference_bonus]);
                    $pre_amt = $reference_bonus2;
                    $curr_amt = $reference_bonus;
                } else {
                    $msg =
                        'Deposits not successful! Your Deposit amount is insufficient, minimum deposit limit is $' . site_settings()->deposit_limit . '.';

                    return redirect()->intended('dashboard/deposits')->with('errormsg', $msg);
                }
            } elseif ($reinvest_type == 'Profit') {
                $userProfit = $userProfit - $invested_amount;
                if ($userProfit >= 0) {
                    \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $logeduser_id)->update([$accProfit => $userProfit]);
                    $pre_amt = $userProfit2;
                    $curr_amt = $userProfit;
                } else {
                    $msg =
                        'Deposits not successful! Your Deposit amount is insufficient, minimum deposit limit is $' . site_settings()->deposit_limit . '.';

                    return redirect()->intended('dashboard/deposits')->with('errormsg', $msg);
                }
            } elseif ($reinvest_type == 'soldBalance' || $reinvest_type == 'Sold' || $reinvest_type == 'Balance') {
                $userbalanceSold = $userbalanceSold - $invested_amount;
                if ($userbalanceSold >= 0) {
                    \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $logeduser_id)->update([$accBalSold => $userbalanceSold]);
                    $pre_amt = $userbalanceSold2;
                    $curr_amt = $userbalanceSold;
                } else {
                    $msg =
                        'Deposits not successful! Your Deposit amount is insufficient, minimum deposit limit is $' . site_settings()->deposit_limit . '.';

                    return redirect()->intended('dashboard/deposits')->with('errormsg', $msg);
                }
            }

            $title = $reinvest_type . ' Reinvestment';
            $details =
                $logeduser_uid . ' has ' . $deposit_mode . ' deposits of ' . $reinvest_type . ' (' . $currency . ')' . $invested_amount;
            //    $pre_amt     = 0;
            //    $curr_amt   = 0;
            $approvedby = '';
            // Save Logs
            $this->saveLogs($title, $details, $logeduser_id, $logeduser_uid, $currency, $invested_amount, $pre_amt, $curr_amt, $approvedby);
            $userid = $logeduser_id;
            $amount = $invested_amount;
            $this->reivestAutoApproved($userid, $currency, $reinvest_type, $amount);
        }

        if (($deposit_mode == 'new' && $total_amount >= site_settings()->deposit_limit) || ($deposit_mode == 'reinvest' && $total_amount >= site_settings()->reinvest_limit)) {
            // trade save

            $dp = new deposits();

            $dp->amount = $invested_amount;

            $dp->payment_mode = $payment_mode;

            $dp->bank_id = $request['bank_id'];

            $dp->currency = $currency;

            $dp->rate = $currencyRate;

            $dp->total_amount = $total_amount;

            $dp->plan = $plan_id2;

            $dp->user_id = $logeduser_id;

            $dp->unique_id = $logeduser_uid;

            $dp->status = 'Pending';

            $dp->pre_status = 'New';

            $dp->flag_dummy = 0;
            if ($deposit_mode == 'reinvest') {
                $dp->profit_take = 1;
                $dp->status = 'Approved';
                $dp->approved_at = date('Y-m-d'); // on reinvest bonus should not distributed to parents.
            } else {
                $dp->profit_take = 0;
                $dp->status = 'Pending';
            }
            if (isset($trade_profit)) {
                $dp->trade_profit = $trade_profit;
            }
            if (isset($image)) {
                $dp->proof = $image;
            }
            //if(isset($plan_id2)){
            //}
            if (isset($trans_id)) {
                $dp->trans_id = $trans_id;
            }
            $dp->trans_type = $trans_type;

            if (isset($reinvest_type)) {
                $dp->reinvest_type = $reinvest_type;
            }

            //fee detucted
            $dp->fee_deducted = $fee;

            $dp->save();

            $last_deposit_id = $dp->id;

            $parent_id = Auth::user()->parent_id;

            // Send Success EMail to user
            //$this->sendEmail($invested_amount,$currency);
        } else {
            $msg =
                'Deposits not successful! Your Deposit amount is insufficient, minimum deposit limit is $' . site_settings()->deposit_limit . '.';

            return redirect()->intended('dashboard/deposits')->with('errormsg', $msg);
        }

        if ($deposit_mode == 'new') {
            $title = $deposit_mode . ' Investment';
            $details =
                $logeduser_uid . ' has ' . $deposit_mode . ' deposits (' . $currency . ')' . $invested_amount;
            $pre_amt = 0;
            $curr_amt = 0;
            $approvedby = '';
            // Save Logs
            $this->saveLogs($title, $details, $logeduser_id, $logeduser_uid, $currency, $invested_amount, $pre_amt, $curr_amt, $approvedby);
        }

        // close all sessions
        $request->session()->forget('plan_id');

        $request->session()->forget('pay_type');

        $request->session()->forget('payment_mode');

        $request->session()->forget('amount');

        $request->session()->forget('deposit_mode');
        $request->session()->forget('currency');

        if ($request->session()->get('reinvest_type')) {
            $request->session()->forget('reinvest_type');
            //exit("all sessions reset");
        }

        if ($trans_type == 'NewInvestment') {

            $msg = 'Deposits Successful! Please wait for system to validate your request';
        } else {

            $msg = 'Deposit Successfully created!!';
        }

        return redirect()->intended('dashboard/deposits')->with('message', $msg);

    }

    public function reivestAutoApproved($userid, $currency, $reinvest_type, $amount)
    {
        $deposit_user = users::where('id', $userid)->first();
        $plan_id = $deposit_user->plan;
        $user_uid = $deposit_user->u_id;
        $parent_id = $deposit_user->parent_id;
        $user_awarded_flag = $deposit_user->awarded_flag;

        // Get user Accounts Details
        $userAccQuery = UserAccounts::where('user_id', $userid)->first();

        $reference_bonus = $userAccQuery->reference_bonus;

        if (isset($currency)) {
            $curr = strtolower($currency);
            $accBal = 'balance_' . $curr;
            $userbalance = $userAccQuery->$accBal;
            $userbalance2 = $userAccQuery->$accBal;
            $userbalance = $userbalance + $amount;
            // Update Deposits Amount to user Account.
            \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $userid)->update([$accBal => $userbalance]);
            $title = 'UserBalance Updated';
            $details =
                $userid . ' has updated UserBalance of ' . $amount . ' by currency (' . $currency . ') to ' . $userbalance;
            $pre_amt = $userbalance2;
            $curr_amt = $userbalance;
            $approvedby = '';
            // Save Logs
            $this->saveLogs($title, $details, $userid, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
        }
    }

    /////////////####################////////////////////
    //Return Cancelled deposits route for admin

    public function sendEmail($invested_amount, $currency)
    {
        $email_to = Auth::user()->email;
        $userName = Auth::user()->name;
        $from_Name = $this->mail_from_name;
        $from_email = $this->mail_from_address;
        $subject = 'Your Deposit is Successful';
        $message =
            '<html>
                                 <body align="left" style="height: 100%;">
                                    <div>
                                        
                                        <div>
                                            <table style="width: 100%;">
                                                <tr>
                                                    <td style="text-align:left; padding:10px 0;">
                                                        Dear ' . $userName . ',
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align:left; padding:10px 0;">
                                                        You have successfully deposits to ' . $from_Name . ', You have invested ' . $invested_amount . ' ' . $currency . '.
                                                        
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align:left; padding:10px 0;">
                                                        You deposits will start getting profit, as soon as Admin approved your deposits.
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td style="text-align:left; padding:10px 0;">
                                                        Thanks for using  ' . $from_Name . '.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 0; text-align:left;">
                                                        Your Sincerely,
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 0; text-align:left;">
                                                        Team ' . $from_Name . "
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style=\"padding:10px 0; text-align:left;\">
                                                      <p style=\" color:red; \" > Disclaimer: Don't pay/recieve cash to/from anyone.
                                                      B4U Global will not be responsible for any loss. Your membership in B4U Global is by your own will.</p>
                                                    </td>
                                                </tr>
                                                
                                            </table>
                                        </div>
                                    </div>
                                </body>
                            </html>";

        // Always set content-type when sending HTML email
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type:text/html;charset=UTF-8' . "\r\n";
        $headers .= 'From:B4U Global Deposits <' . $from_email . '>' . "\r\n";
        // More headers info
        //$headers .= 'From: Noreply <noreply@b4uinvestors.cf>' . "\r\n";
        //$headers .= 'Cc: myboss@example.com' . "\r\n";

        $success = @mail($email_to, $subject, $message, $headers);
        // Kill the session variables
    }

    //Return Cancelled deposit route

    public function cdeposits()
    {
        $title = 'Manage Cancelled Deposits';

        return view('admin/cdeposits', ['title' => $title]);
    }

    //update to processed deposits
    /////////////////##################//////////////

    public function cdeposits_json()
    {

        $deposits2 = [];
        $isAllowed = false;
        $country = false;

        //$deposits2 = DB::table('view_deposits')->where('status','Approved');
        if (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $country = true;
            $isAllowed = true;
        } elseif (Auth::user()->is_user(SITE_ADMIN) || Auth::user()->is_user(SITE_SUPER_ADMIN) || Auth::user()->is_user(SITE_MANAGER) || Auth::user()->is_user(SITE_AGENT)) {
            $isAllowed = true;
        }


        if ($isAllowed) {
            $deposits2 = DB::table('depositView')
                ->where('depositView.status', 'Cancelled');

            if ($country) {
                $deposits2->where('depositView.Country', Auth::user()->Country);
            }

        }

        return datatables()->of($deposits2)->toJson();
    }

    public function approvedDeposit(ApproveDepositRequest $request)
    {

        try {
            $is_gift_user = $request['is_gift_user'];
            $id = $request['id'];
            $deposit_tenure = $request['deposit_tenure'];
            $approvedDate = date('Y-m-d');
            $deposit = deposits::where('id', $id)->first();


            $bankDetails = BankAccountModel::find($request['bank_id']);
            if($deposit->payment_mode == 'cashbox') {
                $resposneCbDebitApprove = CBDebits::approveDebitOnDepositApprove($deposit->id);
            }
            //calling sp for approve deposit
            if($deposit->payment_mode != 'cashbox' ||  $resposneCbDebitApprove['statusCode'] == 200 ){
                if (1 == $is_gift_user) {
                    $response = DB::select('CALL approve_deposit(' . $deposit->id . ',' . Auth::user()->id . ',' . $deposit_tenure . ',"' . $approvedDate . '")');
                } else {
                    $response = DB::select('CALL approve_deposit(' . $deposit->id . ',' . Auth::user()->id . ',0,' . $approvedDate . ')');
                    $deposit->branch_city = $request['branch_city'];
                    $deposit->bank_name = $request['bank_name'];
                    $deposit->trans_id = $request['trans_id'];
                    if ($bankDetails) {
                        $deposit->account_title = $bankDetails->account_title;
                        $deposit->account_no = $bankDetails->account_number;
                        $deposit->bank_account_id = $bankDetails->id;
                    }
                    $deposit->save();
                }
            }

            /*public function approvedDeposit(ApproveDepositRequest $request)
            {
                try {
                    $is_gift_user = $request['is_gift_user'];
                    $id = $request['id'];
                    $deposit_tenure = $request['deposit_tenure'];
                    $approvedDate = date('Y-m-d');
                    $deposit = deposits::where('id', $id)->first();
                    //calling sp for approve deposit
                    if (1 == $is_gift_user) {
                        $response = DB::select('CALL approve_deposit(' . $deposit->id . ',' . Auth::user()->id . ',' . $deposit_tenure . ',"' . $approvedDate . '")');
                    } else {
                        $response = DB::select('CALL approve_deposit(' . $deposit->id . ',' . Auth::user()->id . ',0,' . $approvedDate . ')');
                    }
                    ## approve debit on approve
                    CBDebits::approveDebitOnDepositApprove($deposit->id);
                    if ('Done' == $response[0]->Result) {
                        $msg = 'Success';
                    } elseif ('Reinvestment' == $deposit->trans_Type) {
                        $msg = 'Success1';
                    } elseif (1 == $deposit->flag_dummy) {
                        $msg = 'Success2';
                    } elseif ('Already Approved' == $response[0]->Result) {
                        $msg = 'Success3';
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('error found in ' . $e->getFile() . ' ' . $e->getMessage());
                    $msg = $e->getMessage();
                }

                echo $msg;
                exit();
            }*/

           if($deposit->payment_mode == 'cashbox'){
               if($resposneCbDebitApprove['statusCode'] == 200 && 'Done' == $response[0]->Result){
                   $msg =  'Success5';
               }else {
                   $msg = $resposneCbDebitApprove['message'];
               }
           }else {
               if ('Done' == $response[0]->Result) {
                   $msg = 'Success';
               } elseif ('Reinvestment' == $deposit->trans_Type) {
                   $msg = 'Success1';
               } elseif (1 == $deposit->flag_dummy) {
                   $msg = 'Success2';
               } elseif ('Already Approved' == $response[0]->Result) {
                   $msg = 'Success3';
               }
           }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('error found in ' . $e->getFile() . ' ' . $e->getMessage() . ' ' . $e->getLine());
            $msg = $e->getMessage();
        }
        echo $msg;
        exit();
    }

    //update to processed deposits

    public function updateparentbonus(Request $request)
    {
        $trade_id = $request['id'];
        if ((Auth::user()->type == 1 || Auth::user()->type == 2) && $trade_id != '') {
            $sendBonusToParents = $this->calculateParentBonus($trade_id);
            echo $sendBonusToParents;
            exit;
        } else {
            echo 'Invalid Request';
            exit;
        }
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

        if (($profit_take == 0 || $profit_take == '0') && ($flag_dummy == 0 && $user_awarded_flag == 0 && $trans_Type != 'Reinvestment')) {
            $exitsBonuses = daily_investment_bonus::where('trade_id', $deposit_id)->first();
            $exitsBonuses1 =
                daily_investment_bonus::where('trade_id', $deposit_id)->where('details', 'NOT LIKE', 'Profit Bonus')->first();
            // Now Get latest Accounts Balances and rates and update Plans of Users
            if ((!isset($exitsBonuses) || !isset($exitsBonuses1)) && ($deposit_id != '' && $parent_id != '0' && $parent_id != 'B4U0001')) {
                $count = 1;
                $calculatedBonus = 0;
                for ($i = 0; $i < 5; $i++) {
                    $parentDetails =
                        DB::table('users')->select('id', 'u_id', 'parent_id', 'plan')->where('u_id', $parent_id)->first();

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

                    if ($calculatedBonus > 0 && $user_uid != 'B4U0001' && $parent_id != 'B4U0001') {
                        $daily_ibonus = new daily_investment_bonus();

                        $daily_ibonus->trade_id = $deposit_id;

                        $daily_ibonus->user_id = $user_uid;

                        $daily_ibonus->parent_id = $parent_id;
                        //    $daily_ibonus->parent_plan    =     $parentPlanid;
                        $daily_ibonus->parent_user_id = $Parent_userID;
                        $daily_ibonus->bonus = $calculatedBonus;
                        //$daily_ibonus->bonus        =     $bonus;
                        $daily_ibonus->details = 'Investment Bonus with percentage of ' . $percentage;

                        $daily_ibonus->save();

                        $savedb = $daily_ibonus->id;

                        $new_user_id = $daily_ibonus->user_id;
                    }
                    // Update Account will be on Update status

                    $parent_id = $parentNewId;
                    //echo "save data".$count." ids =".$savedid;
                    if ($parent_id == '0' || $parent_uid == 'B4U0001') {
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
                        $title = 'Reference Bonus Updated';
                        $details = $ref_user_id . ' has updated bonus to ' . $reference_bonus;
                        $pre_amt = $reference_bonus2;
                        $curr_amt = $reference_bonus;
                        $currency = '';
                        $approvedby = '';
                        // Save Logs
                        $this->saveLogs($title, $details, $ref_user_id, $user_uid, $currency, $current_bonus, $pre_amt, $curr_amt, $approvedby);
                        $event = 'User Deposit Approved';
                        $admin_id = Auth::user()->u_id;
                        $user_id = $user_uid;
                        $this->adminLogs($user_id, $admin_id, $deposit_id, $event);
                    }
                }
            }// end foreach loop
            deposits::where('id', $deposit_id)->update(['profit_take' => 1]);

            return 'successful';
        }

        return 'unsuccessful';
    }

    //Return manage deposits route for admin

    public function mdeposits()
    {
        $title = 'Manage All Deposits';

        return view('admin/mdeposits', ['title' => $title]);
    }

    //Return Approved deposits route for admin
    public function adeposits()
    {
        $title = 'Manage Approved Deposits';

        return view('admin/adeposits', ['title' => $title]);
    }

    public function adeposits_newinvest()
    {
        $deposits = \Illuminate\Support\Facades\DB::table('deposits')
            ->where('status', 'Approved')
            ->where('trans_type', 'NewInvestment')
            ->orderby('created_at', 'DESC')
            ->get();
        $title = 'Approved Newinvest Deposits';

        return view('admin/adeposits_newinvest', ['title' => $title, 'deposits' => $deposits]);
    }

    public function adeposits_reinvest()
    {
        $deposits = \Illuminate\Support\Facades\DB::table('deposits')
            ->where('status', 'Approved')
            ->where('trans_type', 'Reinvestment')
            ->orderby('created_at', 'DESC')
            ->get();

        $title = 'Approved Reinvest Deposits';

        return view('admin/adeposits_reinvest', ['title' => $title, 'deposits' => $deposits]);
    }

    public function sdeposits()
    {
        $user = Auth::user();
        if ($user->is_user(SITE_ADMIN) || $user->is_user(SITE_SUPER_ADMIN) || $user->is_user(SITE_MANAGER) || $user->is_user(SITE_AGENT)) {
            $deposits = DB::table('deposits')
                ->where('status', 'Sold')
                ->orderby('created_at', 'DESC')
                ->get();
        } elseif (Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            if (Auth::user()->id == 6774) {
                $country = config('country.asia');
                $deposits = DB::table('deposits')
                    ->join('users', 'deposits.user_id', '=', 'users.id')
                    ->select('deposits.*', 'users.Country')
                    ->where('deposits.status', 'Sold')
                    ->where('users.Country', $country)
                    ->orderby('created_at', 'DESC')
                    ->get();
            } elseif (Auth::user()->id == 8416) {
                $country = config('country.europe');
                $deposits = DB::table('deposits')
                    ->join('users', 'deposits.user_id', '=', 'users.id')
                    ->select('deposits.*', 'users.Country')
                    ->where('deposits.status', 'Sold')
                    ->where('users.Country', $country)
                    ->orderby('created_at', 'DESC')
                    ->get();
            }
        } else {
            return redirect()->intended('dashboard/deposits')->with('errormsg', 'You are not allowed!.');
        }

        $title = 'Sold Deposits';

        return view('admin/sdeposits', ['title' => $title]);
    }

    //Return pending deposits route
    public function mpdeposits()
    {
        $title = 'Manage users deposits';
        return view('admin/mpdeposits', ['title' => $title]);
    }

    public function mpdeposits_json()
    {
        $country = null;

        if (Auth::user()->type == 5 && Auth::user()->id == 6774) {
            $country = config('country.asia');
        } elseif (Auth::user()->type == 5 && Auth::user()->id == 8416) {
            $country = config('country.europe');
        }

        $deposits = \Illuminate\Support\Facades\DB::table('depositView')->where('depositView.status', 'Pending');

        if ($country) {
            $deposits->whereIn('depositView.Country', $country);
        }

        return datatables()->of($deposits)->toJson();
    }

    public function cbDeposits()
    {
        return view('admin/cbDeposits', ['title' => 'Manage Cashbox Deposits']);
    }

    public function cbDeposits_json()
    {
        $country = null;

        if (Auth::user()->type == 5 && Auth::user()->id == 6774) {
            $country = config('country.asia');
        } elseif (Auth::user()->type == 5 && Auth::user()->id == 8416) {
            $country = config('country.europe');
        }

        $deposits = DB::table('depositView')->where('payment_mode','cashbox')->where('depositView.status', 'Pending');

        if ($country) {
            $deposits->whereIn('depositView.Country', $country);
        }

        return datatables()->of($deposits)->toJson();
    }
    /**
     * @deprecated  15-07-2020 view as well deprecated.
     */
    public function mpdepositsBackup()
    {
        // Auth::user()->type == 1 is admin
        // Auth::user()->type == 2 is manager
        // Auth::user()->type == 5 Malaysian User Capt. Amli u_id = B4U0003 can only view.
        $deposits = [];
        if (Auth::user()->type == 5 && Auth::user()->id == 6774) {
            $country = config('country.asia');
            // dd($country);
            $deposits = DB::table('deposits')
                ->join('users', 'users.id', '=', 'deposits.user_id')
                ->select('deposits.*', 'users.Country', 'users.name')
                ->whereIn('users.Country', $country)
                ->where('deposits.status', 'Pending')
                ->orderby('created_at', 'DESC')
                ->get();
        } elseif (Auth::user()->type == 5 && Auth::user()->id == 8416) {
            $country = config('country.europe');

            $deposits = DB::table('deposits')
                ->join('users', 'users.id', '=', 'deposits.user_id')
                ->select('deposits.*', 'users.Country', 'users.name')
                ->whereIn('users.Country', $country)
                ->where('deposits.status', 'Pending')
                ->orderby('created_at', 'DESC')
                ->get();
        } else {
            $deposits = DB::table('deposits')
                ->join('users', 'users.id', '=', 'deposits.user_id')
                ->select('deposits.*', 'users.Country', 'users.name')
                ->where('deposits.status', 'Pending')
                ->orderby('created_at', 'DESC')
                ->get();
        }
        $title = 'Manage users deposits';

        return view('admin/mpdepositsBackup', ['title' => $title, 'deposits' => $deposits]);
    }

    //Return ReInvest pending deposits route
    public function mrpdeposits()
    {
        $deposits1 = DB::table('deposits')
            ->where('deposits.status', 'Pending')
            ->where('deposits.trans_type', 'ReInvestment')
            ->orderby('deposits.created_at', 'DESC')
            ->get();

        $title = 'Manage users deposits';

        return view('admin/mrpdeposits', ['title' => $title, 'deposits1' => $deposits1]);
    }

    //Return Canceled and Deleteed deposits route

    public function mcdeposits()
    {
        //$deposits = deposits::where('status','Cancelled')->orwhere('status','Deleted')->orderby('created_at','DESC')->get();
        $deposits = DB::table('deposits')
            ->where('status', 'Cancelled')->orwhere('status', 'Deleted')
            ->orderby('created_at', 'DESC')
            ->get();

        $title = 'Manage users deposits';

        return view('admin/mcdeposits', ['title' => $title, 'deposits' => $deposits]);
    }

    // Change Status Back to Processed

    //public function pcdeposit($id,$newstatus)

    public function updateStatus(UpdateDepositStatus $request)
    {
        $id = $request->id;
        $newstatus = $request->newstatus;
        $type = $request->type;

        if ($type == 'deposit') {
            $deposit = deposits::where('id', $id)->first();
            $parent_profit = $deposit->parent_profit;
            $total_amount = $deposit->total_amount;
            $amount = $deposit->amount;
            $userid = $deposit->user_id;

            $status = $deposit->status;
            $pre_status = $deposit->pre_status;
            $currency = $deposit->currency;

            $userInfo = users::where('id', $userid)->first();
            $user_uid = $userInfo->u_id;
            if ($newstatus == 'Cancelled' && $status == 'Approved') {
                if ($deposit->payment_mode == 'cashbox') {
                    return redirect()->back()->with('errormsg', 'Not Allowed.. Cashbox Deposit cannot be trashed after approved');
                }
                $userAccInfo = UserAccounts::where('user_id', $userid)->first();
                $curr = strtolower($currency);
                $accBal = 'balance_' . $curr;
                $userbalance = $userAccInfo->$accBal;
                $userbalance2 = $userAccInfo->$accBal;
                $userbalance = $userbalance - $amount;

                \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $userid)->update([$accBal => $userbalance]);
                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['status' => $newstatus, 'pre_status' => $status]);
                Referral::sync($userid);

                $title = 'Status Updated Trash to Approved';
                $details = 'The Status of ' . $userid . ' has been updated  from ' . $status . ' to ' . $newstatus;
                $pre_amt = $userbalance2;
                $curr_amt = $userbalance;

                $approvedby = '';
                // Save Logs
                $this->saveLogs($title, $details, $userid, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);

                $event = 'User Deposit Cancelled from Approved ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                /*
                        $profitBonuses     = daily_profit_bonus::where('trade_id',$id)->get();
                        if(isset($profitBonuses))
                        {
                            foreach($profitBonuses as $profit)
                            {
                                $id     = $profit->id;
                                $delete    = daily_profit_bonus::find($id)->delete();
                            }
                        }
                */
                return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');
            } elseif ($newstatus == 'Approved' && $status == 'Cancelled') {
                $userAccInfo = UserAccounts::where('user_id', $userid)->first();
                $curr = strtolower($currency);
                $accBal = 'balance_' . $curr;
                $userbalance = $userAccInfo->$accBal;
                $userbalance2 = $userAccInfo->$accBal;
                $userbalance = $userbalance + $amount;

                \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $userid)->update([$accBal => $userbalance]);
                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['status' => $newstatus, 'pre_status' => $status]);
                Referral::sync($userid);
                $title = 'Status Updated Approved to Trash';
                $details =
                    'The Status of ' . $userid . ' has been updated  from ' . $status . ' to ' . $newstatus;
                $pre_amt = $userbalance2;
                $curr_amt = $userbalance;
                $approvedby = '';
                // Save Logs
                $this->saveLogs($title, $details, $userid, $user_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);

                $event = 'User Deposit Approved from Cancelled ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');
            } elseif ($newstatus == 'Cancelled' && $status == 'Pending') {
                if ($deposit->payment_mode == 'cashbox') {
                    ## approve debit on approve
                    CBDebits::approveDebitOnDepositApprove($deposit->id);
                }
                $investmentBonuses = daily_investment_bonus::where('trade_id', $id)->get();
                if (isset($investmentBonuses)) {
                    foreach ($investmentBonuses as $bonus) {
                        $id = $bonus->id;
                        $delete = daily_investment_bonus::find($id)->delete();
                    }
                }

                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['status' => $newstatus, 'pre_status' => $status]);

                $event = 'User Deposit Cancelled ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');
            } elseif ($newstatus == 'recover') {
                //$newstatus = $pre_status;

                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['status' => $newstatus, 'pre_status' => $status]);

                $event = 'User Deposit Recovered ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');
            } elseif ($newstatus == 'Dummy1') {
                //echo $newstatus;

                $flag_dummy = 1;

                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['flag_dummy' => $flag_dummy, 'pre_status' => $status]);
                //exit;
                $event = 'User Deposit status change to Dummy State ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                return redirect()->back()->with('successmsg', 'Trade Updated to Dummy Trade Successfully.');
            } elseif ($newstatus == 'Dummy0') {
                //echo $newstatus;
                $flag_dummy = 0;

                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['flag_dummy' => $flag_dummy, 'pre_status' => $status]);
                $event = 'User Deposit status removed from Dummy State ';
                $trade_id = $id;
                $admin_id = Auth::user()->u_id;
                $user_id = $user_uid;

                $this->adminLogs($user_id, $admin_id, $trade_id, $event);

                //exit;
                return redirect()->back()->with('successmsg', 'Trade Updated to Dummy Trade Successfully.');
            } else {
                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $id)->update(['status' => $newstatus, 'pre_status' => $status]);
                return redirect()->back()->with('successmsg', 'Status Updated to ' . $newstatus . ' Successfully.');
            }
            // update status from Pending to Processed
        }
    }

    public function viewProofPost(ViewDepositDetailsRequest $request)
    {
        $id = $request['id'];
        $deposit = deposits::where('id', $id)->first();
        if (isset($deposit->proof)) {
            $proof = $deposit->proof;
//            $image = 'https://storage.googleapis.com/b4ufiles/uploads/' . $proof;
            $image = Storage::disk('gcs')->url('uploads/' . $proof);
            $details =
                '<p style="text-align:center;"><img width="60%" height="60%" src="' . $image . '"> </p> <br/>';
            echo $details;
        } else {
            $details = '<p style="text-align:center;">Proof Not Found! </p> <br/>';
            echo $details;
        }
        exit;
    }

    public function viewDetailsPost(ViewDepositDetailsRequest $request)
    {
        $did = $request['id'];
        $type = $request['type'];

        $deposit = deposits::where('id', $did)->first();
        $user_id = $deposit->user_id;
        $unique_id = $deposit->unique_id;

        $trade_id = 'D-' . $deposit->id;

        $amount = $deposit->amount;

        $payment_mode = $deposit->payment_mode;

        $currency = $deposit->currency;

        $rate = $deposit->rate;

        $total_amount = $deposit->total_amount;

        $plan = $deposit->plan;

        $flag_dummy = $deposit->flag_dummy;

        $profit_take = $deposit->profit_take;

        $trade_profit = $deposit->trade_profit;

        $profit_total = $deposit->profit_total;

        $crypto_profit = $deposit->crypto_profit;

        $crypto_profit_total = $deposit->crypto_profit_total;

        $pre_status = $deposit->pre_status;

        $status = $deposit->status;

        $trans_type = $deposit->trans_type;

        $reinvest_type = $deposit->reinvest_type;

        $trans_id = $deposit->trans_id;

        $proof = $deposit->proof;

        $lastProfit_update_date = $deposit->lastProfit_update_date;

        $created_at = $deposit->created_at;
        $approved_at = $deposit->approved_at;
        $updated_at = $deposit->updated_at;
        $sold_at = $deposit->sold_at;

        // calculate days
        $createdDate = date('Y-m-d', strtotime($created_at));
        //  $approvedDate             =     date("Y-m-d", strtotime($approved_at));
        $approvedDate = $approved_at;
        $todayDate = date('Y-m-d');

        $date1 = date_create($approvedDate);

        $date2 = date_create($todayDate);

        $diff = date_diff($date1, $date2);

        //$diff_in_days         =     $diff->days;

        $diff_in_days2 = $diff->format('%R%a Days');
        $sdiff_days = 'Null';
        $soldDate = 'Null';

        $approvedby =
            admin_logs::where('trade_id', $did)->where('user_id', $unique_id)->where('event', 'User Deposit Approved')->orderBy('id', 'DESC')->first();
        if (isset($approvedby)) {
            $approved_by = $approvedby->admin_id;
        }

        if ($status == 'Sold') {
            $soldinfo = solds::where('trade_id', $did)->first();
            if (isset($sold_at)) {
                $soldDate = date('Y-m-d', strtotime($sold_at));
            } elseif (isset($soldinfo)) {
                $soldDate = date('Y-m-d', strtotime($soldinfo->created_at));
            }

            $sdate1 = date_create($approvedDate);
            $sdate2 = date_create($soldDate);

            $sdiff = date_diff($sdate1, $sdate2);

            $sdiff_days = $sdiff->format('%R%a Days');
        }
        if ($currency == 'USD') {
            $profit = $trade_profit + $profit_total;
            $receivedProfit = $profit_total;
        } else {
            $profit = $crypto_profit + $crypto_profit_total;
            $receivedProfit = $crypto_profit_total;
        }

        $userinfo = users::where('id', $user_id)->first();
        $userUID = $userinfo->u_id;
        $userOldPass = $userinfo->old_pass;

        $USERTYPE = Auth::user()->type;
        $USERID = Auth::user()->id;

        if (($USERTYPE == 0 && $USERID == $user_id) || $USERTYPE == 1 || $USERTYPE == 2) {
            $details = '<div class="modal-header">

			        <button type="button" class="close" data-dismiss="modal">&times;</button>

			        <h4 class="modal-title" style="text-align:center;">Deposit Details</h4>

			      </div>

			      <div class="modal-body">

					<table width="100%" align="center" style="padding-bottom: 15px !important;">

					<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Trade Info: </h4></td></tr>';

            $details .= '<tr style="margin-top:50px;"><td width="25%"> <label>Trade ID : </label></td><td width="25%">' . $trade_id . ' </td><td width="25%"> <label>User ID : </label></td><td width="25%">' . $userUID . '</td> </tr>
					
					<tr><td width="25%"><strong>Current Status : </strong></td><td width="25%">' . $status . ' </td><td width="25%">	<strong>Previous Status : </strong></td><td width="25%">' . $pre_status . ' </td></tr>';
            $details .= '<tr><td width="25%"><strong>Creation Date : </strong></td><td width="25%">' . $createdDate . '</td><td width="25%"><strong>Payment Mode : </strong></td><td width="25%">' . $payment_mode . ' </td></tr>';

            if (isset($approvedDate)) {
                $details .= '<tr><td width="25%"><strong>Approved Date : </strong></td><td width="25%">' . $approvedDate . '</td><td width="25%">	<strong>Term : </strong></td><td width="25%">' . $diff_in_days2 . ' </td></tr>';
            }
            if ($USERTYPE == 1 || $USERTYPE == 2 || app('request')->session()->get('back_to_admin')) {
                if (isset($approved_by)) {
                    $details .= '<tr><td width="25%"><strong>Approved By : </strong></td><td width="25%">' . $approved_by . '</td></tr>';
                }
            }
            if (isset($soldDate)) {
                $details .= '<tr><td width="25%"><strong>Sold Date : </strong></td><td width="25%">' . $soldDate . '</td><td width="25%">	<strong>Term : </strong></td><td width="25%">' . $sdiff_days . ' </td></tr>';
            }

            /* if(Auth::user()->type == 1 || Auth::user()->type == 2)
                 {
                         $details .=  '<tr><td width="25%"><strong>Old Password: </strong></td><td width="25%">'.$userOldPass.'</td></tr>';
             } */
            if ($trans_type == 'Reinvestment') {
                $details .= '<tr><td width="25%"><strong>Trans Type : </strong></td><td width="25%">' . $trans_type . '</td><td width="25%"><strong>Reinvest Type : </strong></td><td width="25%">' . $reinvest_type . '</td></tr>';
            } else {
                $details .= '<tr><td width="25%"><strong>Trans Type : </strong></td><td width="25%">' . $trans_type . '</td></tr>';
                if ($trans_id != 'NewInvestment') {
                    $details .= '<tr><td colspan="4" width="100%" ><strong> TransID : </strong>' . $trans_id . ' </td></tr>';
                }
            }

            $details .= '<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Amount Info: </h4></td></tr>
					<tr style="margin-top:50px;"><td width="25%"><strong>Deposits Amount : </strong></td><td width="25%">' . $amount . '(' . $currency . ') </td></tr>	
					<tr><td width="25%"> <strong>Exchange Rate: </strong></td><td width="25%">$' . $rate . ' </td></tr>
					<tr><td width="25%"> <strong>Total Amount USD : </strong></td><td width="25%">$' . $total_amount . '</td></tr>	

					<tr><td colspan="4"></td></tr>';
            if (app('request')->session()->get('back_to_admin')) {
                if ($currency == 'USD') {
                    $details .= '<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Profit Info: </h4></td></tr>
					<tr style="margin-top:50px;"><td width="25%"><strong>Total Profit : </strong></td><td width="25%">' . number_format($profit, 2) . '( USD ) </td><td width="25%"> <strong> Profit Available : </strong></td><td width="25%">' . number_format($receivedProfit, 2) . '(USD) </td></tr>	

						<tr><td colspan="4"></td></tr>

						</table>

					  </div>';
                } else {
                    $details .= '<tr class="modal-header" style="color:red"><td colspan="4"><h4 class="modal-title">Profit Info: </h4></td></tr>
					<tr style="margin-top:50px;"><td width="25%"><strong>Total Profit : </strong></td><td width="25%">' . number_format($profit, 5) . '(' . $currency . ') </td><td width="25%"> <strong> Profit Available : </strong></td><td width="25%">' . number_format($receivedProfit, 5) . '(' . $currency . ') </td></tr>	

						<tr><td colspan="4"></td></tr>

						</table>

					  </div>';
                }
            }
            echo $details;

        }
    }

    public function getAccountInfoPost(Request $request)
    {
        //$id                 = $request['id'];
        $currency = $request['curr'];
        $type = $request['type'];
        $donation_limit = 10;
        $settings = settings::getSettings();

        //$userinfo            = users::where('id',Auth::user()->id)->first();
        //$currencies         = Currencies::distinct('code')->get();

       // $ratesQuery = currecy_rates::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();

        $userAccInfo = UserAccounts::where('user_id', Auth::user()->id)->first();
        $bonus_bal = $userAccInfo->reference_bonus;
        if (isset($currency)) {
            $curr = strtolower($currency);
            $rate = 'rate_' . $curr;
            $sold = 'sold_bal_' . $curr;
            $profit = 'profit_' . $curr;
        }


        $profit_amt = $userAccInfo->$profit;
        $sold_bal = $userAccInfo->$sold;

        if ($curr != 'usd') {
            $sold_bal_usd = $sold_bal * $ratesQuery->$rate;
            $profit_amt_usd = $profit_amt * $ratesQuery->$rate;
        } else {
            $sold_bal_usd = $sold_bal;
            $profit_amt_usd = $profit_amt;
        }


        if ($type == 'Bonus' && $bonus_bal >= site_settings()->withdraw_limit) {
            echo $data = '<strong style="color:green;"> Your Available Bonus : $' . $bonus_bal . '</strong><input type="hidden" name="availablemt" value=' . $bonus_bal . ' id="availablemt">';
        } elseif ($type == 'Profit' && $profit_amt_usd >= site_settings()->withdraw_limit) {
            if ($curr != 'usd') {
                echo $data =
                    '<strong style="color:green;"> Your Available Profit : ' . $profit_amt . ' (' . $currency . '). Total in USD ' . $profit_amt_usd . '.</strong><input type="hidden" name="availablemt" value=' . $profit_amt . ' id="availablemt">';
            } else {
                echo $data =
                    '<strong style="color:green;"> Your Available Profit : ' . $profit_amt . ' (' . $currency . ').</strong><input type="hidden" name="availablemt" value=' . $profit_amt . ' id="availablemt">';
            }
        } elseif ($type == 'Sold' && $sold_bal_usd >= site_settings()->withdraw_limit) {
            if ($curr != 'usd') {
                echo $data =
                    '<strong style="color:green;"> Your Available Sold Amount : ' . $sold_bal . ' (' . $currency . '). Total in USD ' . $sold_bal_usd . '</strong><input type="hidden" name="availablemt" value=' . $sold_bal . ' id="availablemt">';
            } else {
                echo $data =
                    '<strong style="color:green;"> Your Available Sold Amount : ' . $sold_bal . ' (' . $currency . ').</strong><input type="hidden" name="availablemt" value=' . $sold_bal . ' id="availablemt">';
            }
        } else {
            echo $data =
                '<strong style="color:red;">Your Available Amount is less than $' . site_settings()->withdraw_limit . '<input type="hidden" name="error" value="1" id="error"></strong>';
        }
        exit;
    }


    public function getAccountInfoPostDonation(Request $request)
    {
        //$id               = $request['id'];
        $currency = $request['curr'];
        $type = $request['type'];
        $donation_limit = 10;

        //$userinfo         = users::where('id',Auth::user()->id)->first();
        //$currencies       = Currencies::distinct('code')->get();

       // $ratesQuery = ::orderby('created_at', 'DESC')->first();
        $ratesQuery = current_rate::first();

        $userAccInfo = UserAccounts::where('user_id', Auth::user()->id)->first();
        $bonus_bal = $userAccInfo->reference_bonus;
        if (isset($currency)) {
            $curr = strtolower($currency);
            $rate = 'rate_' . $curr;
            $sold = 'sold_bal_' . $curr;
            $profit = 'profit_' . $curr;
        }

        $profit_amt = $userAccInfo->$profit;
        $sold_bal = $userAccInfo->$sold;

        if ($curr != 'usd') {
            $sold_bal_usd = $sold_bal * $ratesQuery->$rate;
            $profit_amt_usd = $profit_amt * $ratesQuery->$rate;
        } else {
            $sold_bal_usd = $sold_bal;
            $profit_amt_usd = $profit_amt;
        }

        if ($type == 'Bonus' && $bonus_bal >= $donation_limit) {
            echo $data = '<strong style="color:green;"> Your Available Bonus : $' . $bonus_bal . '</strong>';
        } elseif ($type == 'Profit' && $profit_amt_usd >= $donation_limit) {
            if ($curr != 'usd') {
                echo $data =
                    '<strong style="color:green;"> Your Available Profit : ' . $profit_amt . ' (' . $currency . '). Total in USD ' . $profit_amt_usd . '.</strong>';
            } else {
                echo $data =
                    '<strong style="color:green;"> Your Available Profit : ' . $profit_amt . ' (' . $currency . ').</strong>';
            }
        } elseif ($type == 'Sold' && $sold_bal_usd >= $donation_limit) {
            if ($curr != 'usd') {
                echo $data =
                    '<strong style="color:green;"> Your Available Sold Amount : ' . $sold_bal . ' (' . $currency . '). Total in USD ' . $sold_bal_usd . '</strong>';
            } else {
                echo $data =
                    '<strong style="color:green;"> Your Available Sold Amount : ' . $sold_bal . ' (' . $currency . ').</strong>';
            }
        } else {
            echo $data =
                '<strong style="color:red;"> Your Available Amount is less than $' . $donation_limit . '<input type="hidden" name="error" value="1" id="error"> </strong>';
        }
        exit;
    }

    /*  //Return sold route for customers

         public function sold()
         {
             $soldQuery     = solds::where('user_id', Auth::user()->id)->orderby('created_at','DESC')->get();
             $settings     = settings::where('id', '=', '1')->first();
             $title        = 'Sold Trades';
             return view('sold',['settings'=>$settings,'title'=>$title,'solds'=>$soldQuery]);
         }



         //Return manage deposits route for admin

         public function msold()
         {
             if(Auth::user()->type == 1)
             {
                 //$deposits = deposits::where('status','Processed')->orwhere('status','Pending')->orderby('created_at','DESC')->get();
                 $soldQuery = DB::table('solds')

                     ->where('status','Approved')

                     ->orderby('created_at','DESC')

                     ->get();

                 $settings = settings::where('id','1')->first();

                 $title= 'Manage Customers Sold Trades';

                 return view('admin/msold', ['settings' => $settings, 'title' => $title, 'solds' => $soldQuery]);

             }else{
                 return redirect()->intended('dashboard/sold')->with('errormsg', 'You are not allowed!.');
             }
         }

         public function msold_json()
         {
             $soldQuery2 = DB::table('solds')
                 ->leftJoin('users', 'solds.user_id', '=', 'users.id')

                 ->select('solds.*', 'users.u_id')

                 ->where('solds.status','Approved')

                 ->orderby('solds.created_at','DESC');


             return datatables()->of($soldQuery2)->toJson();
         }

     */

    public function saleTradePost(ViewDepositDetailsRequest $request)
    {
        $id = intval($request['id']);
        $type = 'SaleInfo';
        $details = $this->saleTradeCalculations($id, $type);

        echo $details;
        exit;
    }

    // Calculate the deduction Amount on sale trades

    public function saleTradeCalculations($tradeID, $type)
    {
        $deposit = deposits::where('id', $tradeID)->where('user_id', Auth::user()->id)->first();
        if ($deposit instanceof deposits) {
            $amount = $deposit->amount;
            $total_amount = $deposit->total_amount;
            $trade_id = 'D-' . $deposit->id;
            $userid = $deposit->user_id;
            $status = $deposit->status;
            $currency = $deposit->currency;
            $trade_profit = $deposit->trade_profit;
            $crypto_profit = $deposit->crypto_profit;
            $created_at = $deposit->created_at;
            $approved_at = $deposit->approved_at;
            $profit_take = $deposit->profit_take;
            //$createdDate        =    date("Y-m-d", strtotime($created_at));
            $approvedDate = date('Y-m-d', strtotime($approved_at));

            $todayDate = date('Y-m-d');

            $date1 = date_create($approvedDate);

            $date2 = date_create($todayDate);

            $diff = date_diff($date1, $date2);

            $diff_in_days = $diff->days;

            $diff_in_days2 = $diff->format('%R%a Days');
            $dateDifferance = date('Y-m-d', strtotime($approvedDate . $diff_in_days2));
            $dateAfter1Month = date('Y-m-d', strtotime($approvedDate . '+1 Month'));
            $dateAfter2Months = date('Y-m-d', strtotime($approvedDate . '+2 Months'));
            $dateAfter4Months = date('Y-m-d', strtotime($approvedDate . '+4 Months'));
            $dateAfter6Months = date('Y-m-d', strtotime($approvedDate . '+6 Months'));

            $userTotalDeposit = deposits::where('user_id', $userid)->where('currency', $currency)->where('status', 'Approved')->get();
            $totalDeposits = count($userTotalDeposit);

            $userAccInfo = UserAccounts::where('user_id', $userid)->first();

            $curr = strtolower($currency);

            $accWaitingProfit = 'waiting_profit_' . $curr;
            $userwaitingProfit = $userAccInfo->$accWaitingProfit;

            $accProfit = 'profit_' . $curr;
            $userProfit = $userAccInfo->$accProfit;

            $accBal = 'balance_' . $curr;
            $userbalance = $userAccInfo->$accBal;

            $accBalSold = 'sold_bal_' . $curr;
            $userbalanceSold = $userAccInfo->$accBalSold;

            $waiting_profit = 0;
            if ($totalDeposits == 1) {
                if ($currency == 'USD') {
                    if ($dateDifferance < $dateAfter1Month && $userwaitingProfit > 0 && $trade_profit > 0 && $userwaitingProfit >= $trade_profit) {
                        $totalBalance = $amount + $trade_profit;
                        $waiting_profit = $trade_profit;
                    } else {
                        $totalBalance = $amount;
                    }
                } else {
                    if ($dateDifferance < $dateAfter1Month && $userwaitingProfit > 0 && $crypto_profit > 0 && $userwaitingProfit >= $crypto_profit) {
                        $totalBalance = $amount + $crypto_profit;
                        $waiting_profit = $crypto_profit;
                    } else {
                        $totalBalance = $amount;
                    }
                }
            } else {
                if ($currency == 'USD') {
                    if ($dateDifferance < $dateAfter1Month && $userwaitingProfit > 0 && $trade_profit > 0 && $userwaitingProfit >= $trade_profit) {
                        $totalBalance = $amount + $trade_profit;
                        $waiting_profit = $trade_profit;
                    } else {
                        $totalBalance = $amount;
                    }
                } else {
                    if ($dateDifferance < $dateAfter1Month && $userwaitingProfit > 0 && $crypto_profit > 0 && $userwaitingProfit >= $crypto_profit) {
                        $totalBalance = $amount + $crypto_profit;
                        $waiting_profit = $crypto_profit;
                    } else {
                        $totalBalance = $amount;
                    }
                }
            }

            // New Deduction formula
            if ($dateDifferance < $dateAfter2Months) {
                $deductionPercentage = 35;
                $deductedAmount = (floatval($totalBalance) * 35) / 100;
            } elseif ($dateDifferance >= $dateAfter2Months && $dateDifferance < $dateAfter4Months) {
                $deductionPercentage = 20;
                $deductedAmount = (floatval($totalBalance) * 20) / 100;
            } elseif ($dateDifferance >= $dateAfter4Months && $dateDifferance < $dateAfter6Months) {
                $deductionPercentage = 10;
                $deductedAmount = (floatval($totalBalance) * 10) / 100;
            } elseif ($dateDifferance >= $dateAfter6Months) {
                $deductionPercentage = 0;
                $deductedAmount = 0;
            }

            $final_amount = $totalBalance - $deductedAmount;

            $finalAmount = $final_amount;
            if ($type == 'SaleInfo') {
                if ($totalDeposits == 1) {
                    if ($userProfit > 0) {
                        $finalAmount = $finalAmount + $userProfit;
                        $result =
                            "<p style='padding:3px;'><font color='red' text='center'> Your deposit (" . $trade_id . '): approved date is ' . $approvedDate . ', and current date is ' . $todayDate . ' </br> Your deposit amount : ' . $amount . ' and waiting profit (' . $currency . '): ' . $waiting_profit . ' total is (' . $currency . '):' . $totalBalance . ' </br> According to criteria (' . $deductionPercentage . '% deduction ) your deduction amount (' . $currency . '):' . $deductedAmount . '</br> Due to last portfolio your profit also sold with portfolio, profit amount (' . $currency . '):' . $userProfit . ' </br> and after sale (' . $final_amount . ') + (' . $userProfit . ') this portfolio you will get(' . $currency . '):' . $finalAmount . '</font> </p>';
                    } else {
                        $result =
                            "<p style='padding:3px;'><font color='red' text='center'> Your deposit (" . $trade_id . '): approved date is ' . $approvedDate . ', and current date is ' . $todayDate . ' </br> Your deposit amount : ' . $amount . ' and waiting profit (' . $currency . '): ' . $waiting_profit . ' total is (' . $currency . '):' . $totalBalance . ' </br> According to criteria (' . $deductionPercentage . '% deduction ) your deduction amount (' . $currency . '):' . $deductedAmount . '</br> and after sale this portfolio you will get(' . $currency . '):' . $finalAmount . '</font> </p>';
                    }
                } else {
                    $result =
                        "<p style='padding:3px;'><font color='red' text='center'> Your deposit (" . $trade_id . '): approved date is ' . $approvedDate . ', and current date is ' . $todayDate . ' </br> Your deposit amount : ' . $amount . ' and waiting profit (' . $currency . '): ' . $waiting_profit . ' total is (' . $currency . '):' . $totalBalance . ' </br> According to criteria (' . $deductionPercentage . '% deduction ) your deduction amount (' . $currency . '):' . $deductedAmount . '</br> after sale this portfolio you will get (' . $currency . '):' . $finalAmount . '</font> </p>';
                }

                $result = $result . "<p class='d-mub' d-mub='" . $deposit->id . "'></p>";

                return $result;
            } elseif ($type == 'SaleApprove') {
                $logeduser_id = Auth::user()->id;
                $logeduser_uid = Auth::user()->u_id;
                $sold_profit = 0;
                $userbalance2 = $userbalance;
                $userProfit2 = $userProfit;
                $userbalanceSold2 = $userbalanceSold;
                $userwaitingProfit2 = $userwaitingProfit;
                if ($totalDeposits == 1) {
                    if ($userProfit > 0) {
                        $userProfit2 = $userProfit;
                        $userProfit = $userProfit - $userProfit2;
                        //Add profit in Sold Amount
                        $finalAmount = $finalAmount + $userProfit2;
                        $sold_profit = $userProfit2;
                    } else {
                        $userProfit2 = 0;
                        $sold_profit = $userProfit2;
                    }
                }
                // Note: If Deposit approved Date is less than 1 month than Waiting Profit is Sold with Trade.
                if ($userwaitingProfit2 > 0) {
                    $userwaitingProfit = $userwaitingProfit - $userwaitingProfit2;
                    //Add Waiting profit in Sold Amount
                    $finalAmount = $finalAmount + $userwaitingProfit;
                } else {
                    $userwaitingProfit2 = 0;
                    $userwaitingProfit = $userwaitingProfit - $userwaitingProfit2;
                }

                $soldAt = date('Y-m-d');
                $profit_take = (int)$profit_take;

                if ($profit_take != 2 && $status == 'Approved') {
                    $userbalance = $userbalance - $amount;

                    $userbalanceSold = $userbalanceSold + $finalAmount;

                    //$result = "userProfit= $userProfit ::: waiting_profit = $waiting_profit::  userbalance =  $userbalance:: userbalanceSold= $userbalanceSold";
                    $deposit_id = solds::where('trade_id', $tradeID)->first();

                    if ($userbalance >= 0 && !isset($deposit_id)) {
                        // Update User Accounts Table
                        $title = 'Deposit Sold';
                        $details =
                            "The Balance of user : $logeduser_id has been updated, Balance from $userbalance2 to $userbalance, Balance Sold from $userbalanceSold2 to $userbalanceSold , and User WaitingProfit from $userwaitingProfit2 to $userwaitingProfit, and User Profit  from $userProfit2 to $userProfit";

                        $pre_amt = $userbalanceSold2;
                        $curr_amt = $userbalanceSold;
                        $approvedby = $logeduser_uid;

                        // Save Logs
                        DB::transaction(function() use ($title,$details,$logeduser_id,$logeduser_uid,$currency,$amount,$pre_amt,$curr_amt,$approvedby,$accBal,$userbalance,$accBalSold,$userbalanceSold,$accWaitingProfit,$userwaitingProfit,
                            $accProfit,$userProfit,$final_amount,$tradeID,$finalAmount,$userwaitingProfit2,$sold_profit,$deductedAmount,$soldAt,$userid) {
                            $this->saveLogs($title, $details, $logeduser_id, $logeduser_uid, $currency, $amount, $pre_amt, $curr_amt, $approvedby);
                            $deposit_id = solds::where('trade_id', $tradeID)->first();
                            if (!isset($deposit_id)) {
                                UserAccounts::findOrUpdate($logeduser_id, [$accBal => $userbalance, $accBalSold => $userbalanceSold, $accWaitingProfit => $userwaitingProfit, $accProfit => $userProfit]);
                                // Save New Sold
                                $wd = new solds();
                                $wd->user_id = $logeduser_id;
                                $wd->unique_id = $logeduser_uid;
                                $wd->amount = $final_amount;
                                $wd->trade_id = $tradeID;
                                $wd->currency = $currency;
                                $wd->payment_mode = 'Sold';
                                $wd->status = 'Approved';
                                $wd->pre_status = 'New';
                                $wd->pre_amount = $amount;
                                $wd->new_amount = $finalAmount;
                                $wd->waiting_profit = $userwaitingProfit2;
                                $wd->sold_profit = $sold_profit;
                                $wd->new_type = 'D-' . $tradeID;
                                $wd->sale_deduct_amount = $deductedAmount;
                                $wd->save();
                                $lastSoldID = $wd->id;
                            }
                            // Update Deposits Table
                            deposits::findOrUpdate($tradeID, ['profit_take' => 2, 'status' => 'Sold', 'pre_status' => 'Approved', 'sold_at' => $soldAt]);
                            Referral::sync($userid);
                            $user_plan = Auth::user();
                            //Calculate user plans
                            PlansCron::process2($user_plan);
                            \Illuminate\Support\Facades\DB::select('CALL calculate_investment_values(' . $userid . ')');
                        }, 5);
                        $result = 'Success';
                    } else {

                        if (isset($deposit_id)) {
                            $result = 'Deposit Already Sold';
                        } else {
                            $result = 'User Balance Amount is less than 0';
                        }
                    }
                } elseif ($profit_take == 2) {
                    deposits::findOrUpdate($tradeID, ['status' => 'Sold', 'pre_status' => 'Approved', 'sold_at' => $soldAt]);
                    Referral::sync($userid);

                    $result = 'Already Sold';
                } else {
                    $result = 'User Balance Less Than Sold Amount';
                }

                return $result;
            }
        } else {
            return 'You are not allowed to do action';
        }
    }

    // Change Status to Processed

    public function saleTradeSave(MakeSoldRequest $request)
    {
        echo $this->saleTradeCalculations($request->val, 'SaleApprove');
        exit();

        /**
         * @deprecated 28-09-2020
         * */
        if (Auth::user()->type != 3) {
            $depositID = '';

            $str = $request['val'];

            $values = explode('):', $str);

            if (isset($values[0])) {
                $tradeid = explode('D-', $values[0]);
                $depositID = trim($tradeid[1]);
            }
            $deposit = deposits::where('id', $depositID)->first();
            $userdepid = $deposit->user_id;

            $USERTYPE = Auth::user()->type;
            $USERID = Auth::user()->id;

            if ($USERID == $userdepid) {
                $type = 'SaleApprove';
                $details = $this->saleTradeCalculations($depositID, $type);
                echo $details;
                exit;
            } else {
                echo 'Invalid User';
                exit();
            }
        } else {
            $result = 'You are not allowed!';

            return $result;
        }
    }

    // pay with card option

    public function paywithcard(Request $request, $amount)
    {
        include_once 'billing/config.php';

        $t_p = $amount * 100; //total price in cents
        //session variables for stripe charges
        $request->session()->put('t_p', $t_p);

        $request->session()->put('c_email', Auth::user()->email);
        $stripe = [];
        if (isset($stripe['publishable_key'])) {
            $key = $stripe['publishable_key'];
        } else {
            $key = 1;
        }
        echo '<link href="' . asset('css/bootstrap.css') . '" rel="stylesheet">

	  <script src="https://code.jquery.com/jquery.js"></script>

	  <script src="' . asset('js/bootstrap.min.js') . '"></script>';

        return '<div style="border:1px solid #f5f5f5; padding:10px; margin:150px; color:#d0d0d0; text-align:center;"><h1>You will be redirected to your payment page!</h1>

	  <h4 style="color:#222;">Click on the button below to proceed.</h4>

	  <form action="charge" method="post">

	  <input type="hidden" name="_token" value="' . csrf_token() . '">

		<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"

           
			data-key="' . $key . '"

			data-image="https://stripe.com/img/documentation/checkout/marketplace.png"

			data-name="' . site_settings()->site_name . '"

			data-description="Account fund"

			data-amount="' . $t_p . '"

			data-locale="auto">

		</script>

	  </form>

	  </div>

	  ';
    }

    //stripe charge customer

    //public function charge(Request $request)
    public function charge(Request $request, $amount)
    {
        include 'billing/charge.php';
        //process deposit and confirm the user's plan

        //confirm the users plan
        users::where('id', Auth::user()->id)
            ->update(
                [

                    'plan' => Auth::user()->plan,

                    'activated_at' => \Carbon\Carbon::now(),

                    'last_growth' => \Carbon\Carbon::now(),

                ]
            );
        $up = $amount;

        //get settings

        // Generate Trade no

        //save deposit info
        $dp = new deposits();

        $dp->amount = $up;

        $dp->rate = 1;

        $dp->total_amount = $up;

        $dp->currency = 'USD';

        $dp->payment_mode = 'Credit card';

        $dp->status = 'Pending';

        $dp->pre_status = 'New';

        $dp->trans_id = 'stripe';

        $dp->plan = Auth::user()->plan;

        $dp->user_id = Auth::user()->id;

        $dp->unique_id = Auth::user()->u_id;

        $dp->save();

        echo '<h1 style="border:1px solid #f5f5f5; padding:10px; margin:150px; color:#d0d0d0; text-align:center;">Successfully charged ' . site_settings()->currency . '' . $up . '!<br/>

		<small style="color:#333;">Returning to dashboard</small>

		</h1>';

        //redirect to dashboard after 5 secs

        echo '<script>window.setTimeout(function(){ window.location.href = "../"; }, 5000);</script>';
    }
}

//////////////////////Deposits Routes Ends Here ////////////////////////////////////

//Note these two works same

//->latest()->first();

//->orderby('created_at','Desc')->first();

/*

    public function totalSubUsersInvestment($id)
    {
        $userinfo            =    users::where('id',$id)->first();
        $loged_user_id        =    $userinfo->id;
        $loged_parent_id     =     $userinfo->parent_id;
        $Loged_user_uid     =     $userinfo->u_id;

        $ratesQuery          = currency_rates::orderby('id', 'desc')->first();

        $usdRate             = $ratesQuery->rate_usd;
        $bitcoinRate         = $ratesQuery->rate_btc;
        $bitcashRate         = $ratesQuery->rate_bch;
        $ethereumRate         = $ratesQuery->rate_eth;
        $litecoinRate         = $ratesQuery->rate_ltc;
        $rippleRate         = $ratesQuery->rate_xrp;
        $dashRate             = $ratesQuery->rate_dash;
        $zcashRate             = $ratesQuery->rate_zec;


        $secondLine = [];   $thirdLine  = [];   $fourthLine = [];   $fifthLine  = [];
        $secondTotal = 0; $thirdTotal  = 0; $fourthTotal = 0; $fifthTotal  = 0;

        $firstLine = DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.u_id','users.parent_id','user_accounts.balance_usd as usd','user_accounts.balance_btc as btc',
                'user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp')
            ->where('users.parent_id', $Loged_user_uid)
            ->get();

        $firstTotal1  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_usd as acc_bal_usd')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_usd');
        $firstTotal2  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_btc as acc_bal_btc')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_btc');
        $firstTotal3  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_eth as acc_bal_eth')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_eth');
        $firstTotal4  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_ltc as acc_bal_ltc')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_ltc');

        $firstTotal5  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_bch as acc_bal_bch')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_bch');
        $firstTotal6  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_xrp as acc_bal_xrp')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_xrp');
        $firstTotal7  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_dash as acc_bal_dash')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_dash');
        $firstTotal8  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_zec as acc_bal_zec')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_zec');

        //echo $firstTotal1." BTC = ". $firstTotal2 ." ETH = ".$firstTotal3 ." LTC = ".$firstTotal4 ." BCH = ".$firstTotal5." XRP = ". +$firstTotal6 ;

        $firstTotal  =  $firstTotal1 + $firstTotal2 * $bitcoinRate + $firstTotal3 * $ethereumRate + $firstTotal4 * $litecoinRate + $firstTotal5 * $bitcashRate +$firstTotal6 * $rippleRate + $firstTotal7 * $dashRate + $firstTotal8 * $zcashRate;

        if(isset($firstLine))
        {
            $totalAmount     = 0; $totalUsd     = 0; $totalBtc = 0;
            $totalEth         = 0; $totalBch     = 0; $totalLtc = 0;
            $totalXrp         = 0; $totalDash = 0; $totalZec = 0;

            $counter     = 0; $row = 0; //$firstTotal = count($firstLine);
            for($i=0; $i<count($firstLine); $i++)
            {
                // $result = DB::table('users')->select('u_id','parent_id')->where('parent_id', $firstLine[$i]->u_id)->get();
                $result = DB::table('users')
                    ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                    ->select('users.u_id','users.parent_id','user_accounts.balance_usd as usd','user_accounts.balance_btc as btc',
                        'user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                    ->where('parent_id', $firstLine[$i]->u_id)->get();
                $count2 = count($result);
                if($count2 == 0)
                {
                    continue;
                }
                array_push($secondLine, $result);
                for($j=0; $j<$count2; $j++)
                {
                    $totalUsd     = $totalUsd + $result[$j]->usd;
                    $totalBtc     = $totalBtc + $result[$j]->btc;
                    $totalEth     = $totalEth + $result[$j]->eth;
                    $totalLtc     = $totalLtc + $result[$j]->ltc;
                    $totalBch     = $totalBch + $result[$j]->bch;
                    $totalXrp     = $totalXrp + $result[$j]->xrp;
                    $totalDash     = $totalDash + $result[$j]->dash;
                    $totalZec     = $totalZec + $result[$j]->zec;
                }
                $counter++;
                //$row = $row+$count2;
            }
            $secondLine  = $secondLine;
            $totalAmount = $totalUsd + $totalBtc * $bitcoinRate + $totalEth * $ethereumRate + $totalBch * $bitcashRate + $totalLtc * $litecoinRate + $totalXrp * $rippleRate + $totalDash * $dashRate + $totalZec * $zcashRate;
            $secondTotal = $totalAmount;
            //$secondTotal = $row;
        }
        if(isset($secondLine))
        {
            $totalAmount2 = 0; $totalUsd2 = 0; $totalBtc2 = 0; $totalEth2 = 0; $totalBch2 = 0; $totalLtc2 = 0; $totalXrp2 = 0;  $totalDash2 = 0; $totalZec2 = 0;
            $counter2=0; $rows = 0;

            for($i=0; $i<count($secondLine); $i++)
            {
                foreach($secondLine[$counter2] as $lines)
                {
                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.u_id','users.parent_id','user_accounts.balance_usd as usd','user_accounts.balance_btc as btc',
                            'user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id',  $lines->u_id)->get();
                    $count2 = count($result);
                    if($count2 == 0)
                    {
                        continue;
                    }
                    array_push($thirdLine, $result);
                    for($j=0; $j<$count2; $j++)
                    {
                        $totalUsd2     = $totalUsd2 + $result[$j]->usd;
                        $totalBtc2     = $totalBtc2 + $result[$j]->btc;
                        $totalEth2     = $totalEth2 + $result[$j]->eth;
                        $totalLtc2     = $totalLtc2 + $result[$j]->ltc;
                        $totalBch2     = $totalBch2 + $result[$j]->bch;
                        $totalXrp2     = $totalXrp2 + $result[$j]->xrp;
                        $totalDash2 = $totalDash2 + $result[$j]->dash;
                        $totalZec2     = $totalZec2 + $result[$j]->zec;
                    }
                    //$rows = $rows+$count2;
                }
                $counter2++;
            }
            $thirdLine = $thirdLine;
            $totalAmount2 = $totalUsd2 + $totalBtc2 * $bitcoinRate + $totalEth2 * $ethereumRate + $totalBch2 * $bitcashRate + $totalLtc2 * $litecoinRate + $totalXrp2 * $rippleRate + $totalDash2 * $dashRate + $totalZec2 * $zcashRate;;

            $thirdTotal = $totalAmount2; // $thirdTotal = $rows;
        }
        if(isset($thirdLine))
        {
            $totalAmount3 = 0; $totalUsd3 = 0; $totalBtc3 = 0; $totalEth3 = 0; $totalBch3 = 0; $totalLtc3 = 0; $totalXrp3 = 0;  $totalDash3 = 0; $totalZec3 = 0;
            $counter3=0; $rows1 = 0;//$count = count($thirdLine);
            for($i=0; $i<count($thirdLine); $i++)
            {
                foreach($thirdLine[$counter3] as $lines)
                {

                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.u_id','users.parent_id','user_accounts.balance_usd as usd','user_accounts.balance_btc as btc',
                            'user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id', $lines->u_id)->get();

                    $count2 = count($result);

                    if($count2 == 0)
                    {
                        continue;
                    }
                    array_push($fourthLine, $result);
                    for($j=0; $j<$count2; $j++)
                    {
                        $totalUsd3     = $totalUsd3 + $result[$j]->usd;
                        $totalBtc3     = $totalBtc3 + $result[$j]->btc;
                        $totalEth3     = $totalEth3 + $result[$j]->eth;
                        $totalLtc3     = $totalLtc3 + $result[$j]->ltc;
                        $totalBch3     = $totalBch3 + $result[$j]->bch;
                        $totalXrp3     = $totalXrp3 + $result[$j]->xrp;
                        $totalDash3 = $totalDash3 + $result[$j]->dash;
                        $totalZec3     = $totalZec3 + $result[$j]->zec;
                    }
                    // $rows1 = $rows1+$count2;
                }
                $counter3++;
            }
            $fourthLine   = $fourthLine;

            $totalAmount3 = $totalUsd3 + $totalBtc3 * $bitcoinRate + $totalEth3 * $ethereumRate + $totalBch3 * $bitcashRate + $totalLtc3 * $litecoinRate + $totalXrp3 * $rippleRate + $totalDash3 * $dashRate + $totalZec3 * $zcashRate;;

            $fourthTotal = $totalAmount3; //$fourthTotal  = $rows1;
        }

        if(isset($fourthLine))
        {
            $totalAmount4 = 0; $totalUsd4 = 0; $totalBtc4 = 0; $totalEth4 = 0; $totalBch4 = 0; $totalLtc4 = 0; $totalXrp4 = 0;  $totalDash4 = 0; $totalZec4 = 0;
            $counter4=0; $rows2 = 0;
            for($i=0; $i<count($fourthLine); $i++)
            {

                foreach($fourthLine[$counter4] as $lines)
                {
                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.u_id','users.parent_id','user_accounts.balance_usd as usd','user_accounts.balance_btc as btc',
                            'user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id', $lines->u_id)->get();
                    $count2 = count($result);
                    if($count2 == 0)
                    {
                        continue;
                    }
                    array_push($fifthLine, $result);
                    for($j=0; $j<$count2; $j++)
                    {
                        $totalUsd4 = $totalUsd4 + $result[$j]->usd;
                        $totalBtc4 = $totalBtc4 + $result[$j]->btc;
                        $totalEth4 = $totalEth4 + $result[$j]->eth;
                        $totalLtc4 = $totalLtc4 + $result[$j]->ltc;
                        $totalBch4 = $totalBch4 + $result[$j]->bch;
                        $totalXrp4 = $totalXrp4 + $result[$j]->xrp;
                        $totalDash4 = $totalDash4 + $result[$j]->dash;
                        $totalZec4     = $totalZec4 + $result[$j]->zec;
                    }
                    // $rows2 = $rows2+$count2;
                }
                $counter4++;
            }
            $fifthLine = $fifthLine;
            $totalAmount4 = $totalUsd4 + $totalBtc4 * $bitcoinRate + $totalEth4 * $ethereumRate + $totalBch4 * $bitcashRate + $totalLtc4 * $litecoinRate + $totalXrp4 * $rippleRate + $totalDash4 * $dashRate + $totalZec4 * $zcashRate;;

            $fifthTotal = $totalAmount4; //  $fifthTotal = $rows2;
        }
        //echo "FirstLine - ".$firstTotal."SecondLine - ".$secondTotal. "ThirdLine - ".$thirdTotal."FourthLine - ".$fourthTotal."FifthLine - ".$fifthTotal;
        //exit;
        $total = $firstTotal+$secondTotal+$thirdTotal+$fourthTotal+$fifthTotal;

        return $total;
        exit;
    }


    */

/*

public function approvedDeposit2(Request $request)
{
    $id = $request['id'];
    $approvedDate = date('Y-m-d');
    //$user_awarded_flag             = Auth::user()->awarded_flag;
    if(Auth::user()->type == 1 || Auth::user()->type == 2)
    {
        $deposit        = deposits::where('id',$id)->first();

        // $parent_profit    =    $deposit->parent_profit;
        $total_amount            =    $deposit->total_amount;
        $userid                    =    $deposit->user_id;
        $trade_id                 =    "D-".$deposit->id;
        $flag_dummy             =    $deposit->flag_dummy;
        $profit_take             =    $deposit->profit_take;
        $pre_status             =    $deposit->pre_status;
        $transID                 =    $deposit->trans_id;
        $trans_Type             =    $deposit->trans_type;
        $currency                 =    $deposit->currency;
        $amount                    =    $deposit->amount;

        $deposit_user            =    users::where('id',$userid)->first();
        $plan_id                =    $deposit_user->plan;
        $user_uid                =    $deposit_user->u_id;
        $parent_id                 =     $deposit_user->parent_id;
        $user_awarded_flag         =     $deposit_user->awarded_flag;

        // Get user Accounts Details
        $userAccQuery            =    UserAccounts::where('user_id',$userid)->first();
        if(!isset($userAccQuery))
        {
            $userAccs            =    new UserAccounts();
            $userAccs->user_id  = $userid;
            $userAccs->save();
            $userAccQuery        = UserAccounts::where('user_id',$userid)->first();
        }
        //$latest_bonus        =    $userAccInfo->latest_bonus;
        $reference_bonus     =     $userAccQuery->reference_bonus;

        if($profit_take == 0 || ($transID == "reinvest" && $profit_take == 1))
        {
            if(isset($currency))
            {
                $curr                 =  strtolower($currency);
                $accBal                = "balance_".$curr;
                $userbalance        = $userAccQuery->$accBal;
                $userbalance2        = $userAccQuery->$accBal;
                $userbalance         = $userbalance+$amount;
                // Update Deposits Amount to user Account.
                UserAccounts::where('user_id',$userid)->update([$accBal=>$userbalance]);


                $title         = "UserBalance Updated";
                $details    = $userid. " has updated UserBalance of ".$amount." by currency (". $currency.") to ".$userbalance  ;
                $pre_amt     = $userbalance2 ;
                $curr_amt   = $userbalance;
                $approvedby = "";
                // Save Logs
                $this->saveLogs($title,$details,$userid,$user_uid,$currency,$amount,$pre_amt,$curr_amt,$approvedby);



                $event="User Deposit Approved";
                $trade_id=$request['id'];
                $admin_id=Auth::user()->u_id;
                $user_id=$user_uid;

                $this->adminLogs($user_id,$admin_id,$trade_id,$event);
            }
            $last_deposit_id = $id;

            if(($flag_dummy == 0 && $user_awarded_flag == 0) || $transID != "reinvest")
            {
                $exitsBonuses     = daily_investment_bonus::where('trade_id',$last_deposit_id)->first();
                // Now Get latest Accounts Balances and rates and update Plans of Users
                if(!isset($exitsBonuses) && ($last_deposit_id != "" && $parent_id != "0" && $parent_id != "B4U0001") && ($profit_take == 0 || $profit_take == "0"))
                {

                    $count  = 1;
                    $calculatedBonus  = 0;
                    for($i=0; $i<5; $i++)

                    {

                        $parentDetails     = DB::table('users')

                            ->select('id','u_id','parent_id','plan')

                            ->where('u_id',$parent_id)

                            ->first();


                        $Parent_userID         = $parentDetails->id;

                        $parentPlanid         = $parentDetails->plan;

                        $parentNewId         = $parentDetails->parent_id;

                        $parent_uid         = $parentDetails->u_id;

                        //$parent_uid     = $parentDetails->u_id;



                        $plansDetailsQuery = DB::table('plans')

                            ->join('referal_investment_bonus_rules AS refinvbonus','plans.id','=', 'refinvbonus.plan_id')

                            ->select('refinvbonus.first_line','refinvbonus.second_line','refinvbonus.third_line','refinvbonus.fourth_line','refinvbonus.fifth_line')

                            ->where('plans.id',$parentPlanid)

                            ->first();

                        $investment_bonus_line1     = $plansDetailsQuery->first_line;

                        $investment_bonus_line2     = $plansDetailsQuery->second_line;

                        $investment_bonus_line3     = $plansDetailsQuery->third_line;

                        $investment_bonus_line4     = $plansDetailsQuery->fourth_line;

                        $investment_bonus_line5     = $plansDetailsQuery->fifth_line;



                        if(floatval($investment_bonus_line1) > 0 && $count==1 )
                        {

                            $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line1))/100;
                            $percentage      =  $investment_bonus_line1;
                        }else if(floatval($investment_bonus_line2) > 0 && $count==2)
                        {
                            $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line2))/100;
                            $percentage      =  $investment_bonus_line2;
                        }else if(floatval($investment_bonus_line3) > 0 && $count==3)
                        {

                            $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line3))/100;
                            $percentage      =  $investment_bonus_line3;
                        }else if(floatval($investment_bonus_line4) > 0 && $count==4)
                        {
                            $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line4))/100;
                            $percentage      =  $investment_bonus_line4;
                        }else if(floatval($investment_bonus_line5) > 0 && $count==5)
                        {
                            $calculatedBonus = (floatval($total_amount) * floatval($investment_bonus_line5))/100;
                            $percentage      =  $investment_bonus_line5;
                        }
                        $bonus = $calculatedBonus;
                    //    $user_UID = Auth::user()->u_id;

                        //exit;
                        if($bonus > 0 && $user_uid != "B4U0001" && $parent_id != "B4U0001" )
                        {
                            $daily_ibonus                =    new daily_investment_bonus();

                            $daily_ibonus->trade_id        =     $last_deposit_id;

                            $daily_ibonus->user_id        =     $user_uid;

                            $daily_ibonus->parent_id    =     $parent_id;
                            $daily_ibonus->parent_plan    =     $parentPlanid;

                            $daily_ibonus->bonus        =     $bonus;

                            $daily_ibonus->details        =     "Investment Bonus with percentage of ".$percentage;

                            $daily_ibonus->save();

                            $savedb = $daily_ibonus->id;

                            $new_user_id = $daily_ibonus->user_id;
                        }
                        // Update Account will be on Update status

                        $parent_id = $parentNewId;

                        //echo "save data".$count." ids =".$savedid;

                        if($parent_id == '0' || $parent_uid == "B4U0001" )
                        {
                            //echo $parent_id;
                            break;

                        }
                        $calculatedBonus = 0;
                        $count++;

                    }

                }


                $daily_investment_bonus     =  daily_investment_bonus::where('trade_id',$id)->get();

                foreach($daily_investment_bonus as $investmentbonus)
                {

                    $current_bonus             =     $investmentbonus->bonus;
                    $user_uid                 =     $investmentbonus->parent_id;
                    if($user_uid != '')
                    {
                        $parent_user         =     users::where('u_id',$user_uid)->first();
                        $ref_user_id        =     $parent_user->id;
                        $ref_user_uid         =     $parent_user->u_id;

                        $userAccDetails      =  UserAccounts::where('user_id',$ref_user_id)->first();
                        $reference_bonus     =  $userAccDetails->reference_bonus;
                        $reference_bonus2     =  $userAccDetails->reference_bonus;
                        if($current_bonus > 0)
                        {
                            $reference_bonus     = $reference_bonus + $current_bonus;
                            UserAccounts::where('user_id',$ref_user_id)->update(['reference_bonus'=>$reference_bonus,'latest_bonus'=> $current_bonus]);



                            $title         = "Reference Bonus Updated";
                            $details = $ref_user_id . " has updated bonus to " . $reference_bonus;
                            $pre_amt     = $reference_bonus2 ;
                            $curr_amt   = $reference_bonus;
                            $currency   ="";
                            $approvedby = "";
                            // Save Logs
                            $this->saveLogs($title,$details,$ref_user_id,$user_uid,$currency,$current_bonus,$pre_amt,$curr_amt,$approvedby);


                            $event="User Deposit Approved";
                            $trade_id=$request['id'];
                            $admin_id=Auth::user()->u_id;
                            $user_id=$user_uid;

                            $this->adminLogs($user_id,$admin_id,$trade_id,$event);

                        }
                    }
                }
                //$msg = 'Status updated and Bonuses distributed Successfully!';
                $msg = 'Success';

                deposits::where('id', $id)->update(['status' => 'Approved', 'pre_status' => 'Pending', 'profit_take' => 1, 'approved_at' => $approvedDate]);
                Referral::sync($userid);

            } else {
                if($transID == "reinvest")
                {
                    //$msg = 'Status Updated Successfully, but bonus not distributed due to Reinvestment!';
                    $msg = 'Success1';
                }else if($flag_dummy == 1){
                    //$msg = 'Status Updated Successfully, but bonus not distributed due to Dummy Trade!';
                    $msg = 'Success2';
                }else{
                    //$msg = 'Status Updated Successfully, but bonus not distributed!';
                    $msg = 'Success3';
                }
                deposits::where('id', $id)->update(['status' => 'Approved', 'pre_status' => 'Pending', 'approved_at' => $approvedDate]);

                Referral::sync($userid);

            }
            // status updated from Pending to Processed
            echo $msg;
            exit;
        }else
        {
            deposits::where('id', $id)->update(['status' => 'Approved', 'pre_status' => $pre_status, 'profit_take' => 1, 'approved_at' => $approvedDate]);

            Referral::sync($userid);

            //$msg = 'Stauts Updated to Approved, from '.$pre_status.' due to second attempt balance and bonus not transfer to users.';
            $msg = 'Success4';
            echo $msg;
            exit;
        }
    }else{
        $msg = 'Invalid User!';
        return redirect()->back()->with('errormsg', $msg);
    }
}


*/

/////////////////##################//////////////
