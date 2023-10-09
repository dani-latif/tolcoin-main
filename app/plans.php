<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class plans extends Model
{
 
    public function ribonus(){
        //referal_investment_bonus
    	return $this->belongsTo('App\ref_investment_bonus', 'referal_investment_bonus');
    }
    public function rpbonus(){
        //referal_profit_bonus
    	return $this->belongsTo('App\ref_profit_bonus', 'referal_profit_bonus');
    }

}
