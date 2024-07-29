<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Transaction;

use App\Models\TransactionLog;

use App\Models\User;

use App\Models\Notification;

use App\Models\UserNotification;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use PDF;

use PHPMailer\PHPMailer\PHPMailer;

use Carbon\Carbon;



class TransactionLogAdminController extends Controller

{

    public function transactionsList(Request $request)

    {

        $uid = $request->uid;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;



        $transaction = DB::table('transactions')

            ->select('transactions.id', 'transactions.transaction_id', 'transactions.advertiser_code', 'transactions.payment_mode', 'transactions.payment_id', 'transaction_logs.remark', 'transaction_logs.amount', 'transaction_logs.pay_type', 'transaction_logs.created_at')

            ->join('transaction_logs', 'transactions.transaction_id', '=', 'transaction_logs.transaction_id')

            ->where('transactions.advertiser_code', $uid)

            ->orderBy('transactions.id', 'desc');

        $row = $transaction->count();

        $data = $transaction->offset($start)->limit($limit)->get();



        if ($transaction) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['row']     = $row;

            $return['message'] = 'Transaction list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
    
    public function transactionsReport(Request $request)
    {
        $type = $request->type;
        $cat = $request->cat;
        $authProvider = $request->auth_provider;
        $country = $request->country;
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        // $endDate = $request->endDate;
        $endDate = date('Y-m-d', strtotime($request->endDate));
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $report = Transaction::select('users.email','users.country','users.auth_provider','users.website_category','transactions.advertiser_code','transactions.fee','transactions.gst',
            DB::raw('ss_transactions.amount + ss_transactions.fee + ss_transactions.gst as payble_amt'),'transactions.amount as tmaunt','transactions.transaction_id','transactions.payment_mode','transaction_logs.id','transaction_logs.pay_type','transactions.payment_id','transaction_logs.amount','transaction_logs.remark','transaction_logs.created_at','categories.cat_name','transactions.payment_resource','transaction_logs.serial_no','sources.title as source_title'
        )
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->join('categories', 'users.website_category', '=', 'categories.id')
            ->join('sources', 'users.auth_provider', '=', 'sources.source_type')
            ->where('transactions.status', 1)
            ->where('transactions.payment_mode', '!=', 'bonus')
            ->where('transaction_logs.cpn_typ', 0)
            ->where('users.account_type', 0);
        if (strlen($type) > 0) {
            $report->where('transactions.payment_mode', $type);
        }
        if (strlen($cat) > 0) {
            $report->where('categories.cat_name', $cat);
        }
        if (strlen($authProvider) > 0) {
            $report->where('users.auth_provider', $authProvider);
        }
        if (strlen($country) > 0) {  
            $report->where('users.country', $country);
        }
        if ($startDate && $endDate) {
            $report->whereDate('transaction_logs.created_at', '>=', $nfromdate)
                ->whereDate('transaction_logs.created_at', '<=', $endDate);
        }
        if ($src) {
            $report->whereRaw('concat(ss_users.uid,ss_users.email,ss_transactions.transaction_id,ss_transactions.payment_id,ss_transaction_logs.serial_no) like ?', "%{$src}%");
        }
        $report->orderBy('transaction_logs.id', 'desc');
        $row = $report->count();
        $data = $report->offset($start)->limit($limit)->get();
        $totalsuccamt = 0;
        $totalPaybleamt = 0;
        foreach ($data as $value) {
               $totalsuccamt += $value->amount;
               (int)$totalPaybleamt += $value->payble_amt;
               //$totalPaybleamt += (int)$value->amount + (int)$value->fee + (int)$value->gst;
        }
        if ($data) {
            $return['code']                = 200;
            $return['data']                = $data;
            $return['row']                 = $row;
            $return['totalSuccessAmoutn']  = number_format($totalsuccamt, 2);
            $return['totalPaybleamt']      = number_format($totalPaybleamt, 2);
            $return['message'] = 'Transaction list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	// public function transactionsReportExcelImport(Request $request)
    // {

    //     $startDate = $request->startDate;

    //     $nfromdate = date('Y-m-d', strtotime($startDate));

    //     $endDate = $request->endDate;
        
    //   	$report = Transaction::select('users.email', 'transactions.advertiser_code', 'transactions.fee', 'transactions.gst', 'transactions.transaction_id', 'transactions.payment_mode', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.amount', 'transaction_logs.remark', 'transaction_logs.created_at')

    //         ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')

    //         ->join('users', 'users.uid', '=', 'transactions.advertiser_code')

    //       	->whereDate('transaction_logs.created_at', '>=', $nfromdate)

    //       	->whereDate('transaction_logs.created_at', '<=', $endDate)

    //         ->where('transactions.status', 1);
        
    //     $data = $report->orderBy('transaction_logs.id', 'desc')->get();
        
    //   	if ($data) {

    //         $return['code']    = 200;

    //         $return['data']    = $data;

    //         $return['message'] = 'Transaction list retrieved successfully!';

    //     } else {

    //         $return['code']    = 101;

    //         $return['message'] = 'Something went wrong!';

    //     }



    //     return json_encode($return, JSON_NUMERIC_CHECK);

    // }

    public function transactionsReportExcelImport(Request $request)
    {

        $startDate = $request->startDate;

        $nfromdate = date('Y-m-d', strtotime($startDate));

        $endDate = $request->endDate;
        
      	$report = Transaction::select('users.email', 'transactions.advertiser_code','users.auth_provider','users.country','categories.cat_name', 'transactions.fee', 'transactions.gst', 'transactions.transaction_id', 'transactions.payment_mode', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.amount', 'transaction_logs.remark', 'transaction_logs.created_at','sources.title as auth_provider')

            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')

            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')

            ->join('categories', 'users.website_category', '=', 'categories.id')

            ->join('sources', 'users.auth_provider', '=', 'sources.source_type')    
            
          	->whereDate('transaction_logs.created_at', '>=', $nfromdate)

          	->whereDate('transaction_logs.created_at', '<=', $endDate)

            ->where('transactions.status', 1)
            ->where('transactions.payment_mode', '!=', 'bonus')
            ->where('transactions.payment_mode', '!=', 'coupon')
            ->where('users.account_type', '!=', 1);
        
        $data = $report->orderBy('transaction_logs.id', 'desc')->get();
        
        //  foreach ($data as $val) {
        //     $auth_provider = $val->auth_provider;
        //     if ($auth_provider === '7api') {
        //         $val->auth_provider = 'Organic';
        //     } else if ($auth_provider === '7smobileapi') {
        //         $val->auth_provider = 'App';
        //     } else if ($auth_provider === '7sinapi') {
        //         $val->auth_provider = '7searchIn';
        //     } else if ($auth_provider === '7sinfoapi') {
        //         $val->auth_provider = 'Info Ads';
        //     } else if ($auth_provider === 'admin') {
        //         $val->auth_provider = 'Admin';
        //     } else if ($auth_provider === '7susapi') {
        //         $val->auth_provider = 'US Ads';
        //     } else if ($auth_provider === '7scaapi') {
        //         $val->auth_provider = 'CA Ads';
        //     } else if ($auth_provider === '7snetapi') {
        //         $val->auth_provider = 'Net Ads';
        //     } else if ($auth_provider === '7sexternal') {
        //         $val->auth_provider = 'External';
        //     } else {
        //         $val->auth_provider = '--';
        //     }
        // }
        
      	if ($data) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['message'] = 'Transaction list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    

    public function userInfo(Request $request)

    {

        $advertiser_code = $request->uid;

        $userInfo = User::where('uid', $advertiser_code)->first();

        if ($userInfo) {

            $return['code'] = 200;

            $return['data'] = $userInfo;

            $return['message'] = 'User info retrieved successfully!';

        } else {

            $return['code'] = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function transactionsView(Request $request)
    {
        $transactionid = $request->input('transaction_id');
        $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'transactions.payble_amt', 'transactions.fee', 'transactions.gst', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone', 'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 'transactions.advertiser_code', 'transactions.transaction_id', 'transactions.payment_mode', 'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.created_at','transaction_logs.serial_no')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->where('transactions.transaction_id', $transactionid)
            ->first();
        if($report->payment_mode == 'bitcoin' || $report->payment_mode == 'stripe' || $report->payment_mode == 'now_payments' || $report->payment_mode == 'coinpay' || $report->payment_mode == 'tazapay' )
        {
            $report->fee = $report->fee;
        }else{
            $report->fee = $report->fee - $report->fees_tax;

        }

        $report->gst = $report->gst + $report->fees_tax;

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
 
    public function transactionApproved(Request $request)
    { 
        $txn_id = $request->txnid;
        $uid = $request->uid;
        $getUser = DB::table('users')->select('account_type','uid')->where('uid', $uid)->where('status', 0)->where('trash', 0)->first();
        $transaction = Transaction::select('transaction_id', 'advertiser_code', 'amount', 'cpn_id', 'cpn_amt', 'status')->where('transaction_id', $txn_id)->first();
        $txnupdate = Transaction::where('transaction_id', $txn_id)->first();
        $txnupdate->remark = 'Payment added to wallet';
        $txnupdate->status = 1;
        if ($txnupdate->update()) {
            $transaction_log = new TransactionLog();
            $transaction_log->transaction_id = $transaction->transaction_id;
            $transaction_log->advertiser_code = $transaction->advertiser_code;
            $transaction_log->amount = $transaction->amount;
            $transaction_log->pay_type = 'credit';
            // 03-04-2024    
            // $transaction_log->serial_no = generate_serial();
            $transaction_log->serial_no = $getUser->account_type == 1 ? 0 : generate_serial();
            $transaction_log->remark = 'Amount added to wallet successfully';
            $transaction_log->save();
            $userwalletupdate = User::where('uid', $uid)->first();
            if ($userwalletupdate->referal_code != "" && $userwalletupdate->referalpmt_status == 0) {
                $url = "http://refprogramserv.7searchppc.in/api/add-transaction";
                $refData = [
                    'user_id' => $uid,
                    'referral_code' => $userwalletupdate->referal_code,
                    'amount' => $transaction->amount,
                    'transaction_type' => 'Payment',
                ];
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($refData),
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json"
                    ],
                ]);

                $response = curl_exec($curl);

                curl_close($curl);
            }
            $userwalletupdate->referalpmt_status = 1;
            $userwalletupdate->wallet = $userwalletupdate->wallet + $transaction->amount;
            $userwalletupdate->update();
            updateAdvWallet($transaction->advertiser_code, $transaction->amount);
            // if($transaction->cpn_amt !== null )
            if ($transaction->cpn_amt > 0) {
                $transaction_log = new TransactionLog();
                $transaction_log->transaction_id = $transaction->transaction_id;
                $transaction_log->advertiser_code = $transaction->advertiser_code;
                $transaction_log->amount = $transaction->cpn_amt;
                $transaction_log->pay_type = 'credit';
                // $transaction_log->serial_no = generate_serial();
                $transaction_log->cpn_typ = 1;
                $transaction_log->remark = 'Coupon Amount added to wallet successfully';
                $transaction_log->save();
                $usercouponamount = User::where('uid', $uid)->first();
                $usercouponamount->wallet = $usercouponamount->wallet + $transaction->cpn_amt;
                $usercouponamount->update();
                updateAdvWallet($transaction->advertiser_code, $transaction->cpn_amt);
            }

            /* User Section */

            $fullname = "$userwalletupdate->first_name $userwalletupdate->last_name";
            $emailname = $userwalletupdate->email;
            $phone = $userwalletupdate->phone;
            $addressline1 = $userwalletupdate->address_line1;
            $addressline2 = $userwalletupdate->address_line2;
            $city = $userwalletupdate->city;
            $state = $userwalletupdate->state;
            $country = $userwalletupdate->country;
            $useridas = $userwalletupdate->uid;

            /* Transaction Section */
            // 03-04-2024
            // $transactionid = $transaction_log->transaction_id;

            $transactionid = $transaction_log->serial_no > 0 ? $transaction_log->serial_no :  $transaction_log->transaction_id;
            $createdat = $transaction_log->created_at;
            $remark = $transaction_log->remark;
            /* Transaction Log Section */
            $amount        = $txnupdate->amount;
            $paybleamt     = number_format($txnupdate->amount + $txnupdate->fee + $txnupdate->gst,2);
            $paymentmode = $txnupdate->payment_mode;
          //$amount = $txnupdate->amount;
            //$paybleamt = $txnupdate->payble_amt;
            $fee = number_format($txnupdate->fee, 2);
            $gst = number_format($txnupdate->gst, 2);

            $subjects = "Funds Added Successfully - 7Search PPC";
            $report = Transaction::select('transactions.payble_amt', 'transactions.gst', 'transactions.fee', 'transactions.fees_tax', 'transaction_logs.remark', 'transactions.payment_mode', 'transactions.amount')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->where('transactions.transaction_id', $txn_id)
            ->first();
            $fee = $report->fee;
            $subtotal = $report->amount + $report->fee;
            
            $data['details'] = ['subject' => $subjects, 'full_name' => $fullname, 'emails' => $emailname, 'phone' => $phone, 'addressline1' => $addressline1, 'addressline2' => $addressline2, 'city' => $city, 'state' => $state, 'country' => $country, 'createdat' => $createdat, 'user_id' => $useridas, 'transaction_id' => $transactionid, 'payment_mode' => $paymentmode, 'amount' => $amount, 'payble_amt' => $paybleamt, 'fee' => $fee, 'gst' => $gst, 'remark' => $remark, 'subtotal' => $subtotal];
            $data["email"] = $emailname;
            $data["title"] = $subjects;
            $pdf = PDF::loadView('emailtemp.pdf.pdf_stripe', $data);
            $postpdf = time() . '_' . $transactionid;
            $fileName =  $postpdf . '.' . 'pdf';
            $path = public_path('pdf/invoice');
            $finalpath = $path . '/' . $fileName;
            $pdf->save($finalpath);
            $body =  View('emailtemp.transactionrecipt', $data);
            $isHTML = true;
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host       = env('MAIL_HOST', "");
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = true;
            $mail->Port       = env('MAIL_PORT', "");
            $mail->Username   = env('mail_username', "");
            $mail->Password   = env('MAIL_PASSWORD', "");
            $mail->setFrom(env('mail_from_address', ""), "7Search PPC");
            $mail->addAddress($emailname);
            $mail->SMTPSecure = 'ssl';
            $mail->isHTML($isHTML);
            $mail->Subject = $subjects;
            $mail->Body    = $body;
            $mail->addAttachment($finalpath);
            $mail->send();
            /* Closed Section */
            $noti_title = 'Payment successfully';
            $noti_desc = 'Your payment has been successfully completed. The amount is added to your wallet.';
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 1;
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save()) {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $userwalletupdate->uid;
                $noti->user_type = 1;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }
            $return['code'] = 200;
            $return['message'] = 'Transaction approved successfully';
        } else {
            $return['code'] = 101;
           $return['message'] = 'Something went wrong';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

}

