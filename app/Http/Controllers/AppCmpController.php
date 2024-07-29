<?php



namespace App\Http\Controllers;



use App\Models\AdBannerImage;

use App\Models\Campaign;

use App\Models\User;

use App\Models\Activitylog;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;



class AppCmpController extends Controller

{

   public function sartdata(Request $request)

   {

      $userid = $request->uid;

      $ads = $request->ads;

      $campaign = Campaign::select('campaign_id', 'campaign_name')->where('ad_type', $ads)

         ->where('advertiser_code', $userid)

         ->get();

      if ($campaign) {

         $return['code'] = 200;

         $return['msg'] = $campaign;

      } else {

         $return['code'] = 100;

         $return['msg'] = 'Not Found!';

      }

      return json_encode($return, JSON_NUMERIC_CHECK);

   }

   public function getClkImpCmpData(Request $request)
  {
            $validator = Validator::make($request->all(), [
               'uid'       => 'required',
               'to_date'   => 'required|date_format:Y-m-d',
               'from_date' => 'required|date_format:Y-m-d',
         ]);
         if ($validator->fails()) {
               $return['code'] = 100;
               $return['error'] = $validator->errors();
               $return['message'] = 'Valitation error!';
               return json_encode($return);
         }
         $uid = $request->uid;
         $todate = $request->to_date;
         $fromdate = $request->from_date;
         $repType = $request->rep_type;
         $adtype = $request->ad_type;
         $campid = $request->camp_id;
         $limit = $request->lim;
         $page = $request->page;
         $pg = $page - 1;
         $start = ($pg > 0) ? $limit * $pg : 0;

         $nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));
         //echo 'This is nformdate please dekho'. $fromdate;
         if ($repType == 'Campaign') {
               $sql1 = "SELECT imp.created_at as Created, imp.campaign_id as CampaignId, count(imp.id) as Imprs, (SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk.created_at) AND clk.campaign_id = imp.campaign_id) as Clicks, (SELECT IF(SUM(amount) != 'NULL', FORMAT(SUM(amount),2), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk2.created_at) AND clk2.campaign_id = imp.campaign_id ) + FORMAT(SUM(imp.amount),2) as Totals FROM `ss_ad_impressions` imp WHERE imp.advertiser_code = '$uid' AND (created_at BETWEEN '$todate' AND '$nfromdate')";
               if ($campid != 'All') {
                  $campcon = " AND imp.campaign_id = '$campid'";
                  $sql1 = $sql1 . $campcon;
               }
               if ($campid == 'All' && $adtype) {
                  $campcon = " AND imp.ad_type = '$adtype'";
                  $sql1 = $sql1 . $campcon;
               }
               //$limt = "GROUP BY DATE(imp.created_at), imp.campaign_id LIMIT $start, $limit";
            $limt = "GROUP BY DATE(imp.created_at), imp.campaign_id";
         } else {
               $sql1 = "SELECT imp.created_at as Created, count(imp.id) as Imprs, (SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk.created_at) ) as Clicks, (SELECT IF(SUM(amount) != 'NULL', FORMAT(SUM(amount),2), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk2.created_at) AND clk2.campaign_id = imp.campaign_id)  + FORMAT(SUM(imp.amount),2) as Totals FROM `ss_ad_impressions` imp WHERE imp.advertiser_code = '$uid' AND (created_at BETWEEN '$todate' AND '$nfromdate')";
               if ($campid != 'All') {
                  $campcon = " AND imp.campaign_id = '$campid'";
                  $sql1 = $sql1 . $campcon;
               }
               if ($campid == 'All' && $adtype) {
                  $campcon = " AND imp.ad_type = '$adtype'";
                  $sql1 = $sql1 . $campcon;
               }
               //$limt = "GROUP BY DATE(imp.created_at) LIMIT $start, $limit";
            $limt = "GROUP BY DATE(imp.created_at)";
         }

         $sql = $sql1 . $limt;
         $datas = DB::select($sql);
         $row = count($datas);
         
         if (!empty($datas)) {
               $totalclk = '0';
               $totalimp = '0';
               $totalamt = '0';
               $totalctr = '0';
               $totalavgcpc = '0';
               foreach ($datas as $vallue) {
                  $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
                  // $newDate = date("d-m-Y", strtotime($vallue->Created));
                  $newDate = $vallue->Created;
                  $vallue->Created = $newDate;
                  if ($vallue->Clicks == 0) {
                     //$vallue->AvgCPC = round($vallue->Totals / 1, 2);
                        $vallue->AvgCPC = round($vallue->Totals / ($vallue->Imprs + $vallue->Clicks), 2);
                  } else {
                     //$vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);
                     $vallue->AvgCPC = round($vallue->Totals / ($vallue->Imprs + $vallue->Clicks), 2);
                  }
                  $totalimp += $vallue->Imprs;
                  $totalclk += $vallue->Clicks;
                  $totalamt += $vallue->Totals;
                  $vallue->Total = $vallue->Totals;
                  unset($vallue->Totals);
               }
               // $totalctr = $totalclk / $totalimp * 100;
               // $totalavgcpc = $totalamt / $totalclk;
               $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
               $totalavgcpc = $totalamt / ($totalclk + $totalimp);
               $asdsdas = array('total_impression' => round($totalimp, 2), 'total_click' => round($totalclk, 2), 'total_amount' => round($totalamt, 2), 'total_ctr' => round($totalctr, 2), 'total_avgcpc' => round($totalavgcpc, 2));
               $userdata = User::where('uid', $uid)->first();
              // dd($userdata);
               $return['code']    = 200;
               $return['data']    = $datas;
               $return['total']    = $asdsdas;
               $return['row']     = $row;
               $return['wallet']    = $userdata->wallet;
               $return['message'] = 'Succssfully';
         } else {
               $return['code']    = 100;
               $return['message'] = 'Something went wrong!';
         }
         return json_encode($return, JSON_NUMERIC_CHECK);

   }

}

