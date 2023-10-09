<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Console\Commands\NewProfitCron;
class NewPofitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $trade;
    protected $lastProfitRate;

    protected $currentDateCron;

    protected $currentCronDate;

    public function __construct($trade,$lastProfitRate,$currentDateCron,$currentCronDate)
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);


        $this->onQueue('profit');
        $this->onConnection('redis');

        $this->trade = $trade;

        $this->lastProfitRate = $lastProfitRate;
        $this->currentDateCron = $currentDateCron;
        $this->currentCronDate = $currentCronDate;
  
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{


        //echo "here";
     //   $a = new NewProfitCron();
       // echo $this->currentDateCron;
        NewProfitCron::processTrade($this->trade,$this->lastProfitRate,$this->currentDateCron,$this->currentCronDate);
      //    echo 'hrere';
 }catch(\Exception $ex){
            dd($ex);

        }
        //
    }
}
