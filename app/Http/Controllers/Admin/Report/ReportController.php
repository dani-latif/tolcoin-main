<?php

namespace App\Http\Controllers\Admin\Report;

use App\admin_logs;
use App\deposits;
use App\User;
use App\UserAccounts;
use App\users;
use App\withdrawals;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('admin');
    }
    public function index(Request $request)
    {
        $user = User::where('u_id', $request->b4uid);
        if ($user->count()) {
            $user = $user->first();
            $user_account =  UserAccounts::updateNewForSpecific($user->id);
            $newWithdrawals = withdrawals::newSystemWithdrawals($user->id);
            $newDeposits = deposits::newSystemDeposits($user->id);
            //->where('approved_at','>','2018-12-15');
            $iframe = $request->iframe;
            $b4uid = $request->b4uid;
            $r  = compact('user_account', 'newWithdrawals', 'newDeposits', 'user', 'iframe', 'b4uid');
            return view('admin.report.index', $r);
        }
    }

    public function agentsReport(){
        $agents = users::whereNotIn('type',[0,4,302,307])->orderBy('type')->orderBy('name')->get();
         return view('admin.report.agents',compact('agents'));
    }
    public function fetchAgentReport(Request $request){
        $from = date("Y-m-d 00:00:00", strtotime($request['from_date']));
        $to = date("Y-m-d 23:59:59", strtotime($request['to_date']));
        $agentId = $request['userId'];
        $agentDetail = users::whereId($agentId)->first();
        $approvedDeposits = admin_logs::leftJoin('deposits','admin_logs.trade_id','=','deposits.id')->select('trade_id','admin_logs.created_at','unique_id')->where('admin_id',$agentId)->whereBetween('admin_logs.created_at', [$from, $to])->where('event','LIKE','User Deposit Approved')->get();
        $verifiedWithdrawals = withdrawals::select('id','verified_at','unique_id')->where('is_verify',1)->where('verified_by',$agentDetail->u_id)->whereBetween('verified_at', [$from, $to])->get();
        $approvedWithdrawals = withdrawals::select('id','approved_at','unique_id')->where('status','LIKE','Approved')->where('approved_by',$agentDetail->u_id)->whereBetween('approved_at', [$from, $to])->get();
        $verifiedUsers = admin_logs::select('user_id','created_at')->where('admin_id',$agentDetail->u_id)->whereBetween('created_at', [$from, $to])->where('event','LIKE','User Verified')->get();
        $isResult = true;

        $agents = users::whereNotIn('type',[0,4,302,307])->orderBy('type')->orderBy('name')->get();
        return view('admin.report.agents',compact('approvedDeposits','verifiedUsers','verifiedWithdrawals','approvedWithdrawals','isResult','agents','agentDetail'));
    }
}
