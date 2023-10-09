<?php

namespace App\Jobs;

use App\Model\AttractiveFunds;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AttractiveFunSyncUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id)
    {
        //
        $this->user_id = $user_id;
    }
    static function dispatch_to_af($user_id){

       // AttractiveFunSyncUser::dispatch($user_id)->onQueue("af");
        AttractiveFunSyncUser::dispatch($user_id);
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        AttractiveFunds::sync($this->user_id);
        //
    }
}
