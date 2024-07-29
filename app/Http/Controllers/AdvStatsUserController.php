<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\User;

use Illuminate\Support\Facades\DB;

class AdvStatsUserController extends Controller
{
    
    public function advStatistics(Request $request)

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

      	$limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ( $pg > 0 ) ? $limit * $pg : 0;

        
    //   	$sql = DB::table('adv_stats')
    //   	      ->select(

    //               "adv_stats.camp_id", "adv_stats.country",
    
    //               "adv_stats.device_type", "adv_stats.device_os",
    
    //               DB::raw("DATE_FORMAT(ss_adv_stats.udate, '%d-%m-%Y') as created"),
    
    //               DB::raw("SUM(ss_adv_stats.impressions) as Imprs"),
    
    //               DB::raw("SUM(ss_adv_stats.clicks) as Clicks"),
    
    //               DB::raw("IF(SUM(ss_adv_stats.amount) IS NOT NULL, FORMAT(SUM(ss_adv_stats.amount), 5), 0) as Totals"),
    //               DB::raw("SUM(ss_camp_budget_utilize.imp_amount) as imp_amount"),
    //               DB::raw("SUM(ss_camp_budget_utilize.click_amount) as click_amount")
    //           )
    //          ->leftJoin('camp_budget_utilize', function($join) use ($uid, $todate, $fromdate) {
    //             $join->on('adv_stats.camp_id', '=', 'camp_budget_utilize.camp_id')
    //                  ->where('camp_budget_utilize.advertiser_code', $uid)
    //                  ->whereBetween('camp_budget_utilize.udate', [$todate, $fromdate]);
    //         })
           
    //         ->where("adv_stats.advertiser_code", $uid)
    
    //         ->whereBetween("adv_stats.udate", [$todate, $fromdate]);
    
   

    //   if($grpby == 'date') {

    //     $sql->groupByRaw('DATE(ss_adv_stats.udate)');

    //   }

   
    //   else {

    //     $sql->groupByRaw($grpby);

    //   }

      

    //   $datas = $sql->offset($start)->limit($limit)->orderBy('adv_stats.udate', 'DESC')->get()->toArray();
    
        $sql = DB::table('adv_stats')
        ->select(
            "adv_stats.camp_id",
            "adv_stats.country",
            "adv_stats.device_type",
            "adv_stats.device_os",
            DB::raw("DATE_FORMAT(ss_adv_stats.udate, '%d-%m-%Y') as created"),
            DB::raw("SUM(ss_adv_stats.impressions) as Imprs"),
            DB::raw("SUM(ss_adv_stats.clicks) as Clicks"),
            DB::raw("IF(SUM(ss_adv_stats.amount) IS NOT NULL, FORMAT(SUM(ss_adv_stats.amount), 5), 0) as Totals")
           
        )
        
        ->where('adv_stats.advertiser_code', $uid)
        ->whereBetween('adv_stats.udate', [$todate, $fromdate]);

        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_adv_stats.udate)');
        } else {
            $sql->groupByRaw($grpby);
        }
        
        $datas = $sql->offset($start)
            ->limit($limit)
            ->orderBy('adv_stats.udate', 'DESC')
            ->get()
            ->toArray();


      

      if (!empty($datas)) {

        

        $return['code']    		= 200;

        $return['data']    		= $datas;

        $return['message'] 		= 'Successfully';

      } else {

        $return['code']    = 100;

        $return['message'] = 'Something went wrong!';

      }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
}
