<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPromotion extends Model
{
    protected $guarded = ['id'];
    public $timestamps = true;

    ///belongs to the user who got the promotion offer
    public function user(){
        return $this->belongsTo(User::class);
    }
}
