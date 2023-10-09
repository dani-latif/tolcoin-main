<?php

namespace App;

use App\Model\Deposit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CBAccounts extends Model
{
    /**
     * @var string
     */
     static $tableName =  'cb_accounts';
    protected $table = 'cb_accounts';
    const RoundNo = 6;

    static $tableNameCurrenciesColumnRef = 'cb_accounts.currencies_id';

    public static function getCashBoxLockedBalanceByCurrencyCode($currencyCode,int $userId = null)
    {
        $Currencies = Currencies::where('code', $currencyCode)->first();
        if ($Currencies instanceof Currencies) {
            return CBAccounts::where('currencies_id', $Currencies->id)->where('user_id', $userId)->first()->locked_balance;
        } else {
            return 0;
        }
    }

    public static function balanceCalculation(int $cbAccountId)
    {
        $balanceCalculation = DB::select(DB::raw("SELECT id as cb_account_id,current_balance,locked_balance, COALESCE((SELECT sum(amount + fee) as total from cb_debits where cb_debits.cb_account_id = cb_accounts.id and cb_debits.status != 'approved' and cb_debits.status != 'failed' and cb_debits.status != 'deposited' and cb_debits.status != 'cancelled'),0) as expected_locked,
(COALESCE((SELECT sum(amount + fee) as total from cb_credits where cb_credits.cb_account_id = cb_accounts.id and cb_credits.status = 'approved'),0) - COALESCE((SELECT sum(amount + fee) as total from cb_debits where cb_debits.cb_account_id = cb_accounts.id and cb_debits.status != 'failed'  and cb_debits.status != 'cancelled'),0) ) as expected_current
from cb_accounts WHERE cb_accounts.id ='" . $cbAccountId . "'"));
        return $balanceCalculation[0];
    }

    public function user()
    {
        return $this->belongsTo(users::class);
    }

    public function currencies()
    {
        return $this->belongsTo(Currencies::class, 'currencies_id')->first();
    }

    /**
     * @param int $currencyId
     * @param User $userId
     * @return CBAccounts|string
     * @purpose Make Cash Box account of user.
     */
    public static function makeCBAccount(int $currencyId, int $userId)
    {
        try {

            ## get Currency
            $Currency = Currencies::find($currencyId);
            $User = User::find($userId);

            if ($Currency instanceof Currencies && $User instanceof User) {
                $cbAccount = new CBAccounts();
                $cbAccount->name = $Currency->code . ' Account';
                $cbAccount->currencies_id = $Currency->id;
                $cbAccount->user_id = $User->id;
                $cbAccount->current_balance = floatval(0);
                $cbAccount->locked_balance = floatval(0);
                $cbAccount->save();
                return $cbAccount;
            } else {
                return 'Invalid Details';
            }

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

    }

    /**
     * @param int $currencyId
     * @param int $userId
     * @return mixed
     * @Purpose Get CashBox account on Base of Currency and User
     */
    public static function getCBAccountByCurrencyId(int $currencyId, int $userId)
    {
        return CBAccounts::where('currencies_id', $currencyId)->where('user_id', $userId)->first();
    }

    /**
     * @param int $currencyId
     * @param int $userId
     * @return mixed
     * @Purpose Get CashBox account on Base of Currency and User and If CashBox account of that user not found then system will create a new Cash box account.
     */
    public static function getOrCreateIfCBAccountByCurrencyId(int $currencyId, int $userId)
    {
        $CBAccount = self::getCBAccountByCurrencyId($currencyId, $userId);
        if ($CBAccount instanceof CBAccounts) {
            return $CBAccount;
        } else {
            return self::makeCBAccount($currencyId, $userId);
        }
    }


    /**
     * @Purpose Get Current of Specific Currency
     * @param $currencyId
     * @return mixed
     */
    public static function getCashBoxCurrentBalanceByCurrencyId($currencyId)
    {
        return CBAccounts::where('currencies_id', $currencyId)->where('user_id', Auth::user()->id)->first()->current_balance;
    }

    /**
     * @Purpose Get Current of Specific Currency
     * @param $currencyCode
     * @return mixed
     */
    public static function getCashBoxCurrentBalanceByCurrencyCode($currencyCode)
    {
        $Currencies = Currencies::where('code', $currencyCode)->first();
        if ($Currencies instanceof Currencies) {
            return CBAccounts::where('currencies_id', $Currencies->id)->where('user_id', Auth::user()->id)->first()->current_balance;
        } else {
            return 0;
        }

    }
    public static function approveCbTransferBySender(int $cbDebitId)
    {
        try {
            $cbDebit = CBDebits::find($cbDebitId);
            if ($cbDebit instanceof CBDebits) {
                $response = CBDebits::approveDebit($cbDebit);
                $cbCredit = CBCredits::where('cb_debit_id', $cbDebit->id)->first();

                if ($response && $cbCredit instanceof CBCredits) {
                    $cbCreditResponse = CBCredits::approveCreditFundTransfer($cbCredit);
                    if ($cbCreditResponse) {
                        return true;
                    }
                }else{
                    return false;
                }
            }
        }catch(\Exception $exception){
            Log::error('error while doing Approve Cb transfer', [
                'errorMessage' => $exception->getMessage(),
                'errorLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
            return $exception->getMessage();
        }
    }

    public static function getCbAccountReport(int $cbAccId,bool $pdf = false){
        $cbUserAccount = CBAccounts::find($cbAccId);
        $user = User::find($cbUserAccount->user_id);
        $cash_box_accounts = $user->cashBoxAccounts()->get();
        $balanceCalculation = self::balanceCalculation($cbAccId);
        $cbStatement = DB::select(DB::raw("SELECT id,amount+fee AS CreditAmount, '0' AS DebitAmount, currencies_id, type, status , created_at, 'Credit' AS TYPE FROM cb_credits WHERE 	cb_account_id ='" . $cbAccId . "'UNION 
        SELECT id,'0', amount+fee, currencies_id, type, status , created_at, 'Debit' FROM cb_debits WHERE 	cb_account_id ='" . $cbAccId . "' ORDER BY created_at ASC"));
        $balanceCredit = [];
        $balanceDebit = [];
        $amount = 0;
        foreach ($cbStatement as $cb){
            if($cb->TYPE == 'Credit'){
                if($cb->status != 'failed') {
                    $amount = $amount + $cb->CreditAmount;
                }
                //      $balance['C'.$cb->id] = $amount;
                $balanceCredit[$cb->id] = $amount;
            }
            if($cb->TYPE == 'Debit'){
                if($cb->status != 'failed') {
                    $amount = $amount - $cb->DebitAmount;
                }
                //      $balance['D'.$cb->id] = $amount;
                $balanceDebit[$cb->id] = $amount;
            }
        }
        $currency = Currencies::find($cbUserAccount->currencies_id);
        $cbDebitsTypes = CBDebits::select('type',DB::raw('SUM(amount+fee) as cbamount'))->where('cb_account_id',$cbAccId)->groupBy('type')->get();
        $cbCreditsTypes = CBCredits::select('type',DB::raw('SUM(amount+fee) as cbamount'))->where('cb_account_id',$cbAccId)->groupBy('type')->get();
        $cbDeposits = Deposit::where('user_id',$user->id)->where('payment_mode','LIKE','cashbox')->where('currency','LIKE',$currency->code)->get();
        $cbWithdrawals = withdrawals::where('user',$user->id)->where('fund_type','LIKE','cashbox')->where('currency','LIKE',$currency->code)->get();
        $data = [
            'user' => $user,
            'user_cash_box_accounts' => $cash_box_accounts,
            'current_cash_box_account' => $cbUserAccount,
            'balanceCalculation'=> $balanceCalculation,
            'cbStatement' => $cbStatement,
            'balanceCredit' => $balanceCredit,
            'balanceDebit' => $balanceDebit,
            'cbDebitsTypes' => $cbDebitsTypes,
            'cbCreditsTypes' => $cbCreditsTypes,
            'cbDeposits' => $cbDeposits,
            'cbWithdrawals' => $cbWithdrawals,
        ];
        if ($pdf){
            return $data;
        }
        return view('cash_box.report', $data);
    }
}
