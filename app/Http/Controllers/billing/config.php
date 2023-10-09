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
use Stripe\Stripe;

require_once'stripe/init.php';

//fecth stripe keys from settings
$set=settings::getSettings();

$stripe = array(
  "secret_key"      => $set->s_s_k,
  "publishable_key" => $set->s_p_k
);

Stripe::setApiKey($stripe['secret_key']);
