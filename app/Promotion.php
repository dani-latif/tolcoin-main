<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $guarded = ['id'];
    public $timestamps = true;
    protected $table = 'promotions';
    protected $fillable = ['promotion_name','start_date','end_date','min_amount_limit'];

    ///on save/update set some default values
    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->user_id = \Auth::user()->id;
        });
    }
}
