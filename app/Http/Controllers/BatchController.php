<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchValidationRequest;
use App\Http\Requests\BatchWithdrawalRequest;
use App\withdrawals;
use App\Withdrawals\WithdrawalBatches;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function createBatch()
    {
        $withdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->select('users.btc_address', 'users.eth_address', 'users.bch_address', 'users.dash_address', 'users.ltc_address', 'users.xrp_address', 'users.zec_address', 'users.name', 'users.account_no',
                DB::raw("withdrawals.id AS Reference_No"),
                'withdrawals.amount', 'withdrawals.usd_amount', 'withdrawals.currency',
                'withdrawals.unique_id')
            ->where('users.type', '!=', 4)
            ->where('withdrawals.status', 'LIKE', 'Pending')
            ->where('withdrawals.currency', 'NOT LIKE', 'USD')
            ->where('withdrawals.is_paid', '=', 0)
            ->where('withdrawals.is_verify', '=', 1)
            ->where('withdrawals.is_manual_cancel', '!=', 3)
            ->whereNull('withdrawals.fund_type')
            ->whereNull('withdrawals.batch_id')
            ->groupBy('withdrawals.id')->get();
        if($withdrawals->count() == 0){
            return redirect()->back()->with('errormsg', 'No withdrawals available for batch');
        }
        $withdrawalsArray = [];
        $withdrawalBatch = WithdrawalBatches::createNewBatch(0, []);
        if (!$withdrawalBatch['status'] || !$withdrawalBatch['batch'] instanceof WithdrawalBatches) {
            return $withdrawalBatch;
        } else {
            /** @var WithdrawalBatches $withdrawalBatch */
            $withdrawalBatch = $withdrawalBatch['batch'];
        }
        foreach ($withdrawals as $w) {
            $curr = strtolower($w->currency);
            $curr_address = $curr . '_address';
            $crypto_address = (string)$w->$curr_address;
            if (!empty($crypto_address)) {
                $withdrawalsArray[$w->Reference_No] = 'Pending';
                $withdrawalModel = withdrawals::find($w->Reference_No);
                if ($withdrawalModel instanceof withdrawals) {
                    $withdrawalModel->batch_id = $withdrawalBatch->id;
                    $withdrawalModel->save();
                }
            }
        }
        WithdrawalBatches::updateBatch(0, $withdrawalsArray, $withdrawalBatch->id);

        return redirect()->back()->with('message', 'Batch Created Successfully');
    }

    public function uploadBatch(BatchWithdrawalRequest $request)
    {
        $batchId = $request->id;
        $allWithdrawals = withdrawals::join('users', 'users.id', '=', 'withdrawals.user')
            ->select('users.btc_address', 'users.eth_address', 'users.bch_address', 'users.dash_address', 'users.ltc_address', 'users.xrp_address', 'users.zec_address', 'users.name', 'users.account_no',
                DB::raw("withdrawals.id AS Reference_No"),
                'withdrawals.amount', 'withdrawals.usd_amount', 'withdrawals.currency', 'withdrawals.batch_id',
                'withdrawals.unique_id')->whereBatchId($batchId)->get();
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
        foreach ($allWithdrawals as $withdrawals) {
            $date = date("dmy");
            $prestr = "B" . $withdrawals->batch_id;
            $poststr = "d" . $date;
            $refernceno = $prestr . "w" . $withdrawals->Reference_No . $poststr;

            $refernceno = (string)$refernceno;
            $name = (string)$withdrawals->name;
            $unique_id = (string)$withdrawals->unique_id;
            $curr = strtolower($withdrawals->currency);
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
                    $withdrawals->currency,
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

                    $batch = WithdrawalBatches::whereId($batchId)->first();
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

    public function fetchBatchStatus(BatchValidationRequest $request){
          $endPoint =  "https://ewallet.b4uwallet.com/api/v2/peatio/public/batch_status";
        try {
            $client = new \GuzzleHttp\Client();
            $batchStatusApi = $client->request('GET', $endPoint, ['query' => [
                'batch_number' => $request->batchNo,
            ]]);
            if ($batchStatusApi->getStatusCode() == 200) {
                $getBatchStatus = json_decode($batchStatusApi->getBody());

                $batch = WithdrawalBatches::wherebatchNo($request->batchNo)->first();
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

    public function fetchBatchWithdrawalsStatus(BatchValidationRequest $request){
        $batchNo = $request->batchNo;
        $batchWithdrawals =  WithdrawalBatches::whereBatchNo($batchNo)->first();
        $withdrawalsArray = json_decode($batchWithdrawals->withdrawal_ids);
        $withdrawalsArrayFromDB = [];
        foreach ($withdrawalsArray as $key => $value){
            $withdrawalsArrayFromDB[$key] = $value;
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
                $withdrawals = $getWithdrawalsStatus->withdraw_data;
                $withdrawalsArrayFromApi = [];
                foreach($withdrawals as $key => $value){
                    $withdrawal = explode('B',$value->w_id);
                    $withdrawal = explode('w',$withdrawal[1]);
                    $withdrawal = explode('d',$withdrawal[1]);

                    $withdrawalsArrayFromApi[$withdrawal[0]] = $value->status;
                }
                    $updateDBArray = array_intersect_ukey($withdrawalsArrayFromApi,$withdrawalsArrayFromDB, function ($key1,$key2){
                        if ($key2 == $key1)
                            return 0;
                        elseif ($key2 > $key1)
                            return 1;
                        else
                            return -1;
                    });
            $newWithdrawalsArray = array_replace($withdrawalsArrayFromDB,$updateDBArray);

            $batch = WithdrawalBatches::wherebatchNo($batchNo)->first();
            $batch->withdrawal_count = count($newWithdrawalsArray);
            $batch->withdrawal_ids = json_encode($newWithdrawalsArray);
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

    public function updateWithdrawalsStatusToDB(BatchValidationRequest $request){
        $batchNo = $request->batchNo;
        $batchWithdrawals =  WithdrawalBatches::whereBatchNo($batchNo)->first();
        $batchWithdrawals->is_uploaded = 3;
        $batchWithdrawals->save();
        $withdrawalsArray = json_decode($batchWithdrawals->withdrawal_ids);
        foreach ($withdrawalsArray as $key => $value){
                if (strtolower($value) == 'approved' || strtolower($value) == 'confirming' ){
                    withdrawals::approveAndPaid($key, NULL);
                }else{
                    withdrawals::approveAndPaid($key, NULL,'Pending');
                }
            }
        return redirect()->back()->with('message', 'Action Successful');
    }

    public function withdrawalsBatches()
    {
        return view('withdrawal_batches', ['title' => 'Withdrawal Batches']);
    }

    public function withdrawalsBatches_json()
    {
        return datatables()->of(WithdrawalBatches::get())->toJson();
    }

    public function batchWithdrawals(BatchWithdrawalRequest $request)
    {
        return view('batch_withdrawals', [
            'title' => 'Withdrawal Batches',
            'batchId' => $request->id,
            'batch' => WithdrawalBatches::whereId($request->id)->first(),
        ]);
    }

    public function batchWithdrawals_json(BatchWithdrawalRequest $request)
    {
        return datatables()->of(withdrawals::whereBatchId($request->id)->get())->toJson();
    }

}
