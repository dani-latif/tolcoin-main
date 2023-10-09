<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class settings extends Model
{

    ## 4 hours
    const minCacheTimeOut = 14400;
    ## 8 hours
    const midCacheTimeOut = 28800;
    ## 12 hours
    const TopCacheTimeOut = 43200;

    public static function pkrRate()
    {
        return Cache::remember('settings_rate_pkr', self::TopCacheTimeOut, function () {
            return settings::select('rate_pkr')->where('id', '=', 1)->first();
        });
    }

    public static function getSettings()
    {
        return Cache::remember('setting', self::TopCacheTimeOut, function () {
            return settings::where('id', '=', '1')->first();
        });
    }

    ## update cache in settings.
    private function updateSettingsInCache($settingId = 1)
    {
        return Cache::put('setting', settings::find($settingId));
    }

    ## update pkr cache
    private function updatePkrRateCache()
    {
        return Cache::put('settings_rate_pkr', settings::select('rate_pkr')->where('id', '=', 1)->first());
    }

    public static function updateSettingsCache($settingId)
    {
        (new self)->updateSettingsInCache($settingId);
        (new self)->updatePkrRateCache();
    }

    ## clear settings cache..
    public static function clearSettingsCache()
    {
        Cache::pull('setting');
        return true;
    }

    ## clear settings rate pkr
    public static function clearRatePkrSetting()
    {
        Cache::pull('settings_rate_pkr');
        return true;
    }

    ## delete plan cache.
    public static function clearMplanCache()
    {
        Cache::pull('mplansCache');
        return true;
    }

    ## delete plan cache.
    public static function refreshMplanCache()
    {
        self::clearMplanCache();
        $mPlanCache = \Illuminate\Support\Facades\DB::table('plans')
            ->join('referal_investment_bonus_rules', 'referal_investment_bonus_rules.plan_id', '=', 'plans.id')
            ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
            ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();

        \Illuminate\Support\Facades\Cache::put('mplansCache', $mPlanCache);
        return true;
    }

    ## is allowed b4uglobal for admins??
    public static function isPanelAllowedForAdmins($host)
    {
        $isAllowed = true;

        if (env('is_allowed_for_long_ip', '0') == 1) {
            return $isAllowed;
        }

        $user = Auth::user();
        ## non admin type
        $nonAdminsArrayType = [
            0, 4
        ];

        $userType = (int)$user->type;

        if (env('APP_ENV', 'Production') == 'local') {
            ## both site are enabled for both users.
            return $isAllowed;
        } else if ($host == 'b4uglobal.com') {
            ## User enable for b4uglobal.com
            if (((!in_array($userType, $nonAdminsArrayType)) || ($user->is_super_admin == 1))) {
                session()->flash('message', 'Please contact to Developer');
                auth()->logout();
                $isAllowed = false;
            }
        } elseif ($host == 'admin.b4uglobal.com') {
            $isAllowed = false;
//            if ($user->is_super_admin == 1) {
//                return true;
//            }
//            if (in_array($userType, $n//////////////onAdminsArrayType)) {
//                session()->flash('message', 'Please contact to Developer');
//                auth()->logout();
//                $isAllowed = false;
//            }

        } else {


            /*if (((!in_array($userType, $nonAdminsArrayType)) || ($user->is_super_admin == 1))) {
                session()->flash('message', 'You are not allowed to login, Please contact to Developer');
                auth()->logout();
                $isAllowed = false;
            }else{

            }   */
            $isAllowed = true;

        }

        return $isAllowed;
    }

    ## check is user is admin?
    public static function isAdmin()
    {
        $isAdmin = false;
        $user = Auth::user();
        $adminsArrayType = [
            0, 4
        ];

        if ($user) {
            if (((!in_array($user->type, $adminsArrayType)) || ($user->is_super_admin == 1))) {
                $isAdmin = true;
            }
        }

        return $isAdmin;
    }

    ## clear promo investor cache
    public static function clearPromoInvestorCache()
    {
        Cache::pull('promo_investor');
        return true;
    }

}
