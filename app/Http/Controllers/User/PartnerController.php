<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller2;
use App\deposits;
use App\bonus_history;
use App\Model\AttractiveFunds;
use App\Model\Referral;
use App\ref_investment_bonus_rules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller2
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    //view All Partners
    public function partners()
    {
        $title = 'All Partners Info';
        return view('user.partners.partners')->with(
            [
                'title' => $title,
            ]
        );
    }

    public function partnersAfter2()
    {

        ini_set('max_execution_time', 30000);

        $user = Auth::user();
        $loged_user_id = $user->id;
        $loged_parent_id = $user->parent_id;
        $Loged_user_uid = $user->u_id;
        $Loged_user_plan = $user->plan;

        // Call of Refferal Functions
        // \App\Model\Referral::sync($loged_user_id);

        $bonusPercentage = ref_investment_bonus_rules::where('id', $Loged_user_plan)->first();
        $first_line = $bonusPercentage->first_line;
        $second_line = $bonusPercentage->second_line;
        $third_line = $bonusPercentage->third_line;
        $fourth_line = $bonusPercentage->fourth_line;
        $fifth_line = $bonusPercentage->fifth_line;

        $title = 'All Partners Info';
        $bonushistory = bonus_history::where('user_id', $loged_user_id)->orderby('created_at', 'DESC')->first();

        $firstTotal = AttractiveFunds::totalAttractiveFundByLevel(\Auth::user()->id, 1);
        $secondTotal = AttractiveFunds::totalAttractiveFundByLevel(\Auth::user()->id, 2);
        $thirdTotal = AttractiveFunds::totalAttractiveFundByLevel(\Auth::user()->id, 3);
        $fourthTotal = AttractiveFunds::totalAttractiveFundByLevel(\Auth::user()->id, 4);
        $fifthTotal = AttractiveFunds::totalAttractiveFundByLevel(\Auth::user()->id, 5);

        // Bonus Withdrawal
        $bonuswithdrawalQuery = DB::table('withdrawals')
            ->select('usd_amount', 'donation')
            ->where('user', $loged_user_id)
            ->where('payment_mode', 'Bonus')
            ->where(
                function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Pending');
                }
            );

        $bonuswithdrawal = $bonuswithdrawalQuery->sum('usd_amount');
        $bonuswithdrawaldonation = $bonuswithdrawalQuery->sum('donation');

        $bonuswithdrawal = $bonuswithdrawal + $bonuswithdrawaldonation;

        $bonusreinvestment = DB::table('deposits')
            ->select('total_amount')
            ->where('user_id', $loged_user_id)
            ->where('trans_type', 'Reinvestment')
            ->where('reinvest_type', 'Bonus')
            ->where(
                function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Sold');
                }
            )->sum('total_amount');

        // Profit Bonus
        $profitbonus = DB::table('daily_investment_bonus')
            ->select('bonus')
            ->where('parent_id', $Loged_user_uid)
            ->where('details', 'LIKE', 'Profit Bonus')
            ->sum('bonus');

        $totalAttractiveFund = $firstTotal + $secondTotal + $thirdTotal;
        if (\Auth::user()->plan >= 3) {
            $totalAttractiveFund = $totalAttractiveFund + $fourthTotal + $fifthTotal;
        }

        $maxTotal = 0;
        $maxfirstTotal = 0;
        $maxsecondTotal = 0;
        $maxthirdTotal = 0;
        $maxfourthTotal = 0;
        $maxfifthTotal = 0;
        $bonusTotal = 0;
        $bonusFirst = 0;
        $bonusSecond = 0;
        $bonusThird = 0;
        $bonusFourth = 0;
        $bonusFifth = 0;
        $finaltotalbonus = 0;
        $oldBonusTotal = 0;
        $current_level_bonus = 0;
        $newfirstTotal = 0;
        $newsecondTotal = 0;
        $newthirdTotal = 0;
        $newfourthTotal = 0;
        $newfifthTotal = 0;

        // Setting it to not load again and again in testing
        if ((app('request')->session()->get('back_to_admin'))) {
            $maxfirstTotal = AttractiveFunds::totalMaxAttractiveFundByLevel(\Auth::user()->id, 1);
            $maxsecondTotal = AttractiveFunds::totalMaxAttractiveFundByLevel(\Auth::user()->id, 2);
            $maxthirdTotal = AttractiveFunds::totalMaxAttractiveFundByLevel(\Auth::user()->id, 3);
            $maxfourthTotal = AttractiveFunds::totalMaxAttractiveFundByLevel(\Auth::user()->id, 4);
            $maxfifthTotal = AttractiveFunds::totalMaxAttractiveFundByLevel(\Auth::user()->id, 5);

            $maxTotal = $maxfirstTotal + $maxsecondTotal + $maxthirdTotal;
            if (\Auth::user()->plan >= 3) {
                $maxTotal = $maxTotal + $maxfourthTotal + $maxfifthTotal;
            }

            if (isset($bonushistory)) {
                $oldPlan = $bonushistory->plan_id;
                $maxlevel1 = $bonushistory->maxlevel1;
                $maxlevel2 = $bonushistory->maxlevel2;
                $maxlevel3 = $bonushistory->maxlevel3;
                $maxlevel4 = $bonushistory->maxlevel4;
                $maxlevel5 = $bonushistory->maxlevel5;

                $current_level_bonus = $bonushistory->current_level_bonus;
                $oldBonusTotal = $bonushistory->bonus_total;

                if ($Loged_user_plan > $oldPlan) {
                    $newfirstTotal = floatval($maxfirstTotal) - floatval($maxlevel1);
                    $newsecondTotal = floatval($maxsecondTotal) - floatval($maxlevel2);
                    $newthirdTotal = floatval($maxthirdTotal) - floatval($maxlevel3);
                    $newfourthTotal = floatval($maxfourthTotal) - floatval($maxlevel4);
                    $newfifthTotal = floatval($maxfifthTotal) - floatval($maxlevel5);
                } elseif ($Loged_user_plan <= $oldPlan) {
                    if ($maxfirstTotal > $maxlevel1) {
                        $newfirstTotal = floatval($maxfirstTotal) - floatval($maxlevel1);
                    }
                    if ($maxsecondTotal > $maxlevel2) {
                        $newsecondTotal = floatval($maxsecondTotal) - floatval($maxlevel2);
                    }
                    if ($maxthirdTotal > $maxlevel3) {
                        $newthirdTotal = floatval($maxthirdTotal) - floatval($maxlevel3);
                    }
                    if ($maxfourthTotal > $maxlevel4) {
                        $newfourthTotal = floatval($maxfourthTotal) - floatval($maxlevel4);
                    }
                    if ($maxfifthTotal > $maxlevel5) {
                        $newfifthTotal = floatval($maxfifthTotal) - floatval($maxlevel5);
                    }
                }

                if ($newfirstTotal > 0) {
                    $bonusFirst = ($newfirstTotal * $first_line) / 100;
                }
                if ($newsecondTotal > 0) {
                    $bonusSecond = ($newsecondTotal * $second_line) / 100;
                }
                if ($newthirdTotal > 0) {
                    $bonusThird = ($newthirdTotal * $third_line) / 100;
                }
                if ($newfourthTotal > 0) {
                    $bonusFourth = ($newfourthTotal * $fourth_line) / 100;
                }
                if ($newfifthTotal > 0) {
                    $bonusFifth = ($newfifthTotal * $fifth_line) / 100;
                }
            } else {
                $bonusFirst = ($maxfirstTotal * $first_line) / 100;
                $bonusSecond = ($maxsecondTotal * $second_line) / 100;
                $bonusThird = ($maxthirdTotal * $third_line) / 100;
                $bonusFourth = ($maxfourthTotal * $fourth_line) / 100;
                $bonusFifth = ($maxfifthTotal * $fifth_line) / 100;
            }

            $bonusTotal = $bonusFirst + $bonusSecond + $bonusThird;
            if (\Auth::user()->plan >= 3) {
                $bonusTotal = $bonusTotal + $bonusFourth + $bonusFifth;
            }
        }

        $bonusTotal = $bonusTotal + $oldBonusTotal;
        $finaltotalbonus = $profitbonus + $bonusTotal - ($bonuswithdrawal + $bonusreinvestment);

        // Get Referrals Level Wise
        $levelReferrals = [];

        for ($level = 1; $level <= 5; ++$level) {
            if ($level <= 3 || \Auth::user()->plan >= 3) {
                $levelReferrals[$level] = Referral::referralByLevel(\Auth::user()->id, $level);
            }
        }

        // dd($levelReferrals[5][8]);

        // Get deposit list levelwise
        $levelReferralDeposit = [];
        for ($level = 1; $level <= 5; ++$level) {
            if ($level <= 3 || \Auth::user()->plan >= 3) {
                $child_user = null;
                foreach ($levelReferrals[$level] as $referraldepo) {
                    $child_user[] = $referraldepo->child_id;
                }

                $child_users[$level] = $child_user;

                if (null == $child_user) {
                    $child_users[$level] = [];
                }

                $child_user = null;

                $levelReferralDeposit[$level] = deposits::getActiveInvestmentList($child_users[$level]);
            }
        }
        // dd($levelReferralDeposit);

        $BlueMoonPlanChildsList = Referral::BlueMoonPlanChilds(\Auth::user()->id);
        $SilverRankChildsList = Referral::SilverRankChilds(\Auth::user()->id);
        $AuroraPlanChildsList = Referral::AuroraPlanChilds(\Auth::user()->id);
        $CoordinatorRankChildsList = Referral::CoordinatorRankChilds(\Auth::user()->id);
        $CullinanPlanChildsList = Referral::CullinanPlanChilds(\Auth::user()->id);
        $DiamondRankChildsList = Referral::DiamondRankChilds(\Auth::user()->id);

        return view(
            'user.partners._after',
            compact(
                [
                    'levelReferrals',
                    'levelReferralDeposit',
                    'maxTotal',
                    'maxfifthTotal',
                    'maxfourthTotal',
                    'maxthirdTotal',
                    'maxsecondTotal',
                    'maxfirstTotal',
                    'firstTotal',
                    'secondTotal',
                    'thirdTotal',
                    'fourthTotal',
                    'fifthTotal',
                    'totalAttractiveFund',
                    'bonusFirst',
                    'bonusSecond',
                    'bonusThird',
                    'bonusFourth',
                    'bonusFifth',
                    'bonusTotal',
                    'bonuswithdrawal',
                    'bonusreinvestment',
                    'finaltotalbonus',
                    'profitbonus',
                    'bonushistory',
                    'BlueMoonPlanChildsList',
                    'SilverRankChildsList',
                    'AuroraPlanChildsList',
                    'CoordinatorRankChildsList',
                    'CullinanPlanChildsList',
                    'DiamondRankChildsList'
                ]
            )
        );
    }

    public function partnersHistory()
    {
        $user_id = Auth::user()->id;


        //DB::select('exec GetDeposits("15254")');
        DB::select('CALL GetDeposits("' . $user_id . '")');


        return redirect('dashboard/partners');
    }

    public function Delpartners_history()
    {
        $user_id = Auth::user()->id;
       $bonusHistory = \App\bonus_history::where('user_id', $user_id)->first();
        $bonusHistory->delete();




        return redirect('dashboard/partners')->with('msg', 'Bonus History Deleted Successfully');
    }


    public function getBonusHistory(Request $request)
    {
        $userid = $request['id'];
        $count = 1;
        $bonushistory = bonus_history::where('user_id', $userid)->orderby('created_at', 'asc')->get();

        $htmlModel = '<div class="modal-content modal-lg">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal">&times;</button>
							<h4 class="modal-title" style="text-align:center;"><strong>Bonus History</strong></h4>
						</div>
						<div class="modal-body table-responsive">
							<table class="table table-hover">
							<thead>
								<tr>
									<th>Sr #</th>
									<th>Old Plan Name</th>
									<th>New Plan Name</th>
                                    <th>Current Max 1st</th>
									<th>Current Max 2nd</th>
									<th>Current Max 3rd</th>
									<th>Current Max 4th</th>
									<th>Current Max 5th</th>
									<th>Current Max Total</th>
									<th>Change Date</th>
									<th>Verified BY</th>
								</tr>
							</thead>';

        if (isset($bonushistory)) {
            foreach ($bonushistory as $bonus) {
                // dd($bonus->BonusUser == null);
                $bonususer = 'Not Set';
                if ($bonus->BonusUser != null) {
                    $bonususer = $bonus->BonusUser->name;
                }
                $current_level_bonus = 0;
                $current_level_bonus = $bonus->bonus_level1 + $bonus->bonus_level2 + $bonus->bonus_level3 + $bonus->bonus_level4 + $bonus->bonus_level5;

                $htmlModel .= '<tbody>
					<tr> 
						<td>' . $count . '</td>
						<td>' . $bonus->plan_name . '</td>
                        <td>' . $bonus->new_plan_name . '</td>
						<td><table><tr><td>' . $bonus->current_max1 . '</td></tr>
							<tr><td>' . $bonus->bonus_level1 . '</td></tr></table>
						</td>
						<td><table><tr><td>' . $bonus->current_max2 . '</td></tr>
							<tr><td>' . $bonus->bonus_level2 . '</td></tr></table>
						</td>
						<td><table><tr><td>' . $bonus->current_max3 . '</td></tr>
							<tr><td>' . $bonus->bonus_level3 . '</td></tr></table>
						</td>
						<td><table><tr><td>' . $bonus->current_max4 . '</td></tr>
							<tr><td>' . $bonus->bonus_level4 . '</td></tr></table>
						</td>
						<td><table><tr><td>' . $bonus->current_max5 . '</td></tr>
							<tr><td>' . $bonus->bonus_level5 . '</td></tr></table>
						</td>
						<td><table><tr><td>' . $bonus->current_max_total . '</td></tr>
							<tr><td>' . $current_level_bonus . '</td></tr></table>
						</td>
						<td>' . date('Y-m-d', strtotime($bonus->created_at)) . '</td>
						<td>' . $bonususer . '</td>
					</tr>
				</tbody>';
                ++$count;
            }
        } else {
            $htmlModel .= '<tr colspan="8" style="align:center">	
								<h5>No Bonus History Found!</h5><br>
							</tr>';
        }
        $htmlModel .= '</table>
							</div>
						</div>';
        echo $htmlModel;
    }

    // Parners2 New Link
    public function partners2()
    {
        ini_set('max_execution_time', 30000);

        $loged_user_id = Auth::user()->id;
        $loged_parent_id = Auth::user()->parent_id;
        $Loged_user_uid = Auth::user()->u_id;
        $Loged_user_plan = Auth::user()->plan;

        //$userAccs            = UserAccounts::where('user_id',$loged_user_id)->first();
        //$old_bonus          = $userAccs->Fold_ref_bonus;
        //$final_bonus        = $userAccs->reference_bonus;

        $bonusPercentage = ref_investment_bonus_rules::where('id', $Loged_user_plan)->first();

        $first_line = $bonusPercentage->first_line;
        $second_line = $bonusPercentage->second_line;
        $third_line = $bonusPercentage->third_line;
        $fourth_line = $bonusPercentage->fourth_line;
        $fifth_line = $bonusPercentage->fifth_line;

        // Call of Refferal Functions
        // \App\Model\Referral::sync($loged_user_id);
        //dont need this method here

        // Approved
        $secondLine = [];
        $secondTotal = 0;

        $thirdLine = [];
        $thirdTotal = 0;

        $fourthLine = [];
        $fourthTotal = 0;

        $fifthLine = [];
        $fifthTotal = 0;
        // Sold
        $secondLineMax = [];
        $secondTotalMax = 0;

        $thirdLineMax = [];
        $thirdTotalMax = 0;

        $fourthLineMax = [];
        $fourthTotalMax = 0;

        $fifthLineMax = [];
        $fifthTotalMax = 0;

        $bonuswithdrawal = DB::table('withdrawals')
            ->select('usd_amount')
            ->where('user', $loged_user_id)
            ->where('payment_mode', 'Bonus')
            ->where(
                function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Pending');
                }
            )->sum('usd_amount');

        $bonuswithdrawaldonation = DB::table('withdrawals')
            ->select('donation')
            ->where('user', $loged_user_id)
            ->where('payment_mode', 'Bonus')
            ->where(
                function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Pending');
                }
            )->sum('donation');

        $bonuswithdrawal = $bonuswithdrawal + $bonuswithdrawaldonation;


        $bonusreinvestment = DB::table('deposits')
            ->select('total_amount')
            ->where('user_id', $loged_user_id)
            ->where('trans_type', 'Reinvestment')
            ->where('reinvest_type', 'Bonus')
            ->where(
                function ($query) {
                    $query->where('status', 'Approved')
                        ->orWhere('status', 'Sold');
                }
            )->sum('total_amount');

        $profitbonus = DB::table('daily_investment_bonus')
            ->where('parent_id', $Loged_user_uid)
            ->where('details', 'LIKE', 'Profit Bonus')
            ->sum('bonus');

        //////////////////////Calculations For Approved ////////////////////////
        // DB::EnableQueryLog();

        $firstLine = DB::table('users')
            ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.u_id',
                'users.parent_id',
                'users.name',
                'users.email',
                'users.created_at',
                DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'),
                DB::raw(' IF((deposits.trans_type = "Reinvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as totalReinvest'),
                DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as totalSold')
            )
            ->where('users.parent_id', $Loged_user_uid)
            ->groupBy('users.id')
            //->where('deposits.trans_type', "NewInvestment")
            ->orderBy('total', 'DESC')
            ->get();

        //DB::raw('CASE (WHEN (deposits.status != "Approved") THEN 0 ELSE SUM(deposits.total_amount) END) as total'))

        /* $firstLine = DB::table('users')
            ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
            ->select( DB::raw('CASE (WHEN (deposits.status != "Approved") THEN 0 ELSE SUM(deposits.total_amount) END) as total'))
            ->where('users.parent_id', $Loged_user_uid)
            ->groupBy('users.id')
            //->where('deposits.trans_type', "NewInvestment")
            ->orderBy('total',"DESC")
            ->get();  */
        //DB::raw(('CASE WHEN (deposits.trans_type == "NewInvestment" && deposits.status == "Approved") THEN SUM(deposits.total_amount) ELSE 0 END) as total'));

        //echo "<pre>";
        //print_r($firstLine);
        //exit;
        //dd(DB::getQueryLog());

        $firstTotal = DB::table('users')
            ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
            ->select('deposits.total_amount as total')
            ->where('users.parent_id', $Loged_user_uid)
            ->where('deposits.status', 'Approved')
            ->where('deposits.trans_type', 'NewInvestment')
            ->sum('deposits.total_amount');

        //echo $firstTotal;
        //exit;
        $count1Final = count($firstLine);
        $count2Final = 0;
        if (isset($firstLine)) {
            $totalAmount = 0;
            $counter = 0;

            $count = count($firstLine);

            for ($i = 0; $i < $count; ++$i) {
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'),
                        DB::raw(' IF((deposits.trans_type = "Reinvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as totalReinvest'),
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as totalSold')
                    )
                    //DB::raw(' IF((deposits.status != "Approved"),0, SUM(deposits.total_amount)) as total'))
                    ->where('users.parent_id', $firstLine[$i]->u_id)
                    ->groupBy('users.id')
                    ->orderBy('total', 'DESC')
                    ->get();
                //dd(DB::getQueryLog());
                $count2 = count($result);
                // echo $count2."<br>";
                if (0 == $count2) {
                    continue;
                }
                array_push($secondLine, $result);
                for ($j = 0; $j < $count2; ++$j) {
                    $totalAmount = $totalAmount + $result[$j]->total;
                }
                $count2Final = $count2Final + $count2;
                ++$counter;
            }
            $secondLine = $secondLine;

            $secondTotal = $totalAmount;
            //echo $secondTotal."<br>";

            //exit;
        }
        $count3Final = 0;
        if (isset($secondLine)) {
            $totalAmount2 = 0;
            $counter2 = 0;

            $count = count($secondLine);

            for ($i = 0; $i < $count; ++$i) {
                $rows = 0;

                foreach ($secondLine[$counter2] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'),
                            DB::raw(' IF((deposits.trans_type = "Reinvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as totalReinvest'),
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as totalSold')
                        )
                        //DB::raw(' IF((deposits.status != "Approved"),0, SUM(deposits.total_amount)) as total'))
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->orderBy('total', 'DESC')
                        ->get();

                    $count2 = count($result);
                    if (0 == $count2) {
                        continue;
                    }
                    array_push($thirdLine, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount2 = $totalAmount2 + $result[$j]->total;
                    }
                    $count3Final = $count3Final + $count2;
                    ++$rows;
                }

                ++$counter2;
            }
            $thirdLine = $thirdLine;

            $thirdTotal = $totalAmount2;
        }
        $count4Final = 0;
        if (isset($thirdLine)) {
            $totalAmount3 = 0;
            $counter3 = 0;

            $count = count($thirdLine);

            for ($i = 0; $i < $count; ++$i) {
                foreach ($thirdLine[$counter3] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'),
                            DB::raw(' IF((deposits.trans_type = "Reinvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as totalReinvest'),
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as totalSold')
                        )
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->orderBy('total', 'DESC')
                        ->get();

                    $count2 = count($result);

                    if (0 == $count2) {
                        continue;
                    }

                    array_push($fourthLine, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount3 = $totalAmount3 + $result[$j]->total;
                    }
                    $count4Final = $count4Final + $count2;
                }
                ++$counter3;
            }

            $fourthLine = $fourthLine;

            $fourthTotal = $totalAmount3;
        }
        $count5Final = 0;
        if (isset($fourthLine)) {
            $counter4 = 0;

            $totalAmount4 = 0;
            $count = count($fourthLine);

            for ($i = 0; $i < $count; ++$i) {
                foreach ($fourthLine[$counter4] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as total'),
                            DB::raw(' IF((deposits.trans_type = "Reinvestment" && deposits.status = "Approved"),SUM(deposits.total_amount),0) as totalReinvest'),
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as totalSold')
                        )
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->orderBy('total', 'DESC')
                        ->get();

                    $count2 = count($result);

                    if (0 == $count2) {
                        continue;
                    }

                    array_push($fifthLine, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount4 = $totalAmount4 + $result[$j]->total;
                    }
                    $count5Final = $count5Final + $count2;
                }

                ++$counter4;
            }
            $fifthLine = $fifthLine;

            $fifthTotal = $totalAmount4;
        }

        /////////////////////////////////////////////////
        ////// Calculations For Sold Deposits Amount ////
        $firstLineMax = DB::table('users')
            ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.u_id',
                'users.parent_id',
                'users.name',
                'users.email',
                'users.created_at',
                DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as total')
            )
            //DB::raw(' IF((deposits.status != "Sold"), 0, SUM(deposits.total_amount)) as total'))
            ->where('users.parent_id', $Loged_user_uid)
            ->groupBy('users.id')
            ->get();

        //DB::EnableQueryLog();
        $firstTotalMax = DB::table('users')
            ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
            ->select('deposits.total_amount as total')
            ->where('users.parent_id', $Loged_user_uid)
            ->where('deposits.status', 'Sold')
            ->where('deposits.trans_type', 'NewInvestment')
            ->sum('deposits.total_amount');
        //dd(DB::getQueryLog());
        if (isset($firstLineMax)) {
            $totalAmount = 0;
            $counter = 0;

            $count = count($firstLineMax);

            for ($i = 0; $i < $count; ++$i) {
                $result = DB::table('users')
                    ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                    ->select(
                        'users.id',
                        'users.u_id',
                        'users.parent_id',
                        'users.name',
                        'users.email',
                        'users.created_at',
                        DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as total')
                    )
                    //DB::raw(' IF((deposits.status != "Sold"), 0, SUM(deposits.total_amount)) as total'))
                    ->where('users.parent_id', $firstLineMax[$i]->u_id)
                    ->groupBy('users.id')
                    ->get();
                //dd(DB::getQueryLog());
                $count2 = count($result);
                // echo $count2."<br>";
                if (0 == $count2) {
                    continue;
                }
                array_push($secondLineMax, $result);
                for ($j = 0; $j < $count2; ++$j) {
                    $totalAmount = $totalAmount + $result[$j]->total;
                }
                ++$counter;
            }
            $secondLineMax = $secondLineMax;

            $secondTotalMax = $totalAmount;
            //echo $secondTotal."<br>";

            //exit;
        }

        if (isset($secondLineMax)) {
            $totalAmount2 = 0;
            $counter2 = 0;

            $count = count($secondLineMax);

            for ($i = 0; $i < $count; ++$i) {
                $rows = 0;

                foreach ($secondLineMax[$counter2] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as total')
                        )
                        //DB::raw(' IF((deposits.status != "Sold"), 0, SUM(deposits.total_amount)) as total'))
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->get();

                    $count2 = count($result);
                    if (0 == $count2) {
                        continue;
                    }
                    array_push($thirdLineMax, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount2 = $totalAmount2 + $result[$j]->total;
                    }

                    ++$rows;
                }

                ++$counter2;
            }
            $thirdLineMax = $thirdLineMax;

            $thirdTotalMax = $totalAmount2;
        }

        if (isset($thirdLineMax)) {
            $totalAmount3 = 0;
            $counter3 = 0;

            $count = count($thirdLineMax);

            for ($i = 0; $i < $count; ++$i) {
                foreach ($thirdLineMax[$counter3] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as total')
                        )
                        //DB::raw(' IF((deposits.status != "Sold"), 0, SUM(deposits.total_amount)) as total'))
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->get();

                    $count2 = count($result);

                    if (0 == $count2) {
                        continue;
                    }

                    array_push($fourthLineMax, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount3 = $totalAmount3 + $result[$j]->total;
                    }
                }
                ++$counter3;
            }

            $fourthLineMax = $fourthLineMax;

            $fourthTotalMax = $totalAmount3;
        }

        if (isset($fourthLineMax)) {
            $counter4 = 0;

            $totalAmount4 = 0;
            $count = count($fourthLineMax);

            for ($i = 0; $i < $count; ++$i) {
                foreach ($fourthLineMax[$counter4] as $lines) {
                    $result = DB::table('users')
                        ->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
                        ->select(
                            'users.id',
                            'users.u_id',
                            'users.parent_id',
                            'users.name',
                            'users.email',
                            'users.created_at',
                            DB::raw(' IF((deposits.trans_type = "NewInvestment" && deposits.status = "Sold"),SUM(deposits.total_amount),0) as total')
                        )
                        //DB::raw(' IF((deposits.status != "Sold"), 0, SUM(deposits.total_amount)) as total'))
                        ->where('users.parent_id', $lines->u_id)
                        ->groupBy('users.id')
                        ->get();

                    $count2 = count($result);

                    if (0 == $count2) {
                        continue;
                    }

                    array_push($fifthLineMax, $result);

                    for ($j = 0; $j < $count2; ++$j) {
                        $totalAmount4 = $totalAmount4 + $result[$j]->total;
                    }
                }

                ++$counter4;
            }
            $fifthLineMax = $fifthLineMax;

            $fifthTotalMax = $totalAmount4;
        }

        $maxfirstTotal = $firstTotal + $firstTotalMax;
        $maxsecondTotal = $secondTotal + $secondTotalMax;
        $maxthirdTotal = $thirdTotal + $thirdTotalMax;
        $maxfourthTotal = $fourthTotal + $fourthTotalMax;
        $maxfifthTotal = $fifthTotal + $fifthTotalMax;

        $maxTotal = $maxfirstTotal + $maxsecondTotal + $maxthirdTotal;
        if (\Auth::user()->plan >= 3) {
            $maxTotal = $maxTotal + $maxfourthTotal + $maxfifthTotal;
        }

        $bonusFirst = ($maxfirstTotal * $first_line) / 100;
        $bonusSecond = ($maxsecondTotal * $second_line) / 100;
        $bonusThird = ($maxthirdTotal * $third_line) / 100;
        $bonusFourth = ($maxfourthTotal * $fourth_line) / 100;
        $bonusFifth = ($maxfifthTotal * $fifth_line) / 100;

        $bonusTotal = $bonusFirst + $bonusSecond + $bonusThird;
        if (\Auth::user()->plan >= 3) {
            $bonusTotal = $bonusTotal + $bonusFourth + $bonusFifth;
        }

        $account_balance = $firstTotal + $secondTotal + $thirdTotal;
        if (\Auth::user()->plan >= 3) {
            $account_balance = $account_balance + $fourthTotal + $fifthTotal;
        }

        $finaltotalbonus = $profitbonus + $bonusTotal - ($bonuswithdrawal + $bonusreinvestment);

        $title = 'All Partners Info';


        return view('partners2')->with(
            [
                'title' => $title,
                'profitbonus' => $profitbonus,
                'firstLine' => $firstLine,
                'secondLine' => $secondLine,
                'thirdLine' => $thirdLine,
                'fourthLine' => $fourthLine,
                'fifthLine' => $fifthLine,
                'firstTotal' => $firstTotal,
                'secondTotal' => $secondTotal,
                'thirdTotal' => $thirdTotal,
                'fourthTotal' => $fourthTotal,
                'fifthTotal' => $fifthTotal,
                'account_balance' => $account_balance,
                'maxfirstTotal' => $maxfirstTotal,
                'maxsecondTotal' => $maxsecondTotal,
                'maxthirdTotal' => $maxthirdTotal,
                'maxfourthTotal' => $maxfourthTotal,
                'maxfifthTotal' => $maxfifthTotal,
                'maxTotal' => $maxTotal,
                'bonusFirst' => $bonusFirst,
                'bonusSecond' => $bonusSecond,
                'bonusThird' => $bonusThird,
                'bonusFourth' => $bonusFourth,
                'bonusFifth' => $bonusFifth,
                'bonusTotal' => $bonusTotal,
                'bonuswithdrawal' => $bonuswithdrawal,
                'bonusreinvestment' => $bonusreinvestment,
                'finaltotalbonus' => $finaltotalbonus,
                'userlevel1' => $count1Final,
                'userlevel2' => $count2Final,
                'userlevel3' => $count3Final,
                'userlevel4' => $count4Final,
                'userlevel5' => $count5Final,
            ]
        );
    }

    /*

    public function partners_after()
    {
        ini_set('max_execution_time', 30000);
        $loged_user_id        = Auth::user()->id;

        $loged_parent_id    = Auth::user()->parent_id;

        $Loged_user_uid        = Auth::user()->u_id;

        $ratesQuery      =  currency_rates::orderby('id', 'desc')->first();
        //$usdRate             =  $ratesQuery->rate_usd;
        $bitcoinRate         =  $ratesQuery->rate_btc;
        $bitcashRate         =  $ratesQuery->rate_bch;
        $ethereumRate         =  $ratesQuery->rate_eth;
        $litecoinRate         =  $ratesQuery->rate_ltc;
        $rippleRate         =  $ratesQuery->rate_xrp;
        $dashRate             =  $ratesQuery->rate_dash;
        $zcashRate             =  $ratesQuery->rate_zec;

        //$dashcoinRate     = $ratesQuery->rate_dsh;

        $secondLine = [];   $secondTotal = 0;

        $thirdLine  = [];   $thirdTotal  = 0;

        $fourthLine = [];   $fourthTotal = 0;

        $fifthLine  = [];   $fifthTotal  = 0;

        $firstLine = DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.u_id','users.parent_id','users.name','users.email','user_accounts.balance_usd as usd',
                'user_accounts.balance_btc as btc','user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
            ->where('users.parent_id', $Loged_user_uid)
            ->get();

        $firstTotal1  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_usd as acc_bal_usd')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_usd');
        $firstTotal2  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_btc as acc_bal_btc')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_btc');
        $firstTotal3  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_eth as acc_bal_eth')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_eth');
        $firstTotal4  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_ltc as acc_bal_ltc')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_ltc');

        $firstTotal5  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_bch as acc_bal_bch')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_bch');
        $firstTotal6  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_xrp as acc_bal_xrp')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_xrp');
        $firstTotal7  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_dash as acc_bal_dash')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_dash');
        $firstTotal8  =  DB::table('users')
            ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->select('users.id','users.parent_id','user_accounts.balance_zec as acc_bal_zec')
            ->where('parent_id', '=',$Loged_user_uid )->sum('user_accounts.balance_zec');

        //echo $firstTotal1." BTC = ". $firstTotal2 ." ETH = ".$firstTotal3 ." LTC = ".$firstTotal4 ." BCH = ".$firstTotal5." XRP = ". +$firstTotal6 ;

        $firstTotal  =  $firstTotal1 + $firstTotal2 * $bitcoinRate + $firstTotal3 * $ethereumRate + $firstTotal4 * $litecoinRate + $firstTotal5 * $bitcashRate +$firstTotal6 * $rippleRate +$firstTotal7 * $dashRate + $firstTotal8 * $zcashRate;
        //echo "<br>".$firstTotal;
        //exit;
        if(isset($firstLine))
        {
            $totalAmount = 0; $totalUsd = 0; $totalBtc = 0; $totalEth = 0; $totalBch = 0; $totalLtc = 0; $totalXrp = 0; $totalDash = 0; $totalZec = 0;

            $counter=0;

            $count = count($firstLine);

            for($i=0; $i<$count; $i++)

            {
                //echo  $firstLine[$i]->u_id."<br>";
                $result = DB::table('users')
                    ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                    ->select('users.id','users.u_id','users.parent_id','users.name','users.email','user_accounts.balance_usd as usd',
                        'user_accounts.balance_btc as btc','user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                    ->where('parent_id', $firstLine[$i]->u_id)->get();
                $count2 = count($result);
                // echo $count2."<br>";
                if($count2 == 0)
                {
                    continue;
                }
                array_push($secondLine, $result);
                for($j=0; $j<$count2; $j++)
                {

                    $totalUsd     = $totalUsd + $result[$j]->usd;
                    $totalBtc     = $totalBtc + $result[$j]->btc;
                    $totalEth     = $totalEth + $result[$j]->eth;
                    $totalLtc     = $totalLtc + $result[$j]->ltc;
                    $totalBch     = $totalBch + $result[$j]->bch;
                    $totalXrp     = $totalXrp + $result[$j]->xrp;
                    $totalDash     = $totalDash + $result[$j]->dash;
                    $totalZec     = $totalZec + $result[$j]->zec;
                }
                $counter++;
            }
            $secondLine = $secondLine;
            $totalAmount = $totalUsd + $totalBtc * $bitcoinRate + $totalEth * $ethereumRate + $totalBch * $bitcashRate + $totalLtc * $litecoinRate + $totalXrp * $rippleRate +$totalDash * $dashRate + $totalZec * $zcashRate;

            $secondTotal = $totalAmount;
        }
        if(isset($secondLine))

        {

            $totalAmount2 = 0; $totalUsd2 = 0; $totalBtc2 = 0; $totalEth2 = 0; $totalBch2 = 0; $totalLtc2 = 0; $totalXrp2 = 0; $totalDash2 = 0; $totalZec2 = 0; $totalDash2 = 0; $totalZec2 = 0;

            $counter2=0;

            $count = count( $secondLine);

            for($i=0; $i<$count; $i++)

            {

                $rows = 0;

                foreach($secondLine[$counter2] as $lines)

                {

                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.id','users.u_id','users.parent_id','users.name','users.email','user_accounts.balance_usd as usd',
                            'user_accounts.balance_btc as btc','user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id',  $lines->u_id)->get();

                    $count2 = count($result);

                    if($count2 == 0)

                    {

                        continue;

                    }

                    array_push($thirdLine, $result);

                    for($j=0; $j<$count2; $j++)

                    {

                        // $totalAmount2 = $totalAmount2 + $result[$j]->account_bal;
                        $totalUsd2     = $totalUsd2 + $result[$j]->usd;
                        $totalBtc2     = $totalBtc2 + $result[$j]->btc;
                        $totalEth2     = $totalEth2 + $result[$j]->eth;
                        $totalLtc2     = $totalLtc2 + $result[$j]->ltc;
                        $totalBch2     = $totalBch2 + $result[$j]->bch;
                        $totalXrp2     = $totalXrp2 + $result[$j]->xrp;
                        $totalDash2 = $totalDash2 + $result[$j]->dash;
                        $totalZec2     = $totalZec2 + $result[$j]->zec;
                    }

                    $rows++;

                }

                $counter2++;

            }

            $totalAmount2 = $totalUsd2 + $totalBtc2 * $bitcoinRate + $totalEth2 * $ethereumRate + $totalBch2 * $bitcashRate + $totalLtc2 * $litecoinRate + $totalXrp2 * $rippleRate +$totalDash2 * $dashRate + $totalZec2 * $zcashRate;


            $thirdLine = $thirdLine;

            $thirdTotal = $totalAmount2;



        }

        if(isset($thirdLine))

        {

            $totalAmount3 = 0; $totalUsd3 = 0; $totalBtc3 = 0; $totalEth3 = 0; $totalBch3 = 0; $totalLtc3 = 0; $totalXrp3 = 0; $totalDash3 = 0; $totalZec3 = 0;

            $counter3=0;

            $count = count($thirdLine);

            for($i=0; $i<$count; $i++)

            {

                foreach($thirdLine[$counter3] as $lines)

                {

                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.id','users.u_id','users.parent_id','users.name','users.email','user_accounts.balance_usd as usd',
                            'user_accounts.balance_btc as btc','user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id', $lines->u_id)->get();

                    $count2 = count($result);

                    if($count2 == 0)

                    {

                        continue;

                    }

                    array_push($fourthLine, $result);

                    for($j=0; $j<$count2; $j++)
                    {
                        //$totalAmount3 = $totalAmount3 + $result[$j]->account_bal;
                        $totalUsd3     = $totalUsd3 + $result[$j]->usd;
                        $totalBtc3     = $totalBtc3 + $result[$j]->btc;
                        $totalEth3     = $totalEth3 + $result[$j]->eth;
                        $totalLtc3     = $totalLtc3 + $result[$j]->ltc;
                        $totalBch3     = $totalBch3 + $result[$j]->bch;
                        $totalXrp3     = $totalXrp3 + $result[$j]->xrp;
                        $totalDash3 = $totalDash3 + $result[$j]->dash;
                        $totalZec3     = $totalZec3 + $result[$j]->zec;
                    }
                }
                $counter3++;
            }

            $fourthLine   = $fourthLine;
            $totalAmount3 = $totalUsd3 + $totalBtc3 * $bitcoinRate + $totalEth3 * $ethereumRate + $totalBch3 * $bitcashRate + $totalLtc3 * $litecoinRate + $totalXrp3 * $rippleRate +$totalDash3 * $dashRate + $totalZec3 * $zcashRate;

            $fourthTotal  = $totalAmount3;
        }
        if(isset($fourthLine))
        {

            $counter4=0;

            $totalAmount4 = 0; $totalUsd4 = 0; $totalBtc4 = 0; $totalEth4 = 0; $totalBch4 = 0; $totalLtc4 = 0; $totalXrp4 = 0; $totalDash4 = 0; $totalZec4 = 0;

            $count = count($fourthLine);

            for($i=0; $i<$count; $i++)

            {

                foreach($fourthLine[$counter4] as $lines)

                {

                    $result = DB::table('users')
                        ->leftJoin('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                        ->select('users.id','users.u_id','users.parent_id','users.name','users.email','user_accounts.balance_usd as usd',
                            'user_accounts.balance_btc as btc','user_accounts.balance_eth as eth','user_accounts.balance_ltc as ltc','user_accounts.balance_bch as bch','user_accounts.balance_xrp as xrp','user_accounts.balance_dash as dash','user_accounts.balance_zec as zec')
                        ->where('parent_id', $lines->u_id)->get();

                    $count2 = count($result);

                    if($count2 == 0)

                    {

                        continue;

                    }

                    array_push($fifthLine, $result);

                    for($j=0; $j<$count2; $j++)

                    {

                        //$totalAmount4 = $totalAmount4 + $result[$j]->account_bal;
                        $totalUsd4     = $totalUsd4 + $result[$j]->usd;
                        $totalBtc4     = $totalBtc4 + $result[$j]->btc;
                        $totalEth4     = $totalEth4 + $result[$j]->eth;
                        $totalLtc4     = $totalLtc4 + $result[$j]->ltc;
                        $totalBch4     = $totalBch4 + $result[$j]->bch;
                        $totalXrp4     = $totalXrp4 + $result[$j]->xrp;
                        $totalDash4 = $totalDash4 + $result[$j]->dash;
                        $totalZec4     = $totalZec4 + $result[$j]->zec;

                    }

                }

                $counter4++;

            }
            $fifthLine = $fifthLine;

            $totalAmount4 = $totalUsd4 + $totalBtc4 * $bitcoinRate + $totalEth4 * $ethereumRate + $totalBch4 * $bitcashRate + $totalLtc4 * $litecoinRate + $totalXrp4 * $rippleRate +$totalDash4 * $dashRate + $totalZec4 * $zcashRate;

            $fifthTotal = $totalAmount4;
        }

        $title = 'All Partners Info';

        $settings = settings::where('id', '=', '1')->first();

        return view('partners_after') ->with(array(

            'title' => $title,'settings' => $settings,'firstLine'=>$firstLine,'secondLine'=>$secondLine,'thirdLine'=>$thirdLine,'fourthLine'=>$fourthLine,'fifthLine'=>$fifthLine,

            'firstTotal'=>$firstTotal,'secondTotal'=>$secondTotal,'thirdTotal'=>$thirdTotal,'fourthTotal'=>$fourthTotal,'fifthTotal'=>$fifthTotal,'ratesQuery'=>$ratesQuery,

        ));

    }

    */
}
