<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller2;
use App\ref_investment_bonus_rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AwardedPartnerController extends Controller2
{
    public function __construct()
    {
        $this->middleware('auth');
    }



    /*
    public function fixed_deposit($userid)
    {
        if($userid != "")
        {
            users::where('u_id',$userid)->update(['is_fixed_deposit' => 1]);
        }

        return "success";
    }
     if($is_fixed_deposit == 1 && isset($secondLine))
                {
                    $totalLines    = count($secondLine);
                    $totals        = 0;
                    for($i=0; $i < $totalLines; $i++)
                    {
                        foreach($secondLine[$totals] as $u_lines)
                        {
                            $userID = $u_lines->u_id;
                            echo $userID ."<br>" ;
                            $fixed = $this->fixed_deposit($userID);
                        }
                        $totals++;
                    }
                    exit($fixed);
                } */

    // Awarded Partners New Link
    public function awarded_partners()
    {
        if (1 == Auth::user()->type || '1' == Auth::user()->type || 1 == Auth::user()->awarded_flag) {
            ini_set('max_execution_time', 30000);
            $loged_user_id = Auth::user()->id;
            $loged_parent_id = Auth::user()->parent_id;
            $Loged_user_uid = Auth::user()->u_id;
            $Loged_user_plan = Auth::user()->plan;
            $is_fixed_deposit = Auth::user()->is_fixed_deposit;

            $bonusPercentage = ref_investment_bonus_rules::where('id', $Loged_user_plan)->first();

            $first_line = $bonusPercentage->first_line;
            $second_line = $bonusPercentage->second_line;
            $third_line = $bonusPercentage->third_line;
            $fourth_line = $bonusPercentage->fourth_line;
            $fifth_line = $bonusPercentage->fifth_line;

            // Call of Refferal Functions
            //  \App\Model\Referral::sync($loged_user_id);

            //////////////////////Calculations For Approved ////////////////////////

            $firstLine = DB::table('users')
                ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.u_id',
                    'users.parent_id',
                    'users.name',
                    'users.email',
                    'users.created_at',
                    DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                )
                ->where('users.parent_id', $Loged_user_uid)
                ->groupBy('users.id')
                ->orderBy('total', 'DESC')
                ->get();


            $firstTotal = DB::table('users')
                ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                ->select('deposits.total_amount as total')
                ->where('users.parent_id', $Loged_user_uid)
                ->where('deposits.status', 'Approved')
                ->where('deposits.trans_type', 'NewInvestment')
                ->sum('deposits.total_amount');

            $count1Final = count($firstLine);

            // Calculate Level-2 Partners
            $count2Final = 0;
            $secondLine = [];
            $secondTotal = 0;
            if (isset($firstLine)) {
                $result = '';
                $level_ids = collect($firstLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count2Final = count($result);
                $secondTotal = $result->sum("total");
                array_push($secondLine, $result);
                //$count1Final = 0;
                // for ($i = 0; $i < $count; ++$i) {
                //     $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $firstLine[$i]->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //     //dd(DB::getQueryLog());
                //     $count2 = count($result);
                //     // echo $count2."<br>";
                //     if (0 == $count2) {
                //         continue;
                //     }
                //     array_push($secondLine, $result);
                //     for ($j = 0; $j < $count2; ++$j) {
                //         $totalAmount2 = $totalAmount2 + $result[$j]->total;
                //     }
                //     $count2Final = $count2Final + $count2;
                //     ++$counter2;
                // }
                // $secondLine = $secondLine;

                // $secondTotal = $totalAmount2;
            }
            // Calculate Level-3 Partners

            $count3Final = 0;
            $thirdLine = [];
            $thirdTotal = 0;

            if (isset($secondLine)) {
                $result = '';
                $level_ids = collect($secondLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count3Final = count($result);
                $thirdTotal = $result->sum("total");
                array_push($thirdLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $totalAmount3 = 0;
                // $counter3 = 0;

                // $count = count($secondLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     $rows = 0;

                //     foreach ($secondLine[$counter3] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);
                //         if (0 == $count2) {
                //             continue;
                //         }
                //         array_push($thirdLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount3 = $totalAmount3 + $result[$j]->total;
                //         }
                //         $count3Final = $count3Final + $count2;
                //         ++$rows;
                //     }

                //     ++$counter3;
                // }
                // $thirdLine = $thirdLine;

                // $thirdTotal = $totalAmount3;
            }

            // Calculate Level-4 Partners
            $count4Final = 0;
            $fourthLine = [];
            $fourthTotal = 0;

            if (isset($thirdLine)) {
                $result = '';
                $level_ids = collect($thirdLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count4Final = count($result);
                $fourthTotal = $result->sum("total");
                array_push($fourthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $totalAmount4 = 0;
                // $counter4 = 0;

                // $count = count($thirdLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($thirdLine[$counter4] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($fourthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount4 = $totalAmount4 + $result[$j]->total;
                //         }
                //         $count4Final = $count4Final + $count2;
                //     }
                //     ++$counter4;
                // }

                // $fourthLine = $fourthLine;

                // $fourthTotal = $totalAmount4;
            }

            // Calculate Level-5 Partners
            $count5Final = 0;
            $fifthLine = [];
            $fifthTotal = 0;

            if (isset($fourthLine)) {
                $result = '';
                $level_ids = collect($fourthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count5Final = count($result);
                $fifthTotal = $result->sum("total");
                array_push($fifthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter5 = 0;
                // $totalAmount5 = 0;

                // $count = count($fourthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($fourthLine[$counter5] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($fifthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount5 = $totalAmount5 + $result[$j]->total;
                //         }
                //         $count5Final = $count5Final + $count2;
                //     }

                //     ++$counter5;
                // }
                // $fifthLine = $fifthLine;

                // $fifthTotal = $totalAmount5;
            }

            // Calculate Level-6 Partners
            $count6Final = 0;
            $sixthLine = [];
            $sixthTotal = 0;

            if (isset($fifthLine)) {
                $result = '';
                $level_ids = collect($fifthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count6Final = count($result);
                $sixthTotal = $result->sum("total");
                array_push($sixthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter6 = 0;
                // $totalAmount6 = 0;

                // $count = count($fifthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($fifthLine[$counter6] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($sixthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount6 = $totalAmount6 + $result[$j]->total;
                //         }
                //         $count6Final = $count6Final + $count2;
                //     }

                //     ++$counter6;
                // }
                // $sixthLine = $sixthLine;

                // $sixthTotal = $totalAmount6;
            }

            // Calculate Level7 Partners
            $count7Final = 0;
            $seventhLine = [];
            $seventhTotal = 0;

            if (isset($sixthLine)) {
                $result = '';
                $level_ids = collect($sixthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count7Final = count($result);
                $seventhTotal = $result->sum("total");
                array_push($seventhLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter7 = 0;
                // $totalAmount7 = 0;

                // $count = count($sixthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($sixthLine[$counter7] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($seventhLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount7 = $totalAmount7 + $result[$j]->total;
                //         }
                //         $count7Final = $count7Final + $count2;
                //     }

                //     ++$counter7;
                // }
                // $seventhLine = $seventhLine;

                // $seventhTotal = $totalAmount7;
            }

            // Calculate Level8 Partners
            $count8Final = 0;
            $eighthLine = [];
            $eighthTotal = 0;

            if (isset($seventhLine)) {
                $result = '';
                $level_ids = collect($seventhLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count8Final = count($result);
                $eighthTotal = $result->sum("total");
                array_push($eighthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter8 = 0;
                // $totalAmount8 = 0;
                // $count = count($seventhLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($seventhLine[$counter8] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($eighthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount8 = $totalAmount8 + $result[$j]->total;
                //         }
                //         $count8Final = $count8Final + $count2;
                //     }

                //     ++$counter8;
                // }
                // $eighthLine = $eighthLine;

                // $eighthTotal = $totalAmount8;
            }

            // Calculate Level9 Partners
            $count9Final = 0;
            $ninthLine = [];
            $ninthTotal = 0;

            if (isset($eighthLine)) {
                $result = '';
                $level_ids = collect($eighthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count9Final = count($result);
                $ninthTotal = $result->sum("total");
                array_push($ninthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter9 = 0;
                // $totalAmount9 = 0;

                // $count = count($eighthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($eighthLine[$counter9] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($ninthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount9 = $totalAmount9 + $result[$j]->total;
                //         }
                //         $count9Final = $count9Final + $count2;
                //     }

                //     ++$counter9;
                // }
                // $ninthLine = $ninthLine;

                // $ninthTotal = $totalAmount9;
            }

            // Calculate Level-10 Partners
            $count10Final = 0;
            $tenthLine = [];
            $tenthTotal = 0;
            if (isset($ninthLine)) {
                $result = '';
                $level_ids = collect($ninthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count10Final = count($result);
                $tenthTotal = $result->sum("total");
                array_push($tenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter10 = 0;
                // $totalAmount10 = 0;
                // $count = count($ninthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($ninthLine[$counter10] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($tenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount10 = $totalAmount10 + $result[$j]->total;
                //         }
                //         $count10Final = $count10Final + $count2;
                //     }

                //     ++$counter10;
                // }
                // $tenthLine = $tenthLine;

                // $tenthTotal = $totalAmount10;
            }

            // Calculate Level-11 Partners
            $count11Final = 0;
            $eleventhLine = [];
            $eleventhTotal = 0;
            if (isset($tenthLine)) {
                $result = '';
                $level_ids = collect($tenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count11Final = count($result);
                $eleventhTotal = $result->sum("total");
                array_push($eleventhLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter11 = 0;
                // $totalAmount11 = 0;

                // $count = count($tenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($tenthLine[$counter11] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($eleventhLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount11 = $totalAmount11 + $result[$j]->total;
                //         }
                //         $count11Final = $count11Final + $count2;
                //     }

                //     ++$counter11;
                // }
                // $eleventhLine = $eleventhLine;

                // $eleventhTotal = $totalAmount11;
            }

            // Calculate Level-12 Partners
            $count12Final = 0;
            $twelvethLine = [];
            $twelvethTotal = 0;
            if (isset($eleventhLine)) {
                $result = '';
                $level_ids = collect($eleventhLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count12Final = count($result);
                $twelvethTotal = $result->sum("total");
                array_push($twelvethLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter12 = 0;
                // $totalAmount12 = 0;

                // $count = count($eleventhLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($eleventhLine[$counter12] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($twelvethLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount12 = $totalAmount12 + $result[$j]->total;
                //         }
                //         $count12Final = $count12Final + $count2;
                //     }

                //     ++$counter12;
                // }
                // $twelvethLine = $twelvethLine;

                // $twelvethTotal = $totalAmount12;
            }

            // Calculate Level-13 Partners
            $count13Final = 0;
            $thirteenthLine = [];
            $thirteenthTotal = 0;
            if (isset($twelvethLine)) {
                $result = '';
                $level_ids = collect($twelvethLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count13Final = count($result);
                $thirteenthTotal = $result->sum("total");
                array_push($thirteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter13 = 0;
                // $totalAmount13 = 0;

                // $count = count($twelvethLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($twelvethLine[$counter13] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //          DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($thirteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount13 = $totalAmount13 + $result[$j]->total;
                //         }
                //         $count13Final = $count13Final + $count2;
                //     }

                //     ++$counter13;
                // }
                // $thirteenthLine = $thirteenthLine;

                // $thirteenthTotal = $totalAmount13;
            }

            // Calculate Level-14 Partners
            $count14Final = 0;
            $fourteenthLine = [];
            $fourteenthTotal = 0;
            if (isset($thirteenthLine)) {
                $result = '';
                $level_ids = collect($thirteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count14Final = count($result);
                $fourteenthTotal = $result->sum("total");
                array_push($fourteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter14 = 0;
                // $totalAmount14 = 0;

                // $count = count($thirteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($thirteenthLine[$counter14] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($fourteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount14 = $totalAmount14 + $result[$j]->total;
                //         }
                //         $count14Final = $count14Final + $count2;
                //     }

                //     ++$counter14;
                // }
                // $fourteenthLine = $fourteenthLine;

                // $fourteenthTotal = $totalAmount14;
            }

            // Calculate Level-15 Partners
            $count15Final = 0;
            $fifteenthLine = [];
            $fifteenthTotal = 0;
            if (isset($fourteenthLine)) {
                $result = '';
                $level_ids = collect($fourteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count15Final = count($result);
                $fifteenthTotal = $result->sum("total");
                array_push($fifteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter15 = 0;
                // $totalAmount15 = 0;

                // $count = count($fourteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($fourteenthLine[$counter15] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($fifteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount15 = $totalAmount15 + $result[$j]->total;
                //         }
                //         $count15Final = $count15Final + $count2;
                //     }

                //     ++$counter15;
                // }
                // $fifteenthLine = $fifteenthLine;

                // $fifteenthTotal = $totalAmount15;
            }

            // Calculate Level-16 Partners
            $count16Final = 0;
            $sixteenthLine = [];
            $sixteenthTotal = 0;
            if (isset($fifteenthLine)) {
                $result = '';
                $level_ids = collect($fifteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count16Final = count($result);
                $sixteenthTotal = $result->sum("total");
                array_push($sixteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter16 = 0;
                // $totalAmount16 = 0;

                // $count = count($fifteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($fifteenthLine[$counter16] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($sixteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount16 = $totalAmount16 + $result[$j]->total;
                //         }
                //         $count16Final = $count16Final + $count2;
                //     }

                //     ++$counter16;
                // }
                // $sixteenthLine = $sixteenthLine;

                // $sixteenthTotal = $totalAmount16;
            }

            // Calculate Level-17 Partners
            $count17Final = 0;
            $seventeenthLine = [];
            $seventeenthTotal = 0;
            if (isset($sixteenthLine)) {
                $result = '';
                $level_ids = collect($sixteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count17Final = count($result);
                $seventeenthTotal = $result->sum("total");
                array_push($seventeenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter17 = 0;
                // $totalAmount17 = 0;

                // $count = count($sixteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($sixteenthLine[$counter17] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($seventeenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount17 = $totalAmount17 + $result[$j]->total;
                //         }
                //         $count17Final = $count17Final + $count2;
                //     }

                //     ++$counter17;
                // }
                // $seventeenthLine = $seventeenthLine;

                // $seventeenthTotal = $totalAmount17;
            }

            // Calculate Level-18 Partners
            $count18Final = 0;
            $eighteenthLine = [];
            $eighteenthTotal = 0;
            if (isset($seventeenthLine)) {
                $result = '';
                $level_ids = collect($seventeenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count18Final = count($result);
                $eighteenthTotal = $result->sum("total");
                array_push($eighteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter18 = 0;
                // $totalAmount18 = 0;
                // $count = count($seventeenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($seventeenthLine[$counter18] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($eighteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount18 = $totalAmount18 + $result[$j]->total;
                //         }
                //         $count18Final = $count18Final + $count2;
                //     }

                //     ++$counter18;
                // }
                // $eighteenthLine = $eighteenthLine;

                // $eighteenthTotal = $totalAmount18;
            }
            // Calculate Level-19 Partners
            $count19Final = 0;
            $ninteenthLine = [];
            $ninteenthTotal = 0;
            if (isset($eighteenthLine)) {
                $result = '';
                $level_ids = collect($eighteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count19Final = count($result);
                $ninteenthTotal = $result->sum("total");
                array_push($ninteenthLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter19 = 0;
                // $totalAmount19 = 0;

                // $count = count($eighteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($eighteenthLine[$counter19] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($ninteenthLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount19 = $totalAmount19 + $result[$j]->total;
                //         }
                //         $count19Final = $count19Final + $count2;
                //     }

                //     ++$counter19;
                // }
                // $ninteenthLine = $ninteenthLine;

                // $ninteenthTotal = $totalAmount19;
            }
            // Calculate Level-20 Partners
            $count20Final = 0;
            $twentiethLine = [];
            $twentiethTotal = 0;
            if (isset($ninteenthLine)) {
                $result = '';
                $level_ids = collect($ninteenthLine)->flatten()->pluck('u_id')->all();
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total')
                    )
                    ->whereIn('users.parent_id', $level_ids)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                $count20Final = count($result);
                $twentiethTotal = $result->sum("total");
                array_push($twentiethLine, $result);
                // $count = 0;
                // $count2 = 0;
                // $result = '';
                // $counter20 = 0;
                // $totalAmount20 = 0;

                // $count = count($ninteenthLine);

                // for ($i = 0; $i < $count; ++$i) {
                //     foreach ($ninteenthLine[$counter20] as $lines) {
                //         $result = DB::table('users')
                //         ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                //         ->select('users.id','users.u_id','users.parent_id','users.name','users.email','users.created_at',
                //         DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'))
                //         // DB::raw('IF((deposits.trans_type != "NewInvestment" && deposits.status != "Approved"), 0, SUM(deposits.total_amount)) as total'))
                //         ->where('users.parent_id', $lines->u_id)
                //         ->groupBy('users.id')
                //         ->orderBy('total', 'DESC')
                //         ->get();

                //         $count2 = count($result);

                //         if (0 == $count2) {
                //             continue;
                //         }

                //         array_push($twentiethLine, $result);

                //         for ($j = 0; $j < $count2; ++$j) {
                //             $totalAmount20 = $totalAmount20 + $result[$j]->total;
                //         }
                //         $count20Final = $count20Final + $count2;
                //     }

                //     ++$counter20;
                // }
                // $twentiethLine = $twentiethLine;

                // $twentiethTotal = $totalAmount20;
            }

            $account_balance = $firstTotal + $secondTotal + $thirdTotal + $fourthTotal + $fifthTotal;

            $account_balance = $account_balance + $sixthTotal + $seventhTotal + $eighthTotal + $ninthTotal + $tenthTotal;

            $account_balance = $account_balance + $eleventhTotal + $twelvethTotal + $thirteenthTotal + $fourteenthTotal + $fifteenthTotal;
            $account_balance = $account_balance + $sixteenthTotal + $seventeenthTotal + $eighteenthTotal + $ninteenthTotal + $twentiethTotal;

            $title = 'All Partners Info';


            return view('user.awarded.awarded_partners')->with(
                [
                'title' => $title,
                'account_balance' => $account_balance,
                'firstLine' => $firstLine, 'secondLine' => $secondLine, 'thirdLine' => $thirdLine, 'fourthLine' => $fourthLine, 'fifthLine' => $fifthLine,
                'sixthLine' => $sixthLine, 'seventhLine' => $seventhLine, 'eighthLine' => $eighthLine, 'ninthLine' => $ninthLine, 'tenthLine' => $tenthLine,
                'eleventhLine' => $eleventhLine, 'twelvethLine' => $twelvethLine, 'thirteenthLine' => $thirteenthLine, 'fourteenthLine' => $fourteenthLine, 'fifteenthLine' => $fifteenthLine,
                'sixteenthLine' => $sixteenthLine, 'seventeenthLine' => $seventeenthLine, 'eighteenthLine' => $eighteenthLine, 'ninteenthLine' => $ninteenthLine, 'twentiethLine' => $twentiethLine,

                'firstTotal' => $firstTotal, 'secondTotal' => $secondTotal, 'thirdTotal' => $thirdTotal, 'fourthTotal' => $fourthTotal, 'fifthTotal' => $fifthTotal,
                'sixthTotal' => $sixthTotal, 'seventhTotal' => $seventhTotal, 'eighthTotal' => $eighthTotal, 'ninthTotal' => $ninthTotal, 'tenthTotal' => $tenthTotal,
                'eleventhTotal' => $eleventhTotal, 'twelvethTotal' => $twelvethTotal, 'thirteenthTotal' => $thirteenthTotal, 'fourteenthTotal' => $fourteenthTotal, 'fifteenthTotal' => $fifteenthTotal,
                'sixteenthTotal' => $sixteenthTotal, 'seventeenthTotal' => $seventeenthTotal, 'eighteenthTotal' => $eighteenthTotal, 'ninteenthTotal' => $ninteenthTotal, 'twentiethTotal' => $twentiethTotal,

                'userlevel_1' => $count1Final, 'userlevel_2' => $count2Final, 'userlevel_3' => $count3Final, 'userlevel_4' => $count4Final, 'userlevel_5' => $count5Final,
                'userlevel_6' => $count6Final, 'userlevel_7' => $count7Final, 'userlevel_8' => $count8Final, 'userlevel_9' => $count9Final, 'userlevel_10' => $count10Final,
                'userlevel_11' => $count11Final, 'userlevel_12' => $count12Final, 'userlevel_13' => $count13Final, 'userlevel_14' => $count14Final, 'userlevel_15' => $count15Final,
                'userlevel_16' => $count16Final, 'userlevel_17' => $count17Final, 'userlevel_18' => $count18Final, 'userlevel_19' => $count19Final, 'userlevel_20' => $count20Final,
                ]
            );
        } else {
            return redirect()->back()->with('Errormsg', 'Invalid Link!');
        }
    }
}
