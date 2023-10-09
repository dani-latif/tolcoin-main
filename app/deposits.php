<?php

namespace App;

use App\Model\Percentage;
use App\Model\Rate;
use Illuminate\Database\Eloquent\Model;

class deposits extends Model
{
    protected $guarded = ['id'];
    
    static function getBalanceAttractiveFunds($user_id,$currency){
        return self::getActiveInvestment($user_id, $currency);
    }

    static function getActiveInvestment($user_id, $currency)
    {
        $sum_total_amount = deposits::where('user_id', $user_id)->where('currency', $currency)->where('status', 'Approved')->where('trans_type', 'NewInvestment')->sum('total_amount');
        return round($sum_total_amount, 2);
    }

    static function getActiveInvestmentList($user_id)
    {
        $activeInvestmentList = deposits::WhereIn('user_id', $user_id)
            ->whereIn('status', ['Approved', 'Sold'])
            ->where('trans_type', 'NewInvestment')
            ->orderBy('approved_at', 'asc')
            ->get();

        return $activeInvestmentList;
    }
	
	static function getBalanceSoldInvestment($user_id,$currency){
        return self::getSoldInvestment($user_id, $currency);
    }
	static function getSoldInvestment($user_id, $currency)
    {
        $sum_total_sold_amount = deposits::where('user_id', $user_id)->where('currency', $currency)->where('status', 'Sold')->where('trans_type', 'NewInvestment')->sum('total_amount');
        return round($sum_total_sold_amount, 2);
    }
	
	static function getBalanceReinvestment($user_id,$currency){
        return self::getActiveReinvestment($user_id, $currency);
    }
	static function getActiveReinvestment($user_id, $currency)
    {
        $sum_total_reinvest_amount = deposits::where('user_id', $user_id)->where('currency', $currency)->where('status', 'Approved')->where('trans_type', 'Reinvestment')->sum('total_amount');
        return round($sum_total_reinvest_amount, 2);
    }

    static function getBalanceAttractiveFundsOnlySold($user_id,$currency){

        $sum_total_amount = deposits::where('user_id',$user_id)->where('currency',$currency)->where('status','sold')->where('trans_type','NewInvestment')->sum('total_amount');
        $balaceAttrative =  round($sum_total_amount,2);

        //   echo "\ngetBalanceAttractiveFundsOnlySold($user_id,$currency)= ".$balaceAttrative;

        return $balaceAttrative;
    }

    public static function findOrUpdate($id, $data){
        $Model = new deposits();
        $Model->find($id)->update($data);
    }

    public function duser(){
    	return $this->belongsTo('App\users', 'user');
    }

    public function dplan(){
    	return $this->belongsTo('App\plans', 'plan');
    }
    function total_profit_new_system_to_usd(){
        $rate = Rate::last($this->currency);
     //   if($this->sold_at == null){
       //     $rate = Rate::at($this->sold_at,$this->currency);

        //}
        return round($this->total_profit_new_system * $rate,2);
    }
    function cal_total_profit_percetage_new_system(){
        if($this->approved_at == null){
            $this->approved_at = $this->created_at;
            $this->save();
        }
        if($this->sold_at != null){
            $this->total_profit_percetage_new_system = Percentage::sum_until_for_sold_new_system($this->approved_at,$this->sold_at);
        }else{
            $this->total_profit_percetage_new_system = Percentage::sum_until_now_new_system($this->approved_at);
        }
        $this->total_profit_new_system = $this->total_profit_percetage_new_system/100 * $this->amount;
        $this->save();

    }

    static function cal_total_profit_percetage_new_system_for_All_soldsForSpecificUser($user_id){
        deposits::whereNotNull('sold_at')->where('user_id',$user_id)->chunk(10000,function ($deposits){
            foreach($deposits as $deposit){
                $deposit->cal_total_profit_percetage_new_system();
            }
        });
        deposits::whereNull('sold_at')->where('user_id',$user_id)->where('status','Approved')->chunk(10000,function ($deposits){
            foreach($deposits as $deposit){
                $deposit->cal_total_profit_percetage_new_system();
            }
        });


    }

    static function newSystemDeposits($user_id){
        return deposits::whereRaw('user_id = ? and (status like ? or status like ?)',[$user_id,'Sold','Approved']);
    }


    static function correctRate(){
        deposits::select(\DB::raw(' date(created_at) as date,id,currency,amount'))->where('rate',0)->orWhere('total_amount',0)->chunk(100,function ($deposits) {
            foreach($deposits as $deposit){
                $deposit->rate =  \App\Model\Rate::at($deposit->date,$deposit->currency);

           $deposit->total_amount =round( $deposit->amount * $deposit->rate,2);

                $deposit->save();

            }
        });
    }
}
