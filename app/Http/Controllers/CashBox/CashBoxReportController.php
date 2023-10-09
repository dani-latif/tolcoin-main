<?php

namespace App\Http\Controllers\CashBox;

use App\CBAccounts;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashBox\CashBoxAccountIdUserBasedRequest;
use App\Http\Requests\Users\LoginToUserAccountRequest;
use App\User;
use Barryvdh\DomPDF\Facade as PDF;

class CashBoxReportController extends Controller
{
   public function __construct()
   {
   }
   public function userCashboxReport(LoginToUserAccountRequest $request){
       $user = User::find($request->user_id);
       $userAccount = $user->cashBoxAccounts()->first();
       if (empty($userAccount)) {
           return redirect()->back()->with('errormsg', 'No Cashbox account founded against this user');
       } else {
           return CBAccounts::getCbAccountReport($userAccount->id);
           }
   }
    public function fetchCBAccountReport(CashBoxAccountIdUserBasedRequest $request){
        return CBAccounts::getCbAccountReport($request->ac);
    }
    public function updateExpectedToCurrent(CashBoxAccountIdUserBasedRequest $request){
          $balance = CBAccounts::balanceCalculation($request->ac);
          $cbAccounts = CBAccounts::find($request->ac);
          $cbAccounts->current_balance = $balance->expected_current;
          $cbAccounts->locked_balance = $balance->expected_locked;
          $cbAccounts->is_defaulter = 0;
          $cbAccounts->save();

          return CBAccounts::getCbAccountReport($request->ac);
    }
    public function userCashboxReportPDF($CBAccount){
       $data = CBAccounts::getCbAccountReport($CBAccount,true);
        $pdf = PDF::loadView('cash_box.report', $data);
        return $pdf->download('userCashbox.pdf');
    }
}
