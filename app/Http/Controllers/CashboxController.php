<?php

namespace App\Http\Controllers;

use App\CBAccounts;
use App\CBCredits;
use App\CBDebits;
use App\Exports\CBDebitsBatchesExport;
use App\Exports\CBDebitsExport;
use App\Exports\CBDebitsCryptoExport;
use App\Exports\CBDebitsMalayExport;
use App\Exports\CBDebitsNonPKExport;
use App\Exports\FaysalFTCBDebitsExport;
use App\fund_beneficiary;
use App\Http\Requests\CashBox\EditCashBoxAccountRequest;
use App\Http\Requests\CashBox\EditCreditDetailsRequest;
use App\Http\Requests\CashBox\EditDebitDetailsRequest;
use App\Http\Requests\CashBox\ViewCreditDetailsRequest;
use App\Http\Requests\CashBox\ViewDebitDetailsRequest;
use App\Http\Requests\RequestConstants;
use App\Http\Requests\Users\LoginToUserAccountRequest;
use App\Http\Requests\WithDrawals\ImportWithDrawlRequest;
use App\settings;
use App\User;
use http\Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CashboxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function importDebits(ImportWithDrawlRequest $request)
    {
        if ($request->request->get('fileurl')) {
            $content = file_get_contents($request->request->get('fileurl'));
            $lines = explode(PHP_EOL, $content);
            foreach ($lines as $line) {
                $referenceId = str_getcsv($line)[0];
                $cbDebitId = explode("cbd", $referenceId);
                if (isset($cbDebitId[1])) {
                    $cbDebit_Id = intval($cbDebitId[1]);
                    if($request->status == 'approved') {
                        CBDebits::approveAndDeduct($cbDebit_Id,$referenceId, 'deposited');
                    }
                    elseif($request->status == 'failed'){
                        CBDebits::failedAndDeduct($cbDebit_Id, 'failed');
                    }
                }
            }
            //return redirect()->back()->with('successmsg', 'Import ' . count($lines) . ' withdrawals Successfully');
            echo "</br> Import " . count($lines) . " cbDebits Successfully </br>
             <a href=" . url('dashboard/mCbDebits') . ">Go Back</a>";
        }
    }

    public function userCashbox(LoginToUserAccountRequest $request)
    {
        /** @var User $user */
        $user = User::find($request->user_id);
        $user->assignCashBoxAccountOfAllCurrencies();
        ## find users accounts.
        /** @var CBAccounts $userAccounts */
        $userAccounts = $user->cashBoxAccounts()->first();
        if (empty($userAccounts)) {
            ## if no account has attached to the user, then redirect user to no account attached page.
            return redirect()->back()->with('errormsg', 'No Cashbox account founded against this user');
        } else {
            ## current logged in user has attached to some accounts, so, system will redirect user to single account.
            $cash_box_accounts = $user->cashBoxAccounts()->get();
            $current_cash_box_account = $user->cashBoxAccounts()->where('id', $userAccounts->id)->first();
            if (!$current_cash_box_account instanceof CBAccounts) {
                ## If no CashBox account found then redirect user to Not CashBox found Page.
                return redirect()->back()->with('errormsg', 'No Cashbox account founded against this user');
            } else {

                $FundBenificary = fund_beneficiary::where('user_id', Auth::user()->id)
                    ->where('status', 0)
                    ->orderby('created_at', 'DESC')->get();
                ## If Cashbox account found then show him.
                return view('cash_box.index', [
                    'user' => $user,
                    'users_cash_box_accounts' => $cash_box_accounts,
                    'current_cash_box_account' => $current_cash_box_account,
                    'beneficiaries' => $FundBenificary
                ]);
            }
        }
    }

    public function editCbAccount(EditCashBoxAccountRequest $request)
    {
        CBAccounts::whereId($request->id)->update([
            'current_balance' => $request->current_balance,
            'locked_balance' => $request->locked_balance,
        ]);
        return redirect()->back()->with('successmsg', 'Record Updated Successfully');
    }

    public function cbDebitsBatchExport(\Illuminate\Http\Request $request)
    {
       $request->validate(['amount_limit' => RequestConstants::BatchLimitValidation]);
        $amountLimit =  $request->amount_limit;
        $rate_pkr = settings::pkrRate();
        $rate = $rate_pkr->rate_pkr;
        $cbDebits = CBDebits::topDownUsdDebitsBatchQuery($rate);
        $cbDebits = $cbDebits->get();
        if($cbDebits->count() == 0){
            return redirect()->back()->with('message','No Debits found');
        }
        $start = $cbDebits->first()->Reference_No;
        $w = $cbDebits;
        $end = $total = $offset = $i = 0;
        $file_num = 1;
        $total_amount = $w->SUM('amount');
        $total_files = (int)ceil($total_amount / $amountLimit);
        foreach ($cbDebits as $cbd) {
            $total += $cbd->amount;
            if ($total >= $amountLimit) {
                $end = $cbd->Reference_No;
                $filename = "CBDebitsBatch" . '_' . date("dmy") . '_' . $file_num;
                Excel::store(new CBDebitsBatchesExport($rate, $start, $end, $offset), "CBDebitsBatch/" . $filename . '.xlsx');
                $start = $cbd->Reference_No;
                $start = $start + 1;
                $offset = $i;
                $total = 0;
                $file_num++;
            } else {
                if ($file_num == $total_files) {
                    $filename = "CBDebitsBatch" . '_' . date("dmy") . '_' . $file_num;
                    Excel::store(new CBDebitsBatchesExport($rate, $start, $end, $offset), "CBDebitsBatch/" . $filename . '.xlsx');
                    $file_num++;
                }
            }
            $i++;
        }
        $zip_file = 'CBDebitsBatch.zip';
        $path = storage_path('app/CBDebitsBatch');
        self::zippAllFiles($zip_file, $path);
        Storage::deleteDirectory('CBDebitsBatch');
        return response()->download($zip_file)->deleteFileAfterSend(true);
    }
    ### CashBox Debits
    public function FaysalExportCbDebits($ft)
    {
        if($ft == 0) {
            $filename = urlencode("FaysalIBFT_CBDebits_" . date("d-m-Y"));
            return Excel::download(new CBDebitsExport($ft), $filename . '.xlsx');
        }elseif ($ft == 1){
            $filename = urlencode("FaysalFT_CBDebits_" . date("d-m-Y"));
            return Excel::download(new FaysalFTCBDebitsExport($ft), $filename . '.xlsx');
        }
    }

    public function ExportCryptoCbDebits()
    {
        $filename = urlencode("CBCryptoDebits" . '_' . date("d-m-Y"));
        return Excel::download(new CBDebitsCryptoExport, $filename . '.xlsx');
    }

    public function cbDebitsNonPKExport(){
        $filename = urlencode("CBNonPKDebits" . '_' . date("d-m-Y"));
        return Excel::download(new CBDebitsNonPKExport, $filename . '.xlsx');
    }

    public function cbDebitsMalayExport(){
        $filename = urlencode("CBMalayDebits" . '_' . date("d-m-Y"));
        return Excel::download(new CBDebitsMalayExport, $filename . '.xlsx');
    }

    public function verifyDebits()
    {
        try{
        CBDebits::where('status', 'inprocessing')->where('type','topdown')->update(['status' => 'verified']);
        return redirect()->back()->with('message', 'Action Successfully');
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Something went wrong."], 500);
        }
    }

    public function verifyDebit(ViewDebitDetailsRequest $request)
    {
        try{
        CBDebits::whereId($request->id)->where('status', 'pending')->update(['status' => 'verified']);
        return redirect()->back()->with('message', 'Action Successfully');
            } catch (Exception $e) {
        Log::error("something went wrong " . $e->getMessage());
        return response()->json(['message' => "Something went wrong."], 500);
        }
    }

    public function mCbDebits()
    {
        $cbDebits = CBDebits::select('status')->groupBy('status')->get();
        return view('admin/mCbDebits')->with(array('title' => 'CashBox Debits','cbDebits'=>$cbDebits));
    }
    public function sCbDebits($status)
    {
        $cbDebits = CBDebits::select('status')->groupBy('status')->get();
        return view('admin/sCbDebits')->with(array('title' => 'CashBox Debits', 'cbDebits' => $cbDebits, 'status' => $status));
    }
    public function mCbDebits_json()
    {
        $cbDebits = DB::table('cbDebitsView');
        return datatables()->of($cbDebits)->toJson();
    }
    public function sCbDebits_json(string $status = null)
    {
        $cbDebits = DB::table('cbDebitsView')->where('status','LIKE',$status);
        return datatables()->of($cbDebits)->toJson();
    }
    public function editDebitCb(ViewDebitDetailsRequest $request)
    {
        try {
            $cbDebit = CBDebits::find($request->id);
            if ($cbDebit) {
                return view("cash_box.partials._edit_debits_modal", compact('cbDebit'));
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

    public function updateDebitCb(EditDebitDetailsRequest $request)
    {
        try {
            CBDebits::whereId($request->id)->update([
                'amount' => $request->amount,
                'fee' => $request->fee,
                'type' => $request->type,
                'reason' => $request->reason,
            ]);
            return redirect()->back()->with('successmsg', 'Record Updated successfully');
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

    public function cancelDebitCb(ViewDebitDetailsRequest $request){
        try {
            $cbDebit = CBDebits::find($request->id);
            if ($cbDebit) {
                CBDebits::cancelDebit($cbDebit);
                return response()->json(['message' => "Record Delete Successfully"], 200);
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

    public function unDefaultDebitCb(ViewDebitDetailsRequest $request){
        try {
            $cbDebit = CBDebits::find($request->id);
            if ($cbDebit) {
                CBDebits::unDefaultDebit($cbDebit);
                return response()->json(['message' => "Record Updated Successfully"], 200);
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }


    public function failCreditCb(ViewCreditDetailsRequest $request){
        try {
            $cbCredit = CBCredits::find($request->id);
            if ($cbCredit) {
                $cbCredit->status = 'failed';
                $cbCredit->save();
                return response()->json(['message' => "Record Updated Successfully"], 200);
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Something went wrong."], 500);
        }
    }

    public function delDebitCb(ViewDebitDetailsRequest $request){
        try {
            $cbDebit = CBDebits::find($request->id);
            if ($cbDebit) {
                $cbDebit->delete();
                return response()->json(['message' => "Record Delete Successfully"], 200);
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

    ### CashBox Credits

    public function mCbCredits(){
        return view('admin/mCbCredits')->with(array('title' => 'CashBox Credits'));
    }

    public function mCbCredits_json()
    {
        return datatables()->of(CBCredits::get())->toJson();
    }

    public function editCreditCb(ViewCreditDetailsRequest $request){
        try {
            $cbCredit = CBCredits::find($request->id);
            if ($cbCredit) {
                return view("cash_box.partials._edit_credits_modal", compact('cbCredit'));
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Something went wrong."], 500);
        }
    }

    public function updateCreditCb(EditCreditDetailsRequest $request)
    {
        try {
            CBCredits::whereId($request->id)->update([
                'amount' => $request->amount,
                'fee' => $request->fee,
                'type' => $request->type,
                'reason' => $request->reason,
            ]);
            return redirect()->back()->with('successmsg', 'Record Updated successfully');
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

    public function delCreditCb(ViewCreditDetailsRequest $request){
        try {
            $cbCredit = CBCredits::find($request->id);
            if ($cbCredit) {
                $cbCredit->delete();
                return response()->json(['message' => "Record Delete Successfully"], 200);
            }
        } catch (Exception $e) {
            Log::error("something went wrong " . $e->getMessage());
            return response()->json(['message' => "Somethign went wrong."], 500);
        }
    }

}
