<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserWithdrawRule extends Model
{
    protected $guarded = ['id'];
    public $timestamps = true;
    private $user_id;

    public function user()
    {
        return $this->belongsTo("App\User");
    }
}
