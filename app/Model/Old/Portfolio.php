<?php

namespace App\Model\Old;

use App\Model\Deposit;
use App\User;
use App\withdrawals;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $connection = 'mysqlold3';

    protected $table = 'portfolio';


    static function updateSoldAtToNewSystem()
    {
        Portfolio::whereNotNull('sold_at')->chunk(1000, function ($portfolios) {
            self::process($portfolios);
        });
    }

    static function process($portfolios)
    {
        foreach ($portfolios as $portfolio) {
            $user = User::where('u_id', $portfolio->user_id);
            if ($user->count()) {
                $user = $user->first();
                echo $portfolio->amount() . "\n";

                $deposit = Deposit::where('currency', $portfolio->investment_type)->where('user_id', $user->id)->where('amount', 'like', $portfolio->amount())->where('created_at', $portfolio->date_time);
                echo $deposit->count();
                if ($deposit->count()) {
                    $deposit = $deposit->first();
                    $deposit->sold_at = $portfolio->sold_at;
                    $deposit->save();
                    $deposit_id = $deposit->id;
                    $user_id = $user->id;
                    $b4uid = $user->u_id;
                    echo "\ndeposit updated deposit($deposit_id) user($user_id) user($b4uid) \n";

                }

            }
        }
    }

    static function sync($u_id)
    {
        Portfolio::where('user_id', $u_id)->whereNotNull('sold_at')->chunk(1000, function ($portfolios) {
            self::process($portfolios);

        });
        echo "here";
        Portfolio::where('user_id', $u_id)->where('type', 'withdrawal')->where('sold', 1)->chunk(1000, function ($portfolios) {
            self::processWithdrawals($portfolios);
        });
    }

    static function processWithdrawals($portfolios)
    {
        foreach ($portfolios as $portfolio) {
            $user = User::where('u_id', $portfolio->user_id);
            if ($user->count()) {
                $user = $user->first();
                echo $portfolio->amount() . "\n";

                $withdrawal = withdrawals::where('currency', $portfolio->investment_type)->where('user', $user->id)->where('amount', 'like', $portfolio->total_amount())->where('payment_mode', '');
                echo $withdrawal->count();
                if ($withdrawal->count()) {
                    $withdrawal = $withdrawal->first();
                    if ($portfolio->sold) {
                        $withdrawal->payment_mode = 'sold';
                        $withdrawal->save();
                    }
                    $deposit_id = $withdrawal->id;
                    $user_id = $user->id;
                    $b4uid = $user->u_id;
                    echo "\nwithdrawal updated deposit($deposit_id) user($user_id) user($b4uid) \n";

                }

            }
        }
    }

    function total_amount()
    {
        if ($this->investment_type == 'USD') {
            return $this->total_amount;
        }

        if ($this->investment_type == 'BTC') {
            return $this->total_amount_btc;
        }
        if ($this->investment_type == 'ETH') {
            return $this->total_amount_eth;
        }
    }

    function amount()
    {
        if ($this->investment_type == 'USD') {
            return $this->dollar;
        }

        if ($this->investment_type == 'BTC') {
            return $this->bit_coins;
        }
        if ($this->investment_type == 'ETH') {
            return $this->ethereum;
        }

    }
    //
}
