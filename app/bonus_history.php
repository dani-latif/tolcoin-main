<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class bonus_history extends Model
{
    public $table = "bonus_history";

    protected $fillable = [
    						'user_id',
    						'unique_id',
    						'plan_id',
    						'plan_name',
    						'bonus_total',
    						'maxlevel1',
    						'maxlevel2',
    						'maxlevel3',
    						'maxlevel4',
    						'maxlevel5',
                            'created_by',
                            'created_at'
    					];

    public function BonusUser()
    {
        return $this->belongsTo('App\users', 'created_by');
    } 

}