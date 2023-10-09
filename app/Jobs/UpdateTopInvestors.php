<?php

namespace App\Jobs;

use App\top_investors;
use App\users;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateTopInvestors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $from 			=  date("Y-m-d", strtotime($this->from));
        $to 			=  date("Y-m-d", strtotime($this->to));
        \DB::table('top_investors')->truncate();
        $totalAmount 	= 0;
        $totalChild 	= 0;
		$counter 		= 0;
		$uniqueids 		= array();
		$downlineUsers 	= "";
        users::where('status','active')->chunk(10,function($allUsers ) use ($from,$to,&$totalAmount, &$counter,&$uniqueids, &$downlineUsers)
		{
            //$allUsers		= users::where('u_id',"B4U0001")->get();
			//\App\users::where('status','active')->
			if(isset($allUsers))
			{

                foreach($allUsers as $user)
                {
                    $id 	= $user->id;
                    $u_id 	= $user->u_id;
					
                    if($u_id != "")
                    {
						$result 	= \DB::table('users')
							->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
							->select('deposits.total_amount','users.u_id','users.id','users.parent_id')
							->where('users.parent_id', $u_id)
							->where('deposits.status', "Approved")
							->where('deposits.trans_type', "NewInvestment")
							->whereBetween('deposits.approved_at', [$from, $to])
							->groupBy('deposits.user_id')
							->get();
						$count	= count($result);
						$totalChild = $count;
						
						if($count > 0)
						{
							for($i=0; $i<$count; $i++)
							{	
								//echo $result[$i]->u_id."--".$result[$i]->parent_id."--".$result[$i]->total_amount."<br>";
								//exit;
								
								array_push($uniqueids, $result[$i]->u_id);
								$result2 = \DB::table('users')
											->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
											->select('deposits.total_amount','users.u_id','users.id','users.parent_id')
											->where('users.u_id',$result[$i]->u_id)
											->where('deposits.status',"Approved")
											->where('deposits.trans_type',"NewInvestment")
											->whereBetween('deposits.approved_at',[$from, $to])
											->groupBy('deposits.user_id')
											->get(); 
							
								$count2	= count($result2);
								
								if($count2 > 1)
								{
									for($j=0; $j<$count2; $j++)
									{
										
										
										$result3 = \DB::table('users')
											->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
											->select('deposits.total_amount','users.u_id','users.id','users.parent_id')
											->where('users.u_id',$result[$i]->u_id)
											->where('deposits.status',"Approved")
											->where('deposits.trans_type',"NewInvestment")
											->whereBetween('deposits.approved_at',[$from, $to])
											->groupBy('deposits.user_id')
											->sum('deposits.total_amount'); 
									
										$totalAmount = $totalAmount + $result3;
										$count2 ++;
									}// END INNER FOR LOOP
									
								}else{
									$result3 = \DB::table('users')
											->leftJoin('deposits', 'deposits.user_id', '=', 'users.id')
											->select('deposits.total_amount','users.u_id','users.id','users.parent_id')
											->where('users.u_id',$result[$i]->u_id)
											->where('deposits.status',"Approved")
											->where('deposits.trans_type',"NewInvestment")
											->whereBetween('deposits.approved_at',[$from, $to])
											->groupBy('deposits.user_id')
											->sum('deposits.total_amount'); 
									$totalAmount = $totalAmount + $result3;
								}
								$counter++;
								
								$downlineUsers = implode(',',$uniqueids);
								
								
							}// END OUTER FOR LOOP
							
						}
						
                    }
					if($totalAmount >= 5000)
					{
						//echo "\n usersID ($u_id) updated Amount, ($totalAmount) Total SUB users = $count , [ $downlineUsers ]<br>";
						$userExist = top_investors::where('user_id',$id)->first();

						if(!isset($userExist))
						{
							$top1 				= new top_investors();
							$top1->user_id 		= $id;
							$top1->user_uid 	= $u_id;
							$top1->level 		= 1;
							$top1->total_amount = $totalAmount;
							$top1->total_child 	= $totalChild;
							$top1->child_list 	= $downlineUsers;
							$top1->from_date 	= $from;
							$top1->to_date 		= $to;
							$top1->save();
						}
					}
					/* 
					else{
						echo "\n usersID ($u_id) updated Amount, ($totalAmount) <br>";
					} 
					*/
					$uniqueids 		= array();
					$totalAmount 	= 0;
                }// end of loop
            }
        });
    }
}
