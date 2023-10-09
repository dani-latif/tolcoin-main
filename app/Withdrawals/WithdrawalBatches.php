<?php

namespace App\Withdrawals;

use App\withdrawals;
use hollodotme\FastCGI\RequestContents\JsonData;
use http\Env\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WithdrawalBatches extends Model
{
    protected $table = 'withdrawal_batches';

    /**
     * @param string $batch_no
     * @param array $withdrawalArray
     * @return array
     * @Purpose: Save new Batch
     * @author  Mubashar Rashid
     */
    /*public function withdrawals(){
        return $this->hasMany(withdrawals::class);
    }*/
    public static function createNewBatch(string $batch_no, array $withdrawalArray)
    {
        try {
            $withdrawalBatch = new \App\Withdrawals\WithdrawalBatches();
            $withdrawalBatch->batch_no = $batch_no;
            $withdrawalBatch->withdrawal_count = count($withdrawalArray);
            $withdrawalBatch->withdrawal_ids = json_encode($withdrawalArray);
            $withdrawalBatch->save();
            ## Log
            Log::info('New Batch has been Created of ' . $withdrawalBatch->withdrawal_count . ' withdrawals');
            return [
                'status' => true,
                'batch' => $withdrawalBatch,
            ];
        } catch (\Exception $exception) {

            $errorAr = [
                'status' => false,
                'errorMsg' => $exception->getMessage(),
                'getLine' => $exception->getLine(),
                'errorFile' => $exception->getFile(),
                'batch' => []
            ];
            Log::info('Error while creating batch ', $errorAr);
            return $errorAr;
        }

    }

    /**
     * @param string $batch_no
     * @param array $withdrawalArray
     * @param int $batchId
     * @return array
     * @Purpose: Update Batch
     * @author  Mubashar Rashid
     */
    public static function updateBatch(string $batch_no, array $withdrawalArray, int $batchId)
    {
        try {
            $withdrawalBatch = WithdrawalBatches::find($batchId);

            if ($withdrawalBatch instanceof WithdrawalBatches) {
                $withdrawalBatch->batch_no = $batch_no;
                $withdrawalBatch->withdrawal_count = count($withdrawalArray);
                $withdrawalBatch->withdrawal_ids = json_encode($withdrawalArray);
                $withdrawalBatch->uploaded_by = \Illuminate\Support\Facades\Auth::user()->id;
                $withdrawalBatch->save();
            }
            ## Log
            Log::info('Batch has been Update of ' . $withdrawalBatch->withdrawal_count . ' withdrawals');
            return response()->json([
                'status' => true,
                'message' => 'Successfully Updated',
            ]);
        } catch (\Exception $exception) {

            $errorAr = [
                'status' => false,
                'errorMsg' => $exception->getMessage(),
                'getLine' => $exception->getLine(),
                'errorFile' => $exception->getFile(),
                'batch' => []
            ];
            Log::info('Error while Updating batch ', $errorAr);
            return $errorAr;
        }

    }
}
