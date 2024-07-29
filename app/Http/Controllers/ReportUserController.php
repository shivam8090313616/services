<?php



namespace App\Http\Controllers;



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





class ReportUserController extends Controller

{    // Transaction view function
    public function transactionView(Request $request)
    {
        $transactionid = $request->input('transaction_id');
        $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'transactions.payble_amt', 'transactions.gst', 'transactions.fee', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone', 'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 'transactions.advertiser_code', 'transactions.transaction_id', 'transactions.payment_mode', 'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id','transactions.gst_no', 'transaction_logs.created_at','transaction_logs.serial_no')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')

            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')

            ->where('transactions.transaction_id', $transactionid)

            ->first();
        if($report->payment_mode == 'bitcoin' || $report->payment_mode == 'stripe' || $report->payment_mode == 'now_payments' || $report->payment_mode == 'coinpay'){
            $report->fee = $report->fee;
        }else{
            $report->fee = $report->fee - $report->fees_tax;
        } 
        // $report->gst = ($report->gst > 0) ? $report->gst + $report->fees_tax:'';
        $report->subtotal = $report->amount + $report->fee;
        if ($report) {
            $return['code']    = 200;
            $return['data']    = $report;
            $return['message'] = 'Transaction View retrieved successfully!';
        } else {  
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    // public function transactionView(Request $request)
    // {
    //     $transactionid = $request->input('transaction_id');
    //     $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'transactions.payble_amt', 'transactions.gst', 'transactions.fee', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone', 'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 'transactions.advertiser_code', 'transactions.transaction_id', 'transactions.payment_mode', 'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.created_at')
    //         ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
    //         ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
    //         ->where('transactions.transaction_id', $transactionid)
    //         ->first();
    //     $report->gst = $report->gst + $report->fees_tax;
    //     $report->subtotal = $report->amount + $report->fee;

    //     if ($report) {
    //         $return['code']    = 200;
    //         $return['data']    = $report;
    //         $return['message'] = 'Transaction View retrieved successfully!';
    //     } else {
    //         $return['code']    = 101;
    //         $return['message'] = 'Something went wrong!';
    //     }
    //     return json_encode($return, JSON_NUMERIC_CHECK);
    // }

    public function ad_type(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'uid'       => 'required',

            'ad_type'   => 'required',

        ]);

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['message'] = 'Valitation error!';

            return json_encode($return);

        }

        $uid = $request->input('uid');

        $adtype = $request->input('ad_type');

        $getcampdata = Campaign::select('campaign_id', 'campaign_name')->where('advertiser_code', $uid)->where('ad_type', $adtype)->get();

        if ($getcampdata) {

            $return['code']    = 200;

            $return['data']    = $getcampdata;

            $return['message'] = 'Succssfully';

        } else {

            $return['code']    = 100;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }





    public function camp_reportTest(Request $request)

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

            $sql1 = "SELECT imp.created_at as Created, imp.campaign_id as CampaignId, count(imp.id) as Imprs, 

                    (SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk.created_at) AND clk.campaign_id = imp.campaign_id) as Clicks, 

                    (SELECT IF(SUM(amount) != 'NULL', FORMAT(SUM(amount),2), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk2.created_at) 

                    AND clk2.campaign_id = imp.campaign_id ) + FORMAT(SUM(imp.amount),2) as Totals FROM `ss_ad_impressions` imp WHERE imp.advertiser_code = '$uid' AND (created_at BETWEEN '$todate' AND '$nfromdate')";

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
            $sql1 = "SELECT imp.created_at as Created, count(imp.id) as Imprs, 

            (SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk.created_at) ) as Clicks, 

            (SELECT IF(SUM(amount) != 'NULL', FORMAT(SUM(amount),2), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.advertiser_code = '$uid' AND DATE(imp.created_at) = DATE(clk2.created_at) 

            AND clk2.campaign_id = imp.campaign_id)  + FORMAT(SUM(imp.amount),2) as Totals FROM `ss_ad_impressions` imp WHERE imp.advertiser_code = '$uid' AND (created_at BETWEEN '$todate' AND '$nfromdate')";

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

        $userdata = User::where('uid', $uid)->first();

        //dd($row);

        if (!empty($datas)) {

            $totalclk = '0';

            $totalimp = '0';

            $totalamt = '0';

            $totalctr = '0';

            $totalavgcpc = '0';

            foreach ($datas as $vallue) {

                $vallue->CTR = $vallue->Clicks / $vallue->Imprs * 100;

                // $newDate = date("d-m-Y", strtotime($vallue->Created));

                $newDate = $vallue->Created;

                $vallue->Created = $newDate;

                if ($vallue->Clicks == 0 && $vallue->Imprs == 0) {

                    // $vallue->AvgCPC = round($vallue->Totals / 1, 2);

                    $vallue->AvgCPC = 0;

                  	//$vallue->AvgCPC = round($vallue->Totals / ($vallue->Imprs + $vallue->Clicks), 2);

                } else {

                    //$vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);

                    // $vallue->AvgCPC = round($vallue->Clicks / $vallue->Totals , 2);

                    $vallue->AvgCPC = $vallue->Totals / ($vallue->Imprs + $vallue->Clicks);

                  //$vallue->AvgCPC = (120 + 15) / 11.61;

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
            if ($totalclk == 0 && $totalimp == 0) {
                $totalavgcpc = 0;
            } else {
                $totalavgcpc = $totalamt / ($totalclk + $totalimp);
            }
            $asdsdas = array('total_impression' => $totalimp, 'total_click' => $totalclk, 'total_amount' => $totalamt, 'total_ctr' => $totalctr, 'total_avgcpc' => $totalavgcpc);

            $return['code']    = 200;
            $return['data']    = $datas;
            $return['total']    = $asdsdas;
            $return['row']     = $row;
            // $return['wallet']    = number_format($userdata->wallet, 3, '.', '');
            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');
            $return['message'] = 'Succssfully';
        } else {
            $return['code']    = 100;
            // $return['wallet']    = number_format($userdata->wallet, 3, '.', '');

            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');
            $return['message'] = 'Record not found!';
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
            // $return['wallet']    = number_format($userdata->wallet, 3, '.', '');

            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');
            $return['message'] = 'Succssfully';
        } else {
            $return['code']    = 100;
            // $return['wallet']    = number_format($userdata->wallet, 3, '.', '');
            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($userdata->wallet, 3, '.', '');
            $return['message'] = 'Record not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
