<?php

namespace App;

use App\Model\Rate;
use Illuminate\Database\Eloquent\Model;

class UserAccounts extends Model
{



    protected $guarded = [];

    public static function findOrUpdate($id, $data){
        $Model = new UserAccounts();
        $Model->whereUserId($id)->update($data);
    }

    function old_system_balance($currency_small_code)
    {
        $c = "old_profit_" . $currency_small_code;
        return 0 + $this->$c;
    }

    function sold_balance($currency_small_code)
    {
        $total_sold_withdrawal_pending = (withdrawals::where('user',$this->user_id)->where('status','Pending')->where('payment_mode','Sold')->where('currency',strtoupper($currency_small_code))->sum('withdrawal_fee')) + (withdrawals::where('user',$this->user_id)->where('status','Pending')->where('payment_mode','Sold')->where('currency',strtoupper($currency_small_code))->sum('amount'));
        $c = "sold_bal_" . $currency_small_code;
        return 0 + $this->$c  +$total_sold_withdrawal_pending;
    }
    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }


    function updateNewProfit(){
        deposits::cal_total_profit_percetage_new_system_for_All_soldsForSpecificUser($this->user_id);
        foreach(Currencies::all() as $curr){
            $column = "new_profit_".$curr->small_code;

            $this->$column = deposits::where('user_id',$this->user_id)->where('currency',$curr->small_code)->sum('total_profit_new_system');

        }
        $this->save();

    }

    function updateNewWithdrawal(){
        foreach(Currencies::all() as $curr){
            $column = "new_withdrawal_".$curr->small_code;
            $this->$column =    withdrawals::newSystemWithdrawalTotal($this->user_id ,$curr->small_code);

        }
        $this->save();

    }

    function newProfitWithdrawalTotalToUsd(){
       $sum = 0;
        foreach(Currencies::all() as $curr){
            $column = "new_withdrawal_".$curr->small_code;
            $sum = $sum + $this->$column  *  $rate = Rate::last($curr->small_code);

        }
        return round($sum,2);
    }

    function newDepositProfitTotalToUsd(){
        $sum = 0;
        deposits::where('user_id',$this->user_id)->chunk(20,function ($deposits) use (&$sum){
            foreach($deposits as $deposit){
                $sum = $sum + $deposit->total_profit_new_system_to_usd();
            }

        });



        return round($sum,2);
    }
    function oldDepositProfitTotalToUsd(){
        $sum = 0;

        foreach(Currencies::all() as $curr){
            $column = "old_profit_".$curr->small_code;
            $sum = $sum + (Rate::last($curr->small_code)* $this->$column);
        }


        return round($sum,2);
    }


    static function updateNewForSpecific($user_id){
        $userAccount = UserAccounts::where('user_id',$user_id);
        if($userAccount->count()){
            $userAccount = $userAccount->first();

            $userAccount->updateNewProfit();
            $userAccount->updateNewWithdrawal();
            $userAccount->updateFProit();
            return $userAccount;
        }

    }
    function updateFProit(){
        foreach(Currencies::all() as $curr){
            $f_profit = "f_profit_".$curr->small_code;
            $old_profit = "old_profit_".$curr->small_code;
            $new_withdrawal = "new_withdrawal_".$curr->small_code;
            $new_profit = "new_profit_".$curr->small_code;

            $this->$f_profit =  $this->$new_profit - $this->$new_withdrawal +  $this->$old_profit;
            $this->save();
        }
    }
    static function updateNewForAll(){

        UserAccounts::chunk(100,function($userAccounts){
            foreach($userAccounts as $userAccount){

                $userAccount->updateNewProfit();
                $userAccount->updateNewWithdrawal();
                $userAccount->updateFProit();
            }
        });
    }
}
