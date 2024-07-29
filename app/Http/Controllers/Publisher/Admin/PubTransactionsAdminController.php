<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubPayout;
use App\Models\AdImpression;
use App\Models\User;
use App\Models\UserCampClickLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PubTransactionsAdminController extends Controller
{
      
  	public function transacAdminList(Request $request)
    {
      	$sort_order = $request->sort_order;
      	$col = $request->col;
        $status  = $request->payout_sts;
      	$limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;
      	$src = $request->src;
      
      	$trasaclist = DB::table('pub_payouts')
          			->select('pub_payouts.id', 'pub_payouts.publisher_id', 'pub_payouts.transaction_id', 'pub_payouts.amount','pub_payouts.payout_method', 'pub_payouts.payout_transaction_id', 'pub_payouts.status', 'pub_payouts.release_date', 'pub_payouts.remark', 'pub_payouts.created_at', 'users.first_name as first_name', 'users.last_name')
            		->join('users', 'pub_payouts.publisher_id', 'users.uid')
          			->where('pub_payouts.status', $status)
          			->whereDate('pub_payouts.created_at', '>=', $nfromdate)
        			->whereDate('pub_payouts.created_at', '<=', $endDate);
        if($src)
        {
            $trasaclist->whereRaw('concat(ss_pub_payouts.transaction_id, ss_pub_payouts.publisher_id) like ?', "%{$src}%");
            
        }
        $row = $trasaclist->count();
      	
      	if($col)
        {
          if($col == 'first_name')
          {
            $getdata = $trasaclist->offset( $start )->limit( $limit )->orderBy('first_name', $sort_order)->get();
          }
          else
          {  
          	$getdata  = $trasaclist->offset( $start )->limit( $limit )->orderBy('pub_payouts.'.$col, $sort_order)->get();
          }
        }
        else
        {
          $getdata = $trasaclist->offset($start)->limit($limit)->orderBy('pub_payouts.id', 'DESC')->get();
        }
      	//$getdata = $trasaclist->offset($start)->limit($limit)->orderBy('pub_payouts.id', 'DESC')->get();
      	
      	if ($row != null) {
            $return['code']    			= 200;
            $return['data']    			= $getdata;
          	$return['row']     			= $row;
            $return['message'] 			= 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
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
        $transview = PubPayout::where('transaction_id',$transaction)->first();
        if($transview)
        {
            $return['code'] = 200;
            $return['msg']  = 'Data Successfully !'; 
            $return['data']  =$transview;
            
        }
        else
        {
            $return['code']  = 101;
            $return['msg'] = 'Not Transaction !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	public function transactionStatusUpdate (Request $request)
    {
        $validator = Validator::make(
              $request->all(),
              [
                  'txnid' 		=> 'required',
                  'status_type' => 'required',
                  'remark' 		=> 'required',
                  
              ]
          );

        if ($validator->fails()) {
              $return['code'] = 100;
              $return['error'] = $validator->errors();
              $return['message'] = 'Validation error!';
              return json_encode($return);
        }
        $txn_id = $request->txnid;
        $txnupdate = PubPayout::where('transaction_id', $txn_id)->first();
        $txnupdate->remark = $request->remark;
        $txnupdate->status = $request->status_type;
        if ($request->status_type == 1) {
          $user = User::where('uid', $uid)->first();
          if ($user->referal_code != "" && $user->referalpmt_status == 0) {
            $url = "http://refprogramserv.7searchppc.in/api/add-transaction";
            $refData = [
              'user_id' => $uid,
              'referral_code' => $user->referal_code,
              'amount' => $txnupdate->amount,
              'transaction_type' => 'Payout',
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
          $user->referalpmt_status = 1;   
          $user->update();
        }
      	$txnupdate->payout_transaction_id = $request->payout_transac_id ? $request->payout_transac_id : 'NULL';
      	if($txnupdate->update())
        {
            $return ['code'] = 200;
            $return ['message'] = 'Transaction approved successfully';
        }
        else
        {
            $return ['code'] = 101;
            $return ['message'] = 'Something went wrong';    
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
