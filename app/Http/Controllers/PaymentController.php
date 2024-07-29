<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\Country;

class PaymentController extends Controller
{

    /* End BitCoine Section */
    public function paymentcheckurl()
    {
        return view('payment_check');
    }

    public  function getipaddress()
    {
        $wltAmt = getWalletAmount();
        $amount = 20;
        // $ip = $_SERVER['REMOTE_ADDR'];
        $ip = ($wltAmt) > 0 ? $wltAmt : $amount;
        //  $finalres =  ipaddressconr($ip);
        //  $usdamt =  $finalres['data']['famt'];
        // $curcode =  $finalres['data']['currency']; 
        echo $ip; exit;
        
    }



    public function currency()
    {
        $url = "https://api.apilayer.com/fixer/convert?to=INR&from=USD&amount=1";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "apikey: MwjolrLIE6aTyKwWuYrYRqZFy3ai61sc",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($curl);
        $json = json_decode($resp, true);
        $date = $json['date'];
        $usd = $json['query']['amount'];
        $inr = $json['result'];
        $finalinr = number_format($inr, 2);
        $config = Config::where('id', 1)->first();
        $config->usd      = $usd;
        $config->inr      = $finalinr;
        $config->status    = 1;
        if ($config->save()) {
            $udata = array('usd amount' => $config->usd, 'inr amount' => $config->inr);
            $return['code']        = 200;
            $return['data']        = $udata;
            $return['message']     = 'successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return response()->json($return);
    }
}
