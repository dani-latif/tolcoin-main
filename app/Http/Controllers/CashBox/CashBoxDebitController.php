<?php

namespace App\Http\Controllers\CashBox;

use App\CBAccounts;
use App\CBCredits;
use App\CBDebits;
use App\currency_rates;
use App\deposits;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashBox\CashBoxAccountIdUserBasedRequest;
use App\Http\Requests\CashBox\CashBoxDepositRequest;
use App\Http\Requests\CashBox\CashBoxTopUpRequest;
use App\Http\Requests\CashBox\GetCashBoxDebitRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashBoxDebitController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param GetCashBoxDebitRequest $request
     * @return array
     * @throws \Exception
     */
    public function index(CashBoxAccountIdUserBasedRequest $request)
    {
        try {
            return datatables()->of(CBDebits::where('cb_account_id',$request->ac))->toJson();
        } catch (\Exception $exception) {
            Log::error('getCashBoxCreditsRecord', [
                'errorMessage' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @Purpose: Create new deposit from Cash Box.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function store(CashBoxDepositRequest $request)
    {
        DB::beginTransaction();
        try {


            if (env('APP_DEBUG', false) === false && deposits::where('user_id', \Auth::user()->id)->where('created_at', '>', \Carbon\Carbon::now()->subMinutes(5))->count()) {
                return redirect()->intended('dashboard/deposits')->with('errormsg', 'You can make only 1 deposit every 5 mints. please wait 5 mins to make new deposit');
            }

            $invested_amount = $request['cb_amount'];
            $payment_mode = 'cashbox';
            $currency = $request['cb_account'];
            $deposit_mode = 'new';
            $logeduser_id = Auth::user()->id;
            $logeduser_uid = Auth::user()->u_id;
            $image = null;
            $trade_profit = 0;
            $currencyRate = '';

            // Total Amount in USD
            $ratesQuery = currency_rates::orderby('created_at', 'DESC')->first();
            if (isset($currency) && isset($ratesQuery)) {
                $curr = strtolower($currency);
                $rateVal = 'rate_' . $curr;
                $currencyRate = $ratesQuery->$rateVal;
            }
            $fee_deduct = explode(',', site_settings()->deposit_fee);
            $fee = 0;
            //deduct fee from the deposit if depost amount less then equal to 700 usd then 10 usd else 30 usd
            $total_amount = floatval($invested_amount) * floatval($currencyRate);


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

            if ($total_amount >= site_settings()->deposit_limit) {
                // trade save
                $dp = new deposits();
                $dp->amount = $invested_amount;
                $dp->payment_mode = $payment_mode;
                $dp->currency = $currency;
                $dp->rate = $currencyRate;
                $dp->total_amount = $total_amount;
                $dp->plan = Auth::user()->plan;
                $dp->user_id = $logeduser_id;
                $dp->unique_id = $logeduser_uid;
                $dp->pre_status = 'New';
                $dp->flag_dummy = 0;
                $dp->profit_take = 0;
                $dp->status = 'Pending';
                $dp->trade_profit = $trade_profit;
                if (isset($image)) {
                    $dp->proof = $image;
                }
                if (isset($trans_id)) {
                    $dp->trans_id = $trans_id;
                }
                $dp->trans_type = 'NewInvestment';
                //fee detucted
                $dp->fee_deducted = $fee;
                $dp->push();
            } else {
                $msg = 'Deposits not successful! Your Deposit amount is insufficient, minimum deposit limit is $' . site_settings()->deposit_limit . '.';
                return redirect()->intended('dashboard/deposits')->with('errormsg', $msg);
            }

            ### CashBox Calculation ###
            $cashBoxBalance = CBAccounts::getCashBoxCurrentBalanceByCurrencyCode($currency);
            if ($invested_amount <= $cashBoxBalance) {
                try {

                    DB::select("CALL `createCashBoxDebit`('{$currency}', '{$request['cb_amount']}', '{$logeduser_id}','deposit', 'newdeposit','pending', 'Create Deposit in B4U Global #{$dp->id}', NULL , NULL, {$dp->id}, NULL)");
                    ## Logs
                    Log::info('DepositFromCashbox', [
                        'request' => $request->all(),
                    ]);
                } catch (\Exception $exception) {
                    DB::rollBack();
                    Log::info('DepositFromCashboxError', [
                        'request' => $request->all(),
                        'error' => $exception->getMessage(),
                    ]);
                    return redirect()->intended('dashboard/deposits')->with('errormsg', 'System is under maintenance and Deposit from Cashbox is failed');
                }

            } else {
                return redirect()->intended('dashboard/deposits')->with('errormsg', "You don't have enough balance in cash box " . $request->currency . ' account, so you are not able to make deposit . ');
            }

            $title = 'new CashBox Investment';
            $details = $logeduser_uid . ' has created a deposit from CashBox(' . $currency . ') of ' . $invested_amount;
            $pre_amt = 0;
            $curr_amt = 0;
            $approvedby = '';
            // Save Logs
            $this->saveLogs($title, $details, $logeduser_id, $logeduser_uid, $currency, $invested_amount, $pre_amt, $curr_amt, $approvedby);
            DB::commit();
            return redirect()->intended('dashboard/deposits')->with('message', 'Deposit Successfully created from CashBox!!');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Cashbox Deposit', [$exception->getMessage()]);
            return back()->with('System is under maintenance, will get back to you shortly.');
        }
    }

    /**
     * @purpose Top up from CashBox Account
     * @param CashBoxTopUpRequest $cashBoxTopUpRequest
     */
    public function topdown(CashBoxTopUpRequest $cashBoxTopUpRequest)
    {
        DB::beginTransaction();
        try {

            /** @var User $loggedInUser */
            $loggedInUser = Auth::user();

            ## secure check if we have enough balance.
            if ($cashBoxTopUpRequest['amount'] <= CBAccounts::getCashBoxCurrentBalanceByCurrencyCode($cashBoxTopUpRequest->cb_account)) {
                DB::select("CALL `createCashBoxDebit`('{$cashBoxTopUpRequest->cb_account}', '{$cashBoxTopUpRequest['amount']}', '{$loggedInUser->id}','balance', 'topdown','inprocessing', 'Top request from Cashbox', NULL , NULL, NULL, 1)");
                DB::commit();
                return back()->with('successmsg', "You have successfully top {$cashBoxTopUpRequest->amount}  {$cashBoxTopUpRequest->cb_account}");
            } else {
                return back()->with('errormsg', "You don't have enough balance in CashBox Account");
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('TopUpRequestRejected', [
                'request' => $cashBoxTopUpRequest->all(),
                'error' => $exception->getMessage(),
            ]);
            return back()->with('errormsg', 'Top Request is failed, System is under maintenance, we will up shortly');
        }
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
