<?php

namespace App;

use App\Model\Deposit;
use App\Model\Rate;
use App\Withdrawals\WithdrawalBatches;
use http\Env\Response;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * @property  created_by
 */
class withdrawals extends Model
{
    public static function approveAndPaid($withdrawId, $referenceId,$status = 'Approved')
    {
        $withdrawal = withdrawals::where('id', $withdrawId)->where('status', 'Pending');
        if ($withdrawal->count()) {
            $withdrawal = $withdrawal->first();
            if($status == 'Approved') {
                $withdrawal->status = $status;
                $withdrawal->is_paid = 1;
                $withdrawal->paid_at = date('Y-m-d');
                $withdrawal->bank_reference_id = $referenceId;
            }else{
                $withdrawal->batch_id = NULL;
            }
            $withdrawal->save();
            echo "done for withdrawal(" . $withdrawal->id . ") <br>";
        }
    }

    public function getCreatedAtDate()
    {
        return \Carbon\Carbon::parse($this->created_at)->toDateString();
    }

    public function date_string_new_system()
    {
        return "2018-12-15";
    }

    public function isBeforeNewSystem()
    {
        $cdate = \Carbon\Carbon::createFromFormat('Y-m-d', $this->getCreatedAtDate());
        $new_system_start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $this->date_string_new_system());
        return $cdate->lessThan($new_system_start_date);
    }

    /**
     * @return bool
     */
    public function isNewSystem()
    {
        return !$this->isBeforeNewSystem();
    }

    public function duser()
    {
        return $this->belongsTo('App\User', 'user');
    }

    public function toUsd()
    {
        if ($this->status == 'Approved') {
            return round($this->pre_amount * Rate::at(\Carbon\Carbon::parse($this->created_at)->toDateString(), $this->currency), 2);
        }
        return round($this->pre_amount * Rate::last($this->currency), 2);
    }


    public static function newSystemWithdrawalTotal($user_id, $currency)
    {

        // $amount_column = 'crypto_amount';
        // if($currency == 'usd'){
        //     $amount_column = 'amount';
        //}
        $amount_column = 'pre_amount';
        return withdrawals::whereRaw('user = ? and currency = ? and (status like ? or status like ? ) and is_manual_cancel = 0 and date(created_at) >= ? and payment_mode like ?', [$user_id, $currency, 'Pending', 'Approved', '2018-12-15', 'profit'])->sum($amount_column);
    }

    public static function newSystemWithdrawals($user_id)
    {
        return withdrawals::whereRaw('user = ? and date(created_at) >= ? and payment_mode like ? and (status like ? or status like ? )', [$user_id, '2018-12-15', 'profit', 'Pending', 'Approved']);
    }


    public function createdBy()
    {
        return $this->belongsTo("App\users", 'created_by');
    }

    public function verifiedUser()
    {
        return $this->belongsTo("App\admin_logs", 'unique_id');
    }

    public static function getWithdrawlsQuery($rate_pkr, $sold, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select(
                'users.account_no',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                //                DB::raw('SUM(amount) as total_amount'),
                'banks.bank_code'
            )
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            })
            ->where('users.bank_name', 'NOT LIKE', 'Meezan Bank')
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.amount', '<', 1333)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel', '!=', 3);
        if ($sold == 0) {
            $withdrawals->where('withdrawals.payment_mode', 'NOT LIKE', 'Sold');
        } else {
            $withdrawals->where('withdrawals.payment_mode', 'LIKE', 'Sold');
        }
        if ($start) {
            $withdrawals->where('withdrawals.id', '>=', $start);
        }
        if ($end) {
            $withdrawals->where('withdrawals.id', '<=', $end);
        }
        if ($offset) {
            $withdrawals->offset($offset);
        }


        $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        return $withdrawals;
    }

    public static function getWithdrawlsQueryMBFT($rate_pkr, $sold, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select(
                'users.account_no',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                'banks.bank_code'
            )
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('users.bank_name', 'LIKE', 'Meezan Bank')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where('withdrawals.amount', '<', 1333)
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel', '!=', 3);
        if ($sold == 0) {
            $withdrawals->where('withdrawals.payment_mode', 'NOT LIKE', 'Sold');
        } else {
            $withdrawals->where('withdrawals.payment_mode', 'LIKE', 'Sold');
        }
        if ($start) {
            $withdrawals->where('withdrawals.id', '>=', $start);
        }
        if ($end) {
            $withdrawals->where('withdrawals.id', '<=', $end);
        }
        if ($offset) {
            $withdrawals->offset($offset);
        }


        $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        /*
                   $w = $withdrawals->select('withdrawals.id','withdrawals.payment_mode')->get();
                   $c = 0;
                   echo $sold . " <br>";
                   foreach ($w as $y) {
                       echo $c++ . "+  ";
                   }
                   exit($w);*/

        return $withdrawals;
    }


    public static function getWithdrawlsQueryAskariIBFT($rate_pkr, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select(
                'users.account_no',
                'users.account_name',
                'users.bank_name',
                'withdrawals.unique_id',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                //                DB::raw('SUM(amount) as total_amount'),
                'banks.askari_bank_codes',
                'banks.askari_bank_names'
            )
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('withdrawals.payment_mode', 'NOT LIKE', 'Sold')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            })
            ->where('users.bank_name', 'NOT LIKE', 'Askari Bank')
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.amount', '<', 1333)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel', '!=', 3);
        if ($start) {
            $withdrawals->where('withdrawals.id', '>=', $start);
        }
        if ($end) {
            $withdrawals->where('withdrawals.id', '<=', $end);
        }
        if ($offset) {
            $withdrawals->offset($offset);
        }


        $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        return $withdrawals;
    }


    /* public static function getWithdrawlsQueryFaysalIBFT($rate_pkr, $sold_GT2, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select(
                'users.account_no',
                'users.account_name',
                'users.bank_name',
                'withdrawals.unique_id',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                //                DB::raw('SUM(amount) as total_amount'),
                'banks.faysal_bank_codes',
                'banks.faysal_bank_names'
            )
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            })
            ->where('users.bank_name', 'NOT LIKE', 'Faysal Bank')
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.is_manual_cancel', '!=', 3);
        if($sold_GT2 == 2) {
           $withdrawals->where('withdrawals.amount', '>=', 1333)
                       ->where('withdrawals.is_verify', '=', 2);
        }else{
           $withdrawals->where('withdrawals.amount', '<', 1333)
                       ->where('withdrawals.is_verify', '=', 1);
        }      
        if($sold_GT2 == 0) {
            $withdrawals->where('withdrawals.payment_mode', 'NOT LIKE', 'Sold');
        }elseif ($sold_GT2 == 1) {
            $withdrawals->where('withdrawals.payment_mode', 'LIKE', 'Sold');
        }     
        if ($start) {
            $withdrawals->where('withdrawals.id', '>=', $start);
        }
        if ($end) {
            $withdrawals->where('withdrawals.id', '<=', $end);
        }
        if ($offset) {
            $withdrawals->offset($offset);
        }


        $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        return $withdrawals;
    }*/


    public static function getWithdrawlsQueryBankIslamiIBFT($rate_pkr, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select(
                'users.account_no',
                'users.account_name',
                'users.bank_name',
                'withdrawals.unique_id',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                //                DB::raw('SUM(amount) as total_amount'),
                'banks.bank_islami_codes',
                'banks.bank_islami_names'
            )
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('withdrawals.payment_mode', 'NOT LIKE', 'Sold')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where(function ($q) {
                $q->where('users.account_no', 'LIKE', '%' . 'Pk' . '%')
                    ->orWhere('users.account_no', 'LIKE', '%' . 'Ak' . '%');
            })
            ->where('users.bank_name', 'NOT LIKE', 'Bank Islami')
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.amount', '<', 1333)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel', '!=', 3);
        if ($start) {
            $withdrawals->where('withdrawals.id', '>=', $start);
        }
        if ($end) {
            $withdrawals->where('withdrawals.id', '<=', $end);
        }
        if ($offset) {
            $withdrawals->offset($offset);
        }


        $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        return $withdrawals;
    }


    /*  public static function getWithdrawlsQueryAskari_IBT($rate_pkr, $start = null, $end = null, $offset = null)
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->join('banks', function ($join) {
                $join->on('users.bank_name', '=', 'banks.bank_name');
            })
            ->select('users.account_no',
                DB::raw("withdrawals.id AS Reference_No"),
                DB::raw("withdrawals.amount*$rate_pkr AS amount"),
                'banks.bank_code')
            ->where('users.Country', 'LIKE', 'Pakistan')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('users.bank_name', 'LIKE', 'Askari Bank')
            ->where('withdrawals.currency', 'LIKE', 'USD')
            ->where('withdrawals.amount', '<', 1333)
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel','!=', 3);
            if($start){
                $withdrawals->where('withdrawals.id', '>=', $start);
            }
            if($end){

                $withdrawals->where('withdrawals.id', '<=', $end);
            }
            if($offset){
                $withdrawals->offset($offset);
            }


            $withdrawals->whereNull('withdrawals.fund_type')
            ->groupBy('withdrawals.id');
        return $withdrawals;
    }*/


    /**
     * @param $fileUrl
     * @param string $batch_no
     * @return array|\Illuminate\Http\JsonResponse
     * @Purpose :: B4U global is directly giving approved withdrawals to B4U Wallet and after import B4U Wallet returns Batch No, then Batch No will save
     *  to B4U Global with Batch no and withdrawals Ids that are giving to B4U Wallet
     * @author :: Mubashar Rashid
     */
    public static function importApprovedWithdrawalFromB4UWallet($fileUrl, string $batch_no)
    {
        try {


            ## get file content..
            $content = file_get_contents($fileUrl);
            $lines = explode(PHP_EOL, $content);
            ## withdrawal arrays
            $withdrawalsArray = [];
            if (!empty($lines)) {
                ## Create Empty Batch for the reference of Withdrawal
                $withdrawalBatch = WithdrawalBatches::createNewBatch($batch_no, []);
                if (!$withdrawalBatch['status'] || !$withdrawalBatch['batch'] instanceof WithdrawalBatches) {
                    return $withdrawalBatch;
                } else {
                    /** @var WithdrawalBatches $withdrawalBatch */
                    $withdrawalBatch = $withdrawalBatch['batch'];
                }
                ## remove heading row from excel data.
                unset($lines[0]);
                ## import
                foreach ($lines as $key => $column) {
                    if (!empty(str_getcsv($column)[3])) {
                        $withdrawalColumnData = (str_getcsv($column)[3]);
                        $withdrawalId = explode("w", $withdrawalColumnData);
                        $withdrawalId = explode("d", $withdrawalId[1]);
                        $withdrawalsArray[$withdrawalId[0]] = 'Pending';
                        ## save batch no against withdrawal.
                        $withdrawalModel = withdrawals::find($withdrawalId[0]);
                        if ($withdrawalModel instanceof withdrawals) {
                            $withdrawalModel->batch_id = $withdrawalBatch->id;
                            $withdrawalModel->save();
                        }
                    }
                }

                return WithdrawalBatches::updateBatch($batch_no, $withdrawalsArray, $withdrawalBatch->id);

            }
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'errorMsg' => $exception->getMessage(),
                'getLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
        }

    }
}
