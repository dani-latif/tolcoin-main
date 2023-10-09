<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CBCredits extends Model
{
    protected $table = 'cb_credits';
    static $tableNameCurrenciesColumnRef = 'cb_credits.currencies_id';
    static $tableName = 'cb_credits';
    /**
     * @param int $cashBoxAccountId
     * @param float $amount
     * @param float $fee
     * @param null $category
     * @param null $type
     * @param null $reason
     * @param string $status
     * @return CBCredits|string
     *
     * @Purpose Create Credit of User.. For now there is not check that logged in user can make it own credit due to, we are giving them reward so this time we are not restricting codes to do an entry against user owner.
     */
    /*public static function createCredit(int $cashBoxAccountId, float $amount, float $fee, string $category = null, string $type = null, string $reason = null, string $status = 'Pending')
    {
        try {
            DB::beginTransaction();
            $CBAccount = CBAccounts::find($cashBoxAccountId);

            $CBCredit = new CBCredits();
            $CBCredit->user_id = $CBAccount->user_id;
            $CBCredit->cb_account_id = $CBAccount->id;
            $CBCredit->amount = $amount;
            $CBCredit->fee = $fee;
            $CBCredit->currencies_id = $CBAccount->currencies_id;
            $CBCredit->category = $category;
            $CBCredit->type = $type;
            $CBCredit->reason = $reason;
            $CBCredit->status = $status;
            $CBCredit->save();

            Log::info('new credit is made to user account', [
                'credit_details' => $CBCredit
            ]);

            Log::info('User of having id ' . $CBAccount->user_id . ' cash box account current balance is increase ', [
                'cashBoxAccount' => $CBAccount,
                'cbCredit' => $CBCredit
            ]);
            $CBAccount->current_balance = floatval($CBAccount->current_balance) + floatval($CBCredit->amount);
            $CBAccount->save();

            DB::commit();
            return $CBCredit;
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('error while doing new entry in credit', [
                'errorMessage' => $exception->getMessage(),
                'errorLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
            return $exception->getMessage();
        }

    }*/
    public static function createCredit(int $cashBoxAccountId, float $amount, float $fee, string $category = null, string $type = null, string $reason = null, string $status = 'Pending',
                                        int $cb_debit_id = null, int $came_from_cb_account_id = null, int $came_from_user_id = null, int $withdrawal_id = null)
    {
        try {

            $CBCredit = null;
            if ($cb_debit_id) {
                ## if debit id is given, then check, is this debit id already exits in the cb credits, if yes, then don't create debit.
                $CBCredit = CBCredits::where('cb_debit_id', $cb_debit_id)->first();
                if ($CBCredit instanceof CBCredits) {
                    return null;
                }
            }

            if ($withdrawal_id) {
                ## if withdrawal id is given, then check, is this withdrawal id already exits in the cb credits, if yes, then don't create debit.
                $CBCredit = CBCredits::where('withdrawal_id', $withdrawal_id)->first();
                if ($CBCredit instanceof CBCredits) {
                    return "credit {$CBCredit->id} credit id, of this withdrawal already created";
                }
            }

            ## get CashBox Account
            $CBAccount = CBAccounts::find($cashBoxAccountId);

            ## update Current balance of CashBox
            $updateCurrentBalanceRow = DB::table(CBAccounts::$tableName)
                ->where('id', $cashBoxAccountId)
                ->lockForUpdate()->first();

            if ($updateCurrentBalanceRow && !empty($updateCurrentBalanceRow->id)) {

                $updateCurrentBalanceResponse = DB::table(CBAccounts::$tableName)
                    ->where('id', $updateCurrentBalanceRow->id)->increment('current_balance', $amount);

                if ($updateCurrentBalanceResponse === 1) {
                    $CBCredit = new CBCredits();
                    $CBCredit->user_id = $CBAccount->user_id;
                    $CBCredit->cb_account_id = $CBAccount->id;
                    $CBCredit->amount = $amount;
                    $CBCredit->fee = $fee;
                    $CBCredit->currencies_id = $CBAccount->currencies_id;
                    $CBCredit->category = $category;
                    $CBCredit->type = $type;
                    $CBCredit->reason = $reason;
                    $CBCredit->status = $status;
                    $CBCredit->cb_debit_id = $cb_debit_id;
                    $CBCredit->came_from_cb_account_id = $came_from_cb_account_id;
                    $CBCredit->came_from_user_id = $came_from_user_id;
                    $CBCredit->withdrawal_id = $withdrawal_id;
                    $CBCredit->save();
                }
            }

            return $CBCredit;
        } catch (\Exception $exception) {
            Log::error('error while doing new entry in credit', [
                'errorMessage' => $exception->getMessage(),
                'errorLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
            return $exception->getMessage();
        }

    }
    public static function approveCreditFundTransfer(CBCredits $cbCredit)
    {
        try {
            if ($cbCredit->status == 'pending') {
                $cbCredit->status = 'approved';
                $cbCredit->save();

                if ($cbCredit) {
                    $CBAccount = CBAccounts::find($cbCredit->cb_account_id);
                    $totalAmount = $cbCredit->amount + $cbCredit->fee;
                    $cbAccountResponse = CBAccounts::whereId($CBAccount->id)->lockForUpdate()->first();
                    ## if balance is decrease.
                    if ($cbAccountResponse) {
                        ## decrease locked-balance
                        return  CBAccounts::whereId($cbAccountResponse->id)->increment('current_balance', $totalAmount);
                    }
                }
            }
        }catch (\Exception $exception){
            Log::error('error while doing Approve Cb transfer', [
                'errorMessage' => $exception->getMessage(),
                'errorLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
            return $exception->getMessage();
        }
    }

    public static function createInternalTransferCreditFromCBDebit(int $cbDebitId)
    {   $response = "";
        // DB::transaction(function () use ($cbDebitId) {
            $CBDebit = CBDebits::where('type', 'transfer')->where('category', 'balance')->where('id', $cbDebitId)->first();
            if ($CBDebit instanceof CBDebits) {
                $response = "1 Debit Found ! ";
                ## get cash box debit reason
                $reason = $CBDebit->reason;
                $reasonArray = explode('#', $reason);
                if (!empty($reasonArray[1])) {
                    $response .= "2 User Found ! ";
                    ## get B4U UId from Internal transfer string
                    $userB4UUId = $reasonArray[1];
                    ## B4U User
                    $B4UUser = User::where('u_id', $userB4UUId)->first();
                    ## Transfer From User., Debit of User..
                    $TransferFrom = User::find($CBDebit->user_id);
                    ## If B4U User found
                    if ($B4UUser instanceof User) {
                        $response .= "3 TransferTo User Found ! ";
                        $B4UUserId = $B4UUser->id;
                        ## Get the CashBox Account of User and Currency
                        $CashBoxAccount = \App\CBAccounts::where('user_id', $B4UUserId)->where('currencies_id', $CBDebit->currencies_id)->first();
                        if ($CashBoxAccount instanceof \App\CBAccounts) {
                            try {
                                $response .= "4 TransferTo CB Found ! ";
                                $CbCredit = self::createCredit($CashBoxAccount->id, $CBDebit->amount, 0, 'balance', 'transfer', "Internal Transfer from #{$TransferFrom->u_id}"
                                    , 'approved', $CBDebit->id, $CBDebit->cb_account_id, $CBDebit->user_id);

                                if ($CbCredit instanceof CBCredits) {
                                    $CBDebit->cb_credit_id = $CbCredit->id;
                                    $CBDebit->save();
                                }
                            } catch (\Exception $exception) {
                                $response =    Log::error('createInternalTransferCreditFromCBDebitError', ['message' => $exception->getMessage()]);
                            }
                        } else {
                            return $CashBoxAccount;
                        }
                    }
                }
            }
    //    }, config('b4uglobal.RETRY_CASH_BOX_TRANS'));
        return $response ;
    }


}
