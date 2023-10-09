<?php

namespace App\Http\Controllers;

use App\Model\Percentage;
use App\settings;
use Illuminate\Http\Request;
use App\Model\Referral;
use App\current_rate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExratesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth');
        set_time_limit(0);
        // GET EMAIL TEMPLATE DATA SENT IN EMAILS
    }

    public function rates()
    {
        $ratesQueryResults = DB::table('currency_rates')->orderby('created_at', 'DESC')->get();
        $currentRates = DB::table('current_rate')->orderby('created_at', 'DESC')->first();

        return view('exchange_rates')->with(
            array(
            'title' => 'Add Exchange Rates', 'ratesQueryResults' => $ratesQueryResults,
            'currentRates' => $currentRates
            )
        );
    }

    public function postrates(Request $request)
    {
        $validator = $this->validate(
            $request,
            [
            'today_profit' => 'required',

            ]
        );
        $ratesQuery = DB::table('current_rate')->orderby('created_at', 'DESC')->first();
        if ($validator) {
            if (!empty($request['rate_btc'])) {
                $rate_btc = $request['rate_btc'];
            } else {
                $rate_btc = $ratesQuery->rate_btc;
            }
            if (!empty($request['rate_eth'])) {
                $rate_eth = $request['rate_eth'];
            } else {
                $rate_eth = $ratesQuery->rate_eth;
            }
            if (!empty($request['rate_bch'])) {
                $rate_bch = $request['rate_bch'];
            } else {
                $rate_bch = $ratesQuery->rate_bch;
            }
            if (!empty($request['rate_ltc'])) {
                $rate_ltc = $request['rate_ltc'];
            } else {
                $rate_ltc = $ratesQuery->rate_ltc;
            }
            if (!empty($request['rate_xrp'])) {
                $rate_xrp = $request['rate_xrp'];
            } else {
                $rate_xrp = $ratesQuery->rate_xrp;
            }

            if (!empty($request['rate_dash'])) {
                $rate_dash = $request['rate_dash'];
            } else {
                $rate_dash = $ratesQuery->rate_dash;
            }

            if (!empty($request['rate_zec'])) {
                $rate_zec = $request['rate_zec'];
            } else {
                $rate_zec = $ratesQuery->rate_zec;
            }

            if (!empty($request['today_profit'])) {
                $today_profit = $request['today_profit'];
            } else {
                $today_profit = $ratesQuery->today_profit;
            }
            if (!empty($request['cron_date'])) {
                $cron_date = $request['cron_date'];
            } else {
                $cron_date = $ratesQuery->cron_date;
            }
            //$rate_dsh   = $request['rate_dsh'];

            Percentage::now($today_profit);
            Referral::updateToUsdAll();

            $urlRate = 'https://ewallet.b4uwallet.com/api/v2/peatio/public/markets/tickers';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $urlRate);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $data_main = json_decode($response);
            curl_close($curl);
            if ($response) {
                $rate_rsc = $data_main->rscusd->ticker->last;
            }
              else{
                  $rate_rsc = $ratesQuery->rate_rsc;
              }


            if (isset($ratesQuery)) {
                $rates_id = $ratesQuery->id;
                $message = 'Currency Rates are updated!';
                DB::table('current_rate')->where('id', $rates_id)->update(
                    [
                    'today_profit' => $today_profit,
                    'cron_date' => $cron_date,
                    'rate_usd' => '1',
                    'rate_btc' => $rate_btc,
                    'rate_eth' => $rate_eth,
                    'rate_bch' => $rate_bch,
                    'rate_ltc' => $rate_ltc,
                    'rate_xrp' => $rate_xrp,
                    'rate_zec' => $rate_zec,
                    'rate_dash' => $rate_dash,
                    'rate_rsc' => $rate_rsc,
                    ]
                );
                Cache::put('lastCurrencyRate', current_rate::find($rates_id));

            } else {
                $message = 'New Rates added successful!';
                $date = date("Y-m-d h:i:s");
                $rquery1 = new current_rate();
                $rquery1->today_profit = $today_profit;
                $rquery1->rate_usd = 1;
                $rquery1->rate_btc = $rate_btc;
                $rquery1->rate_eth = $rate_eth;
                $rquery1->rate_bch = $rate_bch;
                $rquery1->rate_ltc = $rate_ltc;
                $rquery1->rate_xrp = $rate_xrp;
                $rquery1->rate_zec = $rate_zec;
                $rquery1->rate_dash = $rate_dash;
                $rquery1->created_at = $date;
                $rquery1->save();

                ## store last currency rate.
                Cache::put('lastCurrencyRate', $rquery1);
            }

            //save rates info
            /* $rquery = new currency_rates();
             $rquery->today_profit = $today_profit;
             $rquery->rate_usd = 1;
             $rquery->rate_btc = $rate_btc;
             $rquery->rate_eth = $rate_eth;
             $rquery->rate_bch = $rate_bch;
             $rquery->rate_ltc = $rate_ltc;
             $rquery->rate_xrp = $rate_xrp;
             $rquery->rate_zec = $rate_zec;
             $rquery->rate_dash = $rate_dash;
             $rquery->save();*/
            /* // Comment By Mudassar
            $today = date("Y-m-d");
            $equery = new Event();
            $equery->title      =  $today_profit."%";
            $equery->start_date =  $today;
            $equery->end_date   =  $today;
            $equery->save();
            */
            return redirect()->back()->with('successmsg', $message);
        } else {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }
    }

    public function getRatesOnline()
    {
        $urlRateFinder = 'https://min-api.cryptocompare.com/data/pricemulti?fsyms=BTC,LTC,XRP,DASH,ETH,ZEC,BCH&tsyms=USD';
        $file_headers = @get_headers($urlRateFinder);

        if (isset($file_headers)) {
            if (isset($file_headers[0]) && ('HTTP/1.1 404 Not Found' == $file_headers[0] || 'HTTP/1.0 404 Not Found' == $file_headers[0])) {
                $exists = 'false';
                if ('false' == $exists) {
                    $searchResult = ['BtcRate' => 0, 'EthRate' => 0, 'BchRate' => 0, 'LtcRate' => 0, 'XrpRate' => 0, 'DashRate' => 0, 'ZecRate' => 0, 'result' => 'SearchNotFind'];
                    echo json_encode($searchResult);
                    exit();
                }
            } else {
                $exists = 'true';
                $urlRate = 'https://min-api.cryptocompare.com/data/pricemulti?fsyms=BTC,LTC,XRP,DASH,ETH,ZEC,BCH&tsyms=USD';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $urlRate);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                // curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                //     'Accepts: application/json',
                //     'X-CMC_PRO_API_KEY: 235c8571-1a73-482e-802f-2deb6a737c4d'
                // ));

                $responce = curl_exec($curl);
                $data_main = json_decode($responce);
                curl_close($curl);

                if ($responce) {
                    $currency = ['BTC'];
                    $BTC = $data_main->BTC->USD;
                    if (!isset($BTC)) {
                        $BTC = 0;
                    }
                    $LTC = $data_main->LTC->USD;
                    if (!isset($LTC)) {
                        $LTC = 0;
                    }
                    $XRP = $data_main->XRP->USD;
                    if (!isset($XRP)) {
                        $XRP = 0;
                    }
                    $DASH = $data_main->DASH->USD;
                    if (!isset($DASH)) {
                        $DASH = 0;
                    }
                    $ETH = $data_main->ETH->USD;
                    if (!isset($ETH)) {
                        $ETH = 0;
                    }
                    $ZEC = $data_main->ZEC->USD;
                    if (!isset($ZEC)) {
                        $ZEC = 0;
                    }
                    $BCH = $data_main->BCH->USD;
                    if (!isset($BCH)) {
                        $BCH = 0;
                    }
                }

                $searchResult = ['BtcRate' => $BTC, 'EthRate' => $ETH, 'BchRate' => $BCH, 'LtcRate' => $LTC, 'XrpRate' => $XRP, 'DashRate' => $DASH, 'ZecRate' => $ZEC];
                echo json_encode($searchResult);
                exit();
            }
        } else {
            $searchResult = ['BtcRate' => 0, 'EthRate' => 0, 'BchRate' => 0, 'LtcRate' => 0, 'XrpRate' => 0, 'DashRate' => 0, 'ZecRate' => 0, 'result' => 'SearchNotFind'];
            echo json_encode($searchResult);
            exit();
        }
    }

    public function getRatesFromWallet()
    {
        $apiGetRates = "https://ewallet.b4uwallet.com/api/v2/peatio/public/markets/tickers";
        $file_headers = @get_headers($apiGetRates);
        if (isset($file_headers)) {
            if (isset($file_headers[0]) && ('HTTP/1.1 404 Not Found' == $file_headers[0] || 'HTTP/1.0 404 Not Found' == $file_headers[0])) {
                $msg = "Rate not founded";
            } else {
                //$urlRate = 'https://ewallet.b4uwallet.com/api/v2/peatio/public/markets/tickers?fsyms=rscusd,LTC,XRP,DASH,ETH,ZEC,BCH&tsyms=USD';
                $urlRate = 'https://ewallet.b4uwallet.com/api/v2/peatio/public/markets/tickers';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $urlRate);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($curl);
                $data_main = json_decode($response);
                curl_close($curl);

               $currentRate = current_rate::first();
               $threshold = settings::first();
               $update = 0;
                if ($response) {
                    $apiRSC = $data_main->rscusd->ticker->last;
                    $RSC = $currentRate->rate_rsc;
                    if ($apiRSC - $RSC >= $threshold->rsc_threshold) {
                        $RSC = $apiRSC;
                        $update = 1;
                    }
                    $apiBTC = $data_main->btcusd->ticker->last;
                    $BTC = $currentRate->rate_btc;
                    if ($apiBTC - $BTC >= $threshold->btc_threshold) {
                        $BTC = $apiBTC;
                        $update = 1;
                    }
                    $apiLTC = $data_main->ltcusd->ticker->last;
                    $LTC = $currentRate->rate_ltc;
                    if ($apiLTC - $LTC >= $threshold->ltc_threshold) {
                        $LTC = $apiLTC;
                        $update = 1;
                    }
                    $apiXRP = $data_main->xrpusd->ticker->last;
                    $XRP = $currentRate->rate_xrp;
                    if ($apiXRP - $XRP >= $threshold->xrp_threshold) {
                        $XRP = $apiXRP;
                        $update = 1;
                    }
                    $apiDASH = $data_main->dashusd->ticker->last;
                    $DASH = $currentRate->rate_dash;
                    if ($apiDASH - $DASH >= $threshold->dash_threshold) {
                        $DASH = $apiDASH;
                        $update = 1;
                    }
                    $apiETH = $data_main->ethusd->ticker->last;
                    $ETH = $currentRate->rate_eth;
                    if ($apiETH - $ETH >= $threshold->eth_threshold) {
                        $ETH = $apiETH;
                        $update = 1;
                    }
                    $apiZEC = $data_main->zecusd->ticker->last;
                    $ZEC = $currentRate->rate_zec;
                    if ($apiZEC - $ZEC >= $threshold->zec_threshold) {
                        $ZEC = $apiZEC;
                        $update = 1;
                    }
                    $apiBCH = $data_main->bchusd->ticker->last;
                    $BCH = $currentRate->rate_bch;
                    if ($apiBCH - $BCH >= $threshold->bch_threshold) {
                        $BCH = $apiBCH;
                        $update = 1;
                    }
                    $msg = "Success";
                   if($update == 1) {
                       $currentRate->rate_btc = $BTC;
                       $currentRate->rate_eth = $ETH;
                       $currentRate->rate_ltc = $LTC;
                       $currentRate->rate_bch = $BCH;
                       $currentRate->rate_xrp = $XRP;
                       $currentRate->rate_dash = $DASH;
                       $currentRate->rate_zec = $ZEC;
                       $currentRate->rate_rsc = $RSC;
                       $currentRate->save();
                       Cache::pull('lastCurrencyRate');
                       $msg = "Updated";
                   }
                }
                else{
                    $msg = "No Data Founded";
                }
            }
        } else {
            $msg =  "Invalid Request";
        }
        echo $msg;
        exit();
    }
}
