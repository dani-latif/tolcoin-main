<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Percentage extends Model
{
    static function now($percentage){
       // self::add($percentage,date("Y-m-d"));
    }

    protected $table = 'view_percentages';


    static function import(){


    }
    //
    static function sum_until_for_sold_new_system($approved_at,$sold_at){
        return Percentage::where('date','>','2018-12-15')->where('date','>=',$approved_at)->where('date','<',$sold_at)->sum('value');
    }
    static function sum_until_now_new_system($approved_at){
        return Percentage::where('date','>','2018-12-15')->where('date','>=',$approved_at)->sum('value');
    }

    static function sum_until_for_sold($approved_at, $sold_at)
    {
        try {

            return Percentage::where('date', '>=', $approved_at)->where('date', '<', $sold_at)->sum('value');
        } catch (\Exception $ex) {
            return 0;
        }
    }

    static function sum_until_now(){
        return Percentage::sum('value');
    }

    static function sum_until_now_after($after_date){
        return Percentage::where('date','>',date($after_date))->sum('value');
    }
    static function add($percentage,$date){
    /*
        try{
            Percentage::insert([
                'date' => date($date),
                'value' =>$percentage
            ]);

        }catch (\Exception $exception){
            echo $exception->getMessage();
        }
    */
    }
}
