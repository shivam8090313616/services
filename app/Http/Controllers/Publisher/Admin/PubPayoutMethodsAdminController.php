<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubPayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PubPayoutMethodsAdminController extends Controller
{
  
    public function listMethods(Request $request)
    {	
      	$limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
		
      	$list = PubPayoutMethod::select('id','method_name', 'image', 'processing_fee', 'min_withdrawl', 'description', 'created_at','status', 'display_name');
      	$row        = $list->count();
        $data       = $list->offset( $start )->limit( $limit )->orderBy('id', 'DESC')->get();
      	if($row != null)
        {
        	$return[ 'code' ] = 200;
            $return[ 'data' ] = $data;
          	$return[ 'row' ]  = $row;
          	$return[ 'message' ] = 'Data Successfully found!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Data Not found!';
        }
        return json_encode( $return, JSON_NUMERIC_CHECK );
    }

    public function updatePayoutMethodStatus(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'status' 		=> 'required',
            ]
        );
      if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
      }
     $updateStatus  =  PubPayoutMethod::where('id',$request->id)->update(['status'=>$request->status]);
     if($updateStatus)
      {
      	$return['code'] = 200;
        $return['message'] = 'Update status successfully.';
      }
      else
      {
      	$return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function storeMethods(Request $request)
    {
            $validator = Validator::make(
            $request->all(),
            [
                'method_name' 		=> 'required',
                'image' 			=> 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'processing_fee' 	=> 'required',
              	'min_withdrawl' 	=> 'required',
              	'display_name' 		=> 'required',
            ]
        );
      
      if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
      }
      
      if($request->file('image')) 
      {
        $imagelogo = $request->file('image');
        $logos = time().'.'.$imagelogo->getClientOriginalExtension();
        $destinationPaths = public_path('payout_methos/');
        $imagelogo->move($destinationPaths, $logos);
      }
      
      $payoutMethods = new PubPayoutMethod;
      $payoutMethods->image = $logos;
      
      $payoutMethods->method_name 	 = $request->method_name;
      $payoutMethods->processing_fee = $request->processing_fee;
      $payoutMethods->min_withdrawl  = $request->min_withdrawl;
      $payoutMethods->description    = $request->description;
      $payoutMethods->display_name    = $request->display_name;
      
      if($payoutMethods->save())
      {
      	$return['code'] = 200;
        $return['message'] = 'Payout method name added successfully.';
      }
      else
      {
      	$return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
    	
    }
  
  	public function updateMethods(Request $request)
    {
      if($request->image){
        $validator = Validator::make(
          $request->all(),
          [
              'method_name' 		=> 'required',
              'image' 			=> 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
              'processing_fee' 	=> 'required',
              'min_withdrawl' 	=> 'required',
              'display_name' 		=> 'required',
          ]
      );
      }else{
        $validator = Validator::make(
          $request->all(),
          [
              'method_name' 		=> 'required',
              'processing_fee' 	=> 'required',
              'min_withdrawl' 	=> 'required',
              'display_name' 		=> 'required',
          ]
      );
      }
  if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
  }
      $payoutMethods = PubPayoutMethod::where('id', $request->id)->first();
      if($payoutMethods)
      {
        $payoutMethods->method_name 	 = $request->method_name;
        $payoutMethods->processing_fee = $request->processing_fee;
        $payoutMethods->min_withdrawl  = $request->min_withdrawl;
        $payoutMethods->display_name    = $request->display_name;
        if($request->file('image')) 
        {
          $imagelogo = $request->file('image');
          $logos = time().'.'.$imagelogo->getClientOriginalExtension();
          $destinationPaths = public_path('payout_methos/');
          $imagelogo->move($destinationPaths, $logos);
          $payoutMethods->image = $logos;
        }
        $payoutMethods->description    = $request->description;
        if($payoutMethods->update())
        {
          $return['code'] = 200;
          $return['message'] = 'Updated successfully.';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
      }
      else
      {
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
    	
    }
}
