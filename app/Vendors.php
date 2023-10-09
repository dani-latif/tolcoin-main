<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Vendors extends Model
{
    //
    /**
     * @param int $batchId
     * @param int $userId
     * @param int $currId
     * @param float $amount
     * @author Mubashar Rashid
     */
    public static function createNewVendor(int $batchId, int $userId, int $currId, float $amount)
    {
        try {
        $vendorModel = new Vendors();
        $vendorModel->batch_id = $batchId;
        $vendorModel->status = 'Pending';
        $vendorModel->user_id = $userId;
        $vendorModel->currency_id = $currId;
        $vendorModel->amount = $amount;
        $vendorModel->save();
            return [
                'status' => true,
                'vendor' => $vendorModel,
            ];
        } catch (\Exception $exception) {
            $errorAr = [
                'status' => false,
                'errorMsg' => $exception->getMessage(),
                'getLine' => $exception->getLine(),
                'errorFile' => $exception->getFile(),
                'vendor' => []
            ];
            Log::info('Error while creating vendor ', $errorAr);
            return $errorAr;
        }
    }

    public static function updateStatus($vendorId,$status = 'Approved')
    {
        $vendor = Vendors::find($vendorId);
        if ($vendor->count()) {
            $vendor->status = $status;
            $vendor->save();
            echo "done for vendor(" . $vendorId . ") <br>";
        }
    }
}
