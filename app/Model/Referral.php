<?php

namespace App\Model;

use App\Console\Commands\PlansCron;
use App\Currencies;
use App\deposits;
use App\Http\Controllers\User\PlanController;
use App\Jobs\ReferralImport;
use App\Jobs\ReferralUpdateToUsdAll;
use App\User;
use App\UserAccounts;
use App\bonus_history;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\AttractiveFunSyncUser;
use DB;

class Referral extends Model
{

    //
    static function updateToUsdAll()
    {
        //    ReferralUpdateToUsdAll::dispatch();
    }

    function updateValues()
    {
        //    $rate = Rate::orderBy('created_at', 'desc')->first();
        deposits::correctRate();
        $this->active_new_investment = deposits::getBalanceAttractiveFunds($this->child_id, $this->currency_id);
        $this->sold_new_investment   = deposits::getBalanceSoldInvestment($this->child_id, $this->currency_id);
        $this->active_reinvestment      = deposits::getBalanceReinvestment($this->child_id, $this->currency_id);
        $this->sold                  = deposits::getBalanceAttractiveFundsOnlySold($this->child_id, $this->currency_id);

        $this->save();

        PlansCron::process2(User::find($this->parent_id));
        PlansCron::process2(User::find($this->child_id));
    }

    static function import()
    {
        User::chunk(50, function ($users) {
            foreach ($users as $user) {
                ReferralImport::dispatch($user);
            }
        });
    }

    static function sync($user_id)
    {  
        $user = User::where('id', $user_id);
        if ($user->count()) {
            $user = $user->first();
            ReferralImport::dispatch($user);
        }
    }


    static function makeReferral(User $child)
    {
        $level = 1;
        $parent = User::where('u_id', $child->parent_id);
        while ($level <= 5 && $parent->count()) {
            $parent = $parent->first();
             
           // self::create1($child->id, $child->u_id, $parent->id, $parent->u_id, $level);
            $level = $level + 1;
            AttractiveFunSyncUser::dispatch_to_af($parent->id);
            $parent = User::where('u_id', $parent->parent_id);
        }
    }


    static function create1($child_id, $child_u_id, $parent_id, $parent_u_id, $level)
    {

        $userAccInfo = UserAccounts::where('user_id', $child_id);

        if ($userAccInfo->count()) {
            $userAccInfo = $userAccInfo->first();
            foreach (Currencies::all() as $currency) {

                // if (Referral::where('child_id', $child_id)->where('parent_id', $parent_id)->where('currency_id', $currency->small_code)->count() > 1) {
                //     Referral::where('child_id', $child_id)->where('parent_id', $parent_id)->where('currency_id', $currency->small_code)->delete();
                // }


                // $referral = Referral::where('child_id', $child_id)->where('parent_id', $parent_id)->where('currency_id', $currency->small_code);

                if (Referral::where('child_id', $child_id)->where('parent_id', $parent_id)->count() > 1) {
                    Referral::where('child_id', $child_id)->where('parent_id', $parent_id)->delete();
                }


                $referral = Referral::where('child_id', $child_id)->where('parent_id', $parent_id);
                if ($referral->count()) {
                    $referral = $referral->first();
                } else {
                    $referral = new Referral();
                }
                $referral->child_id = $child_id;
                $referral->child_u_id = $child_u_id;
                $referral->parent_id = $parent_id;
                $referral->parent_u_id = $parent_u_id;
                $referral->level = $level;
                // $referral->currency_id = $currency->small_code;

                $referral->save();
                $referral->updateValues();
            }
        }
    }


    static function totalAttractiveFundByLevel($parent_id, $level)
    {
       $data = Referral::select(\DB::raw("
            referrals.parent_u_id as parent_u_id, 
            referrals.child_id as child_id,
            referrals.level,
            round(sum(referrals_investments_summation.active_new_investment),2) as attractive_funds"))
            ->leftjoin('referrals_investments_summation', 'referrals.child_id', 'referrals_investments_summation.child_id')
            ->where('parent_id', $parent_id)
            ->where('level', $level)
            ->get();
        if(!empty($data))
            return $data[0]->attractive_funds;
        else
            return 0;


       // return round(Referral::where('parent_id', $parent_id)->where('level', $level)->sum('active_new_investment'), 2);
    }

    ///Plans

    static function BlueMoonPlanChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.plan', '=', 2)
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get(); 
    }

    static function AuroraPlanChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.plan', '=', 3)
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get(); 
    }

     static function CullinanPlanChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.plan', '=', 4)
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get(); 
    }


    ///Ranks

    static function SilverRankChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.rank', 'LIKE', 'Silver')
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get();
    }

    static function CoordinatorRankChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.rank', 'LIKE', 'Coordinator')
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get();
    }

     static function DiamondRankChilds($parent_id)
    {
        return User::join('referrals', 'users.u_id', '=', 'referrals.child_u_id')
            ->where('referrals.level', 1)
            ->where('referrals.parent_id', $parent_id)
            ->where('users.rank', 'LIKE', 'Diamond')
            ->where('referrals.created_at', '>=', '2019-07-01 00:00:00')
            ->groupBy('referrals.child_u_id')
            ->get();
    }

    static function totalSoldAttractiveFundByLevel($parent_id, $level)
    {
        $data = Referral::select(\DB::raw("
            referrals.parent_u_id as parent_u_id, 
            referrals.child_id as child_id,
            referrals.level,
            round(sum(referrals_investments_summation.sold),2) as sold"))
            ->leftjoin('referrals_investments_summation', 'referrals.child_id', 'referrals_investments_summation.child_id')
            ->where('parent_id', $parent_id)
            ->where('level', $level)
            ->get();
        if(!empty($data))
            return $data[0]->sold;
        else
            return 0;
    }

    static function totalAttractiveFund($parent_id)
    {
        $user = User::find($parent_id);
        return PlansCron::totalSubUsersInvestment2($parent_id, $user->plan);
    }

    static function referralByLevel($parent_id, $level)
    {
        // return Referral::select(\DB::raw("r.parent_id,r.child_id,r.parent_u_id,r.child_u_id, 
        // round(sum(rs.active_new_investment),2) as attractive_funds, 
        // round(sum(rs.active_reinvestment),2) as active_reinvestment from referrals r
        // inner join referrals_investments_summation rs ON rs.child_id = r.child_id
        // where parent_id=".$parent_id." and r.level <=".$level." group by r.parent_id,r.child_id,r.parent_u_id,r.child_u_id "));

        return Referral::select(\DB::raw("
            referrals.parent_u_id as parent_u_id, 
            referrals.child_id as child_id,
            referrals.level,
            round(sum(referrals_investments_summation.active_new_investment),2) as attractive_funds, 
            round(sum(referrals_investments_summation.active_reinvestment),2) as active_reinvestment"))
            ->leftjoin('referrals_investments_summation', 'referrals.child_id', 'referrals_investments_summation.child_id')
            ->where('parent_id', $parent_id)
            ->where('level', $level)
            ->groupBy('referrals.child_id')
            ->get();

        // return Referral::select(\DB::raw("*"))
        // ->where('parent_id', $parent_id)
        // ->where('level', $level)
        // ->groupBy('child_id')
        // ->get();

        // return Referral::select(\DB::raw("id,parent_id,child_id,parent_u_id,child_u_id, 
        // round(sum(active_new_investment),2) as attractive_funds,
        // round(sum(active_reinvestment),2) as active_reinvestment "))
        // ->where('parent_id', $parent_id)
        // ->where('level', $level)
        // ->groupBy('child_id')
        // ->get();
    }

    function child()
    {

        return $this->belongsTo('App\User', 'child_id', 'id');
    }

    function parent()
    {
        return $this->belongsTo('App\User', 'parent_id', 'id');
    }

    //get parent id partners 
    static function getPartners($parent_id)
    {
        return Referral::select("*")->where('parent_id', $parent_id)->groupBy('child_id')->get();
    }

    //calculate sub investments fund of childs
    static function calculateSubInvestments($id, $plan)
    {
        $level = Referral::plantolevel($plan);
        return DB::select("select sum(rs.active_new_investment) as amount from referrals r
        inner join referrals_investments_summation rs ON rs.child_id = r.child_id
        where r.parent_id = " . $id . " and r.level <= " . $level . "");
    }
    //PLAN TO LEVEL
    static function plantolevel($plan)
    {
        if ($plan == 1) {
            return 3;
        }
        if ($plan == 2) {
            return 3;
        }

        if ($plan >= 3) {
            return 5;
        }
    }
}
