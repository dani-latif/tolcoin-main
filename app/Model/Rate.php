<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected  $table= 'currency_rates';
    //
   static function last($currency_id){
       $currency_id = strtolower($currency_id);
       $rate = Rate::orderBy('created_at','desc')->first();
       return $rate->rate($currency_id);
   }
    static function at($date,$currency_id){
        $currency_id = strtolower($currency_id);
        $rate = Rate::whereRaw('date(created_at) <= ? and rate_btc != ?',[$date,0] )->orderBy('created_at','desc')->first();
        return $rate->rate($currency_id);

    }
    function rate($currency_id){
       $r = "rate_".$currency_id;
       return $this->$r;
    }
}
