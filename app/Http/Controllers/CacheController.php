<?php

namespace App\Http\Controllers;

use App\settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    ## update pkr cache
    public function updatePkrRateCache()
    {
        Cache::put('settings_rate_pkr', settings::select('rate_pkr')->where('id', '=', 1)->first());
        return back();
    }

    public function updateSettingsCache()
    {
        settings::updateSettingsCache(1);
        return back();
    }

    ## clear settings cache..
    public function clearSettingsCache()
    {
        Cache::pull('setting');
        return back();

    }

    ## clear settings rate pkr
    public function clearRatePkrSetting()
    {
        Cache::pull('settings_rate_pkr');
        return back();
    }

    ## refresh Investment plan cache.
    public function refreshInvestmentPlanCache(){
        settings::refreshMplanCache();
    }


    ## refresh Investment plan cache.
    public function clearInvestmentPlanCache(){
        settings::refreshMplanCache();
    }


    ## clear top investor cache.
    public function clearPromoInvestorCache(){
        settings::clearPromoInvestorCache();
    }



}
