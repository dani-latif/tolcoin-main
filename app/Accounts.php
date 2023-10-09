<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Accounts extends Model
{

    protected $guarded = [];
	protected $table = 'useraccounts';
    public function user()
    {
        return $this->belongsToMany('App\User', 'user_id');
    }
 
}