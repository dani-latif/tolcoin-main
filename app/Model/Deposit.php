<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $table = 'deposits';

    function date_string_new_system()
    {
        return "2018-12-15";
    }

    function isBeforeNewSystem()
    {

        $cdate = \Carbon\Carbon::createFromFormat('Y-m-d', $this->start_date_actual());
        $new_system_start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $this->date_string_new_system());
        return $cdate->lessThan($new_system_start_date);
    }

    function is_sold_in_new_system()
    {
        if (empty($this->sold_at)) {
            return false;
        }
        $soldDate = \Carbon\Carbon::createFromFormat('Y-m-d', $this->sold_at());
        $new_system_start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $this->date_string_new_system());
        return $soldDate->greaterThan($new_system_start_date);

    }

    function isNewSystem()
    {
        return true;
    }

    function new_start()
    {
        if ($this->isBeforeNewSystem()) {
            return $this->date_string_new_system();
        } else {
            return $this->start_date_actual();
        }

    }

    function start_date_actual()
    {
        $start = Carbon::parse($this->created_at)->toDateString();
        if (!empty($this->approved_at)) {
            $start = $this->approved_at;
        }
        return $start;
    }

    function start_date()
    {
        return $this->start_date_actual();
    }

    function approved_at()
    {
        return $this->approved_at;
    }

    function sold_at()
    {
        return $this->sold_at;
    }

    function is_sold_at_missed()
    {
        if ($this->status == 'Sold' && empty($this->sold_at)) {
            return true;
        }
        return false;
    }

    function start_end_string()
    {
        return $this->start_date() . " - " . $this->end_date();
    }

    function end_date()
    {
        $end = Carbon::now()->toDateString();
        if (strtolower($this->status) == 'sold') {
            $end = $this->sold_at;
        }
        return $end;
    }

    function days()
    {
        $start = $this->start_date();
        $end = $this->end_date();

        if (empty($start) || empty($end)) {
            return 0;
        }

        $to = \Carbon\Carbon::createFromFormat('Y-m-d', $start);

        $from = \Carbon\Carbon::createFromFormat('Y-m-d', $end);
        if ($from->greaterThan($to)) {

            return $to->diffInDays($from);

        } else {
            return 0;
        }
        // return 0;
    }


    function profit_ratio()
    {

        return round(Percentage::sum_until_for_sold($this->start_date(), $this->end_date()), 2);
    }

    function diffStartWithEndInMonths()
    {
        $start = $this->start_date();
        $end = $this->end_date();
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $start);

        $end = \Carbon\Carbon::createFromFormat('Y-m-d', $end);

        return $end->diffInMonths($start);
    }

    function amount()
    {
        $amount = $this->amount;

        if (strtolower($this->status) == 'sold') {
            if (!$this->is_sold_in_new_system()) {

                if ($this->days() < 30) {
                    $deduct = $amount * 0.30;
                    return $amount - $deduct;
                }
                if ($this->days() < 60) {
                    $deduct = $amount * 0.15;
                    return $amount - $deduct;
                }


            } else {


                if ($this->diffStartWithEndInMonths() < 2) {
                    $deduct = $amount * 0.35;
                    return $amount - $deduct;
                }

                if ($this->diffStartWithEndInMonths() < 4) {
                    $deduct = $amount * 0.20;
                    return $amount - $deduct;
                }
                if ($this->diffStartWithEndInMonths() < 6) {
                 //   echo "aa: ".$this->diffStartWithEndInMonths()."<br>" ;
                    $deduct = $amount * 0.10;
                    return $amount - $deduct;
                    
                }


            }
        }
        return $amount;
    }

    function deduction()
    {
        $amount = $this->amount;
        if (strtolower($this->status) == 'sold' && $this->trans_type != 'NewInvestment') {
            if (!$this->is_sold_in_new_system()) {
                if ($this->days() < 30) {
                    $deduct = $amount * 0.30;
                }
                elseif($this->days() < 60) {
                    $deduct = $amount * 0.15;
                }else{
                    $deduct = 0;
                }
            } else {
                if ($this->diffStartWithEndInMonths() < 2) {
                    $deduct = $amount * 0.35;
                }
                elseif ($this->diffStartWithEndInMonths() < 4) {
                    $deduct = $amount * 0.20;
                }
                elseif ($this->diffStartWithEndInMonths() < 6) {
                    //   echo "aa: ".$this->diffStartWithEndInMonths()."<br>" ;
                    $deduct = $amount * 0.10;
                }else{
                    $deduct = 0;
                }
            }
            return $deduct;
        }
        return 0;
    }


    function realProfit()
    {
        if ($this->currency == 'USD') {
            $profit = number_format($this->amount * $this->profit_ratio() / 100, 2, '.', '');;

        } else {
            $profit = number_format($this->amount * $this->profit_ratio() / 100, 8, '.', '');;

        }
        return $profit;
    }
    function profit()
    {
        $profit = $this->realProfit();
        if (strtolower($this->status) == 'sold') {

            if (!$this->is_sold_in_new_system()) {

                if ($this->days() < 30) {
                    $deduct = $profit * 0.30;
                    return $profit - $deduct;
                }
                if ($this->days() < 60) {
                    $deduct = $profit * 0.15;
                    return $profit - $deduct;
                }
            }

        }

        return $profit;
    }
    //
}
