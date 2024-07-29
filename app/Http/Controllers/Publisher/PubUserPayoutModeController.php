<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubUserPayoutMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PubUserPayoutModeController extends Controller
{
  public function storePayoutMode (Request $request)
    {
      $validator = Validator::make($request->all(), [
        'payout_id'       	  => 'required',
        'payout_name'   	  => 'required',
        'publisher_id' 		  => 'required',
        'pub_withdrawl_limit' => 'required',
        'pay_account_id' => 'required',
      ]);
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
      }
      
      $existingMode = PubUserPayoutMode::where('publisher_id', $request->publisher_id)
      ->where('status', 1)
      ->where('payout_id', $request->payout_id)
      ->where('payout_name', $request->payout_name)
      ->where('pub_withdrawl_limit', $request->pub_withdrawl_limit)
      ->where('pay_account_id', $request->pay_account_id)
      ->exists();
       if ($existingMode) {
          $return['code'] = 101;
          $return['message'] = 'Payout mode already updated!';
          return json_encode($return);
        }

      DB::table('pub_user_payout_modes')->where('publisher_id', $request->publisher_id)->where('status', 1)->update(['status' => 0]);
      $check = PubUserPayoutMode::where('publisher_id', $request->publisher_id)->where('payout_id', $request->payout_id)->first();
      if(!empty($check))
      {
      	$check->payout_id 			= $request->payout_id;
        $check->pay_account_id 		= $request->pay_account_id;
        $check->payout_name 		= $request->payout_name;
        $check->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $check->status = 1;
        if($check->update())
        {
          $return['code'] = 200;
          $return['message'] = 'Updated Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
      }
      else
      {
      	$payoutmode = new PubUserPayoutMode;
        $payoutmode->payout_id 			= $request->payout_id;
        $payoutmode->pay_account_id 	= $request->pay_account_id;
        $payoutmode->publisher_id 		= $request->publisher_id;
        $payoutmode->payout_name 		= $request->payout_name;
        $payoutmode->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $payoutmode->status = 1;
        if($payoutmode->save())
        {
          $return['code'] = 200;
          $return['message'] = 'Added Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
      
    	
    }
}
