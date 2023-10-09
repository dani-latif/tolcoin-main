<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Currencies extends Model
{
    protected $table = 'currencies';
   // protected $fillable = ['name','code','smallcode'];

    // Get currencies in object or get name of currencies.
    public static function getCurrencies($columnName = null){
        $queryBuilder = Currencies::distinct('code')->where('status', 'Active');
        if($columnName){
            $queryBuilder =$queryBuilder->pluck($columnName);
        }else{
          $queryBuilder=   $queryBuilder->get();
        }
        return $queryBuilder;
    }

    public static function getCurrencyPrecisionQueryObject(string $compareTableCurrencyId)
    {
        return Currencies::selectRaw('currency_precision')->whereColumn('currencies.id', $compareTableCurrencyId);
    }
}