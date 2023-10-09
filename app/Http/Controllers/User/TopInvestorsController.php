<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller2;
use App\users;
use App\settings;
use App\top_investors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopInvestorsController extends Controller2
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function topinvestors()
    {
        $top_investors = top_investors::orderby('total_amount', 'Desc')->get();

        return view('admin.topmembers.topinvestors')->with(
            [
                'title' => 'CalculateTotal Profit',
                'topmembers' => $top_investors
            ]
        );
    }

    public function topMemberCalculator(Request $request)
    {
        $from = date("Y-m-d", strtotime($request['from']));
        $to = date("Y-m-d", strtotime($request['to']));


        //$functioncall = $this->updateTopList($request['from'],$request['to']);

        $top_investors = DB::select('CALL top_investors("' . $from . '","' . $to . '")');
        return view('admin.topmembers.topinvestors', compact(['top_investors', 'from', 'to']));

        //UpdateTopInvestors::dispatch($request['from'], $request['to'])->onQueue('af');

        //return redirect()->back()->with('successmsg', 'Top Users Caculations successfully added to queue!');
    }


    public function promoInvestors($OMP = 0)
    {
        $AuthUserId = Auth::user()->id;

        if ($OMP == 1) {
           /* ## default dates
            $from = '2020-07-20';
            $to = '2020-09-05';
            return redirect()->intended('dashboard')->with('errormsg', 'Promo not found!.');
            */
            $from = '2021-04-13';
            $to = '2021-07-01';
            $promo_investors = Cache::remember('promo_investor_' . $OMP ,settings::minCacheTimeOut, function () use ($from, $to) {
                return DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
            });

        } elseif ($OMP == 2) {
            $from = '2021-01-01';            // date("Y-m-d", strtotime($request['from']));
            $to = '2021-06-30';

            $promo_investors = Cache::remember('promo_investor_' . $OMP . '_' . $AuthUserId, settings::minCacheTimeOut, function () use ($AuthUserId, $from, $to) {
                return DB::select('CALL promo_investors_by_user_id("' . $from . '","' . $to . '", "' . $AuthUserId . '")');
            });

        }
        elseif ($OMP == 3) {
            $from = '2021-01-01';            // date("Y-m-d", strtotime($request['from']));
            $to = '2021-06-30';
            $promo_investors = Cache::remember('promo_investor_' . $OMP ,3600, function () use ($from, $to) {
                return DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
            });
        }
        else {
            ## default dates
            $from = '2021-01-01';            // date("Y-m-d", strtotime($request['from']));
            $to = '2021-06-30';
            $promo_investors = Cache::remember('promo_investor', settings::TopCacheTimeOut, function () use ($from, $to) {
                return DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
            });

        }

        return view('PromoInvestors', (['promo_investors' => $promo_investors, 'from' => $from, 'to' => $to, 'OMP' => $OMP]));
    }

    public function oneMonthPromo2021()
    {
        $AuthUserId = Auth::user()->id;
        $from = '2021-04-13';
        $to = '2021-05-31';
        $promo_investors = Cache::remember('promo_investor_2021_' . $AuthUserId, 7200, function () use ($AuthUserId, $from, $to) {
            return DB::select('CALL promo_investors_by_user_id("' . $from . '","' . $to . '", "' . $AuthUserId . '")');
        });
        return view('PromoInvestors', compact(['promo_investors', 'from', 'to']));
    }

    public function sixMonthPromo()
    {
        $from = '2020-07-01';            // date("Y-m-d", strtotime($request['from']));
        $to = '2020-12-31';            // date("Y-m-d", strtotime($request['to']));


        $promo_investors = DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
        return view('investors.PromoInvestorsA50K', compact(['promo_investors', 'from', 'to']));

    }


    public function oneMonthPromo()
    {
        $from = '2020-07-20';            // date("Y-m-d", strtotime($request['from']));
        $to = '2020-08-20';            // date("Y-m-d", strtotime($request['to']));


        $promo_investors = DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
        return view('investors.PromoInvestorsA50K', compact(['promo_investors', 'from', 'to']));

    }


    public function oneMonthPromoExt()
    {
        $from = '2020-07-20';            // date("Y-m-d", strtotime($request['from']));
        $to = '2020-09-05';            // date("Y-m-d", strtotime($request['to']));


        $promo_investors = DB::select('CALL promo_investors("' . $from . '","' . $to . '")');
        return view('investors.PromoInvestorsA50K', compact(['promo_investors', 'from', 'to']));

    }


    public function updateTopList($from, $to)
    {
        \DB::table('top_investors')->truncate();

        //$from            = "2019-02-25";
        //$to            = "2019-03-25";
        $counter = 0;
        $totalAmount = 0;
        $uniqueids = [];
        $downlineUsers = '';
        $totalChild = 0;

        //DB::EnableQueryLog();
        /*
            $followingIds = array('B4U0008722','B4U00018183','B4U00015513','B4U00018783','B4U00017825','B4U0008832','B4U00019011','B4U00013072','B4U0005635','B4U0005395','B4U0007953');

            $allUsers  = users::whereIn('u_id',$followingIds)->get();

        */

        $allUsers = users::where('status', 'active')->get();

        if (isset($allUsers)) {
            $count = 0;
            foreach ($allUsers as $user) {
                $id = $user->id;
                $u_id = $user->u_id;

                if ('' != $u_id) {
                    $result = \DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select('deposits.total_amount', 'users.u_id', 'users.id', 'users.parent_id')
                        ->where('users.parent_id', $u_id)
                        ->where('deposits.status', 'Approved')
                        ->where('deposits.trans_type', 'NewInvestment')
                        ->whereBetween('deposits.approved_at', [$from, $to])
                        ->groupBy('deposits.user_id')
                        ->get();
                    $count = count($result);

                    if ($count > 0) {
                        for ($i = 0; $i < $count; ++$i) {
                            //echo $result[$i]->u_id."--".$result[$i]->parent_id."--".$result[$i]->total_amount."<br>";

                            array_push($uniqueids, $result[$i]->u_id);

                            $result2 = \DB::table('users')
                                ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                                ->select('deposits.total_amount', 'users.u_id', 'users.id', 'users.parent_id')
                                ->where('users.u_id', $result[$i]->u_id)
                                ->where('deposits.status', 'Approved')
                                ->where('deposits.trans_type', 'NewInvestment')
                                ->whereBetween('deposits.approved_at', [$from, $to])
                                ->groupBy('deposits.user_id')
                                ->get();

                            $count2 = count($result2);

                            if ($count2 > 1) {
                                for ($j = 0; $j < $count2; ++$j) {
                                    $result3 = \DB::table('users')
                                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                                        ->select('deposits.total_amount', 'users.u_id', 'users.id', 'users.parent_id')
                                        ->where('users.u_id', $result[$i]->u_id)
                                        ->where('deposits.status', 'Approved')
                                        ->where('deposits.trans_type', 'NewInvestment')
                                        ->whereBetween('deposits.approved_at', [$from, $to])
                                        ->groupBy('deposits.user_id')
                                        ->sum('deposits.total_amount');

                                    $totalAmount = $totalAmount + $result3;
                                    ++$count2;
                                }
                            } else {
                                $result3 = \DB::table('users')
                                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                                    ->select('deposits.total_amount', 'users.u_id', 'users.id', 'users.parent_id')
                                    ->where('users.u_id', $result[$i]->u_id)
                                    ->where('deposits.status', 'Approved')
                                    ->where('deposits.trans_type', 'NewInvestment')
                                    ->whereBetween('deposits.approved_at', [$from, $to])
                                    ->groupBy('deposits.user_id')
                                    ->sum('deposits.total_amount');

                                $totalAmount = $totalAmount + $result3;
                            }
                            ++$counter;

                            $downlineUsers = implode(',', $uniqueids);
                        }
                    }
                    if ($totalAmount >= 5000) {
                        //echo "\n usersID ($u_id) updated Amount, ($totalAmount) Total SUB users = $count , [ $downlineUsers ]<br>";

                        $userExist = \App\top_investors::where('user_id', $id)->first();

                        if (!isset($userExist)) {
                            $top1 = new \App\top_investors();
                            $top1->user_id = $id;
                            $top1->user_uid = $u_id;
                            $top1->level = 1;
                            $top1->total_amount = $totalAmount;
                            $top1->total_child = $totalChild;
                            $top1->child_list = $downlineUsers;
                            $top1->from_date = $from;
                            $top1->to_date = $to;
                            $top1->save();
                        }
                    }
                }

                $uniqueids = [];
                $totalAmount = 0;
            }
        }
    }
}
