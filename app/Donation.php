<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;


class Donation extends Model
{
    protected $table = 'donations';
    protected $fillable = [
        'user_id',
        'user_u_id',
        'amount',
        'donation_type',
        'currency',
        'status',
        'payment_mode',
    ];
    public static $donation_types = [
        0 => 'Covid-19 Relief Fund',
        1 => 'Orphan Home',
        2 => 'Masjid',
        3 => 'Eid Qurban',
        4 => 'Needy People',
    ];
    public static $payment_modes = [
        0 => 'Bonus',
        1 => 'Profit',
        2 => 'Sold',
    ];


    public function user()
    {
        return $this->belongsTo('App\users', 'user_id');
    }

    public function getDonationtypeAttribute($value)
    {
        return isset(self::$donation_types[$value]) ? self::$donation_types[$value] : self::$donation_types[0];
    }
}
