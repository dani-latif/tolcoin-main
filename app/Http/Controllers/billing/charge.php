<?php
namespace App\Http\Controllers;

use App\settings;
use DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Stripe\Charge;
use Stripe\Customer;

//fecth stripe keys and other settings from settings
$set=settings::getSettings();

require_once'config.php';

  $token  = $_POST['stripeToken'];

$customer = Customer::create(
    array(
      'email' => ''.$request->session()->get('c_email').'',
      'source'  => $token
    )
);

$charge = Charge::create(
    array(
      'customer' => $customer->id,
      'amount'   => $request->session()->get('t_p'),
      'currency' => $set->s_currency,
    )
);
  $up=$request->session()->get('t_p')/100;//price convertion from cents
