<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PreviousReportImportor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected  $u_id;
    public function __construct($u_id)
    {
        $this->u_id = $u_id;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
      echo  $u1  = "http://localhost/report.b4uglobal.com/index.php?user_id=".$this->u_id;
      echo "\n";
        file_get_contents($u1);
        sleep(1);
     echo   $u1  = "http://localhost/report.b4uglobal.com/btc.php?user_id=".$this->u_id;
        echo "\n";
        file_get_contents($u1);
        sleep(1);
      echo   $u1  = "http://localhost/report.b4uglobal.com/eth.php?user_id=".$this->u_id;
        echo "\n";
        file_get_contents($u1);
        sleep(1);
     echo   $u1  = "http://localhost/report.b4uglobal.com/usd.php?user_id=".$this->u_id;
        echo "\n";
        file_get_contents($u1);
        sleep(1);
        echo "\n";


    }
}
