<?php

namespace App\Http\Controllers\AppPublisher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Publisher\PubPayoutMethod;
use App\Models\Publisher\PubUserPayoutMode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;




class AppPublisherReportUserController extends Controller
{
  public function ad_report(Request $request)
  {
    $validator = Validator::make($request->all(), [
          'uid'       => 'required',
          'to_date'   => 'required|date_format:Y-m-d',
          'from_date' => 'required|date_format:Y-m-d',
      ]);
      if ($validator->fails()) {
          $return['code'] = 100;
          $return['error'] = $validator->errors();
          $return['message'] = 'Validation error!';
          return json_encode($return);
      }

      $uid = $request->uid;
      $todate = $request->to_date;
      $fromdate = $request->from_date;
      $grpby = $request->group_by;
      $placement = $request->placement;
      $country = $request->country;
      $dmn = $request->domain;
      $limit = $request->lim;
      $page = $request->page;
      $pg = $page - 1;
      $start = ( $pg > 0 ) ? $limit * $pg : 0;
      // $nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));
  
      $sql = DB::table('pub_stats');
      if($grpby == 'domain') {
        $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
        $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
        $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web","pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code",
                      DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, SUM(ss_pub_stats.impressions) as Imprs, 
                      SUM(ss_pub_stats.clicks) as Clicks, IF(SUM(ss_pub_stats.amount) != 'NULL', FORMAT(SUM(ss_pub_stats.amount), 5), 0) as Totals")
                      );
        
      } else {

        $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
        $sql->select(
            "pub_stats.device_type", "pub_stats.device_os", "pub_stats.country",
            "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code",
            DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created"),
            DB::raw("SUM(ss_pub_stats.impressions) as Imprs"),
            DB::raw("SUM(ss_pub_stats.clicks) as Clicks"),
            DB::raw("IF(SUM(ss_pub_stats.amount) IS NOT NULL, FORMAT(SUM(ss_pub_stats.amount), 5), 0) as Totals"));
      }
    
    
    
    $sql->where("pub_stats.publisher_code", $uid)
      ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
    
    if(strlen($country) > 0 )
    {
        $sql->where('pub_stats.country', $country);
    }
    
    if(strlen($dmn) > 0 )
    {
        $sql->where('pub_stats.website_id', $dmn);
    }
    if(strlen($placement) > 0 )
    {
        $sql->where('pub_stats.adunit_id', $placement);
    }
            
    if($grpby == 'date') {
      $sql->groupByRaw('DATE(ss_pub_stats.udate)');
    }
    elseif($grpby == 'domain') {
      $sql->groupByRaw('ss_pub_websites.site_url');
    }
    else {
      $sql->groupByRaw($grpby);
    }
    //$row   = $sql->count();
    $datascount = $sql->orderBy('pub_stats.udate', 'DESC')->get();
    $row   = count($datascount);
    $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
   
    //print_r($datas); exit;
    
    
    if (!empty($datas)) {
      $totalclk = '0';
      $totalimp = '0';
      $totalamt = '0';
      $totalctr = '0';
      $totalavgcpc = '0';
      foreach ($datas as $vallue) {
        if ($vallue->Imprs == 0) {
          $vallue->CTR = 0;
        } else {
          $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
        }
        
        $newDate = $vallue->created;
        $vallue->created = $newDate;
        if ($vallue->Clicks == 0) {
          $vallue->AvgCPC = 0;
        } else {
          $vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);
        }
        $totalimp += $vallue->Imprs;
        $totalclk += $vallue->Clicks;
        $totalamt += $vallue->Totals;
        $vallue->Total = $vallue->Totals;
        unset($vallue->Totals);
      }
      //print_r($datas); exit;
      $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
      $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp): 0;
      $asdsdas = array('total_impression' => round($totalimp, 2), 'total_click' => round($totalclk, 2), 'total_amount' => round($totalamt, 5), 'total_ctr' => round($totalctr, 2), 'total_avgcpc' => round($totalavgcpc, 2));
      $userdata = User::where('uid', $uid)->first();
      $return['code']    		= 200;
      $return['data']    		= $datas;
      $return['total']    	= $asdsdas;
      $return['row']     		= $row;
      $wltPubAmt = getPubWalletAmount($uid);
      $wltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $userdata->pub_wallet;
      $return['wallet']   	= number_format($wltamt, 2);
      $return['message'] 		= 'Successfully';
    } else {
      $return['code']    = 100;
      $return['message'] = 'Something went wrong!';
    }
      return json_encode($return, JSON_NUMERIC_CHECK);
  }
  
  	public function payoutMethodList(Request $request)
    {
      $validator = Validator::make($request->all(), [
        'uid'       => 'required',
      ]);
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
      }
    //   $user = User::where('uid', $request->uid)->distinct()->get();
      $user = User::where('uid', $request->uid)->count();
      if(empty($user))
      {
      	$return['code'] = 102;
        $return['message'] = 'User not found!';
        return json_encode($return);
      }
      
      $listmode = PubUserPayoutMode::select('payout_id', 'payout_name', 'pay_account_id', 'pub_withdrawl_limit')->where('publisher_id', $request->uid)->first();
      
      $listmethod = PubPayoutMethod::select('id', 'method_name', 'image', 'processing_fee', 'min_withdrawl', 'description', 'display_name')->get();
      
      foreach($listmethod as $list)
      {
      	$list->image = config('app.url').'payout_methos'. '/' .$list->image;
        if(!empty($listmode))
        {
          if($listmode->payout_id == $list->id)
          {
          	$list->user_opt = 1;
          }
          else
          {
            $list->user_opt = 0;
          }
        }
        else
        {
        	$list->user_opt = 0;
        }
      	$data[] = $list;
      }
      
      
      if(!empty($data))
      {
      	$return['code'] = 200;
        $return['data'] = $data;
        $return['wid_limit'] = ($listmode) ? $listmode->pub_withdrawl_limit : '';
        $return['pay_account_id'] = ($listmode) ? $listmode->pay_account_id : '';
        $return['payout_name'] = ($listmode) ? $listmode->payout_name : '';
        $return['message'] = 'List fetched successfully!';
      }
      else
      {
      	$return['code'] = 101;
        $return['message'] = 'Data not found!';
      }
      return json_encode($return);
    }
    public function wireTransferGatewayAdd(Request $request){
      $validator = Validator::make($request->all(), [
        'bank_name'       => 'required',
        'account_holder_name'   => 'required',
        'account_number' => 'required',
        'ifsc_code' => 'required',
        'minimum_amount' => 'required',
    ]);
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
    }
    DB::table('pub_user_payout_modes')->where('publisher_id', $request->publisher_id)->where('status', 1)->update(['status' => 0]);
    $updateData  =  PubUserPayoutMode::Where('publisher_id',$request->publisher_id)->where('payout_id', $request->payout_id)->first();
    if($updateData){
      $date = Carbon::now();
      $formatedDate = $date->format('Y-m-d H:i:s');
        $updateData->payout_id 			= $request->payout_id;
        $updateData->pay_account_id 		= $request->pay_account_id;
        $updateData->payout_name 		= $request->payout_name;
        $updateData->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $updateData->bank_name 			= $request->bank_name;
        $updateData->account_holder_name 			= $request->account_holder_name;
        $updateData->account_number 			= $request->account_number;
        $updateData->ifsc_code 			= $request->ifsc_code;
        $updateData->swift_code 			= $request->swift_code;
        $updateData->iban_code 			= $request->iban_code;
        $updateData->minimum_amount 			= $request->minimum_amount;
        $updateData->created_at 			= $request->formatedDate;
        $updateData->updated_at 			= $request->formatedDate;
        $updateData->status = 1;
        if($updateData->update())
        {
          $return['code'] = 200;
          $return['message'] = 'Updated Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
    }else{
      $date = Carbon::now();
      $formatedDate = $date->format('Y-m-d H:i:s');
      $values = array(
        'bank_name' => $request->bank_name,
        'payout_id' => $request->payout_id,
        'publisher_id' => $request->publisher_id,
        'payout_name' => $request->payout_name,
        'pay_account_id' => $request->pay_account_id,
        'pub_withdrawl_limit' => $request->pub_withdrawl_limit,
        'account_holder_name' => $request->account_holder_name,
        'account_number' => $request->account_number,
        'ifsc_code' => $request->ifsc_code,
        'swift_code' => $request->swift_code,
        'iban_code' => $request->iban_code,
        'minimum_amount' => $request->minimum_amount,
        'created_at' => $formatedDate,
        'updated_at' => $formatedDate,
         'status' =>1,
      );
      $datainsert  =  DB::table('pub_user_payout_modes')->insert($values);
      $msg = 'Insert Data Successfully!';
      if($datainsert){
        $return['code'] = 200;
        $return['message'] = $msg;
      }else{
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
    }
      return json_encode($return, JSON_NUMERIC_CHECK);
   }
}
