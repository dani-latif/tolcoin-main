<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixDepositHistory extends Model
{
    protected $guarded = ['id'];
    public $timestamps = true;

    public function deposit(){
        
        return $this->belongsTo('App\deposits', 'deposit_id');
    }

    public function user(){
        
        return $this->belongsTo('App\user', 'user_id');
    }
}
