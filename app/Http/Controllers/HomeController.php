<?php

namespace App\Http\Controllers;

use App\Console\Commands\PlansCron;
use App\daily_investment_bonus;
use App\daily_profit_bonus;
use App\deposits;
use App\Model\Deposit;
use App\Refferal;
use App\UserAccounts;
use App\users;
use Illuminate\Http\Request;


use App\User;
use App\CBAccounts;
use App\CBCredits;
use App\CBDebits;
use App\Adminsetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $users = User::all();
        return view('dashboard', compact('users'));
    }
    public function dashboard()
    {
        $users = User::all();
        return view('dashboard', compact('users'));
    }
    public function tree()
    {
        $users = User::all();

        return view('tree', compact('users'));
    }
    public function tree_view()
    {
        $result = DB::select('SELECT * FROM referrals');


        return view('visual_tree', compact('result'));
    }
    public function make_wallet2()
    {
        // URL of the endpoint
        $url = 'http://tolcoin.net:3001/wallets/create';

// Send a GET request and store the response in a variable
        $response = file_get_contents($url);

        if ($response === false) {
            // Handle the error if the request fails
            echo 'Failed to retrieve data.';
        } else {
            // Output or process the response data
            echo $response;
        }
    }
    public function make_wallet()
    {

        // URL of the endpoint
        $url = 'http://tolcoin.net:3005/wallets/create';

        // Send a GET request and store the response in a variable
        $response = file_get_contents($url);

        if ($response === false) {
            // Handle the error if the request fails
            echo 'Failed to retrieve data.';
        } else {
            // Decode the JSON response
            $data = json_decode($response, true);

            if ($data['code'] == 201 && $data['error'] === false) {
                // Extract address and privateKey
                $address = $data['data']['address'];
                $privateKey = $data['data']['privateKey'];

                // Now you can store $address and $privateKey in your database
                // Replace this with your database storage code
                // Example using MySQLi:
                $user_id = Auth::id();
                $account = CBAccounts::where('user_id',$user_id)->first();
                if($account){
                    CBAccounts::where('user_id',$user_id)
                        ->update([
                            'public_address' => $address,
                            'private_key' => $privateKey
                        ]);
                    return redirect()->back()->with('success', 'Wallet Created Successfully !!.');
                }else{
                    return redirect()->back()->with('error', 'Primary Account Dose Not Exist !!.');
                }


            } else {
//                echo 'Failed to create wallet or received an error.';
                return redirect()->back()->with('error', 'Failed to create wallet or received an error!!.');
            }
        }

    }
    public function get_wallet()
    {
        $user_id = Auth::id();
        $cbAccount = CBAccounts::where('user_id', $user_id)->first();

        if ($cbAccount) {
            $public_address = $cbAccount->public_address;

            // URL of the endpoint
            // $url = 'http://tolcoin.net:3001/wallets/0x86cA7ff81a6934838242f11Cb4aBd8C62E6805Ee';
            // Construct the endpoint URL with the public_address
            $url = 'http://tolcoin.net:3005/wallets/' . $public_address;

            // Send a GET request and store the response in a variable
            $response = file_get_contents($url);

            if ($response === false) {
                // Handle the error if the request fails
                echo 'Failed to retrieve data.';
            } else {
                // Decode the JSON response
                $data = json_decode($response, true);

                if ($data['code'] == 200 && $data['error'] === false) {
                    // Extract the values
                    $balance = $data['data']['balance'];
                    $tolCoinBalance = $data['data']['tolCoinBalance'];

                    // Update the Laravel table
                    $user_id = Auth::id();
                    DB::table('cb_accounts')
                        ->where('user_id', $user_id)
                        ->update([
                            'bnb_balance' => $balance,
                            'tolcoin_balance' => $tolCoinBalance,
                        ]);

                    return redirect()->back()->with('success', 'Wallet Updated Successfully !!.');
                } else {
                    return redirect()->back()->with('error', 'Fail to get update !!.');
                }
            }
        }
    }
    public function get_wallet2()
    {
        $user_id = Auth::id();
        $cbAccount = CBAccounts::where('user_id', $user_id)->first();

        if ($cbAccount) {
            $public_address = $cbAccount->public_address;

            // $public_address = '0x86cA7ff81a6934838242f11Cb4aBd8C62E6805Ee'; // Replace with your actual public address

            // Construct the URL with the dynamic public address
            $url = 'https://tolcoin.net:3005/wallets/' . $public_address;

            // Initialize cURL session
            $ch = curl_init($url);

            // Set cURL options, including a timeout (e.g., 30 seconds)
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set the timeout in seconds (adjust as needed)

            // Set the option to return the response as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the cURL request and store the response in a variable
            $response = curl_exec($ch);

            // Check for cURL errors and handle them if needed
            if (curl_errno($ch)) {
                echo 'cURL error: ' . curl_error($ch);
            } else {
                // Decode the JSON response
                $data = json_decode($response, true);

                if (isset($data['code']) && isset($data['error']) && $data['code'] == 200 && $data['error'] === false) {
                    // Extract the values
                    $balance = $data['data']['balance'];
                    $tolCoinBalance = $data['data']['tolCoinBalance'];

                    // Update the Laravel table
                    $user_id = Auth::id();
                    DB::table('cb_accounts')
                        ->where('user_id', $user_id)
                        ->update([
                            'bnb_balance' => $balance,
                            'tolcoin_balance' => $tolCoinBalance,
                        ]);

                    return redirect()->back()->with('success', 'Wallet Updated Successfully !!.');
                } else {
                    return redirect()->back()->with('error', 'Fail to get update !!.');
                }
            }
        }
    }


    public function register_new_member(Request $request)
    {
//        dd('1eee');
        $user =new User;
        $sponsor=User::where('username',$request['sponsor'])->first();
        $already_exists = User::where('username',$request['username'])->first();
        $already_exists_email = User::where('email',$request['email'])->first();
        if($already_exists){
            return redirect('register')->with('message','Username Already Exists !');
        }
        if($already_exists_email){
            return redirect('register')->with('message','Email Already Exists !');
        }
        // dd($sponsor);
        if($sponsor){
            $sponsor_id = $sponsor->id;
            $sponsor_uid = $sponsor->u_id;
            $childs = User::where('parent_id',$sponsor_uid)->get()->count();
            if($childs){
                if($childs >= 2){
                    return redirect('register')->with('message','Refferals Exceeded limit !');
                }
            }


        }else{
            $sponsor_id = 1;
        }

//        dd($sponsor_id);
        $user->username=$request['username'];
        $user->fName=$request['name'];
        $user->lName=$request['lname'];
        $user->gender='M';
        $user->mobile_number=$request['phone'];
        $user->join_date=date('Y-m-d');
        if($this->check_under_user($sponsor_id)==false){
            session(['status'=>'fail_admin']);
            session(['message'=>'Can not register new member! You exceeded system depth']);
//            dd('eee');
            return view('pages.admin.registration.register');
        }

        else{
//            dd('3eee');
                $value=$this->check_under_user($sponsor_id);
                $user->upline_id=$value["upline_id"];

                $user->sameline_no=$value["sameline_no"];
                $user->level_no=$value["level_no"];
                $user->path=User::where('id',$value["upline_id"])->first()->path.$value["sameline_no"];
                $user->email=$request['email'];
                $user->country=$request['country'];
                $user->state=$request['state'];
                $user->address=$request['address'];
                $user->zip_code=$request['zipcode'];
                $user->city=$request['city'];
                $user->password=bcrypt($request['password']);

                $user->save();
                $user_id = $user->id;
//            $user->u_id=$value["upline_id"];
//            $user->parent_id=$value["upline_id"];
//        dd('4eee');
                User::where('id',$user_id)->update([
                   'u_id' => 'TOL000'.$user_id,
                   'parent_id' => 'TOL000'.$value["upline_id"]
                ]);

            if ($user->parent_id == "TOL0001") {
//                \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $user->id . ',0,1)');
            } else {
//                \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $user->id . ',0,Null)');
            }

               return redirect('login')->with('message','User Registered Successfully !');
            }

    }

    public function check_under_user($id){

        if(User::where('upline_id',$id)->count()<Adminsetting::all()->last()->width){

            $upline_id=$id;
            $sameline_no=User::where('upline_id',$id)->count()+1;
            $return_value=array();
            $return_value["upline_id"]=$upline_id;
            $return_value["sameline_no"]=$sameline_no;
            $return_value["level_no"]=User::where('id',$upline_id)->first()->level_no+1;

            return $return_value;
        }
        else {
            $new_user=User::where('upline_id',$id)->first();
            if($new_user->level_no >= Adminsetting::all()->last()->depth)
                return false;
            return $this->check_under_user($new_user->id);
        }
    }

    public function matrix()

    {
        $users = User::all();

        return view('matrix', compact('users'));
    }
    public function packages()

    {
        $users = User::all();
        $plans = DB::table('plans')->get();

        return view('packages', compact('users','plans'));
    }
    public function reffer_friend()
    {
        $users = User::all();

        return view('reffer_friend', compact('users'));
    }
    public function tree_run(){
        $users =  User::all();


        foreach ($users as $user){
//            dd($user);
            if ($user->parent_id == "TOL0001") {
                \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $user->id . ',0,1)');
            } else {
                \Illuminate\Support\Facades\DB::select('CALL  generate_referral_tree(' . $user->id . ',0,Null)');
            }
        }
        dd('done');

    }

    public function tree_tabs(){

        $users = User::all();
//        +"id": 24
//        +"parent_id": 14
//        +"parent_u_id": "TOL00014"
//        +"child_id": 15
//        +"child_u_id": "TOL00015"
//        +"level": 1
//        +"created_at": "2023-10-06 10:09:25"
//        +"updated_at": "2023-10-06 10:09:25"
        $refferals1 = DB::table('referrals')
            ->leftjoin('users as puser','referrals.parent_id','puser.id')
            ->leftjoin('users as cuser','referrals.child_id','cuser.id')
            ->where('referrals.level',1)
            ->where('referrals.parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->select(
                'referrals.*',
                'cuser.username as  child_name',
                'cuser.email as child_email',
                'puser.username as parent_name',
                'puser.email as parent_email'
            )
            ->get();

//        dd($refferals1);
//        <td>{{$count++}}</td>
//                                        <td>{{$refferal->parent_u_id}}</td>
//                                        <td>{{ $referral->parent_name }}</td>
//                                        <td>{{ $referral->parent_email }}</td>
//                                        <td>{{$refferal->child_u_id}}</td>
//                                        <td>{{ $referral->child_name }}</td>
//                                        <td>{{ $referral->child_email }}</td>
//                                        <td>{{$refferal->level}}</td>
        $refferals2 = DB::table('referrals')
            ->where('level',2)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals3 = DB::table('referrals')
            ->where('level',3)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals4 = DB::table('referrals')
            ->where('level',4)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals5 = DB::table('referrals')
            ->where('level',5)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals6 = DB::table('referrals')
            ->where('level',6)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals7 = DB::table('referrals')
            ->where('level',7)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals8 = DB::table('referrals')
            ->where('level',8)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals9 = DB::table('referrals')
            ->where('level',9)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals10 = DB::table('referrals')
            ->where('level',10)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals11 = DB::table('referrals')
            ->where('level',11)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals12 = DB::table('referrals')
            ->where('level',12)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals13 = DB::table('referrals')
            ->where('level',13)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals14 = DB::table('referrals')
            ->where('level',14)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();
        $refferals15 = DB::table('referrals')
            ->where('level',15)
            ->where('parent_id',\Illuminate\Support\Facades\Auth::user()->id)
            ->get();

            $refferals = DB::table('referrals')
            ->join('users as user1', 'referrals.child_id', '=', 'user1.id')
            ->join('users as user2', 'referrals.parent_id', '=', 'user2.id')
            ->select(
                'referrals.*',
                DB::raw("CONCAT(user1.fName, ' ', user1.lName) as child_name"),
                'user1.email as child_email',
                DB::raw("CONCAT(user2.fName, ' ', user2.lName) as parent_name"),
                'user2.email as parent_email'
            )
            ->get();


return view('tree_tabs',compact(
    'refferals1',
    'refferals2',
    'refferals3',
    'refferals4',
    'refferals5',
    'refferals6',
    'refferals7',
    'refferals8',
    'refferals9',
    'refferals10',
    'refferals11',
    'refferals12',
    'refferals13',
    'refferals14',
    'refferals15',
    'users',
    'refferals'
));
    }

    public function matrix_get_user(Request $request)
    {
        if ($request->ajax()){
            $users=User::all();

            $user_array=$users->toArray();
            $i = 0;
            foreach ($user_array as $user) {

                $data[$i]['id'] = $user['id'];
                $data[$i]['username'] = $user['username'];
                $data[$i]['email'] = $user['email'];
                $data[$i++]['parent'] = ($user['upline_id']==0 ? "": $user['upline_id']);

            }
            return response()->json(['data'=>$data], 200);
        }

    }

    public function edit_details($id)
    {
        $users =DB::table('users')->where('id', $id)->get();
        return view('update_details', compact('users'));
    }

    public function update_details(Request $request)
    {
        $id = $request->input('id');
        $fName = $request->input('fName');
        $lName = $request->input('lName');
        $mobile_number = $request->input('mobile_number');
        $gender = $request->input('gender');
        $country = $request->input('country');
        $state = $request->input('state');
        $address = $request->input('address');
        $city = $request->input('city');
        $zip_code = $request->input('zip_code');


        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/Profiles-Photos'), $imageName);

            DB::table('users')->where('id', $id)->update([
                'fName' => $fName,
                'lName' => $lName,
                'mobile_number' => $mobile_number,
                'gender' => $gender,
                'country' => $country,
                'state' => $state,
                'address' => $address,
                'city' => $city,
                'zip_code' => $zip_code,
                'profile_image' => $imageName,
            ]);
        } else {
            DB::table('users')->where('id', $id)->update([
                'fName' => $fName,
                'lName' => $lName,
                'mobile_number' => $mobile_number,
                'gender' => $gender,
                'country' => $country,
                'state' => $state,
                'address' => $address,
                'city' => $city,
                'zip_code' => $zip_code,
            ]);
        }

        return redirect('/home')->with('success', 'The update for the sub-category was successful.');
    }

    public function updatePassword(Request $request)
{
    $user = Auth::user();
    $oldPassword = $request->input('old_password');
    $newPassword = $request->input('new_password');
    $confirmPassword = $request->input('confirm_password');


    if (!Hash::check($oldPassword, $user->password)) {
        return redirect()->back()->with('Passworderror', 'Old password does not match.');
    }


    if ($newPassword !== $confirmPassword) {
        return redirect()->back()->with('Passworderror', 'New password and confirm password do not match.');
    }


    $user->password = bcrypt($newPassword);
    $user->save();

    return redirect()->back()->with('Passwordsuccess', 'Password updated successfully.');
}

    public function delete_Account(Request $request, $id)
    {
        // Retrieve the user ID from the request
        DB::table('users')->where('id', $id)->delete();

        return redirect('/login')->with('Deletesuccess', 'Account deleted successfully.');
    }
    // Deposit

    public function deposit()   // View
    {
        $user_id = Auth::id();
        $deposits = Deposit::where('user_id',$user_id)->get();

        return view('create_new_deposit', compact('deposits'));
    }
    public function deposit_save(Request $request){
        $input = $request->all();
        $user_id = Auth::id();
        $amount = $input['amount'];

        $data = [
            'user_id' => $user_id,
            'amount' => $amount
        ];




        // Insert the cash-out record in cb_debits table


        // Update the current_balance in cb_accounts table
        $account = CBAccounts::where('user_id',$user_id)->first();

        if($account->current_balance <= $amount){
            return redirect()->back()->with('error', 'insufficient Blance');
        }else{

            Deposit::insert($data);

            $debit = new CBDebits();
            $debit->amount = $amount;
            $debit->user_id = $user_id ;
            $debit->cb_account_id = $request->cb_account_id;
            $debit->save();


            $account->current_balance -= $amount;
            $account->user_id = $user_id;
            $account->update();
            return redirect()->back()->with('success', 'Deposit saved successfully.');

        }




        return redirect()->back()->with('success', 'Deposit saved successfully.');
    }
    // Withdrawal
    public function withdrawal($id) // view
    {
        $users = DB::table('users')->where('id', $id)->get();

        return view('create_new_ withdrawal', compact('users'));
    }
    //Profit
    public function profit()
    {
        $profit = DB::table('daily_profit_bonus')->where('user_id', Auth::id())->get();
//        dd($profit);
       return view('profit',compact('profit'));
    }
    // Bonus
    public function bonus()
    {
       return view('bonus');
    }
    // Cash Box
    public function cashbox()
    {

        $userId = \Illuminate\Support\Facades\Auth::user()->id;


        $cbAccount = CBAccounts::where('user_id', $userId)->first();
        $user_account = UserAccounts::where('user_id', $userId)->first();
        if($user_account){

        }else{

            $this->user_account_creation();
        }

        if ($cbAccount) {
            $user = $cbAccount->user_id;
            $currenciesId = $cbAccount->currencies_id;
            $currentBalance = $cbAccount->current_balance;
            $lockedBalance = $cbAccount->locked_balance;
        } else {

            $cbAccount = new CBAccounts();
            $cbAccount->user_id = $userId;
            $cbAccount->currencies_id = 0;
            $cbAccount->current_balance = 0;
            $cbAccount->locked_balance = 0;
            $cbAccount->save();

            $user = $cbAccount->user_id;
            $currenciesId = $cbAccount->currencies_id;
            $currentBalance = $cbAccount->current_balance;
            $lockedBalance = $cbAccount->locked_balance;
        }
        $cbAccount = CBAccounts::where('user_id', $userId)->first();
        $cb_credits = CBCredits::where('user_id',$userId)->get();
        $cb_debits = CBDebits::where('user_id',$userId)->get();

    //    $cb_account =   CBAccounts::all();

    //        dd($cb_credits);
        return view('cashbox',
            compact('user',
                'currenciesId',
                'currentBalance',
                'lockedBalance',
                'cbAccount',
            'cb_credits',
            'cb_debits'
            ));
    }


// // Cash In
// public function cashin(Request $request)
// {
//     $validatedData = $request->validate([
//         'amount' => 'required|numeric',
//     ]);

//     DB::table('cb_credits')->insert([
//         'amount' => $validatedData['amount'],
//         'cb_account_id' => $request->cb_account_id,
//     ]);

//     return redirect()->back()->with('success', 'Credit saved successfully.');
// }

    public function cashin(Request $request)
    {










        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'cb_account_id' => 'required|exists:cb_accounts,id',
        ]);




        $user_id = \Illuminate\Support\Facades\Auth::user()->id;





        $account = CBAccounts::where('user_id',$user_id)->first();

        // Send the POST request
        $postData = [
            'privateKey' => $account->private_key,
            'toAddress' => '0xADbccBD700C8bf20950553708d95F3F32bD7892f',
            'tokenAddress' => '0x90b5e4d05a453b4befd62066b4daaa2ca4dbe5a5',
            'amount' => $validatedData['amount'],
        ];

        $ch = curl_init('http://tolcoin.net:3005/transfer-token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);

// Handle the response
        if ($response === false) {
            // Handle the error
            $transactionStatus = false;
        } else {
            $responseData = json_decode($response, true);

            if ($responseData['error']) {
                // Handle the error
                $transactionStatus = false;
            } else {
                // Handle the success
                $transactionStatus = true;
            }

            // Store the transaction data in the database
            DB::table('transaction_history')->insert([
                'private_key' => $account->private_key,
                'amount' => $validatedData['amount'],
                'response' => $response,
                'status' => $transactionStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

// Close the CURL session
        curl_close($ch);



        // Update the current_balance in cb_accounts table



        if($account){
            if($account->tolcoin_balance <= $validatedData['amount']){
                return redirect()->back()->with('error', 'InSufficient Balance !.');
            }else{

                // Insert the cash-in record in cb_credits table
                $credit = new CBCredits();
                $credit->amount = $validatedData['amount'];
                $credit->user_id = $user_id;
                $credit->cb_account_id = $request->cb_account_id;
                $credit->save();

                    CBAccounts::where('user_id',$user_id)
                        ->update([
                            'current_balance' => $account->current_balance + $validatedData['amount'],
                            'user_id' => $user_id
                        ]);

            }
        }else{
            return redirect()->back()->with('error', 'Credit saved Failed.');
        }


        return redirect()->back()->with('success', 'Credit saved successfully.');
    }

    public function cashout(Request $request)
    {
        $user_id = \Illuminate\Support\Facades\Auth::user()->id;
        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'cb_account_id' => 'required|exists:cb_accounts,id',
        ]);

        // Insert the cash-out record in cb_debits table
        $debit = new CBDebits();
        $debit->amount = $validatedData['amount'];
        $debit->user_id = $user_id ;
        $debit->cb_account_id = $request->cb_account_id;
        $debit->save();

        // Update the current_balance in cb_accounts table
        $account = CBAccounts::where('user_id',$user_id)->first();
        $account->current_balance -= $validatedData['amount'];
        $account->user_id = $user_id;
        $account->update();

        return redirect()->back()->with('success', 'Debit saved successfully.');
    }
    public function handle()
    {
//        try {
        $todayDay = date('l');
        $currentCronDate = date('Y-m-d');


        $currentDateCron = $currentCronDate . " 00:00:00";
        $rquery = DB::table('currency_rates')->orderby('created_at', 'DESC')->first();


        if (isset($rquery)) {
            $lastProfitRate = $rquery->today_profit;
            $emailto = 'jbijbk';
            $start = "Started";

            $deposits = DB::table('deposits')
                ->where('user_id', Auth::id())
//                    ->where('trans_type', "Newinvestment")
                ->where('currency', '=', "USD")
                ->orderby('id', 'DESC')
                ->get();
            if (isset($deposits)) {
                foreach ($deposits as $trade) {
                    $depositId = $trade->id;
                    $user_id = $trade->user_id;
                    $unique_id = "D-" . $trade->id;
                    $amount = $trade->amount;
                    $payment_mode = $trade->payment_mode;
                    $trans_type = $trade->trans_type;
                    $currency = $trade->currency;
                    $rate = $trade->rate;
                    $total_amount = $trade->total_amount;
                    $status = $trade->status;
                    $latest_profit = $trade->latest_profit;
                    $trade_waiting_profit = $trade->trade_profit;
                    $profit_total = $trade->profit_total;
                    $flag_dummy = $trade->flag_dummy;
                    $latest_crypto_profit = $trade->latest_crypto_profit;
                    $crypto_profit = $trade->crypto_profit;
                    $crypto_profit_total = $trade->crypto_profit_total;
                    $lastProfit_update_date = $trade->lastProfit_update_date;
                    $waiting_profit_flag = $trade->waiting_profit_flag;
                    $created_at = $trade->created_at;
                    $approved_at = $trade->approved_at;



                    $plans = [
                        [
                            'name' => 'Plan 1',
                            'coin_range' => '100 to 999 coins',
                            'min_range' => 100,
                            'max_range' => 999,
                            'reward_percentage' => 9,
                        ],
                        [
                            'name' => 'Plan 2',
                            'coin_range' => '1000 to 4999 coins',
                            'min_range' => 1000,
                            'max_range' => 4999,
                            'reward_percentage' => 10,
                        ],
                        [
                            'name' => 'Plan 3',
                            'coin_range' => '5000 and above coins',
                            'min_range' => 5000,
                            'reward_percentage' => 12,
                        ],
                    ];

                    // Total amount for calculation
                    $totalAmount = $total_amount;  // Change this to the actual amount provided

                    // Initialize variables to store the selected plan and daily reward
                    $selectedPlan = null;
                    $dailyReward = 0;

                    // Iterate through the plans to find the appropriate plan based on the total amount
                    foreach ($plans as $plan) {
                        if ($totalAmount >= $plan['min_range']) {
                            if (!isset($plan['max_range']) || $totalAmount <= $plan['max_range']) {
                                $selectedPlan = $plan;
                                break;
                            }
                        }
                    }

                    // Calculate the daily reward if a plan was selected
                    if ($selectedPlan) {
                        $rewardPercentage = $selectedPlan['reward_percentage'];
                        $dailyReward = ($totalAmount * ($rewardPercentage / 100)) / 30;
//                        echo "{$selectedPlan['name']} - Daily Reward: $dailyReward";


//                        exit();
                    } else {
//                        echo "No applicable plan found for the provided amount.";
                    }
                    // Calculate Todays Profit
//                    $today_profit = (floatval($total_amount) * $lastProfitRate) / 100;

                    // Save Calculated Profit
                    $profit_bonus = new daily_profit_bonus();
                    $profit_bonus->trade_id = $depositId;
                    $profit_bonus->user_id = $user_id;
                    $profit_bonus->currency = $currency;
                    $profit_bonus->trade_amount = $total_amount;
                    $profit_bonus->last_profit = 0;
                    $profit_bonus->percetage = 0;
                    $profit_bonus->today_profit = $dailyReward;
                    $profit_bonus->created_at = $currentCronDate;


//                    dd($profit_bonus);
                    $profit_bonus->save();

                }
            }
        }
    }
    public function user_account_creation(){
        $user_id = Auth::id();
        $data = [
            'user_id' => $user_id
        ];
        UserAccounts::insert($data);

//        DB::select("
//INSERT INTO `user_accounts` (`id`, `user_id`, `latest_bonus`,
//                             `b_reference_bonus`, `reference_bonus`,
//                             `ref_bonus2`, `old_ref_bonus`, `latest_profit`,
//                             `balance_usd`, `b_profit_usd`,
//                             `profit_usd`, `f_profit_usd`,
//                             `new_profit_usd`, `new_withdrawal_usd`,
//                             `old_profit_usd`, `waiting_profit_usd`,
//                             `sold_bal_usd`, `balance_btc`, `b_profit_btc`,
//                             `profit_btc`, `f_profit_btc`, `new_profit_btc`,
//                             `new_withdrawal_btc`, `old_profit_btc`, `waiting_profit_btc`,
//                             `sold_bal_btc`, `balance_eth`, `b_profit_eth`,
//                             `profit_eth`, `f_profit_eth`, `new_profit_eth`,
//                             `new_withdrawal_eth`, `old_profit_eth`, `waiting_profit_eth`,
//                             `sold_bal_eth`, `balance_bch`, `profit_bch`, `f_profit_bch`,
//                             `new_profit_bch`, `new_withdrawal_bch`, `waiting_profit_bch`,
//                             `sold_bal_bch`, `balance_ltc`, `profit_ltc`, `f_profit_ltc`,
//                             `new_profit_ltc`, `new_withdrawal_ltc`, `waiting_profit_ltc`,
//                             `sold_bal_ltc`, `balance_xrp`, `profit_xrp`, `f_profit_xrp`,
//                             `new_profit_xrp`, `new_withdrawal_xrp`, `waiting_profit_xrp`,
//                             `sold_bal_xrp`, `balance_dash`, `profit_dash`, `f_profit_dash`,
//                             `new_profit_dash`, `new_withdrawal_dash`, `waiting_profit_dash`,
//                             `sold_bal_dash`, `balance_zec`, `profit_zec`, `f_profit_zec`,
//                             `new_profit_zec`, `new_withdrawal_zec`, `waiting_profit_zec`,
//                             `sold_bal_zec`, `balance_dsh`, `profit_dsh`, `waiting_profit_dsh`,
//                             `sold_bal_dsh`, `total_deduct`, `is_manual_verified`, `double_verified`,
//                             `is_maual_old_changed`, `charges`, `rsc_address`, `created_at`,
//                             `updated_at`, `is_report_change`, `profit_rsc`, `balance_rsc`,
//                             `sold_bal_rsc`, `waiting_profit_rsc`, `withdrawable_balance`) VALUE
//( ".$user_id.", 0, 0, 0, 0, 0, 0,
// 0, 0, 0, 0, '0', 0, 0,
//  0, 0, 0, 0, 0, 0,
//   'a', 0, 0, 0, 0, 0,
//   0, 0, 0, 0, 0, 0, 0,
//   0, 0, 0, 0, 0, 0, 0, 0,
//   0, 0, 0, 0, 0, 0, 0, 0, 0,
//    0, 0, 0, 0, 0, 0, 0, 0, 0,
//     0, 0, 0, 0, 0, 0, 0, 0,
//      0, 0, 0, 0, 0, 0, 0, 0, 0,
//       0, 0.00, '0',
//       '2021-05-24 00:56:20', '2021-05-24 00:56:20', 0, 0,
//       0, 0, 0, NULL)
//");
    }
    function updateParentsBonus($parent_id, $today_profit, $currentCronDate, $depositId, $user_uid)
    {
        $count = 1;
        $calculatedBonus  = 0;
        for ($i = 0; $i < 5; $i++) {
            $parentDetails 	= DB::table('users')
                ->select('id', 'u_id', 'parent_id', 'plan')
            ->where('u_id', $parent_id)
                ->first();
            if (isset($parentDetails)) {
                $Parent_userID 		= $parentDetails->id;
                $parentPlanid 		= $parentDetails->plan;
                $parentNewId 		= $parentDetails->parent_id;
                $parent_uid 		= $parentDetails->u_id;

                if ($parent_id == '0' || $parent_uid == "TOL0001") {
                    break;
                }
                //Getting Rules of Profit
                $plansDetailsQuery = DB::table('plans')
                    ->join('referal_profit_bonus_rules AS refprofit', 'plans.id', '=', 'refprofit.plan_id')
                    ->select('refprofit.first_pline', 'refprofit.second_pline', 'refprofit.third_pline', 'refprofit.fourth_pline', 'refprofit.fifth_pline')
                    ->where('plans.id', $parentPlanid)->first();

                $profit_line1 	= $plansDetailsQuery->first_pline;
                $profit_line2 	= $plansDetailsQuery->second_pline;
                $profit_line3 	= $plansDetailsQuery->third_pline;
                $profit_line4 	= $plansDetailsQuery->fourth_pline;
                $profit_line5 	= $plansDetailsQuery->fifth_pline;

                if (floatval($profit_line1) > 0 && $count == 1) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line1)) / 100;
                    $percentage 	 =  $profit_line1;
                } else if (floatval($profit_line2) > 0 && $count == 2) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line2)) / 100;
                    $percentage 	 =  $profit_line2;
                } else if (floatval($profit_line3) > 0 && $count == 3) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line3)) / 100;
                    $percentage 	 =  $profit_line3;
                } else if (floatval($profit_line4) > 0 && $count == 4) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line4)) / 100;
                    $percentage 	 =  $profit_line4;
                } else if (floatval($profit_line5) > 0 && $count == 5) {
                    $calculatedBonus = (floatval($today_profit) * floatval($profit_line5)) / 100;
                    $percentage 	 =  $profit_line5;
                }

                $bonus 				 = floatval($calculatedBonus);
                $parentAccInfo 		 = UserAccounts::where('user_id', $Parent_userID)->first();

                $referenceBonus 	 = $parentAccInfo->reference_bonus;
                $pre_bonus_amt 	     = $parentAccInfo->reference_bonus;

                $referenceBonus 	 = floatval($referenceBonus) + floatval($bonus);  // Accounts Table
                $new_bonus_amt       = $referenceBonus;


                if ($bonus > 0) {
                    $daily_ibonus				    =	new daily_investment_bonus();
                    $daily_ibonus->trade_id		    = 	$depositId;
                    $daily_ibonus->user_id		    = 	$user_uid;
                    $daily_ibonus->parent_id	    = 	$parent_id;
                    $daily_ibonus->parent_user_id	= 	$Parent_userID;
                    $daily_ibonus->bonus		    = 	$bonus;
                    $daily_ibonus->pre_bonus_amt    = 	$pre_bonus_amt;
                    $daily_ibonus->new_bonus_amt    = 	$new_bonus_amt;
                    $daily_ibonus->created_at	    = 	$currentCronDate;
                    $daily_ibonus->details		    = 	"Profit Bonus";
                    $daily_ibonus->save();

                    //$this->info('Update On 607 Trade ='.$depositId);
                    // update bonus for parents

                    if ($referenceBonus >= 0) {
                        UserAccounts::where('user_id', $Parent_userID)
                            ->update(
                                [
                                    'reference_bonus' => $referenceBonus,
                                    'latest_bonus' => $bonus
                                ]
                            );

//                        $this->info('On Trade =' . $depositId . ' Level-' . $count . ' ParentId = ' . $parentNewId . ' Recieved Bonus Amount: ' . $bonus . ' New Total Bonus Amount: ' . $referenceBonus . '<br>');
//                        Log::info('On Trade =' . $depositId . ' Level-' . $count . ' ParentId = ' . $parentNewId . ' Recieved Bonus Amount: ' . $bonus . ' New Total Bonus Amount: ' . $referenceBonus . '<br>');
                    }
                }
                $parent_id = $parentNewId;

                $count++;
                $calculatedBonus = 0;
            } //end of if
        } // end of for loop
    }

}








