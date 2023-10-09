<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller2;
use App\Http\Requests\Users\LoginToUserAccountRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller2
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function loginToUserAccount(LoginToUserAccountRequest $request)
    {
        $adminid = Auth::user()->id;
        $role_id = Auth::user()->role_id;

        $request->session()->put('adminUserType', Auth::user()->type);
        $request->session()->put('adminUserObj', Auth::user());

        \Auth::loginUsingId($request->user_id);
        $request->session()->put('back_to_admin', true);
        $request->session()->put('Admin_Id', $adminid);
        if($role_id){

            $request->session()->put('role_id', $role_id);
        }
        return redirect()->to('dashboard');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginToAdminAccount(Request $request)
    {
        if ($request->session()->get('back_to_admin')) {
            $adminid = $request->session()->get('Admin_Id');
            $request->session()->put('back_to_admin', false);

            Session::remove('adminUserType');
            Session::remove('adminUserObj');

            \Auth::loginUsingId(\App\user::where('id', $adminid)->first()->id);

            return redirect()->to('/dashboard/searchUser');
            // return redirect()->to('/dashboard');
        }
    }

    public function logout2()
    {
        \Auth::logout();
        return redirect()->to('/login');
    }
}
