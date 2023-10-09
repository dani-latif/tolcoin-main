<?php

namespace App\Http\Controllers\Cashbox;

use App\CashboxBatches;
use App\CBAccounts;
use App\CBDebits;
use App\Currencies;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SignedUrlUploadController;
use App\Http\Requests\CbBatchIdValidationRequest;
use App\Http\Requests\CbBatchNoValidationRequest;
use App\Vendors;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use function GuzzleHttp\Psr7\str;

class CashBoxBatchController extends Controller
{
    public function __construct()
    {
    }
    public function createCbBatch()
    {
        ## Fetch Users groupBy currency of CBDepits
        $cbUsers = CBDebits::topDownNonUsdDebitsQuery();
        $count = 0;
        foreach($cbUsers as $cbUser) {
            $curr = Currencies::find($cbUser->currencies_id);
            $curr_address = $curr->small_code . '_address';
            $crypto_address = (string)$cbUser->$curr_address;
            if (!empty($crypto_address)) {
                $count = 1;
            }
        }
        if($count == 0){
            return redirect()->back()->with('errormsg', 'No Debits available for batch');
        }
        $cashboxBatch = CashboxBatches::createNewBatch(0, []);
        $cashboxBatch = $cashboxBatch['batch'];
        ##Create Vendors against this users
        foreach($cbUsers as $cbUser){
            $curr = Currencies::find($cbUser->currencies_id);
            $curr_address = $curr->small_code . '_address';
            $crypto_address = (string)$cbUser->$curr_address;
            if (!empty($crypto_address)) {
                $amount = DB::select(DB::raw('(select SUM(amount) as am from cb_debits WHERE user_id=' . $cbUser->userId . ' and currencies_id=' . $cbUser->currencies_id . ' and status="verified" and type ="topdown" and cb_batch_id is null and cb_vendor_id is null  ) '));
                $vendorBatches = Vendors::createNewVendor($cashboxBatch->id, $cbUser->userId, $cbUser->currencies_id, $amount[0]->am);
                $vendorBatches = $vendorBatches['vendor'];
                CBDebits::whereUserId($cbUser->userId)
                    ->where('currencies_id', '=', $cbUser->currencies_id)
                    ->where('status', 'LIKE', 'verified')
                    ->where('type', 'LIKE', 'topdown')
                    ->whereNull('cb_batch_id')
                    ->whereNull('cb_vendor_id')
                    ->update([
                        'cb_batch_id' => $cashboxBatch->id,
                        'cb_vendor_id' => $vendorBatches->id,
                    ]);
             //
            }
        }
        $vendorsBatch = Vendors::where('batch_id',$cashboxBatch->id)->get();
        $vendorsArray = [];
        foreach ($vendorsBatch as $vendor){
            $vendorsArray[$vendor->id] = 'Pending';
        }
        CashboxBatches::updateBatch(0, $vendorsArray, $cashboxBatch->id);
        return redirect()->back()->with('message', 'Batch Created Successfully');
    }

    public function cashboxBatches()
    {
        return view('cash_box.cashbox_batches', ['title' => 'Cashbox Batches']);
    }

    public function cashboxBatches_json()
    {
        return datatables()->of(CashboxBatches::get())->toJson();
    }

   public function batchCashbox(CbBatchIdValidationRequest $request){
       return view('cash_box.batch_cashbox', [
           'title' => 'Batch Vendors',
           'batchId' => $request->id,
           'batch' => CashboxBatches::whereId($request->id)->first(),
       ]);
   }

    public function batchCashbox_json(CbBatchIdValidationRequest $request)
    {
        return datatables()->of(Vendors::whereBatchId($request->id)->get())->toJson();
    }

    public function deleteCbBatch(CbBatchIdValidationRequest $request)
    {
        $batchId = $request->id;
        $batch = CashboxBatches::find($batchId);
           if($batch->batch_status != 'Pending'){
               // return redirect()->back()->with('errormsg', 'This batch cannot be deleted',500);
                return response('This batch cannot be deleted!',500);
            }
            try {
              CBDebits::where('cb_batch_id',$batchId)->where('status', 'LIKE', 'verified')->where('type', 'LIKE', 'topdown')->update(['cb_batch_id' => NULL , 'cb_vendor_id' => NULL]);
              $deleteVendors = DB::select(DB::raw("Delete FROM  vendors WHERE batch_id ='" . $batchId . "'"));
              $batch->delete();
              return response('Deleted Successfully!');
            }catch (\Exception $exception){
              //  $responseApi = $exception->getResponse();
               // Log::info($responseApi->getStatusCode() . "Message: " . $responseApi->getReasonPhrase() );
                return response( $exception->getMessage() .  " LineNo : "  . $exception->getLine()  .  " LineNo : "  . $exception->getFile(),500);
               // return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage ! ' . $responseApi->getStatusCode() .  " : "  .$responseApi->getReasonPhrase());
            }
    }
    public function uploadCbBatch(CbBatchIdValidationRequest $request)
    {
        $batchId = $request->id;
        $allDebits = Vendors::join('users', 'users.id', '=', 'vendors.user_id')
            ->select('users.u_id','users.btc_address', 'users.eth_address', 'users.bch_address', 'users.dash_address', 'users.ltc_address', 'users.xrp_address', 'users.zec_address', 'users.name', 'users.account_no',
                DB::raw("vendors.id AS Reference_No"),
                'vendors.amount', 'vendors.currency_id', 'vendors.batch_id')->whereBatchId($batchId)->where('vendors.status','Pending')->get();
        $storageDirectory = storage_path('app/temp/bat_file_downloads');
        if (!is_dir($storageDirectory)) {
            Storage::makeDirectory('temp/bat_file_downloads');
        }
        $BatchesFile = $storageDirectory . '/' . 'B' . $batchId . 'T' . time() . '.csv';
        if (!is_file($BatchesFile)) {
            fopen($BatchesFile, 'w');
        }
        $columns = ['BTC_Address', 'Amount', 'Currency', 'W_ID', 'B4U_ID', 'User_Name'];
        $file = fopen($BatchesFile, 'w');
        fputcsv($file, $columns);
        foreach ($allDebits as $withdrawals) {
            $date = date("dmy");
            $prestr = "B" . $withdrawals->batch_id;
            $poststr = "d" . $date;
            $refernceno = $prestr . "w" . $withdrawals->Reference_No . $poststr;

            $refernceno = (string)$refernceno;
            $name = (string)$withdrawals->name;
            $unique_id = (string)$withdrawals->u_id;
            $currency = Currencies::find($withdrawals->currency_id);
            $curr = $currency->small_code;
            $curr_address = $curr . '_address';
            $crypto_address = (string)$withdrawals->$curr_address;
            $amount = $withdrawals->amount + 0.0000006;
            $amount = round($amount, 6);

            if ($curr == 'xrp') {
                $xrp_address = explode(",", $withdrawals->xrp_address);
                if (isset($xrp_address[1])) {
                    $xrp_address = $xrp_address[0] . '?dt=' . $xrp_address[1];
                } else {
                    $xrp_address = $xrp_address[0];
                }
                $crypto_address = $xrp_address;
            }
            if (!empty($crypto_address)) {
                $fileArray = [
                    $crypto_address,
                    $amount,
                    $currency->code,
                    $refernceno,
                    $unique_id,
                    $name,
                ];
                fputcsv($file, $fileArray);
            }
        }
        fclose($file);
        $response = SignedUrlUploadController::uploadFileFromServerToAWS($BatchesFile, basename($BatchesFile));
        if ($response['status']) {
            // $endpoint = "http://www.coinee.cf/api/v2/peatio//public/batch_withdrawal";
            $endpoint = "https://ewallet.b4uwallet.com/api/v2/peatio/public/batch_withdrawal";
            $client = new \GuzzleHttp\Client();
            try {
                $responseApi = $client->request('POST', $endpoint, ['query' => [
                    'file_url' => $response['url'],
                    // 'email' => 'admin@barong.io', b4utrades@gmail.com ,
                    'email' => Auth::user()->email,
                    'otp_code' =>$request->otp_code,
                ]]);
                if($responseApi->getStatusCode() == 201){
                    $getBatch = json_decode($responseApi->getBody());

                    $batch = CashboxBatches::whereId($batchId)->first();
                    $batch->is_uploaded = 1;
                    $batch->batch_no = $getBatch->batch_id;
                    $batch->save();

                    return redirect()->back()->with('message','Batch: '.  $getBatch->batch_id   .' created successfully.');
                }
                else{
                    Log::info($responseApi->getStatusCode() . "Message: " . $responseApi->getReasonPhrase() );
                    return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage : ' . $responseApi->getReasonPhrase());
                }
            }catch (GuzzleException $exception){
                $responseApi = $exception->getResponse();
                Log::info($responseApi->getStatusCode() . "Message: " . $responseApi->getReasonPhrase() );
                return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage ! ' . $responseApi->getStatusCode() .  " : "  .$responseApi->getReasonPhrase());
            }
        }
    }

    public function fetchCbBatchStatus(CbBatchNoValidationRequest $request){
        $endPoint =  "https://ewallet.b4uwallet.com/api/v2/peatio/public/batch_status";
        try {
            $client = new \GuzzleHttp\Client();
            $batchStatusApi = $client->request('GET', $endPoint, ['query' => [
                'batch_number' => $request->batchNo,
            ]]);
            if ($batchStatusApi->getStatusCode() == 200) {
                $getBatchStatus = json_decode($batchStatusApi->getBody());

                $batch = CashboxBatches::wherebatchNo($request->batchNo)->first();
                $batch->batch_status = $getBatchStatus->status;
                $batch->save();
                return redirect()->back()->with('message', 'Action Successful');
            } else {
                Log::info($batchStatusApi->getStatusCode() . "Message: " . $batchStatusApi->getReasonPhrase());
                return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage : ' . $batchStatusApi->getReasonPhrase());
            }
        }catch (GuzzleException $exception){
            $responseApi = $exception->getResponse();
            Log::info($responseApi->getStatusCode() . "Message: " . $responseApi->getReasonPhrase() );
            return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage ! ' . $responseApi->getStatusCode() .  " : "  .$responseApi->getReasonPhrase());
        }
        // $this->fetchBatchWithdrawalsStatus($request->batchNo);
    }

    public function fetchCbBatchVendorsStatus(CbBatchNoValidationRequest $request){
        $batchNo = $request->batchNo;
        $batchVendors =  CashboxBatches::whereBatchNo($batchNo)->first();
        $vendorsArray = json_decode($batchVendors->vendors_ids);
        $vendorsArrayFromDB = [];
        foreach ($vendorsArray as $key => $value){
            $vendorsArrayFromDB[$key] = $value;
        }
        ## Send Request to fetch withdrawals status
        $endPoint =  "https://ewallet.b4uwallet.com/api/v2/peatio/public/batch_withdrawals_status";
        try {
            $client = new \GuzzleHttp\Client();
            $batchWitdrawalsStatusApi = $client->request('GET', $endPoint, ['query' => [
                'batch_number' => $batchNo,
            ]]);
            if($batchWitdrawalsStatusApi->getStatusCode() == 200){
                ## if request is validate then fetch status of withdrawals
                $getWithdrawalsStatus = json_decode($batchWitdrawalsStatusApi->getBody());
                $vendors = $getWithdrawalsStatus->withdraw_data;
                $vendorsArrayFromApi = [];
                foreach($vendors as $key => $value){
                    $vendor = explode('B',$value->w_id);
                    $vendor = explode('w',$vendor[1]);
                    $vendor = explode('d',$vendor[1]);

                    $vendorsArrayFromApi[$vendor[0]] = $value->status;
                }
                $updateDBArray = array_intersect_ukey($vendorsArrayFromApi,$vendorsArrayFromDB, function ($key1,$key2){
                    if ($key2 == $key1)
                        return 0;
                    elseif ($key2 > $key1)
                        return 1;
                    else
                        return -1;
                });
                $newVendorsArray = array_replace($vendorsArrayFromDB,$updateDBArray);

                $batch = CashboxBatches::wherebatchNo($batchNo)->first();
                $batch->vendors_count = count($newVendorsArray);
                $batch->vendors_ids = json_encode($newVendorsArray);
                $batch->is_uploaded = 2;
                $batch->save();
                return redirect()->back()->with('message', 'Action Successful');
            }
            else{
                Log::info($batchWitdrawalsStatusApi->getStatusCode() . "Message: " . $batchWitdrawalsStatusApi->getReasonPhrase() );
                return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage : ' . $batchWitdrawalsStatusApi->getReasonPhrase());
            }
        }catch (GuzzleException $exception){
            $responseApi = $exception->getResponse();
            Log::info($responseApi->getStatusCode() . "Message: " . $responseApi->getReasonPhrase() );
            return redirect()->back()->with('errormsg', 'Something went wrong!! Please try again. ErrorMessage ! ' . $responseApi->getStatusCode() .  " : "  .$responseApi->getReasonPhrase());
        }
    }

    public function updateVendorsStatusToDB(CbBatchIdValidationRequest $request){
        $batchId = $request->id;
        $batchWithdrawals =  CashboxBatches::find($batchId);
        $batchWithdrawals->is_uploaded = 3;
        $batchWithdrawals->save();
        $vendorsArray = json_decode($batchWithdrawals->vendors_ids);
        foreach ($vendorsArray as $key => $value) {
            $fee = CBDebits::select(DB::raw('SUM(fee) as fees'))->where('cb_vendor_id', $key)->get();
            $fee = $fee[0];
            $vendor = Vendors::find($key);
            $amount = $vendor->amount + $fee->fees;
            $cbAccounts = CBAccounts::where('user_id', $vendor->user_id)->where('currencies_id', $vendor->currency_id)->first();
            if ($value == 'Approved') {
                Vendors::updateStatus($key, $value);
                CBDebits::updateStatus($key, 'approved');
                $newlockedBalance = $cbAccounts->locked_balance - $amount;
            } else {
                CBDebits::updateStatus($key, 'failed');
                Vendors::updateStatus($key, 'Failed');
                $newlockedBalance = $cbAccounts->locked_balance - $amount;
                $newcurrentBalance = $cbAccounts->current_balance + $amount;
                $cbAccounts->current_balance = $newcurrentBalance;
            }
            $cbAccounts->locked_balance = $newlockedBalance;
            $cbAccounts->save();
        }
        return redirect()->back()->with('message', 'Action Successful');
    }
}
