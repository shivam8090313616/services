<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;

class AdvReportAdminController extends Controller
{
    public function adReport(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $toDate = $request->to_date;
        $fromDate = $request->from_date;
        $grpby = $request->group_by;
        $advertiser = $request->advertiser;    
        $campaign = $request->campaign;
        $ad_type = $request->ad_type;
        $limit = $request->lim ?? 10;
        $page = $request->page ?? 1;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $sql = DB::table('camp_budget_utilize')
            ->select(
                'camp_budget_utilize.camp_id',
                DB::raw("DATE_FORMAT(ss_camp_budget_utilize.udate, '%d-%m-%Y') as created"),
                DB::raw('IFNULL(SUM(ss_camp_budget_utilize.impressions), 0) as Imprs'),
                DB::raw('IFNULL(SUM(ss_camp_budget_utilize.clicks), 0) as Clicks'),
                DB::raw('IFNULL(SUM(ss_camp_budget_utilize.amount), 0) as Totals'),
                DB::raw('IFNULL(SUM(ss_camp_budget_utilize.imp_amount), 0) as CpmAmt'),
                DB::raw('IFNULL(SUM(ss_camp_budget_utilize.click_amount), 0) as CpcAmt')
            );

        if($grpby == 'advertiser' || $grpby == 'campaign') {
            $sql->leftjoin('users', 'camp_budget_utilize.advertiser_code', '=', 'users.uid')
                ->addSelect(
                    DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name"),
                    DB::raw('IFNULL(COUNT(ss_camp_budget_utilize.imp_amount), 0) as Cpm_count'),
                    'users.uid'
                );
        } else if($grpby == 'country') {
            $sql->leftJoin('adv_stats', 'camp_budget_utilize.camp_id', '=', 'adv_stats.camp_id')
            ->addSelect('adv_stats.country');
        } else if($grpby == 'device_type'){
            $sql->leftJoin('adv_stats', 'camp_budget_utilize.camp_id', '=', 'adv_stats.camp_id')
            ->addSelect('adv_stats.device_type');
        } else if($grpby == 'device_os'){
            $sql->leftJoin('adv_stats', 'camp_budget_utilize.camp_id', '=', 'adv_stats.camp_id')
            ->addSelect('adv_stats.device_os');
        }

        $sql->whereBetween('camp_budget_utilize.udate', [$fromDate, $toDate]);
        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_camp_budget_utilize.udate)');
        } else if ($grpby == 'advertiser') {
            $sql->groupByRaw('ss_users.uid');
        } else if ($grpby == 'campaign') {
            $sql->groupByRaw('ss_camp_budget_utilize.camp_id');
        } else {
            $sql->groupByRaw($grpby);
        }

        $rows = $sql->get()->count();
        $data = $sql->offset($start)->limit($limit)->orderBy('camp_budget_utilize.udate', 'DESC')->get();

        if ($data->isNotEmpty()) {
            $totalClicks = $totalImprs = $totalAmount =  $totalCampaign = 0;

            $data->transform(function ($value) use (&$totalClicks, &$totalImprs, &$totalAmount, &$totalCampaign) {
                $value->CTR = ($value->Imprs == 0) ? 0 : round(($value->Clicks / $value->Imprs) * 100, 2);
                $value->AvgCPC = ($value->Clicks == 0) ? 0 : round($value->CpcAmt / $value->Clicks, 2);
                $value->AvgCPM = ($value->Imprs == 0) ? 0 : round($value->CpmAmt / $value->Imprs, 2);

                $totalImprs += $value->Imprs;
                $totalClicks += $value->Clicks;
                $totalAmount += $value->Totals;
                $totalCampaign+= $value->Cpm_count;
                return $value;
            });

            $totalCTR = ($totalClicks == 0) ? 0 : round(($totalClicks / $totalImprs) * 100, 2);
            $totalAvgCPC = ($totalClicks == 0) ? 0 : round($totalAmount / $totalClicks, 2);
            $totalAvgCPM = ($totalImprs == 0) ? 0 : round($totalAmount / $totalImprs, 2);

            $totals = [
                'total_impression' => round($totalImprs, 3),
                'total_click' => round($totalClicks, 3),
                'total_amount' => round($totalAmount, 5),
                'total_ctr' => round($totalCTR, 3),
                'total_avgcpc' => round($totalAvgCPC, 3),
                'total_avgcpm' => round($totalAvgCPM, 3),
                'total_campaign' => $totalCampaign,
            ];

            $return['code'] = 200;
            $return['data'] = $data;
            $return['total'] = $totals;
            $return['row'] = $data->count();
            $return['rows'] = $rows;
            $return['message'] = 'Advertiser Stats Report Find Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Data not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // fetch user detail on dropdown adv_stats
    public function adv_statslist(){
        $data = DB::table("adv_stats")->select('advertiser_code')->where('advertiser_code','!=','')->orderByDesc('id')->groupBy('advertiser_code')->get();
        if($data->isNotEmpty()){
            $return['code'] = 200;
            $return['message'] = "Advertiser list fethced successfully.";
            $return['row'] = $data->count();
            $return['data'] = $data;
        } else{
            $return['code'] = 101;
            $return['message'] = "Data not found!";
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // fetch advertiser dropdown ad type list
    public function adv_adtypelist(Request $request){
        $advertiser_code = $request->advertiser_code;
        $validator = Validator::make($request->all(), [
             'advertiser_code' => 'required|exists:adv_stats,advertiser_code'
        ]);
        if($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $data = Campaign::select('ad_type')->where('advertiser_code', $advertiser_code)->orderBy('ad_type', 'asc')->groupBy('ad_type')->get();
        if($data->isNotEmpty()){
            $return['code'] = 200;
            $return['data'] = $data;
            $return['message'] = "Ad type list fetched successfully.";
        } else{
                $return['code'] = 101;
                $return['message'] = "Data not found!";
            }
        

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // fetch advertiser adtype campaign dropdown list
    public function adv_adtypecampaignlist(Request $request){
        $advertiser_code = $request->advertiser_code;
        $ad_type = $request->ad_type;
        $validator = Validator::make($request->all(), [
            'advertiser_code' => 'required|exists:adv_stats,advertiser_code',
            'ad_type' => 'required|exists:campaigns,ad_type'
        ]);
        if($validator->fails()){
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }

        $data = Campaign::select("advertiser_code","ad_type","campaign_id")->where('advertiser_code',$advertiser_code)->where('ad_type',$ad_type)->get();
        if($data->isNotEmpty()){
            $return['code'] = 200;
            $return['message'] = "Campaign id fetched successfully";
            $return['data'] = $data;
            $return['row'] = $data->count();
        } else{
            $return['code'] = 101;
            $return['message'] = 'Data not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
