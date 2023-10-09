<?php

namespace App\Http\Controllers\Email;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UnSubscibeController extends Controller
{
    //
    public function unsubscribe($user_id, $hash)
    {
        $observed = $hash;
        $predicted = hash('sha256', $user_id . "" . env('APP_KEY'));
        if ($observed != $predicted) {
            return "Invalid Link";
        }

        $user = User::findOrFail($user_id);
        if ($user->is_subscribe == 0) {
            return "This link is expired";
        }

        $user->is_subscribe = 0;

        $user->save();
        //   $user->update(['is_subscribe'=>0]);
        //  echo json_encode($user);

        return "\nYou are  Un-Subscribed Successfully";
    }
}
