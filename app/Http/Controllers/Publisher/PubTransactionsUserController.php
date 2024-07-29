<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PubTransactionsUserController extends Controller
{
      
  	public function transacList(Request $request)
    {
        $uid  = $request->uid;
      	$trasaclist = PubPayout::select('id', 'transaction_id', 'amount','payout_method', 'payout_transaction_id', 'status', 'release_date', 'remark', 'created_at')
            		->where('publisher_id', $uid)->get();
      	$row = $trasaclist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $trasaclist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
