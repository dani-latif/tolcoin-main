<?php

namespace App\Http\Controllers;

use App\Http\Requests\Plans\PlansIdRequest;
use App\ref_investment_bonus;
use App\ref_profit_bonus;
use App\settings;
use Carbon\Carbon;
use Dompdf\Image\Cache;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Model\Referral;
use DB;
use App\users;
use App\plans;
use App\ref_investment_bonus_rules;
use App\ref_profit_bonus_rules;
use App\Console\Commands\PlansCron;

class PlansController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Response
     */

    //  ALL Plans Functions
    //Plans route
    public function plans()
    {
        $title = 'Plans';
        $JoinsQuery = \Illuminate\Support\Facades\DB::table('plans')
            ->join('referal_investment_bonus_rules', 'referal_investment_bonus_rules.plan_id', '=', 'plans.id')
            ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
            ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();
        return view('plans', ['plans' => $JoinsQuery, 'pplans' => $JoinsQuery, 'title' => $title]);
    }

    //Main Plans route
    public function mplans()
    {
        $title = 'Main Plans';

        $JoinsQuery = \Illuminate\Support\Facades\Cache::remember('mplansCache', settings::TopCacheTimeOut, function () {
            return \Illuminate\Support\Facades\DB::table('plans')
                ->join('referal_investment_bonus_rules', 'referal_investment_bonus_rules.plan_id', '=', 'plans.id')
                ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
                ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();
        });
        return view('admin/mplans', ['plans' => $JoinsQuery, 'title' => $title]);
    }

    //Add plan requests
    public function addplan(Request $request)
    {
        $plan = new plans();
        $plan->name = $request['name'];
        $plan->price = $request['price'];
        $plan->personel_investment_limit = $request['personel_investment_limit'];
        $plan->structural_investment_limit = $request['structural_investment_limit'];
        $plan->expected_return = $request['return'];
        $plan->increment_type = "0";
        $plan->increment_interval = "0";
        $plan->increment_amount = "0";
        $plan->expiration = "No";
        $plan->type = 'Main';

        $plan->save();
        $last_id = $plan->id;

        if ($last_id) {
            // $updateQuery = DB::table('referal_investment_bonus_rules');
            // Save referal Investment Bonus Limits
            $investmentBonus = new ref_investment_bonus_rules();
            $investmentBonus->plan_id = $last_id;
            $investmentBonus->first_line = $request['first_line'];
            $investmentBonus->second_line = $request['second_line'];
            $investmentBonus->third_line = $request['third_line'];
            $investmentBonus->fourth_line = $request['fourth_line'];
            $investmentBonus->fifth_line = $request['fifth_line'];
            $investmentBonus->save();
            // Save referal Profit Bonus Limits
            $profitBonus = new ref_profit_bonus_rules();
            $profitBonus->plan_id = $last_id;
            $profitBonus->first_pline = $request['first_pline'];
            $profitBonus->second_pline = $request['second_pline'];
            $profitBonus->third_pline = $request['third_pline'];
            $profitBonus->fourth_pline = $request['fourth_pline'];
            $profitBonus->fifth_pline = $request['fifth_pline'];
            $profitBonus->save();
        }
        ## refresh plan cache
        settings::refreshMplanCache();
        return redirect()->back()->with('message', 'Plan created Successfully!');
    }


    //Update plans
    public function updateplan(Request $request)
    {
        plans::where('id', $request['id'])
            ->update(
                [
                    'name' => $request['name'],
                    'price' => $request['price'],
                    'personel_investment_limit' => $request['personel_investment_limit'],
                    'structural_investment_limit' => $request['structural_investment_limit'],
                    'expected_return' => $request['return'],
                    'type' => 'Main',
                    'expiration' => "0",
                    //'increment_type' => $request['t_type'],
                    //'increment_amount' => $request['t_amount'],
                    //'increment_interval' => $request['t_interval'],
                    // 'expiration' => $request['expiration'],

                ]
            );

        ref_investment_bonus::where('plan_id', $request['id'])
            ->update(
                [
                    'first_line' => $request['first_line'],
                    'second_line' => $request['second_line'],
                    'third_line' => $request['third_line'],
                    'fourth_line' => $request['fourth_line'],
                    'fifth_line' => $request['fifth_line'],
                ]
            );

        ref_profit_bonus::where('plan_id', $request['id'])
            ->update(
                [
                    'first_pline' => $request['first_pline'],
                    'second_pline' => $request['second_pline'],
                    'third_pline' => $request['third_pline'],
                    'fourth_pline' => $request['fourth_pline'],
                    'fifth_pline' => $request['fifth_pline'],
                ]
            );

        ## refresh plan cache
        settings::refreshMplanCache();

        return redirect()->back()
            ->with('message', 'Action Sucessful!');
    }


    //Promo Plans route
    public function pplans()
    {
        return view('pplans')
            ->with(
                array(
                    'title' => 'Promo Plans',
                    'plans' => plans::where('type', 'promo')->get(),
                )
            );
    }

    //Jon a plan
    public function joinplan(PlansIdRequest $joinPlanRequest)
    {
        $plan = plans::where('id', $joinPlanRequest->id)->first();
        if ($plan->type == 'Main') {
            users::where('id', Auth::user()->id)
                ->update(
                    [
                        'plan' => $plan->id,
                        'entered_at' => Carbon::now(),
                    ]
                );
        } elseif ($plan->type == 'Promo') {
            users::where('id', Auth::user()->id)
                ->update(
                    [
                        'promo_plan' => $plan->id,
                    ]
                );
        }
        return redirect()->route('dashboard')
            ->with('message', 'Congratulations! You successfully joined a plan.');
    }

    //Trash Plans route
    public function trashplan(PlansIdRequest $joinPlanRequest)
    {
        plans::where('id', $joinPlanRequest->id)->delete();
        return redirect()->back()->with('message', 'Action Sucessful!');
    }


    public function upPlanAF()
    {

        $userid = Auth::user()->id;

        Referral::sync($userid);
       // DB::select('CALL calculate_investment_values(' . $userid . ')');
        DB::select('CALL calculate_investment_valuesNew(' . $userid . ')');
        $user_plan = Auth::user();
        //Calculate user plans
        PlansCron::process2($user_plan);

        $result = 'Plan updated successfully!';

        return redirect()->back()->with('message', $result);
    }
}
