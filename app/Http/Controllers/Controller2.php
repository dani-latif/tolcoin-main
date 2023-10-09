<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\confirmations;

use DB;

use Mail;

class Controller2 extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

 
    public function __construct()
    {
                  

        // $this->middleware('auth');
    }
}
