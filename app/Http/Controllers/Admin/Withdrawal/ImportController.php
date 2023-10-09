<?php

namespace App\Http\Controllers\Admin\Withdrawal;

use App\Http\Requests\SaveBatchRequest;
use App\Http\Requests\WithDrawals\ImportWithDrawlRequest;
use App\withdrawals;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImportController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($id)
    {
        $settings = site_settings();
        return view('admin.withdrawal.import.index', compact('settings','id'));
    }

    public function approve(ImportWithDrawlRequest $request)
    {
        if ($request->request->get('fileurl')) {
            $content = file_get_contents($request->request->get('fileurl'));
            $lines = explode(PHP_EOL, $content);
            foreach ($lines as $line) {
                $referenceId = str_getcsv($line)[0];
                $withdrawalId = explode("w", $referenceId);
                //  $withdrawalId = explode("d", $withdrawalId[1]);
                if (isset($withdrawalId[1])) {
                    $withdrawal_Id = intval($withdrawalId[1]);

                    withdrawals::approveAndPaid($withdrawal_Id, $referenceId,'Approved');
                    //echo "Import w-".$withdrawalId." withdrawals Successfully";
                }
            }

            //return redirect()->back()->with('successmsg', 'Import ' . count($lines) . ' withdrawals Successfully');
            echo "</br> Import " . count($lines) . " withdrawals Successfully </br>
             <a href=" . url('dashboard/awithdrawals') . ">Go Back</a>";

        }
    }

    public function saveBatchImport(SaveBatchRequest $request)
    {
        return withdrawals::importApprovedWithdrawalFromB4UWallet($request->fileurl, $request->batch_no);
    }
}
