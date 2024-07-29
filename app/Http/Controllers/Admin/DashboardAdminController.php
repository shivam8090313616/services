<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserCampClickLog;
use App\Models\AdImpression;
use App\Models\UserNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardAdminController extends Controller
{
    
    public function dashboard_old(Request $request)
	{

	     
		/* open Today Section  */
		$todaydate = date('Y-m-d');
		
        $todayData = DB::table('camp_budget_utilize')
                    ->selectRaw('SUM(impressions)as imps, SUM(clicks)as clks, SUM(amount) as amt')
                    ->whereDate('udate', '=', $todaydate)
                    ->first();
        $datareport = array('click' => $todayData->clks, 'impression' => $todayData->imps, 'amount' => round($todayData->amt, 2));
		$return['today'] = $datareport;
		
		/* closed Today Section  */

		/* open Yesterday Section  */
		$yesterdays = date("Y-m-d", strtotime("-1 days"));
		
		$yesterdayData = DB::table('camp_budget_utilize')
                    ->selectRaw('SUM(impressions)as imps, SUM(clicks)as clks, SUM(amount) as amt')
                    ->whereDate('udate', '=', $yesterdays)
                    ->first();
		$datareporty = array('click' => $yesterdayData->clks, 'impression' => $yesterdayData->imps, 'amount' => round($yesterdayData->amt, 2));
		$return['yesterday'] = $datareporty;
		/* closed Yesterday Section  */
		
		/* open this month data  */
		$month = date("m");
		
		$monthData = DB::table('camp_budget_utilize')
                    ->selectRaw('SUM(impressions)as imps, SUM(clicks)as clks, SUM(amount) as amt')
                    ->whereMonth('udate', '=', $month)
                    ->first();
		$datareportm = array('click' => $monthData->clks, 'impression' => $monthData->imps, 'amount' => round($monthData->amt, 2));
        $return['thismonth'] = $datareportm;
   
		/* closed this month data  */
		
		
        /* open life time data */
		/* Total Click & Amount */
		
		$lifetimeData = DB::table('camp_budget_utilize')
                    ->selectRaw('SUM(impressions)as imps, SUM(clicks)as clks, SUM(amount) as amt')
                    ->first();
		$datareportl = array('click' => $lifetimeData->clks, 'impression' => $lifetimeData->imps, 'amount' => round($lifetimeData->amt, 2));
		$return['lifetime'] = $datareportl;
           //return json_encode($return , JSON_NUMERIC_CHECK); exit;

		/* closed life time data  taking 17 second*/
        
        /* ------------------------ Open Device Data Get Section ---------------------------  */
        
        $cacheKey = 'sqlcidevice_results';
        $sqlcideviceval = Cache::remember($cacheKey, now()->addHours(1), function () {
            return DB::select("SELECT COUNT(id) as cmp, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE device_type='Desktop') as desktop_imp,
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE device_type='Mobile') as mobile_imp, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE device_type='Tablet') as tablet_imp, 
                (SELECT COUNT(id) FROM ss_user_camp_click_logs WHERE device_type='Desktop') as desktop_clk, 
                (SELECT COUNT(id) FROM ss_user_camp_click_logs WHERE device_type='Mobile') as mobile_clk, 
                (SELECT COUNT(id) FROM ss_user_camp_click_logs WHERE device_type='Tablet') as tablet_clk 
                FROM ss_campaigns");
        });
        // $adTypeQrRes = Cache::get($cacheKey);
        // $adTypeQrRes['total'] = 1000;
        // Cache::put($cacheKey, $adTypeQrRes, now()->addHours(1));
            
		$tatimp = $sqlcideviceval[0]->desktop_imp + $sqlcideviceval[0]->mobile_imp + $sqlcideviceval[0]->tablet_imp;
		if ($tatimp == 0) {
			$prdesk2 = 0;
		} else {
			$prdesk2 = ($sqlcideviceval[0]->desktop_imp / $tatimp) * 100;
		}
		$prdesk = number_format($prdesk2, 2);
		$desktop['desktop'] = array('click' => $sqlcideviceval[0]->desktop_clk, 'impression' => $sqlcideviceval[0]->desktop_imp, 'percent' => $prdesk);
		if ($tatimp == 0) {
			$prmobile2 = 0;
		} else {
			$prmobile2 = ($sqlcideviceval[0]->mobile_imp / $tatimp) * 100;
		}
		$prmobile = number_format($prmobile2, 2);
		$desktop['mobile'] = array('click' => $sqlcideviceval[0]->mobile_clk, 'impression' => $sqlcideviceval[0]->mobile_imp, 'percent' => $prmobile);
		if ($tatimp == 0) {
			$prtablate2 = 0;
		} else {
			$prtablate2 = ($sqlcideviceval[0]->tablet_imp / $tatimp) * 100;
		}
		$prtablate = number_format($prtablate2, 2);
		$desktop['tablet'] = array('click' => $sqlcideviceval[0]->tablet_clk, 'impression' => $sqlcideviceval[0]->tablet_imp, 'percent' => $prtablate);
		if ($sqlcideviceval) {
			$return['device'] = $desktop;
		}
       // 		return json_encode($return , JSON_NUMERIC_CHECK); exit;
       
       
       // 		/* Taking 3.3 second*/
       
       
       /* ------------------------ Open Device OS Get Section ---------------------------  */
       
        $cacheKey = 'sqlcidevice_results_takings';
        $sqlciosval = Cache::remember($cacheKey, now()->addHours(1), function () {
         return DB::select("SELECT COUNT(id) as cmp, 
                    (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE device_os='Windows') as desktop_imp, 
                    (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE  device_os='apple') as mobile_imp, 
                    (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE  device_os='android') as tablet_imp, 
                    (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE  device_os='Windows') as desktop_clk, 
                    (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE device_os='apple') as mobile_clk, 
                    (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE  device_os='android') as tablet_clk 
                    FROM `ss_campaigns`");
        });

        $totimp = $sqlciosval[0]->desktop_imp + $sqlciosval[0]->mobile_imp + $sqlciosval[0]->tablet_imp;
		if ($totimp == 0) {
			$windowspr2 = 0;
		} else {
			$windowspr2 = ($sqlciosval[0]->desktop_imp / $totimp) * 100;
		}
		$windowspr = number_format($windowspr2, 2);
		$osclick['windows'] = array('click' => $sqlciosval[0]->desktop_clk, 'impression' => $sqlciosval[0]->desktop_imp, 'percent' => $windowspr);
		if ($totimp == 0) {
			$applepr2 = 0;
		} else {
			$applepr2 = ($sqlciosval[0]->mobile_imp / $totimp) * 100;
		}
		$applepr = number_format($applepr2, 2);
		$osclick['apple'] = array('click' => $sqlciosval[0]->mobile_clk, 'impression' => $sqlciosval[0]->mobile_imp, 'percent' => $applepr);
		if ($totimp == 0) {
			$android2 = 0;
		} else {
			$android2 = ($sqlciosval[0]->tablet_imp / $totimp) * 100;
		}
		$androidn = number_format($android2, 2);
		$osclick['android'] = array('click' => $sqlciosval[0]->tablet_clk, 'impression' => $sqlciosval[0]->tablet_imp, 'percent' => $androidn);
		if ($osclick) {
			$return['os'] = $osclick;
		}
		/* Taking 2.8 second*/
		
		//	return json_encode($return , JSON_NUMERIC_CHECK); exit;
		
		/* ------------------------ Open Traffic By Ads Section ---------------------------  */
			
        $cacheKey = 'ad_type_query_results';
        $adTypeQr = Cache::remember($cacheKey, now()->addHours(1), function () {
            return DB::select("SELECT COUNT(id) as total, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='text') as text, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='native') as native, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='social') as social, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='banner') as banner, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='popup') as popup, 
                (SELECT COUNT(id) FROM ss_ad_impressions WHERE ad_type='video') as video 
                FROM ss_ad_impressions");
        });
        $adTypeQrRes = $adTypeQr[0];
      
		$textAd['text'] = $adTypeQrRes->text;
		$textAd['native'] = $adTypeQrRes->native;
		$textAd['social'] = $adTypeQrRes->social;
		$textAd['banner'] = $adTypeQrRes->banner;
		$textAd['video'] = $adTypeQrRes->video;
		$textAd['popup'] = $adTypeQrRes->popup;

		$finatext = $adTypeQrRes->total;
      
      	if($finatext == 0)
		{
			$finatext ='1';	
		}
		else
		{
			$finatext =$finatext;
		}
		$textAdp['textper']   = round(($textAd['text'] / $finatext) * 100, 2);
		$textAdp['nativeper'] = round(($textAd['native'] / $finatext) * 100, 2);
		$textAdp['socialper'] = round(($textAd['social'] / $finatext) * 100, 2);
		$textAdp['bannerper'] = round(($textAd['banner'] / $finatext) * 100, 2);
		$textAdp['videoper']  = round(($textAd['video'] / $finatext) * 100, 2);
		$textAdp['popupper']  = round(($textAd['popup'] / $finatext) * 100, 2);
		
		if ($textAd) {
			$return['ads'] = $textAd;
			$return['adsp'] = $textAdp;
		}

		/* Taking 4 second */
		
		/* ------------------------ Open User Type Wise Count Data Get Section ---------------------------  */
		
		/* Open Today count Publisher & Advertiser */
		$todayusers  = DB::table('users')->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$advertiserctoday = DB::table('users')->where('user_type', 1)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$publisherctoday = DB::table('users')->where('user_type', 2)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$bothctoday = DB::table('users')->where('user_type', 3)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		/* closed Today count Publisher & Advertiser */

		/* open yesterday count Publisher & Advertiser  */
		$yesterusers = DB::table('users')->where('trash', 0)->whereDate('created_at', '=', $yesterdays)->count();
		$advertisercyesterday = DB::table('users')->where('trash', 0)->where('user_type', 1)->whereDate('created_at', '=', $yesterdays)->count();
		$publishercyesterday = DB::table('users')->where('trash', 0)->where('user_type', 2)->whereDate('created_at', '=', $yesterdays)->count();
		$bothcyesterday = DB::table('users')->where('trash', 0)->where('user_type', 3)->whereDate('created_at', '=', $yesterdays)->count();
		/* closed yesterday count Publisher & Advertiser */
		
		/* open Lifetime count Publisher & Advertiser */
		$alltimeusers = DB::table('users')->where('trash', 0)->count();
		$advertiserc =DB::table('users')->where('user_type', 1)->where('trash', 0)->count();
		$publisherc = DB::table('users')->where('user_type', 2)->where('trash', 0)->count();
		$bothc = DB::table('users')->where('user_type', 3)->where('trash', 0)->count();
		/* closed Lifetime count Publisher & Advertiser */
		$usercont = array('today' => $todayusers, 'advertiserctoday' => $advertiserctoday, 'publisherctoday' => $publisherctoday, 'bothctoday' => $bothctoday, 'yesterday' => $yesterusers, 'advertisercyesterday' => $advertisercyesterday, 
		                  'publishercyesterday' => $publishercyesterday, 'bothcyesterday' => $bothcyesterday, 'lifetime' => $alltimeusers, 'advertiserc' => $advertiserc, 'publisherc' => $publisherc, 'bothc' => $bothc);
		if ($usercont) {
			$return['usercont'] = $usercont;
		}
		
		/* Taking 855 mili second*/
		
		
		/* ------------------------ Open Users Status Wise Count Data Section ---------------------------  */
		
		$activeuser   = DB::table('users')->where('status', 0)->where('user_type', 1)->where('trash', 0)->count();
		$inactiveuser = DB::table('users')->where('status', 1)->where('user_type', 1)->where('trash', 0)->count();
		$suspenduser  = DB::table('users')->where('status', 3)->where('user_type', 1)->where('trash', 0)->count();
		$pendinguser  = DB::table('users')->where('status', 2)->where('user_type', 1)->where('trash', 0)->count();
		$holduser     = DB::table('users')->where('status', 4)->where('user_type', 1)->where('trash', 0)->count();
		$userais = array('active_user' => $activeuser, 'pendinguser' => $pendinguser, 'holduser' => $holduser, 'inactive_user' => $inactiveuser, 'suspend_user' => $suspenduser);
		if ($userais) {
			$return['usersais'] = $userais;
		}
		
		/* ------------------------ Open Top 5 Advertisers (Wallet) Section ---------------------------  */
		
		$topusers = DB::table('users')->select(
			"users.uid",
			"users.wallet",
			"users.email",
			DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as user_name")
		)->where('trash', 0)->orderBy('wallet', 'DESC')->limit(5)->get();
		$topuser = array('topuser' => $topusers);
		if ($topuser) {
			$return['topuser'] = $topuser;
		}
		
		/* Taking 909 mili second*/
		return json_encode($return, JSON_NUMERIC_CHECK);
	}

	public function dashboard_new(Request $request)
	{
	    $todaydate = date('Y-m-d');
		$yesterdays = date("Y-m-d", strtotime("-1 days"));
		$udate = date('Y-m-d', strtotime('-30 days'));
		
		$ldata = [
		    "click" => 0,
		    "impression" => 0,
		    "amount" => 0
		];
		
		$mdata = [
		    "click" => 0,
		    "impression" => 0,
		    "amount" => 0
		];
		
		$ydata = [
		    "click" => 0,
		    "impression" => 0,
		    "amount" => 0
		];
		
		$tdata = [
		    "click" => 0,
		    "impression" => 0,
		    "amount" => 0
		];
		
		$amt = 0;
		
        $todayData = DB::table('camp_budget_utilize')
                    ->selectRaw('SUM(impressions) as imps, SUM(clicks) as clks, SUM(amount) as amt, udate')
                    ->whereDate('udate', '>', $udate)
                    ->groupBy('udate')
                    ->get();
                    
        foreach($todayData as $row) {
            
            $mdata['click'] = $mdata['click']+$row->clks;
            $mdata['impression'] = $mdata['impression']+$row->imps;
            $amt = $amt+$row->amt;
            
            if($todaydate == $row->udate) {
                $tdata = [
        		    "click" => $row->clks,
        		    "impression" => $row->imps,
        		    "amount" => number_format($row->amt,2)
        		];
            }
            
            if($yesterdays == $row->udate) {
                $ydata = [
        		    "click" => $row->clks,
        		    "impression" => $row->imps,
        		    "amount" => number_format($row->amt,2)
        		];
            }
            
        }  
        
        $mdata['amount'] = number_format($amt, 2);
        
        $return['today'] = $tdata;
        $return['yesterday'] = $ydata;
        $return['thismonth'] = $mdata;
    
        
		
	$userdata = DB::select("SELECT device_os, device_type, SUM(amount) amt, SUM(impressions) impression, SUM(clicks) click 
      						FROM ss_adv_stats imp GROUP BY imp.country, device_os, device_type");
  	
  	$device = [
                    "desktop" => [
                        "impression" => 0,
                        "click" => 0
                    ],
                    "mobile" => [
                        "impression" => 0,
                        "click" => 0
                    ],
                    "tablet" =>[
                        "impression" => 0,
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
            
        $amt2 = 0;
         
  		foreach($userdata as $udata) {
  		    
  		         
            $ldata['click'] = $ldata['click']+$udata->click;
            $ldata['impression'] = $ldata['impression']+$udata->impression;
            $amt2 = $amt2+$udata->amt;
  		    
  		    if($udata->device_type == 'Mobile') {
                    $device['mobile']['impression'] = $device['mobile']['impression']+$udata->impression;
                  	$device['mobile']['click'] = $device['mobile']['click']+$udata->click;
                } elseif($udata->device_type == 'Desktop') {
                    $device['desktop']['impression'] = $device['desktop']['impression']+$udata->impression;
                  	$device['desktop']['click'] = $device['desktop']['click']+$udata->click;
                } elseif($udata->device_type == 'Tablet') {
                    $device['tablet']['impression'] = $device['tablet']['impression']+$udata->impression;
                  	$device['tablet']['click'] = $device['tablet']['click']+$udata->click;
                }
                
                if($udata->device_os == 'linux') {
                    $os['linux']['impression'] = $os['linux']['impression']+$udata->impression;
                  	$os['linux']['click'] = $os['linux']['click']+$udata->click;
                } elseif($udata->device_os == 'windows') {
                    $os['windows']['impression'] = $os['windows']['impression']+$udata->impression;
                  	$os['windows']['click'] = $os['windows']['click']+$udata->click;
                } elseif($udata->device_os == 'android') {
                    $os['android']['impression'] = $os['android']['impression']+$udata->impression;
                  	$os['android']['click'] = $os['android']['click']+$udata->click;
                } elseif($udata->device_os == 'apple') {
                    $os['apple']['impression'] = $os['apple']['impression']+$udata->impression;
                  	$os['apple']['click'] = $os['apple']['click']+$udata->click;
                }
                
                
  		}
  		
  		
  		
  		  $prc1 = ($device['mobile']['impression']/$ldata['impression']) * 100;
          $device['mobile']['percent']  = number_format($prc1,2);
          $prc2 = ($device['desktop']['impression']/$ldata['impression']) * 100;
          $device['desktop']['percent']  = number_format($prc2,2);
          $prc3 = ($device['tablet']['impression']/$ldata['impression']) * 100;
          $device['tablet']['percent']  = number_format($prc3,2);
          
          $prc4 = ($os['linux']['impression']/$ldata['impression']) * 100;
          $os['linux']['percent']  = number_format($prc4,2);
          $prc5 = ($os['windows']['impression']/$ldata['impression']) * 100;
          $os['windows']['percent']  = number_format($prc5,2);
          $prc6 = ($os['android']['impression']/$ldata['impression']) * 100;
          $os['android']['percent']  = number_format($prc6,2);
          $prc7 = ($os['apple']['impression']/$ldata['impression']) * 100;
          $os['apple']['percent']  = number_format($prc7,2);
  		
  		
  		$ldata['amount'] = number_format($amt2, 2);
  		
  		$return['lifetime'] = $ldata;
  		
  		$return['device'] = $device;
        $return['os'] = $os;
  		
  		
  		$adTypeQrRes = DB::table('campaigns')
      		               ->select('campaigns.ad_type', DB::raw('SUM(ss_camp_budget_utilize.impressions) impression') )
      						->leftJoin('camp_budget_utilize', 'camp_budget_utilize.camp_id','=','campaigns.campaign_id') 
      						->groupBy('campaigns.ad_type')->get()->toArray();
  		
	foreach($adTypeQrRes as $adtype) {
	    
	    if($adtype->ad_type == 'banner') {
	        $textAd['banner'] = $adtype->impression;
	    } elseif($adtype->ad_type == 'text') {
	        $textAd['text'] = $adtype->impression;
	    } elseif($adtype->ad_type == 'social') {
	        $textAd['social'] = $adtype->impression;
	    } elseif($adtype->ad_type == 'native') {
	        $textAd['native'] = $adtype->impression;
	    } elseif($adtype->ad_type == 'popup') {
	        $textAd['popup'] = $adtype->impression;
	    }
	    
	}
	    
    $textAd['video'] = 0;


		$finatext = array_sum(array_column($adTypeQrRes, 'impression'));

      
		if($finatext == 0)
		{
			$finatext ='1';	
		}
		else
		{
			$finatext =$finatext;
		}
		$textAdp['textper']   = round(($textAd['text'] / $finatext) * 100, 2);
		$textAdp['nativeper'] = round(($textAd['native'] / $finatext) * 100, 2);
		$textAdp['socialper'] = round(($textAd['social'] / $finatext) * 100, 2);
		$textAdp['bannerper'] = round(($textAd['banner'] / $finatext) * 100, 2);
		$textAdp['videoper']  = round(($textAd['video'] / $finatext) * 100, 2);
		$textAdp['popupper']  = round(($textAd['popup'] / $finatext) * 100, 2);
		
		if ($textAd) {
			$return['ads'] = $textAd;
			$return['adsp'] = $textAdp;
		}
		
	    
		/* open Today count Publisher & Advertiser */
		$todayusers  = User::where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$advertiserctoday = User::where('user_type', 1)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$publisherctoday = User::where('user_type', 2)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		$bothctoday = User::where('user_type', 3)->where('trash', 0)->whereDate('created_at', '=', $todaydate)->count();
		/* closed Today count Publisher & Advertiser */

		/* open yesterday count Publisher & Advertiser  */
		$yesterusers = User::where('trash', 0)->whereDate('created_at', '=', $yesterdays)->count();
		$advertisercyesterday = User::where('trash', 0)->where('user_type', 1)->whereDate('created_at', '=', $yesterdays)->count();
		$publishercyesterday = User::where('trash', 0)->where('user_type', 2)->whereDate('created_at', '=', $yesterdays)->count();
		$bothcyesterday = User::where('trash', 0)->where('user_type', 3)->whereDate('created_at', '=', $yesterdays)->count();
		/* closed yesterday count Publisher & Advertiser */
		
		/* open Lifetime count Publisher & Advertiser */
		$alltimeusers = User::where('trash', 0)->count();
		$advertiserc = User::where('user_type', 1)->where('trash', 0)->count();
		$publisherc = User::where('user_type', 2)->where('trash', 0)->count();
		$bothc = User::where('user_type', 3)->where('trash', 0)->count();
		/* closed Lifetime count Publisher & Advertiser */
		$usercont = array('today' => $todayusers, 'advertiserctoday' => $advertiserctoday, 'publisherctoday' => $publisherctoday, 'bothctoday' => $bothctoday, 'yesterday' => $yesterusers, 'advertisercyesterday' => $advertisercyesterday, 'publishercyesterday' => $publishercyesterday, 'bothcyesterday' => $bothcyesterday, 'lifetime' => $alltimeusers, 'advertiserc' => $advertiserc, 'publisherc' => $publisherc, 'bothc' => $bothc);
		if ($usercont) {
			$return['usercont'] = $usercont;
		}
		$activeuser   = User::where('status', 0)->where('user_type', 1)->where('trash', 0)->count();
		$inactiveuser = User::where('status', 1)->where('user_type', 1)->where('trash', 0)->count();
		$suspenduser  = User::where('status', 3)->where('user_type', 1)->where('trash', 0)->count();
		$pendinguser  = User::where('status', 2)->where('user_type', 1)->where('trash', 0)->count();
		$holduser     = User::where('status', 4)->where('user_type', 1)->where('trash', 0)->count();
		$userais = array('active_user' => $activeuser, 'pendinguser' => $pendinguser, 'holduser' => $holduser, 'inactive_user' => $inactiveuser, 'suspend_user' => $suspenduser);
		if ($userais) {
			$return['usersais'] = $userais;
		}
		$topusers = User::select(
			"users.uid",
			"users.wallet",
			"users.email",
			DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as user_name")
		)->where('trash', 0)->where('account_type', 0)->orderBy('wallet', 'DESC')->limit(5)->get();
		$topuser = array('topuser' => $topusers);
		if ($topuser) {
			$return['topuser'] = $topuser;
		}
		return json_encode($return, JSON_NUMERIC_CHECK);
	}
}
