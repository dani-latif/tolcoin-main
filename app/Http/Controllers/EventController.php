<?php
namespace App\Http\Controllers;

use App\Event;
use App\ph;
use App\gh;
use DateTime;
use DB;
use Mail;
use fypyuhu\LaravelFullcalendar\Facades\Calendar;

class EventController extends Controller
{
    public function index()
    {
        $events = [];
        $data = Event::all();
        if ($data->count()) {
            foreach ($data as $key => $value) {
                $events[] = Calendar::event(
                    $value->title,
                    true,
                    new DateTime($value->start_date),
                    new DateTime($value->end_date.' +1 day'),
                    null,
                    // Add color and link on event
                        [
                          'color' => '#ff0000',
                          'url' => 'pass here url and any route',
                            ]
                );
            }
        }
        $calendar = Calendar::addEvents($events);
                
        $defaultOptions = [
         'header'         => [
         'left'             => 'title',
           /*
           'left' => 'prev,next ',
           'right' => 'month,agendaWeek,agendaDay',
           'title'=>'Weekend', */
              ],
              'defaultView'     => 'month',
              'firstDay'         => 1,
              'height'         => 500,
              'weekMode'        => 'liquid'
           ];
        $calendar->setOptions($defaultOptions);
        return view('fullcalender', compact('calendar'));
    }
}
