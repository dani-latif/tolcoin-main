<?php

namespace App\Http\Controllers;

use App\BankAccountModel;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Misc\NumberValidationRequest;
use App\Http\Requests\Referrals\SaveReferralsTree;
use App\Http\Requests\UpdateUserKycRequest;
use App\Http\Requests\Users\EditAccountInfoRequest;
use App\Http\Requests\Users\EditWalletAccountInformation;
use App\Http\Requests\Users\GetUserInfoRequest;
use App\Http\Requests\Users\ResetUserPasswordRequest;
use App\Http\Requests\Users\UpdatePasswordRequest;
use App\Http\Requests\Users\UpdateUserAvatar;
use App\Http\Requests\Users\UserDeleteRequest;
use App\Http\Requests\Users\UserIdRequest;
use App\Kyc;
use App\settings;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Log;
use fypyuhu\LaravelFullcalendar\Facades\Calendar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\agents;
use App\users;
use App\plans;
use App\Model\Referral;
use App\AlbumImages;
use App\deposits;
use App\withdrawals;
use DB;
use App\Banks;
use App\Event;
use App\UserWithdrawRule;
use Mail;
use App\Countries;
use App\User;
use App\UserAccountHistory;
use App\VerifyUser;
use App\UserAccounts;
use App\OTPToken;

/**
 * Class UsersController
 *
 * @package App\Http\Controllers
 */
class UsersController extends Controller
{
    /**
     * @return Factory|View
     * @throws Exception
     */
    public function index()
    {

        /** @deprecated  21-07-2020 */

//        DB::table('plans')
//            ->join(
//                'referal_investment_bonus_rules',
//                'referal_investment_bonus_rules.plan_id',
//                '=',
//                'plans.id'
//            )
//            ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
//            ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();


//        $images = \Illuminate\Support\Facades\Cache::rememberForever('imageGalleryCache', function () {
//            return \Illuminate\Support\Facades\DB::table('image_gallery')->get();
//        });


        /*  $title = "weekend";
         dd(Calendar::event($title,true,date('Y-m-d'),
             date('Y-m-d')));
          exit; */

        ## 21600 = 6 hours
        ## 43200 = 12 hours

        $JoinsQuery = \Illuminate\Support\Facades\Cache::remember(config('cachevalue.mplansCache'), settings::minCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('plans')
                ->join('referal_investment_bonus_rules', 'referal_investment_bonus_rules.plan_id', '=', 'plans.id')
                ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
                ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();
        });
        $withdrawals = Cache::remember(config('cachevalue.withdrawals'), settings::TopCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('withdrawals')
                ->leftJoin('users', 'withdrawals.user', '=', 'users.id')
                ->select('withdrawals.*', 'users.u_id')
                ->where('withdrawals.amount', '>', 0)
                ->orderBy('withdrawals.id', 'desc')
                ->take(6)->get();

        });
        $solds = Cache::remember(config('cachevalue.soldCache'), settings::TopCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('solds')
                ->leftJoin('users', 'solds.user_id', '=', 'users.id')
                ->select('solds.*', 'users.u_id')
                ->where('solds.amount', '>', 0)
                ->orderBy('solds.id', 'desc')
                ->take(6)->get();

        });
        $deposits = Cache::remember(config('cachevalue.depositCache'), settings::TopCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('deposits')
                ->leftJoin('users', 'deposits.user_id', '=', 'users.id')
                ->select('deposits.*', 'users.u_id')
                ->where('deposits.amount', '>', 0)
                ->orderBy('deposits.id', 'desc')
                ->take(6)->get();

        });
        $images_gallery = Cache::remember(config('cachevalue.imageGalleryCache'), settings::minCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('image_gallery')->orderBy('id', 'desc')->take(6)->get();
        });
        $promo_id = 13;
        $album_gallery = Cache::remember(config('cachevalue.albumGalleryCache'), settings::minCacheTimeOut, function () use ($promo_id) {
            return \Illuminate\Support\Facades\DB::table('albums')->where('id', '!=', $promo_id)->orderBy('id', 'desc')->take(2)->get();
        });
        $promo = Cache::remember(config('cachevalue.albumsCache'), settings::minCacheTimeOut, function () use ($promo_id) {
            $albumsRecords = \Illuminate\Support\Facades\DB::table('albums')->where('id', $promo_id)->first();
            if (empty($albumsRecords)) {
                return [];
            } else {
                return $albumsRecords;
            }
        });
        $promo_imgs = Cache::remember(config('cachevalue.albumsIdCache'), settings::minCacheTimeOut, function () use ($promo_id) {
            return AlbumImages::where('albums_id', $promo_id)->orderBy('id', 'desc')->get();
        });
        $events = [];
        $data = Cache::remember(config('cachevalue.eventsAllCache'), settings::minCacheTimeOut, function () {
            return Event::all();
        });


        if ($data->count()) {
            foreach ($data as $key => $value) {
                $events[] = Calendar::event(
                    $value->title,
                    true,
                    new DateTime($value->start_date),
                    new DateTime($value->end_date . ' +1 day')
                /*,
                null,
                [
                'color' => '#ff0000',
                'url' => '#',
                ] */
                );
            }
        }

        $calendar = Calendar::addEvents($events);

        $defaultOptions = [
            'header' => [
                'left' => 'title',
                /*
                'left' => 'prev,next ',
                'right' => 'month,agendaWeek,agendaDay',
                'title'=>'Weekend', */
            ],
            'defaultView' => 'month',
            'firstDay' => 1,
            'height' => 500,
            'weekMode' => 'liquid',
            'aspectRatio' => 2,
        ];

        $calendar->setOptions($defaultOptions);
        return view('home.index')->with(array(
            'title' => site_settings()->site_title,
            'pplans' => $JoinsQuery,
            'withdrawals' => $withdrawals,
            'deposits' => $deposits,
            'solds' => $solds,
            'promo' => $promo,
            'promo_imgs' => $promo_imgs,
            'calendar' => $calendar,
            'images_gallery' => $images_gallery,
            'album_gallery' => $album_gallery
        ));
    }

    /**
     * Licensing and registration route
     *
     * @return Factory|View
     */
    public function licensing()
    {
        return view('home.licensing')
            ->with(
                array(
                    'title' => 'Licensing, regulation and registration',
                )
            );
    }

    /**
     * @return Factory|View
     */
    public function terms()
    {
        return view('home.terms')
            ->with(
                array(
                    'title' => 'Terms of Service',
                )
            );
    }

    /**
     * Privacy policy route
     *
     * @return Factory|View
     */
    public function privacy()
    {
        return view('home.agreement')
            ->with(
                array(
                    'title' => 'Privacy Policy',
                )
            );
    }

    /**
     * @return Factory|View
     */
    public function partnership_agreement()
    {
        return view('home.partnership_agreement')
            ->with(
                array(
                    'title' => 'Partnership Agreement',
                )
            );
    }


    /**
     * Render FAQ page
     *
     * @return Factory|View
     */
    public function faq()
    {
        return view('home.faq')
            ->with(
                array(
                    'title' => 'FAQs',
                )
            );
    }


    /**
     * Render about us page
     *
     * @return Factory|View
     */
    public function about()
    {
        return view('home.about')
            ->with(
                array(
                    'title' => 'About',
                )
            );
    }


    /**
     * Pcalculator route
     *
     * @return Factory|View
     */
    public function pcalculator()
    {
        return view('home.profitCalculator')
            ->with(
                array(
                    'title' => 'ProfitCalculator',
                )
            );
    }


    /**
     * Contact route
     *
     * @return Factory|View
     */
    public function contact()
    {
        return view('home.contact')
            ->with(
                array(
                    'title' => 'Contact',
                )
            );
    }

    /**
     * Render contact us page
     *
     * @return Factory|View
     */
    public function contactus()
    {
        return view('home.contactus')
            ->with(
                array(
                    'title' => 'Contact Us',
                )
            );
    }


    /**
     * Send contact message to admin email
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function sendcontact(Request $request)
    {
        $to = site_settings()->contact_email;

        $subject = "Contact message from " . site_settings()->site_name;

        $msg = substr(wordwrap($request['message'], 70), 0, 350);
        $headers = "From: " . $request['name'] . ": " . $request['email'] . "\r\n";

        /*   //send email*/
        @mail($to, $subject, $msg, $headers);

        return redirect()->back()->with('message', ' Your message was sent successfully!');
    }


    /**
     * Update profile photo to DB*
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function updatephoto(UpdateUserAvatar $request)
    {
        $uploadedFile = SignedUrlUploadController::uploadImageToGoogleCloud($request->get('fileurl'), 'profile_Img');
        if ($uploadedFile['status']) {
            $image = !empty($uploadedFile['result']['imageName']) ? $uploadedFile['result']['imageName'] : null;
        } else {
            \Illuminate\Support\Facades\Log::error('Error while updating profile picture',
                [
                    'aws-file-upload-url' => $request->request->get('fileurl')
                ]);
        }
        if (empty($image)) {
            $image = Auth::user()->photo;
        }

        \Illuminate\Support\Facades\DB::table('users')->where('id', $request['id'])->update(['photo' => $image]);
        return redirect("/dashboard/accountdetails")->with('message', 'User updated Successful');

    }

    /**
     * Return add account form
     *
     * @param Request $request
     *
     * @return Factory|View
     */

    public function reset_otp()
    {
        $AuthUser = Auth::user()->id;
        $adminid = \Illuminate\Support\Facades\Session::get('Admin_Id');
        if($adminid == $AuthUser){
            return redirect('/dashboard/accountdetails');
        }else{
            $useracc = User::where('id', $AuthUser)->first();
            $useracc->two_fa_auth_type = 3;
            $useracc->secure_pin = null;
            $useracc->save();
        }
        return redirect('/dashboard/accountdetails');
    }


    public function accountdetails()
    {
        $AuthUser = Auth::user();
        $account_history = $AuthUser->accountHistory()->latest()->first();
        $countries = \Illuminate\Support\Facades\DB::table('countries')->get();
        $xrp_address = explode(",", $AuthUser->xrp_address);
        $adminid = \Illuminate\Support\Facades\Session::get('Admin_Id');
        $useracc = User::where('id', $adminid)->first();
        if($useracc && $useracc->role_id == 1 ){
            $feature_reset_otp = true;
        }else{
            $feature_reset_otp = false;
        }

        return view('updateacct')->with(
            array(
                'title' => 'Update account details',
                'history' => $account_history,
                'xrp_address' => $xrp_address,
                'countries' => $countries,
                'feature_reset_otp' => $feature_reset_otp
            )
        );
//
    }

    /**
     * Update account and contact info
     *
     * @param Request $request
     *
     * @return RedirectResponse|Redirector
     * @throws ValidationException
     */
    public function updateacct(EditWalletAccountInformation $request)
    {
        if (!app('request')->session()->get('back_to_admin')) {
            if (\Illuminate\Support\Facades\Session::has('account_info') && !$request->exists("_key")) {
                $data = \Illuminate\Support\Facades\Session::pull('account_info');
                $request = $request->merge($data);
                \Illuminate\Support\Facades\Session::forget('account_info');
            } else {
                $otp = new OTPToken();
                $random_string = $otp->generateCode();
                if (PinController::sendOtpSms(Auth::user()->phone_no, $random_string)) {
                    $otp->code = $random_string;
                    $otp->user_id = Auth::user()->id;
                    $otp->otp_type = "account";
                    $otp->save();

                    \Illuminate\Support\Facades\Session::forget('account_info');
                    \Illuminate\Support\Facades\Session::put('account_info', $request->all());
                    return redirect()->route('getOtp', [$otp->id]);
                } else {
                    return redirect()->back()->with('successmsg', 'If you are unable to receive OTP on your Mobile Number or Email. Please contact to our support team!');
                }
            }
        }


        if ((!app('request')->session()->get('back_to_admin') && Auth::user()->id == $request['id'])) {

            \Illuminate\Support\Facades\DB::table('users')->where('id', $request['id'])->update(
                [
                    'btc_address' => $request->btc_address,
                    'eth_address' => $request->eth_address,
                    'bch_address' => $request->bch_address,
                    'ltc_address' => $request->ltc_address,
                    'rsc_address' => $request->rsc_address,
                    'xrp_address' => $request->xrp_address1 . ',' . $request->xrp_address2,
                    'dash_address' => $request->dash_address,
                    'zec_address' => $request->zec_address,
                ]
            );


            $userAccountHistory = new UserAccountHistory();
            $userAccountHistory->user_id = $request->id;
            $userAccountHistory->btc_address = $request->btc_address;
            $userAccountHistory->eth_address = $request->eth_address;
            $userAccountHistory->bch_address = $request->bch_address;
            $userAccountHistory->ltc_address = $request->ltc_address;
            $userAccountHistory->xrp_address = $request->xrp_address1 . ',' . $request->xrp_address2;
            $userAccountHistory->dash_address = $request->dash_address;
            $userAccountHistory->zec_address = $request->zec_address;
            $userAccountHistory->updated_by = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
            $userAccountHistory->save();

            return redirect("/dashboard/accountdetails")->with('message', 'User updated Sucessful');
        } else {
            return redirect()->back()->with('errormsg', 'You are not allowed.!');
        }
    }

    public function updateprofile(EditAccountInfoRequest $request)
    {

        if (!app('request')->session()->get('back_to_admin')) {

            if (\Illuminate\Support\Facades\Session::has('personal_info') && !$request->exists("_key")) {

                $data = \Illuminate\Support\Facades\Session::pull('personal_info');
                $request = $request->merge($data);
                \Illuminate\Support\Facades\Session::forget('personal_info');
            } else {

                $otp = new OTPToken();
                $random_string = $otp->generateCode();
                if (PinController::sendOtpSms(Auth::user()->phone_no, $random_string)) {
                    ///save otp into database
                    $otp->code = $random_string;
                    $otp->user_id = Auth::user()->id;
                    $otp->otp_type = "personal_info";
                    $otp->save();

                    $request->request->remove('_key');
                    ///put request into session for later use
                    \Illuminate\Support\Facades\Session::forget('personal_info');
                    \Illuminate\Support\Facades\Session::put('personal_info', $request->request->all());
                    return redirect()->route('getOtp', [$otp->id]);
                } else {
                    return redirect()->back()->with('successmsg', 'If you are unable to receive OTP on your Mobile Number or Email. Please contact to our support team!');
                }
            }
        }

        $country = $request['Country'];
        $countryID = 0;
        if ($country != "") {
            $countryDetails = Countries::where('country_name', 'Like', $country)->first();
            $countryID = $countryDetails->id;
        }

        if (isset($request['bank_name2']) && $request['bank_name2'] != "") {
            $bankName = $request['bank_name2'];
            $countryDetails = Banks::where('country_name', 'Like', $country)->where('bank_name', 'Like', $bankName)->first();
            if (!isset($countryDetails)) {
                $addBank = new Banks();
                $addBank->country_id = $countryID;
                $addBank->country_name = $country;
                $addBank->bank_name = $bankName;
                $addBank->save();
            }
        }


        if ((!app('request')->session()->get('back_to_admin'))) {

            \Illuminate\Support\Facades\DB::table('users')->where('id', $request['id'])->update(
                [

                    'bank_name' => $request->bank_name,
                    'account_name' => $request->account_name,
                    'account_no' => $request->account_no,
                    'acc_hold_No' => $request->acc_hold_No,
                    'name' => $request->name,
                    'phone_no' => $request->phone_no,
                    'Country' => $request->Country,
                    'kin_bank_info' => $request->kin_bank_info
                ]
            );

            $userAccountHistory = new UserAccountHistory();
            $userAccountHistory->user_id = $request->id;
            $userAccountHistory->bank_name = $request->bank_name;
            $userAccountHistory->account_name = $request->account_name;
            $userAccountHistory->account_no = $request->account_no;
            $userAccountHistory->acc_hold_No = $request->acc_hold_No;
            $userAccountHistory->updated_by = (app('request')->session()->get("Admin_Id")) ? app('request')->session()->get("Admin_Id") : Auth::user()->id;
            $userAccountHistory->save();

            return redirect("/dashboard/accountdetails")->with('message', 'User updated Sucessful');
        } else {
            return redirect()->back()->with('errormsg', 'You are not allowed.!');
        }
    }

    /**
     * Return bank details form
     *
     * @param Request $request
     */
    public function banksDetails(Request $request)
    {
        $country = $request['country'];
        if (isset($request['country'])) {
            $banksAll = Banks::where('country_name', 'LIKE', $country)->get();
            if (isset($banksAll)) {
                echo json_encode($banksAll);
            } else {
                $banksAll = "Bank Not Found";
                echo $banksAll;
            }
        } else {
            echo "Invalid Request";
        }
    }

    public function cityBanks(Request $request)
    {
        $cityName = $request['cityName'];
        if ($cityName) {
            $banksAll = BankAccountModel::select('bank_name')->where('branch_city', 'LIKE', $cityName)->groupBy('bank_name')->get();
            if (!$banksAll) {
                $banksAll = "Bank Not Found";
            }
            return $banksAll;
        } else {
            return "Invalid Request";
        }
    }

    public function getAccountTitleNo(Request $request)
    {
        $bankName = $request['bankName'];
        $cityName = $request['cityName'];
        if ($bankName) {
            $banksAll = BankAccountModel::select('id','account_title','account_number')->where('branch_city', 'LIKE', $cityName)->where('bank_name', 'LIKE', $bankName)->get();
            if (!$banksAll) {
                $banksAll = "Information Not Found";
            }
            return $banksAll;
        } else {
            return "Invalid Request";
        }
    }

    /**
     * Return add change password form
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function changepassword(Request $request)
    {
        return view('changepassword')->with(array('title' => 'Change Password'));
    }


    /**
     * Update Password
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function updatepass(UpdatePasswordRequest $request)
    {
        if (!password_verify($request['old_password'], $request['current_password'])) {
            return redirect()->back()->with('message', 'Incorrect Old Password');
        }

        \Illuminate\Support\Facades\DB::table('users')->where('id', $request['id'])->update(['password' => bcrypt($request['password']),]);
        return redirect()->back()->with('message', 'Password Updated Sucessful');
    }

    /**
     * Delete user
     *
     * @param UserIdRequest $userIdRequest
     * @return RedirectResponse
     */
    public function deluser(UserIdRequest $userIdRequest)
    {
        $deposits = deposits::where('user_id', $userIdRequest->id)->get();

        if (!empty($deposits)) {
            foreach ($deposits as $deposit) {
                \Illuminate\Support\Facades\DB::table('deposits')->where('id', $deposit->id)->delete();
            }
        }

        $withdrawals = withdrawals::where('user', $userIdRequest->id)->get();

        if (!empty($withdrawals)) {
            foreach ($withdrawals as $withdrawals) {
                \Illuminate\Support\Facades\DB::table('withdrawals')->where('id', $withdrawals->id)->delete();
            }
        }

        \Illuminate\Support\Facades\DB::table('users')->
        where('id', $userIdRequest->id)->delete();
        return redirect()->route('manageusers')->with('message', 'User has been deleted!');
    }


    /**
     * User info modal request
     *
     * @param Request $request
     *
     * @return false|mixed|string
     */
    public function getUserInfoPost(GetUserInfoRequest $request)
    {
//        return 'dddd';
        $id = $request['id'];
        $mtype = $request['mtype'];
        $user_info = users::where('id', $id)->first();
        $user_accHistory = UserAccountHistory::where('user_id', $request->id)->first();
//        return 'dddd'.$user_accHistory;
        $htmlModel = (string)view(
            'admin.partials._user_post_info',
            compact(
                'mtype',
                'user_info',
                'user_accHistory',
                'id'
            )
        );
//        return $htmlModel;
        return json_encode($htmlModel);
    }


    /**
     * Update account and contact info
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */

    public function updateRole(Request $request)
    {
        if (isset($request['user_id']) && isset($request['user_type'])) {
            $userData = users::where('id', $request['user_id'])->first();
            $newRole = $request['user_type'];
            $updateArray = [];

            $event = null;

            if ($newRole == "manager") {
                $value = 2;
                $updateArray = ['type' => $value];
                $event = "User Role has been Updated to Manager";
            } elseif ($newRole == "normal") {
                $key = "type";
                $value = 0;
                $updateArray = ['type' => $value, 'awarded_flag' => $value, 'post_check' => $value];
                $event = "User Role has been Updated to Normal";
            } elseif ($newRole == "post_check") {
                $key = "type";
                $value = 1;
                $updateArray = ['post_check' => $value];
                $event = "User Role has been Updated to Post Check User";
            } elseif ($newRole == "committe_acc") {
                $value = 3;
                $updateArray = ['type' => $value];
                $event = "User type has been changed to Committee Account";
            } elseif ($newRole == "dummy") {
                $value = 2;
                $updateArray = ['awarded_flag' => $value];
                $event = "User Role has been Updated to Dummy";
            } elseif ($newRole == "awarded") {
                $value = "B4U0001";
                $updateArray = ['parent_id' => $value, 'awarded_flag' => 1];
                $event = "User Role has been Updated to Awarded User";
            } elseif ($newRole == "update_parent") {
                //  dd($request->all());
                if ($request['parentid'] != $userData->parent_id) {
                    if ($userData->parent_id == "SRG0001") {
                        $parentId = trim($request['parentid']);
                        \Illuminate\Support\Facades\DB::table('referrals')->where('child_id', $request['user_id'])->delete();
                    } else {
                        return redirect()->back()->with('errormsg', 'You cant change parent from anyother parent except B4U0001.!');
                    }
                } else {
                    $parentId = $userData->parent_id;
                }

                if (isset($request['email'])) {
                    $email = trim($request['email']);
                } else {
                    $email = $userData->email;
                }
                if (isset($request['bank_acc_info'])) {
                    $bank_acc_info = trim($request['bank_acc_info']);
                } else {
                    $bank_acc_info = $userData->bank_acc_info;
                }

                $updateArray = ['parent_id' => $parentId, 'email' => $email, 'bank_acc_info' => $bank_acc_info];
                if ($request['parentid'] != $userData->parent_id) {
                    \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $request['user_id'] . ',0,Null)');
                }

                $event = "User Parent has been Changed to " . $request['parentid'] . " from " . $userData->parent_id;
            }

            \Illuminate\Support\Facades\DB::table('users')->where('id', $request['user_id'])->update($updateArray);


            $trade_id = "";
            $admin_id = Auth::user()->u_id;
            $user_id1 = users::where('id', $request['user_id'])->select('users.u_id')->first();
            $user_id = $user_id1->u_id;

            $this->adminLogs($user_id, $admin_id, $trade_id, $event);


            return redirect()->route('manageusers')->with('message', 'User updated Successful');
        } else {
            return redirect()->route('manageusers')->with('error', 'User updated Not Successful');
        }
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function withdrawRules(Request $request)
    {
        $user_id = $request['user_id'];

        $withdrawRule = UserWithdrawRule::where('user_id', $user_id)->first();


        if (empty($request['remarks']) && isset($withdrawRule)) {
            $remarks = $withdrawRule->remarks;
        } else {
            $remarks = $request['remarks'];
        }


        if (isset($withdrawRule)) {
            UserWithdrawRule::where('user_id', $user_id)
                ->update(
                    [

                        'can_take_profit' => $request['can_take_profit'] ? '1' : '0',
                        'can_take_bonus' => $request['can_take_bonus'] ? '1' : '0',
                        'can_take_sold' => $request['can_take_sold'] ? '1' : '0',
                        'can_take_withdraw' => $request['can_take_withdraw'] ? '1' : '0',
                        'remarks' => $remarks,

                    ]
                );
        } else {
            $wdRule = new UserWithdrawRule();
            $wdRule->user_id = $user_id;
            $wdRule->can_take_profit = $request['can_take_profit'] ? '1' : '0';
            $wdRule->can_take_bonus = $request['can_take_bonus'] ? '1' : '0';
            $wdRule->can_take_sold = $request['can_take_sold'] ? '1' : '0';
            $wdRule->can_take_withdraw = $request['can_take_withdraw'] ? '1' : '0';
            $wdRule->remarks = $remarks;
            $wdRule->save();
        }
        return redirect()->back()->with('message', 'User Wihdraw Rules Updated Successfully..!');
    }


    /**
     * @return Factory|View
     */
    public function referuser()
    {
        return view('referuser')->with(
            array(
                'title' => 'Refer user',
            )
        );
    }


    /**
     * Pay with card option
     *
     * @param Request $request
     * @param         $amount
     *
     * @return string
     */
    public function paywithcard(Request $request, NumberValidationRequest $numberValidationRequest)
    {
        include_once 'billing/config.php';

        $t_p = $numberValidationRequest->amount * 100;

        /* total price in cents
        session variables for stripe charges */
        $request->session()->put('t_p', $t_p);
        $request->session()->put('c_email', Auth::user()->email);

        echo '<link href="' . asset('css/bootstrap.css') . '" rel="stylesheet">
		<script src="https://code.jquery.com/jquery.js"></script>
		<script src="' . asset('js/bootstrap.min.js') . '"></script>';

        return ('<div style="border:1px solid #f5f5f5; padding:10px; margin:150px; color:#d0d0d0; text-align:center;"><h1>You will be redirected to your payment page!</h1>

		<h4 style="color:#222;">Click on the button below to proceed.</h4>
		<form action="charge" method="post">
		<input type="hidden" name="_token" value="' . csrf_token() . '">
		  <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
			  data-key="' . $stripe['publishable_key'] . '"
			  data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
			  data-name="' . $set->site_name . '"
			  data-description="Account fund"
			  data-amount="' . $t_p . '"
			  data-locale="auto">
		  </script>
		</form>
		</div>
		');
    }


    /**
     * Daily user logs for user
     *
     * @return Factory|View
     */
    public function dailyuserLogs()
    {
        $userId = Auth::user()->id;
        $logeduserId = Auth::user()->u_id;

        $daily_logs_list = DB::table('logs')
            ->where("user_id", $userId)
            ->where("title", "NOT LIKE", "Status Updated Approved to Trash")
            ->where("title", "NOT LIKE", "Status Updated Trash to Approved")
            ->where("title", "NOT LIKE", "%Status Updated%")
            ->where("title", "NOT LIKE", "%Balance Updated%")
            ->where("hide_bit", "0")
            ->orderby('logs.created_at', 'DESC')->get();

        //$daily_logs_list2 = Logs::get();
        $title = 'Daily User Logs';
        return view('/userlogs', ['title' => $title, 'daily_logs_list' => $daily_logs_list]);
    }


    /**
     * Stripe charge customer
     *
     * @param Request $request
     */
    public function charge(Request $request)
    {
        include 'billing/charge.php';


        users::where('id', Auth::user()->id)
            ->update(
                [

                    'confirmed_plan' => Auth::user()->plan,

                    'activated_at' => Carbon::now(),

                    'last_growth' => Carbon::now(),

                ]
            );

        $p = plans::where('id', Auth::user()->plan)->first();
        $earnings = site_settings()->referral_commission * $p->price / 100;


        if (!empty(Auth::user()->parent_id)) {
            agents::where('agent', Auth::user()->parent_id)->increment('total_activated', 1);
            agents::where('agent', Auth::user()->parent_id)->increment('earnings', $earnings);
        }

        /* save deposit info*/
        $dp = new deposits();
        $dp->amount = $up;
        $dp->payment_mode = 'Credit card';
        $dp->status = 'processed';
        $dp->proof = 'stripe';
        $dp->plan = Auth::user()->plan;
        $dp->user = Auth::user()->id;
        $dp->save();

        echo '<h1 style="border:1px solid #f5f5f5; padding:10px; margin:150px; color:#d0d0d0; text-align:center;">Successfully charged ' . $set->currency . '' . $up . '!<br/>
    	  <small style="color:#333;">Returning to dashboard</small>
	   </h1>';

        /* redirect to dashboard after 5 secs*/
        echo '
        	  <script>
            	   window.setTimeout(function(){
        	           window.location.href = "../";
        	       }, 5000);
        	  </script>
            ';
    }

    /**
     *
     */
    public function viewibanimage()
    {
        $image = "images/IBAN.jpg";

        $details = '<p style="text-align:center;"><small>An IBAN is a unique number that is generated for each and every account held with HBL. The IBAN for Pakistan will be 24 digits in length and will contain the following information; Country Code, Security Digits, Bank Code followed by your current Bank Account Number </small><br><small style="color:red;text-align:left !important;">Note: Please add characters without spaces or dashes.</small><br><img width="80%" src="' . asset($image) . '"> </p> <br/>';

        echo $details;

        exit;
    }


    /**
     * Delete user by id
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function deleteUserById(UserDeleteRequest $request)
    {
        $user = User::findOrfail($request['id']);

//        $this->authorize('delete', [Auth::user(), $user]);
        try {
            if (isset($request['id'])) {
                \Illuminate\Support\Facades\DB::table('verify_users')->where('user_id', $request['id'])->delete();
                \Illuminate\Support\Facades\DB::table('user_accounts')->where('user_id', $request['id'])->delete();
                \Illuminate\Support\Facades\DB::table('user_account_histories')->where('user_id', $request['id'])->delete();
                // UserAccounts::where('user_id',$request['id'])->delete();
                $this->adminLogs($user->u_id, Auth::user()->u_id, 0, 'User Deleted');
                $user->delete();
                echo 'User has been deleted';
                exit();
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::info("Something went wrong. " . $e->getMessage());
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * @return Factory|View
     */
    public function getKyc()
    {
        $user = Auth::user();
        $kyc = $user->kyc ?? new Kyc();
        $view = 'edit';
        return view('user.kyc', compact('user', 'kyc', 'view'));
    }

    /**
     * @param UpdateUserKycRequest $request
     *
     * @return RedirectResponse
     */
    public function postKyc(UpdateUserKycRequest $request)
    {
        $user = Auth::user();
        $data = $request->getValidRequest();
        if ($user->kyc()->count() > 0) {
            $user->kyc()->update($data);
        } else {
            $data['status'] = -1;
            $user->kyc()->create($data);
        }
        return redirect()->back()->with('message', 'KYC information has been updated successfully');
    }

    /**
     * Render user KYC page
     *
     * @param  $user
     *
     * @return Factory|View
     */
    public function showKyc()
    {
        $user = Auth::user();
        $kyc = $user->kyc ?? new Kyc();
        $view = 'show';
        return view('user.kyc', compact('user', 'kyc', 'view'));
    }


    /**
     * @param Request $request
     */
    public function getReferralTree(Request $request)
    {
        $userid = $request['id'];
        $count = 1;
        $referralTree = Referral::where('child_id', $userid)->orderby('level', 'ASC')->get();
        $htmlModel = '<div class="modal-content modal-lg">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title" style="text-align:center;"><strong>Referral Tree</strong></h4>
                        </div>
                        <div class="modal-body table-responsive">
                            <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sr #</th>
                                    <th>Parent U_ID</th>
                                    <th>Child U_ID</th>
                                    <th>Level</th>
                                    <th>Created Date</th>
                                   
                                </tr>
                            </thead>';

        if (isset($referralTree)) {
            foreach ($referralTree as $rTree) {
                $htmlModel .= '<tbody>
                    <tr> 
                        <td>' . $count . '</td>
                        <td>' . $rTree->parent_u_id . '</td>
                        <td>' . $rTree->child_u_id . '</td>
                        <td>' . $rTree->level . '</td>
                        <td>' . date('Y-m-d', strtotime($rTree->created_at)) . '</td>
                    </tr>
                </tbody>';
                ++$count;
            }
        } else {
            $htmlModel .= '<tbody><tr colspan="8" style="align:center">    
                                <h5>No Referral Tree Found!</h5><br>
                            </tr></tbody>';
        }
        $htmlModel .= '</table>
                            </div>
                        </div>';
        echo $htmlModel;
    }


    /**
     * @param SaveReferralsTree $saveReferralsTree
     * @return RedirectResponse
     */
    public function saveReferralTree(SaveReferralsTree $saveReferralsTree)
    {
        \Illuminate\Support\Facades\DB::table('referrals')->
        where('child_id', $saveReferralsTree->id)->delete();
        \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $saveReferralsTree->id . ',0,Null)');
        return redirect()->back()->with('message', 'Referral Tree Generated.');
    }
}
