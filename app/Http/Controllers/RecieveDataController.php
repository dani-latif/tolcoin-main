<?php

namespace App\Http\Controllers;

use App\Jobs\PreviousReportRecieve;
use App\Model\Import\PreviousReport;
use App\User;
use Illuminate\Http\Request;

class RecieveDataController extends Controller
{
    public function index($password, $login_id, $type, $earning, $reinvest, $deduction, $withdrawal, $balance)
    {
        if ($password == '1234515555555') {
            $pre_report = PreviousReport::where('login_id', $login_id)->where('type', $type);
            if ($pre_report->count()) {
                $pre_report = $pre_report->first();
            } else {
                $pre_report = new PreviousReport();
            }
            $pre_report->login_id = $login_id;
            $user = User::where('u_id', $login_id);
            if ($user->count()) {
                $user = $user->first();
                $pre_report->user_id = $user->id;
            }
            $pre_report->type =  $type;
            $pre_report->earning =  $earning;

            $pre_report->reinvest=  $reinvest;
            $pre_report->deduction=  $deduction;
            $pre_report->withdrawal=  $withdrawal;
            $pre_report->balance=  $balance;

            $pre_report->save();
        }
    }
    //
}
