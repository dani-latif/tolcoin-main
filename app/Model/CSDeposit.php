<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CSDeposit extends Deposit
{
    function isNewSystem()
    {
        return !$this->isBeforeNewSystem();
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
        return $this->new_start();
    }

    function new_start()
    {
        if ($this->isBeforeNewSystem()) {
            return $this->date_string_new_system();
        } else {
            return $this->start_date_actual();
        }

    }




}
