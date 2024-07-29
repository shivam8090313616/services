<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Validator;

class PaymentAdminController extends Controller
{
    public function list(Request $request)
    {


        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        // $endDate = $request->endDate;
        $endDate = date('Y-m-d', strtotime($request->endDate));
        $src = $request->src;
        $pmethod = $request->pmethod;
        $tstaus = $request->tstaus;
        $country = $request->country;
        $authProvider = $request->auth_provider;
        $accounttype = $request->uType;

        $data = Transaction::select(
            'users.email',
            'users.country',
            'users.auth_provider',
            'users.account_type',
            'transactions.advertiser_code',
            'transactions.fee',
            'transactions.gst',
            'transactions.amount',
            'transactions.transaction_id',
            'transactions.payment_mode',
            'transactions.payment_id',
            'transactions.cpn_amt',
            'transactions.cpn_code',
            'transactions.screenshot',
            'transactions.status',
            'transactions.created_at',
            'transactions.payment_resource',
            'sources.title as source_title'
        )
            ->join('users', 'users.uid', '=', 'transactions.advertiser_code')
            ->join('sources', 'users.auth_provider', '=', 'sources.source_type')
            ->whereDate('transactions.created_at', '>=', $nfromdate)
            ->whereDate('transactions.created_at', '<=', $endDate);


        // $data = Transaction::select('*')->whereDate('created_at', '>=', $nfromdate)
        // ->whereDate('created_at', '<=', $endDate);
        // if ($src) {
        //     $data->whereRaw('concat(ss_users.uid,ss_users.email,ss_transactions.transaction_id,ss_transactions.payment_id) like ?', "%{$src}%");
        // }

        if ($pmethod) {
            $data->whereRaw('concat(ss_transactions.payment_mode) like ?', "%{$pmethod}%");
        }
        if ($tstaus >= 0) {
            $data->whereRaw('concat(ss_transactions.status) like ?', "%{$tstaus}%");
        }
        if (strlen($authProvider) > 0) {
            $data->where('users.auth_provider', $authProvider);
        }

        if ($accounttype == 2) {
            $data = $data;
        } else if (strlen($accounttype) > 0) {
            $data->where('users.account_type', $accounttype);
        }
        else if(strlen($src)>0){
            $data->whereRaw('concat(ss_users.uid,ss_users.email,ss_transactions.transaction_id,ss_transactions.payment_id) like ?', "%{$src}%");
        }
        else {
            $data->where('users.account_type', 0);
        }
        if (strlen($country) > 0) {
            $data->where('users.country', $country);
        }
        $row = $data->count();
        $getdata = $data->offset($start)->limit($limit)->orderBy('transactions.id', 'DESC')->get();
        $totalsuccamt = 0;
        foreach ($getdata as $key => $value) {
            if ($value->status == 1) {
                $totalsuccamt += $value->amount;
            }
        }
        if ($getdata) {
            $return['data']          = $getdata;
            $return['row']           = $row;
            $return['totalAmounts']  = $totalsuccamt;
            $return['message']       = 'Succssfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function view(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'transaction' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $transaction = $request->transaction;
        $transview = Transaction::where('transaction_id', $transaction)->first();
        $translog = TransactionLog::where('transaction_id', $transaction)->get();
        if ($transview) {
            $return['code'] = 200;
            $return['msg']  = 'Data Successfully !';
            $return['data']  = $transview;
            $return['data1'] = $translog;

        }
        else
        {
            $return['code']  = 101;
            $return['msg'] = 'Not Transaction !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
