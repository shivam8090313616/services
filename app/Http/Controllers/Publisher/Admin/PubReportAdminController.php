<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Publisher\Pubstats;
use App\Models\UserCampClickLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PubReportAdminController extends Controller
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
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
        $placement = $request->placement;
        $country = $request->country;
        $dmn = $request->domain;
        $category = $request->category;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        
        $sql = DB::table('pub_stats');
        //     adunit_id pub_stats  pub_websites
        if ($grpby == 'domain') {
            $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
            $sql->leftJoin('categories', 'pub_websites.website_category', '=', 'categories.id');
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid","categories.cat_name as web_cat",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs, 
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals")
            );
        } else {
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid","pub_adunits.site_url",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs,
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals"));
        }
        
        $sql->where('pub_stats.publisher_code', '!=', 'NULL')
            ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
        
        if (strlen($country) > 0) {
            $sql->where('pub_stats.country', $country);
        }
        
        if (strlen($dmn) > 0) {
            $sql->where('pub_stats.website_id', $dmn);
        }
        
        if (strlen($placement) > 0) {
            $sql->where('pub_stats.adunit_id', $placement);
        }
        
        // if(strlen($category) > 0 && $grpby == 'domain'){
        //   $sql->where("pub_websites.website_category",$category); 
        // }

        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_pub_stats.udate)');
        } elseif ($grpby == 'domain') {
            $sql->groupByRaw('ss_pub_websites.site_url');
        } elseif ($grpby == 'adunit_id') {
            $sql->groupByRaw('ss_pub_stats.adunit_id');
        } else {
            $sql->groupByRaw($grpby);
        }
        
        $rows = $sql->get()->count();
        $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
        $row = count($datas);
        
        if (!empty($datas)) {
            $totalclk = 0;
            $totalimp = 0;
            $totalamt = 0;
            $totalctr = 0;
            $totalavgcpc = 0;
        
            foreach ($datas as $value) {
                if ($value->Imprs == 0) {
                    $value->CTR = 0;
                } else {
                    $value->CTR = round($value->Clicks / $value->Imprs * 100, 2);
                }
        
                $newDate = $value->created;
                $value->created = $newDate;
        
                if ($value->Clicks == 0) {
                    $value->AvgCPC = 0;
                } else {
                    $value->AvgCPC = round($value->Totals / $value->Clicks, 2);
                }
        
                $totalimp += $value->Imprs;
                $totalclk += $value->Clicks;
                $totalamt += $value->Totals;
                $value->Total = $value->Totals;
                unset($value->Totals);
            }
        
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
            $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
        
            $totals = [
                'total_impression' => round($totalimp, 3),
                'total_click' => round($totalclk, 3),
                'total_amount' => round($totalamt, 5),
                'total_ctr' => round($totalctr, 3),
                'total_avgcpc' => round($totalavgcpc, 3)
            ];
        
            $return['code'] = 200;
            $return['data'] = $datas;
            $return['total'] = $totals;
            $return['row'] = $row;
            $return['rows'] = $rows;
            $return['message'] = 'Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }



    public function adReportTest(Request $request)
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
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
        $placement = $request->placement;
        $country = $request->country;
        $dmn = $request->domain;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        
        $sql = DB::table('pub_stats');
        //     adunit_id pub_stats  pub_websites pub_stats
        if ($grpby == 'domain') {
            $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs, 
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals")
            );
        } else {
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs,
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals"));
        }
        
        $sql->where('pub_stats.publisher_code', '!=', 'NULL')
            ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
        
        if (strlen($country) > 0) {
            $sql->where('pub_stats.country', $country);
        }
        
        if (strlen($dmn) > 0) {
            $sql->where('pub_stats.website_id', $dmn);
        }
        
        if (strlen($placement) > 0) {
            $sql->where('ss_pub_stats.adunit_id', $placement);
        }
        
        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_pub_stats.udate)');
        } elseif ($grpby == 'domain') {
            $sql->groupByRaw('ss_pub_websites.site_url');
        } elseif ($grpby == 'adunit_id') {
            $sql->groupByRaw('ss_pub_stats.adunit_id');
        } else {
            $sql->groupByRaw($grpby);
        }
        
        $rows = $sql->get()->count();
        $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
        $row = count($datas);
        
        if (!empty($datas)) {
            $totalclk = 0;
            $totalimp = 0;
            $totalamt = 0;
            $totalctr = 0;
            $totalavgcpc = 0;
        
            foreach ($datas as $value) {
                if ($value->Imprs == 0) {
                    $value->CTR = 0;
                } else {
                    $value->CTR = round($value->Clicks / $value->Imprs * 100, 2);
                }
        
                $newDate = $value->created;
                $value->created = $newDate;
        
                if ($value->Clicks == 0) {
                    $value->AvgCPC = 0;
                } else {
                    $value->AvgCPC = round($value->Totals / $value->Clicks, 2);
                }
        
                $totalimp += $value->Imprs;
                $totalclk += $value->Clicks;
                $totalamt += $value->Totals;
                $value->Total = $value->Totals;
                unset($value->Totals);
            }
        
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
            $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
        
            $totals = [
                'total_impression' => round($totalimp, 3),
                'total_click' => round($totalclk, 3),
                'total_amount' => round($totalamt, 5),
                'total_ctr' => round($totalctr, 3),
                'total_avgcpc' => round($totalavgcpc, 3)
            ];
        
            $return['code'] = 200;
            $return['data'] = $datas;
            $return['total'] = $totals;
            $return['row'] = $row;
            $return['rows'] = $rows;
            $return['message'] = 'Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function reportImprDetail(Request $request)
    {
        $adunit_id = base64_decode($request->adunit_id);
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate . ' - 1 days'));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        //dd($adunit_id,$startDate,$endDate);
        if ($startDate == '' && $endDate == '') {
            $impDetail = DB::table('ad_impressions')
                ->select('id', 'device_type', 'device_os', 'ip_addr', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id);
        } else {
            $impDetail = DB::table('ad_impressions')
                ->select('id', 'device_type', 'device_os', 'ip_addr', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id)
                ->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }

        $impDetail->orderBy('ad_impressions.id', 'DESC');

        $row1 = $impDetail->get();
        $row = $row1->count();
        $data = $impDetail->offset($start)->limit($limit)->get();

        if ($row != null) {
            $return['code']        = 200;
            $return['data']        = $data;
            $return['row']         = $row;
            $return['message']     = 'List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function reportClickDetail(Request $request)
    {
        $adunit_id = base64_decode($request->adunit_id);
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate . ' - 1 days'));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        if ($startDate == '' && $endDate == '') {
            $impDetail = DB::table('user_camp_click_logs')
                ->select('id', 'device_type', 'device_os', 'ip_address', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id);
        } else {
            $impDetail = DB::table('user_camp_click_logs')
                ->select('id', 'device_type', 'device_os', 'ip_address', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id)
                ->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }

        $impDetail->orderBy('user_camp_click_logs.id', 'DESC');

        $row1 = $impDetail->get();
        $row = $row1->count();
        $data = $impDetail->offset($start)->limit($limit)->get();

        if ($row != null) {
            $return['code']        = 200;
            $return['data']        = $data;
            $return['row']         = $row;
            $return['message']     = 'List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
