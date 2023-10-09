<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankAccountModel extends Model
{
    protected $table = 'bank_accounts';

    /**
     * @Purpose: This function returns All Bank account with their country name
     * @Developer: Suleman Khan <sulaman@sulaman.pk>
     * @return mixed
     */
    public static function getAllBankAccounts()
    {

        return Cache::remember('bankAccountsCache',settings::TopCacheTimeOut, function () {
            $bankAccounts = DB::table('bank_accounts')->select('*')->leftJoin('countries', 'bank_accounts.country_id', '=', 'countries.id')
                ->where('is_hide', '=', 0)
                ->orderBy('country_id', 'DESC')->get();
            $tempArray = [];
            foreach ($bankAccounts as $account) {
                $tempArray[$account->country_id][] = $account;
            }

            return $tempArray;

        });

    }

     public static function getPakBankAccounts()
    {
        return Cache::remember('pakBankAccountsCache',settings::TopCacheTimeOut, function () {
            $pakBankAccounts = DB::table('bank_accounts')->select('*','banks.id as bank_id')
            ->leftJoin('countries', 'bank_accounts.country_id', '=', 'countries.id')
            ->leftJoin('banks', 'bank_accounts.bank_name', '=', 'banks.bank_name')
            ->where('bank_accounts.country_id', 167)->groupBy('bank_accounts.bank_name')->get();
            return $pakBankAccounts;
        });
    }

    public static function getBankCities(){
        return Cache::remember('getBankCities',settings::TopCacheTimeOut,function (){
           return self::select('branch_city')->where('country_id',167)->whereNotNull('branch_city')->groupBy('branch_city')->get();
        });
    }

    /*public static function getAccountDetails(){
        return Cache::remember('getAccountDetails',settings::TopCacheTimeOut,function (){
            return self::select('account_title','account_number')->where('country_id',167)->get();
        });
    }*/
}
