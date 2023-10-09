<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class daily_investment_bonus extends Model
{
 
    public $table = "daily_investment_bonus"; 

    public function parent(){
        return $this->belongsTo("App\users","parent_user_id","id");
    }

    public function user(){
        return $this->belongsTo("App\users","user_id","u_id");
    }

}