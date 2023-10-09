<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CBDebits extends Model
{

    static $tableName = 'cb_debits';
    protected $table = 'cb_debits';
    static $tableNameCurrenciesColumnRef = 'cb_debits.currencies_id';

    /**
     * @Purpose :: Approve Debit whenever Deposit approve
     * @param int $depositId
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function topDownNonUsdDebitsQuery(int $batch = 0)
    {
        $cbDebits = CBDebits::join('users', 'users.id', '=', 'cb_debits.user_id')
            ->select('users.btc_address', 'users.eth_address', 'users.bch_address', 'users.dash_address', 'users.ltc_address', 'users.xrp_address','users.rsc_address',
                'users.zec_address', 'users.name', 'users.account_no','users.bank_name','users.phone_no','users.Country','users.u_id',
                DB::raw("cb_debits.id AS Reference_No"),
                DB::raw("users.id AS userId"),
                'cb_debits.amount', 'cb_debits.currencies_id')
            ->where('users.type', '!=', 4)
            ->where('cb_debits.status', 'LIKE', 'verified')
            ->where('cb_debits.type', 'LIKE', 'topdown')
            ->where('cb_debits.currencies_id', '!=', 1)
          //  ->where('cb_debits.currencies_id', '!=', 9)
            ->whereNull('cb_debits.cb_batch_id')
            ->whereNull('cb_debits.cb_vendor_id');
            if($batch == 0){
            $cbDebits->groupBy('userId')
                ->groupBy('cb_debits.currencies_id')
                ->get();
                }elseif($batch == 1){
                $cbDebits->groupBy('cb_debits.id');
            }
        return $cbDebits;
        /* */
    }
    public static function approveDebit(CBDebits $cbDebit)
    {
        try {
            if($cbDebit->status == 'pending'){
                $cbDebit->status = 'approved';
                $cbDebit->save();

                if ($cbDebit){
                    $CBAccount = CBAccounts::find($cbDebit->cb_account_id);
                    $totalAmount = $cbDebit->amount + $cbDebit->fee;
                    $cbAccountResponse = CBAccounts::whereId($CBAccount->id)->where('locked_balance', '>=', $totalAmount)->lockForUpdate()->first();
                    ## if balance is decrease.
                    if ($cbAccountResponse) {
                        ## decrease locked-balance
                        return CBAccounts::whereId($cbAccountResponse->id)->decrement('locked_balance', $totalAmount);
                    }
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

    public static function topDownUsdDebitsQuery(int $ft = null)
    {
        $rate = settings::pkrRate();
        $rate_pkr = $rate->rate_pkr;
        $cbDebits = CBDebits::join('users', 'users.id', '=', 'cb_debits.user_id')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select('users.account_no',
                'users.account_no',
                'users.account_name',
                'users.bank_name',
                'users.u_id',
                'banks.faysal_bank_codes',
                'banks.faysal_bank_names',
                DB::raw("cb_debits.id AS Reference_No"),
                DB::raw("cb_debits.amount*$rate_pkr AS amount"))
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            });
        if($ft == 0){
            $cbDebits->where('users.bank_name', 'NOT LIKE', 'Faysal Bank');
        }elseif ($ft == 1){
            $cbDebits->where('users.bank_name', 'LIKE', 'Faysal Bank');
        }
        $cbDebits->where('users.type', '!=', 4)
            ->where('cb_debits.status', 'LIKE', 'verified')
        //    ->where('cb_debits.amount', '>=', '700')
            ->where('cb_debits.type', 'LIKE', 'topdown')
            ->where('cb_debits.currencies_id', '=', 1)
            ->whereNull('cb_debits.cb_batch_id')
            ->whereNull('cb_debits.cb_vendor_id')
            ->groupBy('cb_debits.id');

        return $cbDebits;
    }

    public static function approveDebitOnDepositApprove(int $depositId)
    {
        ## find deposit.
        $Deposit = \App\deposits::find($depositId);
        if ($Deposit->payment_mode == 'cashbox' && strtolower($Deposit->status) == 'pending') {
            ## find debit from deposit.
            $debitId = $Deposit->cb_debit_id;
            ## find is valid debit.
            $CBDebits = \App\CBDebits::find($debitId);
            if ($CBDebits instanceof \App\CBDebits && strtolower($CBDebits->status) == 'pending') {
                ## i found the debit now find the CashBox Account of User.
                $CBAccount = \App\CBAccounts::find($CBDebits->cb_account_id);
                if ($CBAccount instanceof \App\CBAccounts) {
                    ## check is enough locked balance available, to deduct.
                    $LockedBalance = CBAccounts::getCashBoxLockedBalanceByCurrencyCode($Deposit->currency, $CBDebits->user_id);
                    if ($LockedBalance < $Deposit->amount) {
                      //  return ['status' => false, 'message' => 'It seems, This user has duplicate Deposits from CashBox because locked amount is not equal to deposit amount'];
                        return ['statusCode' => 300, 'message' => 'Deposit amount is less than locked balance'];
                    }
                    switch (strtolower($Deposit->status)) {
                        case 'pending':
                            $CBDebits->status = 'approved';
                            $CBDebits->save();

                            $CBAccount->locked_balance = floatval($CBAccount->locked_balance) - floatval($CBDebits->amount);
                            $CBAccount->save();
                            return ['statusCode' => 200, 'message' => 'Cash Box Debit Approved'];
                            break;
                        case 'cancelled':
                        case 'deleted':

                            $CBDebits->status = $Deposit->status;
                            $CBDebits->save();

                            $CBAccount->locked_balance = floatval($CBAccount->locked_balance) - floatval($CBDebits->amount);
                            $CBAccount->current_balance = floatval($CBAccount->current_balance) + floatval($CBDebits->amount);
                            $CBAccount->save();

                            break;
                    }
                } else {
                        return ['statusCode' => 300, 'message' => 'Cash Box Account is not found'];
                }
            } else {
                    return ['statusCode' => 300, 'message' => 'Cash Box Debit not found'];
            }
        }
    }

    public static function updateStatus($vendorId, string $status)
    {
        $cbDebit = CBDebits::where('cb_vendor_id', $vendorId);
        if ($cbDebit->count()) {
            $cbDebit->update(['status' => $status]);
        }
    }

    public static function approveAndDeduct($debitId, $referenceId, string $status)
    {
        $cbDebit = CBDebits::whereId($debitId)->where('status', 'verified');
        if ($cbDebit->count()) {
            $cbDebit = $cbDebit->first();
            $cbDebit->status = $status;
            $cbDebit->bank_reference_id = $referenceId;
            $cbDebit->save();

            $cbAccounts = CBAccounts::whereId($cbDebit->cb_account_id)->first();
            $newlockedBalance = $cbAccounts->locked_balance - $cbDebit->amount - $cbDebit->fee;
            $cbAccounts->locked_balance = $newlockedBalance;
            $cbAccounts->save();
            echo "done for debit(" . $cbDebit->id . ") <br>";
        }
    }

    public static function failedAndDeduct(int $debitId, string $status)
    {
        $cbDebit = CBDebits::whereId($debitId)->where('status', 'verified');
        if ($cbDebit->count()) {
            $cbDebit = $cbDebit->first();
            $cbDebit->status = $status;
            $cbDebit->save();

            $cbAccounts = CBAccounts::whereId($cbDebit->cb_account_id)->first();
            $newlockedBalance = $cbAccounts->locked_balance - $cbDebit->amount - $cbDebit->fee;
            $newcurrentBalance = $cbAccounts->current_balance + $cbDebit->amount + $cbDebit->fee;
            $cbAccounts->locked_balance = $newlockedBalance;
            $cbAccounts->current_balance = $newcurrentBalance;
            $cbAccounts->save();
            echo "done for debit(" . $cbDebit->id . ") <br>";
        }
    }

    public static function cancelDebit(CBDebits $cbDebit){
        if($cbDebit->status == 'inprocessing' || $cbDebit->status == 'pending' ){
            $cbDebit->status = 'cancelled';
            $cbDebit->save();

            $cbAccount = CBAccounts::find($cbDebit->cb_account_id);
            if ($cbDebit && $cbAccount instanceof CBAccounts){
                $cbAccount->current_balance = $cbAccount->current_balance + $cbDebit->amount + $cbDebit->fee ;
                $cbAccount->locked_balance = $cbAccount->locked_balance - ($cbDebit->amount + $cbDebit->fee);
                $cbAccount->save();

                return $cbAccount;
            }
        }
    }

    public static function unDefaultDebit(CBDebits $cbDebit){
        if($cbDebit->status == 'inprocessing'){
            $cbDebit->status = 'pending';
            $cbDebit->is_defaulter = 0;
            $cbDebit->save();

           return $cbDebit;
        }
    }
    public static function topDownUsdDebitsBatchQuery($rate_pkr, $start = null, $end = null, $offset = null)
    {
        $cbDebits = CBDebits::join('users', 'users.id', '=', 'cb_debits.user_id')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select('users.account_no',
                'users.account_no',
                'users.account_name',
                'users.bank_name',
                'users.u_id',
                'banks.faysal_bank_codes',
                'banks.faysal_bank_names',
                DB::raw("cb_debits.id AS Reference_No"),
                DB::raw("cb_debits.amount*$rate_pkr AS amount"))
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            })
            ->where('users.bank_name', 'NOT LIKE', 'Faysal Bank')
            ->where('users.type', '!=', 4)
            ->where('cb_debits.status', 'LIKE', 'verified')
            ->where('cb_debits.amount', '>=', '700')
            ->where('cb_debits.type', 'LIKE', 'topdown')
            ->where('cb_debits.currencies_id', '=', 1);
        if ($start) {
            $cbDebits->where('cb_debits.id', '>=', $start);
        }
        if ($end) {
            $cbDebits->where('cb_debits.id', '<=', $end);
        }
        if ($offset) {
            $cbDebits->offset($offset);
        }
        $cbDebits->whereNull('cb_debits.cb_batch_id')
            ->whereNull('cb_debits.cb_vendor_id')
            ->groupBy('cb_debits.id');

        return $cbDebits;
    }

}
