<?php

namespace App\Http\Controllers\AppPublisher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AppPubDashboardUserController extends Controller
{
    public function cia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',

        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid =  $request->uid;
        $option =  $request->option;
        $userdetail = User::where('uid', $uid)->first();
        if (empty($userdetail)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        $userdata = DB::select("SELECT date(udate) as date, country, device_os, device_type, SUM(amount) amt, SUM(impressions) impression, SUM(clicks) click 
      						FROM ss_pub_stats imp WHERE imp.publisher_code='" . $uid . "' 
            				AND DATE(imp.udate) >= DATE('" . $date . "') GROUP BY imp.country, device_os, device_type, date(udate)");
        $dates = array_unique(array_column($userdata, 'date'));
        $imps = array_sum(array_column($userdata, 'impression'));
        $clks = array_sum(array_column($userdata, 'click'));
        $amts = array_sum(array_column($userdata, 'amt'));
        $device = [
            "desktop" => [
                "impression" => 0,
            ],
            "mobile" => [
                "impression" => 0,
            ],
            "tablet" => [
                "impression" => 0,
            ]
        ];

        $device2 = [
            "desktop" => [
                "click" => 0
            ],
            "mobile" => [
                "click" => 0
            ],
            "tablet" => [
                "click" => 0
            ]
        ];

        $os = [
            "linux" => [
                "impression" => 0,
                "click" => 0
            ],
            "windows" =>  [
                "impression" => 0,
                "click" => 0
            ],
            "android" =>  [
                "impression" => 0,
                "click" => 0
            ],
            "apple" =>  [
                "impression" => 0,
                "click" => 0
            ]
        ];

        $gdata = [];

        foreach ($userdata as $udata) {
            if ($udata->device_type == 'Mobile') {
                $device['mobile']['impression'] = $device['mobile']['impression'] + $udata->impression;
                $device2['mobile']['click'] = $device2['mobile']['click'] + $udata->click;
            } elseif ($udata->device_type == 'Desktop') {
                $device['desktop']['impression'] = $device['desktop']['impression'] + $udata->impression;
                $device2['desktop']['click'] = $device2['desktop']['click'] + $udata->click;
            } elseif ($udata->device_type == 'Tablet') {
                $device['tablet']['impression'] = $device['tablet']['impression'] + $udata->impression;
                $device2['tablet']['click'] = $device2['tablet']['click'] + $udata->click;
            }
            foreach ($dates as $date) {

                if ($udata->date == $date) {

                    if (array_key_exists($date, $gdata)) {
                        $gdata[$date] = [
                            "date" => $date,
                            "imps" => $gdata[$date]['imps'] + $udata->impression,
                            "click" => $gdata[$date]['click'] + $udata->click,
                            "amt" => $gdata[$date]['amt'] + $udata->amt
                        ];
                    } else {
                        $gdata[$date] = [
                            "date" => $date,
                            "imps" => $udata->impression,
                            "click" => $udata->click,
                            "amt" => $udata->amt
                        ];
                    }
                }
            }
        }


        if ($imps != 0) {
            $prc1 = ($device['mobile']['impression'] / $imps) * 100;
            $device['mobile']['percent']  = number_format($prc1, 2);
            $prc2 = ($device['desktop']['impression'] / $imps) * 100;
            $device['desktop']['percent']  = number_format($prc2, 2);
            $prc3 = ($device['tablet']['impression'] / $imps) * 100;
            $device['tablet']['percent']  = number_format($prc3, 2);
        } else {
            $device['mobile']['percent'] = 0;
            $device['desktop']['percent']  = 0;
            $device['tablet']['percent']  = 0;
        }
        if ($clks != 0) {

            $prc4 = ($device2['mobile']['click'] / $clks) * 100;
            $device2['mobile']['percent']  = number_format($prc4, 2);
            $prc5 = ($device2['desktop']['click'] / $clks) * 100;
            $device2['desktop']['percent']  = number_format($prc5, 2);
            $prc6 = ($device2['tablet']['click'] / $clks) * 100;
            $device2['tablet']['percent']  = number_format($prc6, 2);
        } else {
            $device2['mobile']['percent']  = 0;
            $device2['desktop']['percent']  = 0;
            $device2['tablet']['percent']  = 0;
        }
        $todayfreport = [
            'click' => $clks,
            'impression' => $imps,
            'amount' => number_format($amts, 5)
        ];

        $ndate = date('d-m-Y');

        if ($option == 0) {
            $newDate = date("d-m-Y", strtotime($ndate . "-$option day"));
            $sdate = date('Y-m-d', strtotime("-$option day"));
        } else {
            $ddd =  $option - 1;
            $newDate = date("d-m-Y", strtotime($ndate . "-$ddd day"));
            $sdate = date('Y-m-d', strtotime("-$ddd day"));
        }

        $totalcampclicks = [];
        $totalcampimp = [];
        $totaldate = [];
        $totalamt = [];

        $startDate = strtotime($newDate);
        $endDate = strtotime($ndate);

        for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {

            $xxdate = date('Y-m-d', $currentDate);
            $totaldate[] = date('d', $currentDate);

            if (in_array($xxdate, $dates)) {
                $uclick = 0;
                $uimp = 0;
                foreach ($gdata as $imp) {
                    // print_r($imp);
                    if ($imp['date'] == $xxdate) {
                        $uclick = $imp['click'];
                        $uimp = $imp['imps'];
                        $uamt = number_format($imp['amt'], 5);
                    }
                }
                $totalcampclicks[] = $uclick;
                $totalcampimp[] = $uimp;
                $totalamt[] = $uamt;
            } else {
                $totalcampclicks[] = 0;
                $totalcampimp[] = 0;
                $totalamt[] = 0;
            }
        }
        $maindata = array('data' => $todayfreport, 'date' => $totaldate, 'click' => $totalcampclicks, 'impression' => $totalcampimp, 'rev' => $totalamt);

        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;

        $return['device_imp'] = $device;
        $return['device_click'] = $device2;

        if ($option == 0) {
            $filterdate = \Carbon\Carbon::today()->subDays($option);
        } else {
            $beforedate =  $option - 1;
            $filterdate = \Carbon\Carbon::today()->subDays($beforedate);
        }

        $countimp = DB::table('pub_stats')
            ->select('country', DB::raw('SUM(impressions) as total'))
            ->where('publisher_code', $uid)
            ->whereDate('udate', '>=', $filterdate)
            ->groupBy('country')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->toArray();

        if ($countimp) {
            $return['country_imp'] = [
                "countries" =>  array_column($countimp, 'country'),
                "data" => array_column($countimp, 'total')
            ];
        } else {
            $return['country_imp'] = [
                "countries" =>  [],
                "data" => []
            ];
        }




        /* Countries clicks get */
        $countclk = DB::table('pub_stats')
            ->select('country', 'udate', DB::raw('SUM(clicks) as total'))
            ->where('publisher_code', $uid)
            ->whereDate('udate', '>=', $filterdate)
            ->groupBy('country')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->toArray();
        if ($countclk) {
            $return['country_click'] = [
                "countries" =>  array_column($countclk, 'country'),
                "data" => array_column($countclk, 'total')
            ];
        } else {
            $return['country_click'] = [
                "countries" =>  [],
                "data" => []
            ];
        }

        $totalwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('trash', 0)->count();
        $holdwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 3)->where('trash', 0)->count();
        $activewebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 4)->where('trash', 0)->count();
        $suspendwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 5)->where('trash', 0)->count();
        $Unverifiedwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 1)->where('trash', 0)->count();
        $rejectwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 6)->where('trash', 0)->count();
        $web[] = array('total_website' => $totalwebsite, 'approved' => $activewebsite, 'reject' => $rejectwebsite, 'hold' => $holdwebsite, 'suspend' => $suspendwebsite ,'inreview'=>$Unverifiedwebsite);
          if($web)
          {
              $return['website'] = $web;
          }
        /* Get number of adunites */
        $aduni = DB::table('pub_adunits')
            ->select('ad_type', DB::raw('count(id) as ads'))
            ->where('uid', $uid)
            ->where('trash', 0)
            ->where('status', 2)
            ->groupBy('ad_type')
            ->orderBy('ad_type', 'ASC')
            ->get()->toArray();
        $totalad = array_sum(array_column($aduni, 'ads'));

        if ($aduni) {
            $return['adunit'] = $aduni;
            $return['total_adunit'] = $totalad;
        } else {
            $return['adunit'] = [];
            $return['total_adunit'] = 0;
        }

        $wltPubAmt = getPubWalletAmount($uid);
        $pubwltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $userdetail->pub_wallet;

        $return['wallet'] = number_format($pubwltamt, 2);
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
