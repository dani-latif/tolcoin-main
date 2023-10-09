<?php

namespace App\Http\Controllers\FrontEnd\Home;

use DateTime;
use DB;
use App\Event;
use App\AlbumImages;
use App\Http\Controllers\Controller;
use MaddHatter\LaravelFullcalendar\Facades\Calendar;

class HomeController extends Controller
{
    /* Index*/

    public function index()
    {
        $JoinsQuery = DB::table('plans')
            ->join('referal_investment_bonus_rules', 'referal_investment_bonus_rules.plan_id', '=', 'plans.id')
            ->join('referal_profit_bonus_rules', 'referal_profit_bonus_rules.plan_id', '=', 'plans.id')
            ->where('plans.type', 'Main')->orderby('plans.id', 'ASC')->get();

        $images = DB::table('image_gallery')->get();
        
        $withdrawals = DB::table('withdrawals')
            ->leftJoin('users', 'withdrawals.user', '=', 'users.id')
            ->select('withdrawals.*', 'users.u_id')
            ->where('withdrawals.amount', '>', 0)
            ->orderBy('withdrawals.id', 'desc')
            ->take(6)->get();
                                    
        $solds      = DB::table('solds')
            ->leftJoin('users', 'solds.user_id', '=', 'users.id')
            ->select('solds.*', 'users.u_id')
            ->where('solds.amount', '>', 0)
            ->orderBy('solds.id', 'desc')
            ->take(6)->get();
            
        $deposits         = DB::table('deposits')
            ->leftJoin('users', 'deposits.user_id', '=', 'users.id')
            ->select('deposits.*', 'users.u_id')
            ->where('deposits.amount', '>', 0)
            ->orderBy('deposits.id', 'desc')
            ->take(6)->get();
        $images_gallery     = DB::table('image_gallery')->orderBy('id', 'desc')->take(6)->get();
        $promo_id = 9;
        $album_gallery     = DB::table('albums')->where('id', '!=', $promo_id)->orderBy('id', 'desc')->take(2)->get();

        $promo     = DB::table('albums')->where('id', $promo_id)->first();
        $promo_imgs = AlbumImages::where('albums_id', $promo_id)->orderBy('id', 'desc')->get();
        $events = [];
        $data = Event::all();
        /*  $title = "weekend";
         dd(Calendar::event($title,true,date('Y-m-d'),
             date('Y-m-d')));
          exit; */
        if ($data->count()) {
            foreach ($data as $key => $value) {
                $events[] = Calendar::event(
                    $value->title,
                    true,
                    new DateTime($value->start_date),
                    new DateTime($value->end_date.' +1 day')
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
            'header'    => [
            'left'      => 'title',
            /*
            'left' => 'prev,next ',
            'right' => 'month,agendaWeek,agendaDay',
            'title'=>'Weekend', */
            ],
            'defaultView'   => 'month',
            'firstDay'    => 1,
            'height'    => 500,
            'weekMode'    => 'liquid',
            'aspectRatio'   => 2,
        ];
        $calendar->setOptions($defaultOptions);

        return view('newHome.index')->with(
            array(
            'title'          => site_settings()->site_title,
            'pplans'         => $JoinsQuery,
            'withdrawals'    => $withdrawals,
            'deposits'       => $deposits,
            'solds'          => $solds,
            'promo'          => $promo,
            'promo_imgs'     => $promo_imgs,
            'calendar'       => $calendar,
            'images_gallery' => $images_gallery,
            'album_gallery'  => $album_gallery
    
            )
        );
    }


    /* About*/

    public function about()
    {
        return view('newHome.about')
            ->with(
                array(
                'title' => 'About',
                )
            );
    }


    /*  Terms of service route */

    public function terms()
    {
        return view('newHome.terms')
            ->with(
                array(
                'title' => 'Terms of Service',
                )
            );
    }



    /* //Privacy policy route*/

    public function privacy()
    {
        return view('newHome.privacy')

            ->with(
                array(
                'title' => 'Privacy Policy',
                )
            );
    }

    /* //partnership_agreement route*/

    public function partnership_agreement()
    {
        return view('newHome.partnership_agreement')

            ->with(
                array(
                'title' => 'Partnership Agreement',
                )
            );
    }


    /* Pcalculator route*/

    public function pcalculator()
    {
        return view('newHome.indexSections.profitCalculator')

        ->with(
            array(
            'title' => 'ProfitCalculator',
            )
        );
    }
}
