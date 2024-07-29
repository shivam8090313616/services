<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdImpression;
use App\Models\PubWebsite;
use App\Models\PubAdunit;
use App\Models\User;
use App\Models\UserCampClickLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PubDashboardAdminController extends Controller
{
    public function adminCia(Request $request)
    {
        $option =  $request->option;
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        
        $res = DB::table('pub_stats')
               ->select((DB::raw('SUM(clicks) as click')), (DB::raw('SUM(impressions) as imprs')),  (DB::raw('SUM(amount) as amt')) )
               ->where('publisher_code', '!=', 'null')->whereDate('udate', '>=', $date)->first();
        $totaluser =    DB::table('users')->where('user_type', 2)->where('trash', 0)->whereDate('created_at', '>=', $date)->count();
                        
        
        $totalweb =     DB::table('pub_websites')->whereDate('created_at', '>=', $date)->count();
                        

        $totaladunit =  DB::table('pub_adunits')->whereDate('created_at', '>=', $date)->count();
                        
        $todayfreport = array('click' => $res->click, 'impression' => $res->imprs, 'amount' => round($res->amt, 5), 'user' => $totaluser, 'website' => $totalweb, 'adunit' => $totaladunit);
        
        $ndate = date('d-m-Y');
        if ($option == 0) {
            $newDate = date("d-m-Y", strtotime($ndate . "-$option day"));
        } else {
            $ddd =  $option - 1;
            $newDate = date("d-m-Y", strtotime($ndate . "-$ddd day"));
        }
        $startDate = strtotime($newDate);
        $endDate = strtotime($ndate);
        
        for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {
            $xxxdate[] = date('jS M', $currentDate);
            $xxdate = date('Y-m-d', $currentDate);
            $newdata = DB::table('pub_stats')
                             ->select((DB::raw('IFNULL(SUM(clicks), 0) as click')), (DB::raw('IFNULL(SUM(impressions), 0) as imprs')),  (DB::raw('IFNULL(SUM(amount), 0) as amt')) )
                             ->where('publisher_code', '!=', 'null')
                             ->whereDate('udate', '=', $xxdate)->first();
            $totalcampclicks[] = $newdata->click;
            $totalimpclicks[] = $newdata->imprs;
            $totalamtimpclicks[] = $newdata->amt;

            $ucount = User::where('user_type', '!=', 1)->where('trash', 0)->whereDate('created_at', $xxdate)->count();
            $totalusers[] = $ucount;

            $webcount = PubWebsite::whereDate('created_at', $xxdate)->count();
            $totalwebsites[] = $webcount;

            $adunitcount = PubAdunit::whereDate('created_at', $xxdate)->count();
            $totaladunits[] = $adunitcount;
        }
        $maindata =  array('data' => $todayfreport, 'date' => $xxxdate, 'click' => $totalcampclicks, 'impression' => $totalimpclicks, 'rev' => $totalamtimpclicks, 'user' => $totalusers, 'website' => $totalwebsites, 'adunit' => $totaladunits);
  

        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
    


        /* Device impressions and clicks query */
        
        
      $userdata = DB::select("SELECT date(udate) as date, country, device_os, device_type, SUM(amount) amt, SUM(impressions) impression, SUM(clicks) click 
      						FROM ss_pub_stats imp WHERE DATE(imp.udate) >= DATE('".$date."') GROUP BY imp.country, device_os, device_type, date(udate)");
      						
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
            "tablet" =>[
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
            "tablet" =>[
                "click" => 0
            ]
        ];
            
            
        
            
        foreach($userdata as $udata) {
  	    
  	         
            if($udata->device_type == 'Mobile') {
                $device['mobile']['impression'] = $device['mobile']['impression']+$udata->impression;
                $device2['mobile']['click'] = $device2['mobile']['click']+$udata->click;
            } elseif($udata->device_type == 'Desktop') {
                $device['desktop']['impression'] = $device['desktop']['impression']+$udata->impression;
                $device2['desktop']['click'] = $device2['desktop']['click']+$udata->click;
            } elseif($udata->device_type == 'Tablet') {
                $device['tablet']['impression'] = $device['tablet']['impression']+$udata->impression;
                $device2['tablet']['click'] = $device2['tablet']['click']+$udata->click;
            }
        }
        
          if($imps != 0 )
          {
              $prc1 = ($device['mobile']['impression']/$imps) * 100;
              $device['mobile']['percent']  = number_format($prc1,2);
              $prc2 = ($device['desktop']['impression']/$imps) * 100;
              $device['desktop']['percent']  = number_format($prc2,2);
              $prc3 = ($device['tablet']['impression']/$imps) * 100;
              $device['tablet']['percent']  = number_format($prc3,2);
          }  
          else
          {
               $device['mobile']['percent'] = 0;
               $device['desktop']['percent']  = 0;
               $device['tablet']['percent']  = 0;
               
               
          }
          if($clks != 0 )
          {
          
              $prc4 = ($device2['mobile']['click']/$clks) * 100;
              $device2['mobile']['percent']  = number_format($prc4,2);
              $prc5 = ($device2['desktop']['click']/$clks) * 100;
              $device2['desktop']['percent']  = number_format($prc5,2);
              $prc6 = ($device2['tablet']['click']/$clks) * 100;
              $device2['tablet']['percent']  = number_format($prc6,2);
          }
          else
          {
               $device2['mobile']['percent']  = 0;
               $device2['desktop']['percent']  = 0;
               $device2['tablet']['percent']  = 0;
               
          }
      						
      						
        

        if ($userdata) {
            $return['device_imp'] = $device;
            $return['device_click'] = $device2;
        }
        
        

        /* Countries impressions get */
        
        $countimp = DB::table('pub_stats')
                    ->select('country', DB::raw('SUM(impressions) as total'))
                    ->where('publisher_code', '!=', 'null')
                    ->whereDate('udate', '>=', $date)
                    ->groupBy('country')
                    ->orderBy('total', 'DESC')
                    ->limit(10)
                    ->get()->toArray();


      	if ($countimp) {
            $return['country_imp'] = [
              "countries" =>  array_column($countimp, 'country'),
              "data" => array_column($countimp, 'total')
            ];
        }
      	else
        {
        	$return['country_imp'] = [
              "countries" =>  [],
              "data" => []
            ];
        }
        

        /* Countries clicks get */
        
        $countclk = DB::table('pub_stats')
            ->select('country', DB::raw('SUM(clicks) as total'))
            ->where('publisher_code','!=', 'null')
            ->whereDate('udate', '>=', $date)
            ->groupBy('country')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->toArray();

        if ($countclk) {	
              $return['country_click'] = [
                "countries" =>  array_column($countclk, 'country'),
                "data" => array_column($countclk, 'total')
              ];
          }
      	 else
         {
              $return['country_click'] = [
                "countries" =>  [],
                "data" => []
              ];
         }
        
           
       
        /* Get number of users */
        $totalActiveUsers   = DB::table('users')->where('status', 0)->where('user_type','!=', 1)->where('trash', 0)->count();
        $totalInctiveUsers   = DB::table('users')->where('status', 1)->where('user_type','!=', 1)->where('trash', 0)->count();
        $totalPendingUsers   = DB::table('users')->where('status', 2)->where('user_type','!=', 1)->where('trash', 0)->count();
        $totalHoldUsers   = DB::table('users')->where('status', 4)->where('user_type','!=', 1)->where('trash', 0)->count();
        $totalSuspendedUsers   = DB::table('users')->where('status', 3)->where('user_type','!=', 1)->where('trash', 0)->count();

        $web[] = array('total_active_users' => $totalActiveUsers, 'total_inactive_users' => $totalInctiveUsers, 'total_pending_users' => $totalPendingUsers, 'total_hold_users' => $totalHoldUsers, 'total_suspended_users' => $totalSuspendedUsers);
        if ($web) {
            $return['totalusers'] = $web;
        }
      
      	/* Get top 5 publishers */

        
		$pub = DB::table('users')->select('id', DB::raw("CONCAT(ss_users.first_name, '' , ss_users.last_name) as name"), 'uid', 'email', 'pub_wallet')
    					->where('user_type','!=', 1)
          				->where('status', 0)
          				->where('trash', 0)
          				->orderBy('pub_wallet', 'DESC')->limit(5)->get();
      	foreach($pub as $pubdata)
        {
        	$pubdata->pub_wallet = number_format($pubdata->pub_wallet, 2);
        }
        if($pub)
        {
        	$return['toppublisher'] = $pub;
        }
		
        
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
