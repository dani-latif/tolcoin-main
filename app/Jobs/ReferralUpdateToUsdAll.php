<?php

namespace App\Jobs;

use App\Currencies;
use App\Model\AttractiveFunds;
use App\Model\Rate;
use App\Model\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ReferralUpdateToUsdAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       // self::updateToUsdAll();
       // AttractiveFunds::syncForAllUser();
        //
    }
    static function updateToUsdAll()
    {

        $rate = Rate::orderBy('created_at', 'desc')->first();

        foreach (Currencies::all() as $currency) {
            $c = $currency->small_code;
            $r = $rate->rate($c);
     //       \DB::select("UPDATE `referrals` SET `to_usd` = balance * $r where currency_id = '$c' ");

        }

    }
}
