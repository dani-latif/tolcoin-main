<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class solds extends Model
{

    public function dsold(){
    	return $this->belongsTo('App\users', 'user');
    }
 
}