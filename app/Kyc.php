<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Kyc extends Model
{
    protected $table = 'user_kyc';
    protected $uploadFilePath = 'public/';
    protected $fillable = [
        'user_id',
        'mother_name',
        'dob',
        'cnic',
        'passport',
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'dob'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected function uploadPath($filename)
    {
        return $this->uploadFilePath . $this->user()->first()->id . DIRECTORY_SEPARATOR . $filename;
    }

    public function getPassportImageAttribute()
    {
        return Storage::disk('gcs')->url($this->uploadPath($this->passport));
    }

    public function getCnicImageAttribute()
    {
        return Storage::disk('gcs')->url($this->uploadPath($this->cnic));
//        return Storage::disk('local')->exists($this->cnic) ? url('public/' . Storage::url($this->cnic)) : false;
    }
}
