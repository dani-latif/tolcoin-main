<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OTPToken extends Model
{
    protected $table ="otp_tokens";
    
    const EXPIRATION_TIME = 15; // minutes

    protected $fillable = [
        'code',
        'user_id',
        'used'
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($attributes['code'])) {
            $attributes['code'] = $this->generateCode();
        }

        parent::__construct($attributes);
    }

    /**
     * Generate code
     *
     * @return string
     */
    public function generateCode()
    {
        $code = mt_rand(100000, 999999);

        return $code;
    }
    /**
     * True if the token is not used nor expired
     *
     * @return bool
     */
    public function isValid()
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    /**
     * Is the current token used
     *
     * @return bool
     */
    public function isUsed()
    {
        return $this->used;
    }

    /**
     * Is the current token expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->created_at->diffInMinutes(\Carbon\Carbon::now()) > static::EXPIRATION_TIME;
    }
}
