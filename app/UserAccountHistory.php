<?php

namespace App;
use App\User;
use Illuminate\Database\Eloquent\Model;

class UserAccountHistory extends Model
{
    protected $guarded = ['id'];
    public $timestamps = true;
    
    public function user(){
        return $this->belongsTo("App\User");
    }

}
