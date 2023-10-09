<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CashboxBatches extends Model
{
    protected $table = 'cashbox_batches';

    public static function createNewBatch(string $batch_no, array $debitsArray)
    {
        try {
            $cashboxBatch = new CashboxBatches();
            $cashboxBatch->batch_no = $batch_no;
            $cashboxBatch->vendors_count = count($debitsArray);
            $cashboxBatch->vendors_ids = json_encode($debitsArray);
            $cashboxBatch->save();
            ## Log
            Log::info('New Batch has been Created of ' . $cashboxBatch->cbDebits_count . ' withdrawals');
            return [
                'status' => true,
                'batch' => $cashboxBatch,
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
    public static function updateBatch(string $batch_no, array $vendorsArray, int $batchId)
    {
        try {
            $cashboxBatch = CashboxBatches::find($batchId);
            if ($cashboxBatch instanceof CashboxBatches) {
                $cashboxBatch->batch_no = $batch_no;
                $cashboxBatch->vendors_count = count($vendorsArray);
                $cashboxBatch->vendors_ids = json_encode($vendorsArray);
                $cashboxBatch->uploaded_by = \Illuminate\Support\Facades\Auth::user()->id;
                $cashboxBatch->save();
            }
            ## Log
            Log::info('Batch has been Update of ' . $cashboxBatch->withdrawal_count . ' withdrawals');
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
