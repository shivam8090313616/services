<?php

namespace App\Http\Controllers\Advertisers;

use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCampClickLog;
use App\Models\Transaction;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class AppReportUserControllers extends Controller
{


    public function transactionView(Request $request)
    {
        $transactionid = $request->input('transaction_id');
        $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'transactions.payble_amt', 'transactions.gst', 'transactions.fee', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone', 'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 'transactions.advertiser_code', 'transactions.transaction_id', 'transactions.payment_mode', 'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.created_at')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->where('transactions.transaction_id', $transactionid)
            ->first();
        if ($report) {
            $return['code']    = 200;
            $return['data']    = $report;
            $return['msg'] = 'Transaction View retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function ad_type(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'       => 'required',
            'ad_type'   => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid = $request->input('uid');
        $adtype = $request->input('ad_type');
        $getcampdata = Campaign::select('campaign_id', 'campaign_name')->where('advertiser_code', $uid)->where('ad_type', $adtype)->get();
        if ($getcampdata) {
            $return['code']    = 200;
            $return['data']    = $getcampdata;
            $return['msg'] = 'Succssfully';
        } else {
            $return['code']    = 100;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    public function camp_report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'       => 'required',
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
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


        $sql1 = DB::table('campaigns')
        ->select('campaigns.ad_type', 'camp_budget_utilize.camp_id as CampaignId', 'camp_budget_utilize.udate as Created', 
         DB::raw('SUM(ss_camp_budget_utilize.impressions) Imprs, SUM(ss_camp_budget_utilize.clicks) Clicks, SUM(ss_camp_budget_utilize.amount) Totals'))
        ->leftJoin('camp_budget_utilize', 'camp_budget_utilize.camp_id','=','campaigns.campaign_id')
        ->where('camp_budget_utilize.advertiser_code', $uid)
        ->whereBetween('camp_budget_utilize.udate', [$todate, $nfromdate]); 
            if ($campid != 'All') {
            $sql1 = $sql1->where('campaigns.campaign_id', $campid);
            }
            if ($campid == 'All' && $adtype) {
            $sql1 = $sql1->where('campaigns.ad_type', $adtype);
            }
            if ($repType == 'Campaign') {
            $limt =  $sql1->groupBy('camp_budget_utilize.udate', 'camp_budget_utilize.camp_id')->get();
            } else {
            $limt =  $sql1->groupBy('camp_budget_utilize.udate')->get();
            }
        $sql = $sql1 . $limt;
        $datas = DB::select($sql);
        $row = count($datas);
        //dd($row);
        if (!empty($datas)) {
            $totalclk = '0';
            $totalimp = '0';
            $totalamt = '0';
            $totalctr = '0';
            $totalavgcpc = '0';
            foreach ($datas as $vallue) {
                $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
                $newDate = $vallue->Created;
                $vallue->Created = $newDate;
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
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
            $totalavgcpc = $totalamt / $totalclk;
            $asdsdas = array('total_impression' => round($totalimp, 2), 'total_click' => round($totalclk, 2), 'total_amount' => round($totalamt, 2), 'total_ctr' => round($totalctr, 2), 'total_avgcpc' => round($totalavgcpc, 2));
            $userdata = User::where('uid', $uid)->first();
          	$return['code']    = 200;
            $return['data']    = $datas;
            $return['total']    = $asdsdas;
            $return['row']     = $row;
            $wltAmt = getWalletAmount($uid);
          	$return['wallet']    = ($wltAmt) > 0 ? $wltAmt : $userdata->wallet;
            $return['msg'] = 'Succssfully';
        } else {
            $return['code']    = 100;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
