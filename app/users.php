<?php

namespace App;

use App\Http\Controllers\UsersController;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\ParameterBag;

class users extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    /*
    protected $fillable = [
        'name', 'email', 'password', 'u_id', 'parent_id', 'plan',
    ];

    */
    protected $fillable = [
        'email'
    ];
    protected $dates = ['created_at', 'updated_at'];


    public static function getUserDetailsFromCallCenter(int $userId)
    {
        $endPoint = "searchUser";
        $endUrl = config('b4uglobal.CallCenterService') . $endPoint;
        $payLoad = [
            'user_id' =>  $userId,
            'api_token' => "ksand23jkr4o#$@sdn#nasd"
        ];
        try {
              $result = Http::post($endUrl,$payLoad);
              if ($result->status() != 200 && !is_null($result)) {
                      $response = [
                          'myMessage' => "Something went wrong in Api",
                          'status' => $result->status(),
                          'message' => $result->json()
                      ];
                      Log::info('message', $response);
                  return false;
              }
                  return $result;
        }catch (\Exception $exception){
            return false;
        }
    }

    public function gh()
    {
        return $this->hasMany('App\gh', 'donation_from');
    }

    public function ruser()
    {
        return $this->hasMany('App\gh', 'donation_to');
    }

    public function dp()
    {
        return $this->hasMany('App\deposits', 'user');
    }

    public function wd()
    {
        return $this->hasMany('App\withdrawals', 'user');
    }

    public function dplan()
    {
        return $this->belongsTo('App\plans', 'plan');
    }

    public function sd()
    {
        return $this->belongsTo('App\solds', 'user');
    }

    public function BonusHistory()
    {
        return $this->hasMany('App\bonus_history', 'created_by');
    }

    public function verifiedBy()
    {
        return $this->hasOne("App\admin_logs", 'user_id');
    }

    /**
     * Get the user's phone number.
     *
     * @param string $value
     * @return string
     */
    public function getPhoneNoAttribute($value)
    {
        $value = str_replace('+920', '+923', $value);
        $value = str_replace('03', '+923', $value);
        $value = str_replace(' ', '', $value);
        $value = str_replace('+920092', '+92', $value);

        return $value;
    }

    /**
     * set the user's phone number.
     *
     * @param string $value
     * @return string
     */
    public function setPhoneNoAttribute($value)
    {
        $value = str_replace('+920', '+923', $value);
        $value = str_replace('03', '+923', $value);
        $value = str_replace(' ', '', $value);
        $value = str_replace('+920092', '+92', $value);
        $this->attributes['phone_no'] = $value;
    }

    public static function getUserDetails($userId)
    {
        return User::find($userId);
    }

}
