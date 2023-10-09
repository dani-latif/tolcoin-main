<?php

namespace App\Jobs\Temp;

use App\Model\Old\Portfolio;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class OldSoltAtHit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $u_id;

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
        //  file_get_contents("http://localhost/report.b4uglobal.com/index.php?user_id=" . $this->u_id . "&iframe=1");
        Portfolio::sync($this->u_id);

        // echo "\n\nDone for User: ".User::where('u_id',$this->u_id)->first();
        //
    }
}
