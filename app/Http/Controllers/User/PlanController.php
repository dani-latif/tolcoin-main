<?php

namespace App\Http\Controllers\User;

use App\Console\Commands\PlansCron;
use App\Model\AttractiveFunds;
use App\plans;
use App\users;
use App\Http\Controllers\Controller2;
use Illuminate\Support\Facades\Auth;
use App\Model\Referral;

class PlanController extends Controller2
{
    public function plan_json2()
    {
        $user = Auth::user();
        $partners = Referral::getPartners($user->id);


        //commented by mudassar
        // PlansCron::process2(users::findOrFail(Auth::user()->id));
        $r = [];
//        $r['total_attractive_funds'] = intval(Referral::calculateSubInvestments($user->id, $user->plan)[0]->amount);//round(PlansCron::totalSubUsersInvestment2($user->id, $user->plan));
        $r['total_attractive_funds'] = AttractiveFunds::sumAllLevelMinAmountAttractiveFunds();


        $r['count_total_partners'] = $partners->count();//intval(PlansCron::countSubUsers2($user->id, $user->plan));
        $r['plan_name'] = $user->dplan->name;//plans::findOrFail($user->plan)->name;
        return $r;
    }

    public function plan_json()
    {
        ini_set('max_execution_time', 3000);

        PlansCron::process(users::findOrFail(Auth::user()->id));
        $user = users::findOrFail(Auth::user()->id);
        $r = [];
        $r['total_attractive_funds'] = round(PlansCron::totalSubUsersInvestment($user->id, $user->plan));
        $r['count_total_partners'] = intval(PlansCron::countSubUsers($user->id, $user->plan));
        $r['plan_name'] = plans::findOrFail($user->plan)->name;

        return $r;
    }
}
