<?php

namespace App\Model;

use App\Console\Commands\PlansCron;
use App\deposits;
use App\Jobs\AttractiveFunSyncUser;
use App\User;
use App\users;
use App\Model\Referral;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AttractiveFunds extends Model
{

    const InitialLevelsList = [
        1, 2, 3
    ];

    const supperLevelsList = [
        4, 5
    ];


    //
    static function syncForAllUser()
    {
        User::orderBy('id', 'asc')->chunk(10, function ($users) {
            foreach ($users as $user) {
                AttractiveFunSyncUser::dispatch_to_af($user->id);
            }
        });
    }

    static function sync($user_id)
    {
        try {
            //   echo "\nuser_id = $user_id\n";
            for ($i = 1; $i <= 5; $i++) {
                self::createOrUpdate($user_id, $i);
            }
            // PlansCron::process2(User::find($user_id));

        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /*
        static function syncForMaxWithSoldlevelWise($user_id,$level){

            $i = $level;
            $att1 = Referral::totalAttractiveFundByLevel($user_id,$i);
            echo "\nAttractiveFundsByLevel($user_id,$i)= ".$att1;
            $att2 = Referral::calAttractiveFundWithSolds($user_id,$i);
            echo "\ncalAttractiveFundWithSolds($user_id,$i)= ".$att2;
            echo "\n";
            self::createOrUpdateWithSpecificMax($user_id, $i, $att1,$att2);

        }
    */

    static function createOrUpdate($user_id, $level)
    {
        $af = AttractiveFunds::where('user_id', $user_id)->where('level', $level);
        if ($af->count()) {
            $af = $af->first();
        } else {
            $af = new AttractiveFunds();
            $af->max_amount = 0;
        }
        $amount = Referral::totalAttractiveFundByLevel($user_id, $level);
        $sold = Referral::totalSoldAttractiveFundByLevel($user_id, $level);
        $af->user_id = $user_id;
        $af->amount = $amount;

        $af->level = $level;
        $proposed_max_amount = $amount + $sold;
        if ($af->max_amount < $proposed_max_amount) {
            $af->max_amount = $proposed_max_amount;

        }
        $af->save();
    }

    static function createOrUpdateWithSpecificMax($user_id, $level, $amount, $max)
    {
        $af = AttractiveFunds::where('user_id', $user_id)->where('level', $level);
        if ($af->count()) {
            $af = $af->first();
        } else {
            $af = new AttractiveFunds();
        }

        $af->user_id = $user_id;
        $af->amount = $amount;
        $af->level = $level;
        $af->max_amount = $max;
        $af->save();
    }

    static function totalAttractiveFundByLevel($user_id, $level)
    {
        $af = AttractiveFunds::where('user_id', $user_id)->where('level', $level);
        if ($af->count()) {
            $af = $af->first();
            return $af->amount;
        }
        return 0;
    }

    static function totalAttractiveFundByLevelRecursiveSum($user_id, $level)
    {
        return AttractiveFunds::where('user_id', $user_id)->where('level', '<=', $level)->sum('amount');
    }

    static function totalMaxAttractiveFundByLevel($user_id, $level)
    {
        $af = AttractiveFunds::where('user_id', $user_id)->where('level', $level);
        if ($af->count()) {
            $af = $af->first();
            return $af->max_amount;
        }
        return 0;
    }

    static function listCurrentTop50Users()
    {
        $q = "SELECT user_id,round(sum(amount)) as attractive_funds FROM `attractive_funds` GROUP by user_id ORDER BY `attractive_funds` DESC  limit 0 ,100";
        return \DB::select($q);
    }

    static function listMaxTop50Users()
    {
        $q = "SELECT user_id,round(sum(max_amount)) as attractive_funds FROM `attractive_funds` GROUP by user_id ORDER BY `attractive_funds` DESC  limit 0 ,50";
        return \DB::select($q);
    }

    static function sumAllLevelMinAmountAttractiveFunds($user_id = null)
    {
        $userLevels = self::InitialLevelsList;

        if (!$user_id) {
            $user_id = Auth::user()->id;
        }

        if (Auth::user()->plan >= 3) {
            $userLevels = array_merge(self::InitialLevelsList, self::supperLevelsList);
        }

        $af = (float)AttractiveFunds::where('user_id', $user_id)->whereIn('level', $userLevels)->sum('amount');
        return '$' . number_format($af, 2);
    }
}
