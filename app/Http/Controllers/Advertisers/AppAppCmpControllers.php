<?php

namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;

use App\Models\AdBannerImage;

use App\Models\Campaign;

use App\Models\User;

use App\Models\Activitylog;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

class AppAppCmpControllers extends Controller



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



         $return['msg'] = 'No Data Found!';



      }



      return json_encode($return, JSON_NUMERIC_CHECK);



   }



   public function getClkImpCmpDatatest(Request $request)

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



  //$nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));

  

  

      $sql1 = DB::table('campaigns')

                   ->select('campaigns.ad_type', 'camp_budget_utilize.camp_id as CampaignId', 'camp_budget_utilize.udate as Created', 

                    DB::raw('SUM(ss_camp_budget_utilize.impressions) Imprs, SUM(ss_camp_budget_utilize.clicks) Clicks, SUM(ss_camp_budget_utilize.amount) Totals'))

             ->leftJoin('camp_budget_utilize', 'camp_budget_utilize.camp_id','=','campaigns.campaign_id')

             ->where('camp_budget_utilize.advertiser_code', $uid)

             ->whereBetween('camp_budget_utilize.udate', [$todate, $fromdate]); 

            

    if ($campid != 'All') {

          

          $sql1 = $sql1->where('campaigns.campaign_id', $campid);

      }

      if ($campid == 'All' && $adtype) {

          $sql1 = $sql1->where('campaigns.ad_type', $adtype);

      }

  $row        = $sql1->count();

  if ($repType == 'Campaign') {

    $limt =  $sql1->groupBy('camp_budget_utilize.udate', 'camp_budget_utilize.camp_id')->orderBy('camp_budget_utilize.udate', 'asc')->offset($start)->limit($limit)->get();

  //$limt =  $sql1->groupBy('camp_budget_utilize.udate', 'camp_budget_utilize.camp_id')->get();

  

  } else {

      

    $limt =  $sql1->groupBy('camp_budget_utilize.udate')->offset($start)->limit($limit)->get();

  }

  $datas = $limt;

  //$row = count($limt);

  $userdata = User::where('uid', $uid)->first();

  //dd($row);

  if (!empty($datas)) {

      $totalclk = '0';

      $totalimp = '0';

      $totalamt = '0';

      $totalctr = '0';

      $totalavgcpc = '0';

      foreach ($datas as $vallue) {

          if($vallue->Clicks == 0 || $vallue->Imprs == 0)

          {

              $vallue->CTR = 0;

          }

          else

          {

              $vallue->CTR = ($vallue->Clicks / $vallue->Imprs) * 100;

          }

          // $newDate = date("d-m-Y", strtotime($vallue->Created));

          $newDate = $vallue->Created;

          $vallue->Created = $newDate;

          if ($vallue->Clicks == 0 && $vallue->Imprs == 0) {

              $vallue->AvgCPC = 0;

          } else {

              $vallue->AvgCPC = $vallue->Totals / ($vallue->Imprs + $vallue->Clicks);

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

      //$totalavgcpc = $totalamt / ($totalclk + $totalimp);

      if($totalclk == 0 && $totalimp == 0)

      {

         $totalavgcpc = 0;

      }

      else

      {

        $totalavgcpc = $totalamt / ($totalclk + $totalimp);

      }

      $asdsdas = array('total_impression' => $totalimp, 'total_click' => $totalclk, 'total_amount' => $totalamt, 'total_ctr' => $totalctr, 'total_avgcpc' => $totalavgcpc);

      

      $return['code']    = 200;

      $return['data']    = $datas;

      $return['total']    = $asdsdas;

      $return['row']     = $row;

    //   $return['wallet']    = number_format($userdata->wallet, 3, '.', '');

      $wltAmt = getWalletAmount($uid);
      $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');

      $return['message'] = 'Succssfully';

  } else {

      $return['code']    = 100;

    //   $return['wallet']    = number_format($userdata->wallet, 3, '.', '');
      $wltAmt = getWalletAmount($uid);
      $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');

      $return['message'] = 'Record not found!';

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



      //$nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));





      $sql1 = DB::table('campaigns')

          ->select(

              'campaigns.ad_type',

              'camp_budget_utilize.camp_id as CampaignId',

              'camp_budget_utilize.imp_amount',

              'camp_budget_utilize.click_amount',

              'camp_budget_utilize.udate as Created',

              DB::raw('SUM(ss_camp_budget_utilize.impressions) Imprs, SUM(ss_camp_budget_utilize.clicks) Clicks, SUM(ss_camp_budget_utilize.amount) Totals')

          )

          ->leftJoin('camp_budget_utilize', 'camp_budget_utilize.camp_id', '=', 'campaigns.campaign_id')

          ->where('camp_budget_utilize.advertiser_code', $uid)

          ->whereBetween('camp_budget_utilize.udate', [$todate, $fromdate]);



      if ($campid != 'All') {



          $sql1 = $sql1->where('campaigns.campaign_id', $campid);

      }

      if ($campid == 'All' && $adtype) {

          $sql1 = $sql1->where('campaigns.ad_type', $adtype);

      }

      $row        = $sql1->count();

      if ($repType == 'Campaign') {

          $limt =  $sql1->groupBy('camp_budget_utilize.udate', 'camp_budget_utilize.camp_id')->orderBy('camp_budget_utilize.udate', 'asc')->offset($start)->limit($limit)->get();

          //$limt =  $sql1->groupBy('camp_budget_utilize.udate', 'camp_budget_utilize.camp_id')->get();



      } else {



          $limt =  $sql1->groupBy('camp_budget_utilize.udate')->offset($start)->limit($limit)->get();

      }

      $datas = $limt;

      //$row = count($limt);

      $userdata = User::where('uid', $uid)->first();

      //dd($row);

      if (!empty($datas)) {

          $totalclk = '0';

          $totalimp = '0';

          $totalamt = '0';

          $totalctr = '0';

          $totavgcpc = '0';

          $totavgcpm = '0';

          $totalavgcpc = '0';

          $totalavgcpm = '0';

          foreach ($datas as $vallue) {



              if ($vallue->Clicks == 0 || $vallue->Imprs == 0) {

                  $vallue->CTR = 0;

              } else {

                  $vallue->CTR = ($vallue->Clicks / $vallue->Imprs) * 100;

              }

              // $newDate = date("d-m-Y", strtotime($vallue->Created));

              $newDate = $vallue->Created;

              $vallue->Created = $newDate;

              if ($vallue->Clicks == 0 && $vallue->Imprs == 0) {

                  $vallue->AvgCPC = 0;

              } else {

                  // $vallue->AvgCPC = $vallue->Totals / ($vallue->Imprs + $vallue->Clicks);

                  $vallue->AvgCPC = $vallue->click_amount > 0 ? $vallue->click_amount / $vallue->Clicks : 0;

                  $vallue->AvgCPM = $vallue->imp_amount > 0 ? $vallue->imp_amount / $vallue->Imprs : 0;

                  

              }

              $totalimp += $vallue->Imprs;

              $totalclk += $vallue->Clicks;

              $totavgcpc += $vallue->AvgCPC;

              $totavgcpm += $vallue->AvgCPM;

              $totalamt += $vallue->Totals;

              $vallue->Total = $vallue->Totals;

              unset($vallue->Totals);

          }

          // $totalctr = $totalclk / $totalimp * 100;

          // $totalavgcpc = $totalamt / $totalclk;

          $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;

          //$totalavgcpc = $totalamt / ($totalclk + $totalimp);

          if ($totalclk == 0 && $totalimp == 0) {

              $totalavgcpc = 0;

          } else {

              // $totalavgcpc = $totalamt / ($totalclk + $totalimp);

              $totalavgcpc = $totavgcpc;

              $totalavgcpm = $totavgcpm;

          }

          $asdsdas = array('total_impression' => $totalimp, 'total_click' => $totalclk, 'total_amount' => $totalamt, 'total_ctr' => $totalctr, 'total_avgcpc' => $totalavgcpc, 'total_avgcpm' => $totalavgcpm);



          $return['code']    = 200;

          $return['data']    = $datas;

          $return['total']    = $asdsdas;

          $return['row']     = $row;

        //   $return['wallet']    = number_format($userdata->wallet, 3, '.', '');
          $wltAmt = getWalletAmount($uid);
          $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');

          $return['message'] = 'Succssfully';

      } else {

          $return['code']    = 100;

        //   $return['wallet']    = number_format($userdata->wallet, 3, '.', '');
          $wltAmt = getWalletAmount($uid);
          $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');

          $return['message'] = 'Record not found!';

      }

      return json_encode($return, JSON_NUMERIC_CHECK);

  }

}



