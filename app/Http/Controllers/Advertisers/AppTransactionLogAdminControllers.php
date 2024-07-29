<?php

namespace App\Http\Controllers\Advertisers;

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

class AppTransactionLogAdminControllers extends Controller
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
            $return['msg'] = 'Transaction list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function transactionsReport(Request $request)
    {
        $type = $request->type;
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $report = Transaction::select('users.email', 'transactions.advertiser_code', 'transactions.fee', 'transactions.gst', 'transactions.transaction_id', 'transactions.payment_mode', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.amount', 'transaction_logs.remark', 'transaction_logs.created_at')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->where('transactions.status', 1);
        // ->join('categories', 'categories.id', '=', 'transactions.category');
        if (strlen($type) > 0) {
            $report->where('transactions.payment_mode', $type);
        }
        if ($startDate && $endDate) {
            $report->whereDate('transaction_logs.created_at', '>=', $nfromdate)
                ->whereDate('transaction_logs.created_at', '<=', $endDate);
        }
        if ($src) {
            $report->whereRaw('concat(ss_users.uid,ss_users.email,ss_transactions.transaction_id) like ?', "%{$src}%");
        }
        $report->orderBy('transaction_logs.id', 'desc');
        $row = $report->count();
        $data = $report->offset($start)->limit($limit)->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['msg'] = 'Transaction list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function transactionsReportExcelImport(Request $request)
    {
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;

        $report = Transaction::select('users.email', 'transactions.advertiser_code', 'transactions.fee', 'transactions.gst', 'transactions.transaction_id', 'transactions.payment_mode', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.amount', 'transaction_logs.remark', 'transaction_logs.created_at')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->whereDate('transaction_logs.created_at', '>=', $nfromdate)
            ->whereDate('transaction_logs.created_at', '<=', $endDate)
            ->where('transactions.status', 1);

        $data = $report->orderBy('transaction_logs.id', 'desc')->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['msg'] = 'Transaction list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
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
            $return['msg'] = 'User info retrieved successfully!';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function transactionsViewOld(Request $request)
    {

        $transactionid = $request->input('transaction_id');
        $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'transactions.payble_amt', 'transactions.fee', 'transactions.gst', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone', 'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 'transactions.advertiser_code', 'transactions.transaction_id', 'transactions.payment_mode','transactions.gst_no', 'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 'transaction_logs.created_at', 'transaction_logs.serial_no')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->where('transactions.transaction_id', $transactionid)
            ->first();

        if ($report->payment_mode == 'bitcoin' || $report->payment_mode == 'stripe' || $report->payment_mode == 'now_payments') {
            $report->fee = $report->fee;
        } else {
            $report->fee = $report->fee - $report->fees_tax;
        }
        $report->gst = $report->gst + $report->fees_tax;
        $report->subtotal = $report->amount + $report->fee;

        $report->transaction_id = ($report->serial_no) ? $report->serial_no : $report->transaction_id;
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
    
    public function transactionsView(Request $request)
    {
        $transactionid = $request->input('transaction_id');
        $report = Transaction::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"),'transactions.payble_amt',
                                       DB::raw("CASE WHEN ss_transaction_logs.serial_no > 0 THEN ss_transaction_logs.serial_no ELSE ss_transactions.transaction_id END as transaction_id"),
                                      'transactions.fee', 'transactions.gst', 'transactions.fees_tax', 'transaction_logs.remark', 'users.phone',
                                      'users.address_line1', 'users.address_line2', 'users.city', 'users.state', 'users.country', 'users.email', 
                                      'transactions.advertiser_code', 'transactions.payment_mode','transactions.gst_no',
                                      'transactions.amount', 'transaction_logs.id', 'transaction_logs.pay_type', 'transactions.payment_id', 
                                      'transaction_logs.created_at', 'transaction_logs.serial_no')
            ->join('transaction_logs', 'transaction_logs.transaction_id', '=', 'transactions.transaction_id')
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->where('transactions.transaction_id', $transactionid)
            ->first();
        if ($report->payment_mode == 'bitcoin' || $report->payment_mode == 'stripe' || $report->payment_mode == 'now_payments' || $report->payment_mode == 'coinpay' || $report->payment_mode == 'tazapay') {
            $report->fee = number_format($report->fee, 2);
        } else {
            $report->fee = number_format($report->fee - $report->fees_tax, 2);
        }
        $report->gst = number_format($report->gst, 2);
        $report->payble_amt = number_format($report->payble_amt, 2);
        $report->subtotal = number_format($report->amount + $report->fee, 2);
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
            $transaction_log->remark = 'Amount added to wallet successfully';
            $transaction_log->save();
            $userwalletupdate = User::where('uid', $uid)->first();
            $userwalletupdate->wallet = $userwalletupdate->wallet + $transaction->amount;
            $userwalletupdate->update();
            // if($transaction->cpn_amt !== null )
            if ($transaction->cpn_amt > 0) {
                $transaction_log = new TransactionLog();
                $transaction_log->transaction_id = $transaction->transaction_id;
                $transaction_log->advertiser_code = $transaction->advertiser_code;
                $transaction_log->amount = $transaction->cpn_amt;
                $transaction_log->pay_type = 'credit';
                $transaction_log->cpn_typ = 1;
                $transaction_log->remark = 'Coupon Amount added to wallet successfully';
                $transaction_log->save();
                $usercouponamount = User::where('uid', $uid)->first();
                $usercouponamount->wallet = $usercouponamount->wallet + $transaction->cpn_amt;
                $usercouponamount->update();
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
            $transactionid = $transaction_log->transaction_id;
            $createdat = $transaction_log->created_at;
            $remark = $transaction_log->remark;
            /* Transaction Log Section */
            $paymentmode = $txnupdate->payment_mode;
            $amount = $txnupdate->amount;
            $paybleamt = $txnupdate->payble_amt;
            $fee = $txnupdate->fee;
            $gst = $txnupdate->gst;
            /* Mail Section */
            $subjects = "Funds Added Successfully - 7Search PPC";
            $data['details'] = ['subject' => $subjects, 'full_name' => $fullname, 'emails' => $emailname, 'phone' => $phone, 'addressline1' => $addressline1, 'addressline2' => $addressline2, 'city' => $city, 'state' => $state, 'country' => $country, 'createdat' => $createdat, 'user_id' => $useridas, 'transaction_id' => $transactionid, 'payment_mode' => $paymentmode, 'amount' => $amount, 'payble_amt' => $paybleamt, 'fee' => $fee, 'gst' => $gst, 'remark' => $remark];
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
            $noti_title = 'Payment Successfully ';
            $noti_desc = '#' . $transactionid . '  ' . 'Your Payment is successfully completed. Amount is added in your wallet';
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
            $return['msg'] = 'Transaction approved successfully';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Something went wrong';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
