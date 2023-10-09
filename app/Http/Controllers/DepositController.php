<?php

namespace App\Http\Controllers;

use App\Http\Requests\Deposits\UpdateDepositRequest;
use App\Http\Requests\Deposits\ViewDepositDetailsRequest;
use Illuminate\Http\Request;
use App\deposits;
use Auth;
use DB;
use Exception;
use Log;

class DepositController extends Controller
{

    //function to revert the already approved deposit
    public function revertDeposit(ViewDepositDetailsRequest $request)
    {
        try {
            $response = \Illuminate\Support\Facades\DB::select('CALL reverse_approved_deposits(' . $request->id . ',' . \Illuminate\Support\Facades\Auth::user()->id . ')');
            return response()->json(['msg' => $response[0]->Result], 200);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Some error found " . $e->getMessage());
            return response()->json(['msg' => "Something went wrong. Please try again later."], 500);
        }
    }


    //function to update the deposit
    public function updateDeposit(UpdateDepositRequest $request)
    {
        try {
            if (empty($request['reinvest_type'])) {
                $request['reinvest_type'] = '';
            }
            $Deposit = deposits::find($request->id);
            \Illuminate\Support\Facades\DB::table('deposits')->where('id', $request->id)->update($request->getValidRequest());
            $this->adminLogs($Deposit->unique_id, \Illuminate\Support\Facades\Auth::user()->u_id, $request->id, 'Edit Deposit, deposit reference number is ' . $request->id);
            return redirect()->back()->with('message', 'Action Successful!');
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Some error found updateDeposit " . $e->getMessage());
            return redirect()->back()->with('message', 'Action Fail!');
        }
    }

    //Return sold deposit json
    public function soldDepositsJson()
    {

        $country = null;
        $isAllowed = false;
        $deposits = [];

        try {

            if (\Illuminate\Support\Facades\Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
                $isAllowed = true;
                if (\Illuminate\Support\Facades\Auth::user()->id == 6774) {
                    $country = config('country.asia');
                } elseif (Auth::user()->id == 8416) {
                    $country = config('country.europe');
                }

            } elseif (
                Auth::user()->is_user(SITE_ADMIN)
                || Auth::user()->is_user(SITE_SUPER_ADMIN)
                || Auth::user()->is_user(SITE_MANAGER)
                || Auth::user()->is_user(SITE_AGENT)
            ) {
                $isAllowed = true;
            }

            if ($isAllowed) {

                $deposits = \Illuminate\Support\Facades\DB::table('depositView')
                    ->where('depositView.status', 'Sold');

                if ($country) {
                    $deposits->where('users.Country', $country);
                }
            }

            return datatables()->of($deposits)->toJson();
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }


    //Return Approved deposit route
    public function ApproveDepositJson()
    {
        $deposits2 = [];
        $isAllowedToView = false;
        $country = false;


        if (\Illuminate\Support\Facades\Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            if (\Illuminate\Support\Facades\Auth::user()->id == 6774) {
                $country = config('country.asia');
                $isAllowedToView = true;
            } elseif (\Illuminate\Support\Facades\Auth::user()->id == 8416) {
                $country = config('country.europe');
                $isAllowedToView = true;
            }
        } elseif (\Illuminate\Support\Facades\Auth::user()->is_user(SITE_ADMIN)
            || \Illuminate\Support\Facades\Auth::user()->is_user(SITE_MANAGER)
            || \Illuminate\Support\Facades\Auth::user()->is_user(SITE_AGENT)
        ) {
            $isAllowedToView = true;
        }

        if ($isAllowedToView) {
            $deposits2 = \Illuminate\Support\Facades\DB::table('depositView')->where('status', 'Approved');

            if ($country) {
                $deposits2->whereIn('depositView.Country', $country);
            }
        }


        return datatables()->of($deposits2)->toJson();
    }

    ///Manage Deposit

    /**
     * @return mixed
     * @throws Exception
     */
    public function ManageDepositJson()
    {

        $isAllowed = false;
        $country = null;
        $deposits2 = [];


        if (\Illuminate\Support\Facades\Auth::user()->is_user(SITE_COUNTRY_MANAGER)) {
            $isAllowed = true;
            if (\Illuminate\Support\Facades\Auth::user()->id == 6774) {
                $country = config('country.asia');
            } elseif (\Illuminate\Support\Facades\Auth::user()->id == 8416) {
                $country = config('country.europe');
            }
        } elseif (\Illuminate\Support\Facades\Auth::user()->is_user(SITE_ADMIN) || \Illuminate\Support\Facades\Auth::user()->is_user(SITE_SUPER_ADMIN) || \Illuminate\Support\Facades\Auth::user()->is_user(SITE_MANAGER) || \Illuminate\Support\Facades\Auth::user()->is_user(SITE_AGENT)) {
            $isAllowed = true;
        }

        if ($isAllowed) {
            $deposits2 = \Illuminate\Support\Facades\DB::table('depositView');
            if ($country) {
                $deposits2 = $deposits2->whereIn('Country', $country);
            }
        }


        return datatables()->of($deposits2)->toJson();
    }
}
