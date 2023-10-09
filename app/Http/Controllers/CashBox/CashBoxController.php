<?php

namespace App\Http\Controllers\CashBox;

use App\CBAccounts;
use App\fund_beneficiary;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashBox\CashboxDebitRequest;
use App\Http\Requests\CashBox\CashBoxTransferRequest;
use App\Http\Requests\CashBox\IsValidCashBoxAccountIdRequest;
use App\Http\Requests\Currency\CurrencyIdRequest;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CashBoxController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|RedirectResponse|Response|View
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        ## find users accounts.
        /** @var CBAccounts $userAccounts */
        $userAccounts = $user->cashBoxAccounts()->first();
        if (empty($userAccounts)) {
            ## if no account has attached to the user, then redirect user to no account attached page.
            return view('cash_box.no-account-attached');
        } else {
            ## current logged in user has attached to some accounts, so, system will redirect user to single account.
            return redirect()->route('cash-box.show', [$userAccounts->id]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        dd($request->all());
    }
    /**
     * Display the specified resource.
     *
     * @param IsValidCashBoxAccountIdRequest $isValidCashBoxAccountIdRequest
     * @return Application|Factory|View|void
     */
    public function show(IsValidCashBoxAccountIdRequest $isValidCashBoxAccountIdRequest)
    {
        /** @var User $user */

        $current_cash_box_account = CBAccounts::where('id', $isValidCashBoxAccountIdRequest->cash_box)->first();
        $user = User::find($current_cash_box_account->user_id);
        $cash_box_accounts = $user->cashBoxAccounts()->get();

        if (!$current_cash_box_account instanceof CBAccounts) {
            ## If no CashBox account found then redirect user to Not CashBox found Page.
            return view('cash_box.no-account-attached');
        } else {

            $FundBenificary = fund_beneficiary::where('user_id', $current_cash_box_account->user_id)
                ->where('status', 0)
                ->orderby('created_at', 'DESC')->get();

            ## If Cashbox account found then show him.
            return view('cash_box.index', [
                'user' => $user,
                'users_cash_box_accounts' => $cash_box_accounts,
                'current_cash_box_account' => $current_cash_box_account,
                'beneficiaries' => $FundBenificary
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, int $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id)
    {
        //
    }

    /**
     * @Purpose Transfer CashBox Amount, from one users to other users!
     * @param CashBoxTransferRequest $cashBoxTransferRequest
     * @return RedirectResponse
     */
    public function fundTransfer(CashBoxTransferRequest $cashBoxTransferRequest)
    {

        try {
            ## Get User Id.
            $TransferFromUserId = $cashBoxTransferRequest->user()->id;
            $ReceiverB4UId = $cashBoxTransferRequest->fund_receivers_id;
            if (empty($ReceiverB4UId) || $ReceiverB4UId == 'notexist') {
                $ReceiverB4UId = $cashBoxTransferRequest->fund_receivers_id2;
            }
            $TransferToUser = User::where('u_id', $ReceiverB4UId)->first();

            if ($TransferToUser instanceof User) {
                $TransferToUserId = $TransferToUser->id;
                ## if we successfully find a user, to whom the money is transferring.
                DB::select(" CALL `fundTransferToUser`('{$cashBoxTransferRequest->cb_account}', {$cashBoxTransferRequest->amount}, {$TransferToUserId}, {$TransferFromUserId})");
                return back()->with('successmsg', $cashBoxTransferRequest->amount . ' ' . $cashBoxTransferRequest->cb_account . ' has been successfully transfer to ' . $ReceiverB4UId);
            } else {
                return back()->with('errormsg', 'Receiver user\'s B4U Id is not valid');
            }

        } catch (\Exception $exception) {
            Log::info('FailedCashBoxAmountTransfer', [
                'reqeuest' => $cashBoxTransferRequest->all(),
                'errorMsg' => $exception->getMessage()
            ]);
            return back()->with('errormsg', $exception->getMessage());
        }

    }

    public function approveCbTransfer(CashboxDebitRequest $request){
        try {
            $response = CBAccounts::approveCbTransferBySender($request->id);
            if($response){
                return "Success";
            }else{
                return false;
            }
        }catch(\Exception $exception){
            Log::error('error while doing Approve Cb transfer', [
                'errorMessage' => $exception->getMessage(),
                'errorLine' => $exception->getLine(),
                'errorFile' => $exception->getFile()
            ]);
            return $exception->getMessage();
        }
    }

}
