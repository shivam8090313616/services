<?php

namespace App\Http\Controllers\AppPublisher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Rules\CustomValidationRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Activitylog;
use Illuminate\Support\Str;
use App\Models\User;


class AppPublisherUserController extends Controller
{
  
    public function profileInfo($uid)
    {
      $user = User::select('first_name', 'last_name', 'email', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pub_wallet', 'profile_lock','phonecode','messenger_type','messenger_name')
        ->where('uid', $uid)->first();
      if ($user) {
        $return['code']    = 200;
        $return['data']    = $user;
        $wltPubAmt = getPubWalletAmount($uid);
        $wltamt    = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
        $return['wallet']   	= number_format($wltamt, 2);
        $return['message'] = 'User profile info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }

      return json_encode($return);
    }
    public function update(Request $request)
    {
       $uid = $request->uid;
       $getmessengertype =  DB::table('messengers')->where('messenger_name',$request->messenger_type)->where('status',1)->first();
       if($getmessengertype == null){
                $return['code']     = 101;
                $return['message']  = 'Something went wrong in messenger type';
               return json_encode($return, JSON_NUMERIC_CHECK);
       }
        $userKycAcpt = User::select('id', 'uid', 'phone')
            ->where('uid', $uid)
            ->where(function ($query) {
                $query->where('photo_verified', 2)->orWhere('photo_id_verified', 2);
            })
            ->first();

        if (!empty($userKycAcpt)) {
            return $this->updateProfileWithValidation($request, $uid, $getmessengertype->messenger_name);
        } else {
            return $this->updateProfileWithoutValidation($request, $uid);
        }
    }

    private function updateProfileWithValidation(Request $request, $uid  ,$mname)
    {
        $user = User::select('id', 'uid', 'phone', 'first_name', 'last_name', 'email')->where('uid', $uid)->first();
        if($mname === 'None'){
          $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
            'messenger_type' => 'required',
        ], [
            'phone_number.required' => 'The phone Number. must contain only numeric characters.',
            'phone_number.between' => 'The phone Number. must contain minimum 4 and maximum 15 digits.',
        ]);
        }else{
          $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
            'messenger_type' => 'required',
            'messenger_name' => 'required',
        ], [
            'phone_number.required' => 'The phone Number. must contain only numeric characters.',
            'phone_number.between' => 'The phone Nmuber. must contain minimum 4 and maximum 15 digits.',
        ]);
        }
        return $this->handleValidationResponse($validator, $request, $user);
    }
    private function updateProfileWithoutValidation(Request $request, $uid)
    {
        $user = User::select('id', 'uid', 'phone')->where('uid', $uid)->first();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'phone_number' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
            'address_line1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => ['required', new CustomValidationRules($request)],
            'messenger_type' => 'required',
            'messenger_name' => 'required',
            'phonecode' => ['required', new CustomValidationRules($request)],
        ]);
        return $this->handleValidationResponse($validator, $request, $user);
    }

    private function handleValidationResponse($validator, $request, $user)
    {
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['message'] = 'Validation Error';
            $return['error'] = $validator->errors();
            return json_encode($return);
        }
        return json_encode($this->getSuccessResponse($user,$request), JSON_NUMERIC_CHECK);
    }
    private function getSuccessResponse($user ,$request)
    {
     if($request->country){
        $count_name = DB::table('countries')->select('id', 'name', 'phonecode', 'status', 'trash')->where('name',$request->country)->where('status', 1)->where('trash', 1)->first();
        if(!$count_name){
            $return['code']      = 101;
            $return['message']   = 'Country Not Exist!';
            return json_encode($return);
        }
    }
        $uid = $request->uid;
        $res = $request->all();
        $data['first_name']       =  $res['first_name'];
        $data['last_name']        =  $res['last_name'];
        $data['country']          =  $res['country'];
        $data['phone']            =  $res['phone_number'];
        $data['messenger_type']   =  $res['messenger_type'];
        $data['address_line1']    =  $res['address_line1'];
        $data['address_line2']    =  $res['address_line2'];
        $data['state']            =  $res['state'];
        $data['city']             =  $res['city'];
        $data['phonecode']        =  $res['phonecode'];
        $data['messenger_name']   =  $res['messenger_name'];
        $type = 2;
        userUpdateProfile($data,$uid,$type);
        
      $userKycAcpt = User::select('id', 'uid', 'phone')
        ->where('uid', $user->uid)
        ->where(function ($query) {
            $query->where('photo_verified', 2)->orWhere('photo_id_verified', 2);
        }) ->first();
        if($userKycAcpt){
          $user                   = User::where('uid', $user->uid)->first();
          $user->phone            = $request->phone_number;
          $user->messenger_type   = $request->messenger_type;
          $user->messenger_name   = $request->messenger_name;
        }else{
          $user                   = User::where('uid', $user->uid)->first();
          $user->first_name       = $request->first_name;
          $user->last_name        = $request->last_name;
          $user->phone            = $request->phone_number;
          $user->phonecode        = $count_name->phonecode;
          $user->address_line1    = $request->address_line1;
          $user->address_line2    = $request->address_line2;
          $user->city             = $request->city;
          $user->state            = $request->state;
          $user->messenger_name   = $request->messenger_name;
          $user->messenger_type   = $request->messenger_type;
          $user->country          = $count_name->name;
        }
        if ($user->update()) {
            $email = $user->email;
            $fullname = $user->first_name . ' ' . $user->last_name;
            $useridas = $user->uid;
            $data['details'] = array('subject' => 'Profile Updated successfully - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
            $subject = 'Profile Updated successfully - 7Search PPC';
            $body =  View('emailtemp.pubuserprofileupdated', $data);
            sendmailUser($subject,$body,$email); 
        }
        return [
            'code' => 200,
            'uid' => $user->uid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'message' => 'Updated Successfully',
        ];
    }
    public function pubKycUpload(Request $request)
    {
      	if(!$request->user_photo && !$request->user_photo_id) {
          	$validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'user_photo' => 'required',
                'user_photo_id' => 'required',
            ]
        ); 
       }
         if($request->user_photo && $request->user_photo_id) {
           $validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'user_photo' => 'required',
                'user_photo_id' => 'required',
            ]
        ); 
       }
      if($request->user_photo && !$request->user_photo_id) {
       
           	$validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'user_photo' => 'required',
            ]
        ); 
       }
      
      
      if(!$request->user_photo && $request->user_photo_id) {
          	$validator = Validator::make(
              $request->all(),
              [
                  'uid' => 'required',
                  'user_photo_id' => 'required',
                  // 'user_photo_id' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
              ]
          ); 
       }
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
      	$user        = User::where('uid', $request->uid)->first();
      	if(empty($user))
        {
          $return['code']    = 101;
          $return['message'] = 'User not Found!';
          return json_encode($return);
        }
      	 if($request->user_photo) {
          $base_str = explode(';base64,', $request->user_photo);
          $ext = str_replace('data:image/', '', $base_str[0]);
          $image = base64_decode($base_str[1]);
          $imageName = md5(Str::random(10)) . '.' . $ext; 
          file_put_contents('kycdocument/'.$imageName, $image); 
          $user->user_photo  = $imageName;
          $user->photo_verified  		= 1;
         }
      
      	if($request->user_photo_id) {

          $base_str = explode(';base64,', $request->user_photo_id);
          $ext = str_replace('data:image/', '', $base_str[0]);
          $image = base64_decode($base_str[1]);
          $imageIdName = md5(Str::random(10)) . '.' . $ext; 
          file_put_contents('kycdocument/'.$imageIdName, $image); 
          $user->user_photo_id  = $imageIdName;
          $user->photo_id_verified	= 1;
      	}
          if ($user->update()) {
            
            /* Adunit Activity Add & Generate Notification */
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->uid;
                $activitylog->type    = 'Kyc Documnet';
                $activitylog->description    = 'Kyc Document uploaded by user successfully';
                $activitylog->status    = '1';
                $activitylog->save();
              
            	/* Admin Section  */
              
              	$email = $user->email;
                $fullname = $user->first_name . ' ' . $user->last_name;
                $useridas = $user->uid;
                                
              
              	$data['details'] = array('subject' => 'Kyc Document uploaded by user successfully - Publisher 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
                             
                $adminmail1 = 'advertisersupport@7searchppc.com';
                // $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.userkycuploadedadmin', $data);
                $subjectadmin = 'Kyc Document uploaded by user successfully - Publisher 7Search PPC';
                $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
                if($sendmailadmin == '1') 
                {
                    $return['code'] = 200;
                    $return['message']  = 'Mail Send & Document Uploaded successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['message']  = 'Mail Not Send But Document Uploaded successfully !';
                }
          } else {
              $return['code']    = 101;
              $return['message'] = 'Something went wrong!';
          }
          
       
        return json_encode($return);
    }
  
  	public function pubKycInfo(Request $request)
    {
      $user = User::select('user_photo','user_photo_remark', 'user_photo_id_remark', 'user_photo_id', 'photo_verified', 'photo_id_verified')
        ->where('uid', $request->uid)->first();
      $user->user_photo = config('app.url').'kycdocument'. '/' .$user->user_photo;
      $user->user_photo_id = config('app.url').'kycdocument'. '/' .$user->user_photo_id;
      //$doc_log = PubDocumentLog::select('status','remark')->where('uid', $request->uid)->
      if ($user) {
        $return['code']    = 200;
        $return['data']    = $user;
        $return['message'] = 'User Kyc info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }

      return json_encode($return, JSON_NUMERIC_CHECK);
    }
  	
  	public function payoutUpload(Request $request)
    {
      	$validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
              	'payout_method' => 'required',
              	'withdrawl_limit' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
      	$user        = User::where('uid', $request->uid)->first();
      	if(empty($user))
        {
          $return['code']    = 101;
          $return['message'] = 'User not Found!';
          return json_encode($return);
        }
          $user->payout_method    = $request->payout_method;
          $user->withdrawl_limit  = $request->withdrawl_limit;
          if ($user->update()) {
            	/* Adunit Activity Add & Generate Notification */
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->uid;
                $activitylog->type    = 'Kyc Documnet';
                $activitylog->description    = 'Payout detail and withdrawl limit uploaded by user successfully';
                $activitylog->status    = '1';
                $activitylog->save();
              $return['code']    = 200;
              $return['message'] = 'Document Uploaded successfully!';
          } else {
              $return['code']    = 101;
              $return['message'] = 'Something went wrong!';
          }
          
       
        return json_encode($return);
    }
  
  	public function payoutInfo(Request $request)
    {
      $user = User::select('payout_method', 'withdrawl_limit')
        ->where('uid', $request->uid)->first();
      if ($user) {
        $return['code']    = 200;
        $return['data']    = $user;
        $return['message'] = 'User Payout info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }

      return json_encode($return);
    }
  
  	public function pay_info_Old(Request $request) {
    	 $user = User::select('payout_method', 'withdrawl_limit', 'photo_verified', 'photo_id_verified', 'pub_wallet')
        ->where('uid', $request->uid)->first();
       $pay = DB::table('pub_payouts')->
         		select(
         			DB::raw('SUM(amount) as amt'), 
                    DB::raw("(SELECT SUM(amount) FROM ss_pub_payouts WHERE ss_pub_payouts.status = 1 AND ss_pub_payouts.publisher_id = '". $request->uid ."') as withdrawl_amt")
       			)->
      			where('publisher_id', $request->uid)->first();
      
      $pay_list = DB::table('pub_payouts')->
         		select('transaction_id', 'amount', 'release_date', 'status', 'remark', DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"))->
      			where('publisher_id', $request->uid)->where('status', 1)->get();
       $upc_list = DB::table('pub_payouts')->
         		select('transaction_id', 'amount', 'status', 'remark',DB::raw("DATE_FORMAT(release_date, '%d-%m-%Y') as release_date"), DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"))->
      			where('publisher_id', $request->uid)->where('status', '!=', 1)->get();
      
      if ($user) {
        $payMode = DB::table('pub_user_payout_modes')
           ->select('pub_withdrawl_limit','payout_name', 'payout_id', 'pub_payout_methods.image', 'pub_payout_methods.display_name','pub_user_payout_modes.status')
             ->join('pub_payout_methods', 'pub_user_payout_modes.payout_id', 'pub_payout_methods.id')
             ->where('pub_user_payout_modes.publisher_id', $request->uid)->where('pub_user_payout_modes.status', 1)->first();
            if($user->photo_verified == 0 && $user->photo_id_verified == 1){
                $data['kyc_status'] =  0;
            }elseif($user->photo_verified == 1 && $user->photo_id_verified == 0){
              $data['kyc_status'] =  0;
            }elseif($user->photo_verified == 0 && $user->photo_id_verified == 3){
              $data['kyc_status'] =  3;
            }elseif($user->photo_verified == 3 && $user->photo_id_verified == 0){
              $data['kyc_status'] =  3;
            }elseif($user->photo_verified == 1 && $user->photo_id_verified == 1){
              $data['kyc_status'] =  1;
            }elseif($user->photo_verified == 1 && $user->photo_id_verified == 3){
              $data['kyc_status'] =  3;
            }elseif($user->photo_verified == 3 && $user->photo_id_verified == 1){
              $data['kyc_status'] =  3;
            }else{
              $data['kyc_status'] = $user->photo_verified;
            }
           
        $data['status'] = ($payMode) ? $payMode->status : 0;
        $total_earn = number_format($pay->amt+$user->pub_wallet, 2);
        $data['total_earning'] = $total_earn ? $total_earn : 0;
        $total_wit = number_format($pay->withdrawl_amt, 2);
        $data['total_withdrawl'] = $total_wit ? $total_wit : 0;
        $avl_amt = number_format($user->pub_wallet, 2);
        $data['avalable_amt'] = $avl_amt ? $avl_amt : 0;
        $data['upcoming_pay_list'] = $upc_list;
        $data['pay_list'] = $pay_list;
        $data['kyc_status'] = ($user->photo_verified == 2 && $user->photo_id_verified == 2) ? 1 : 0;
        $data['pay_mode_status'] = (!empty($payMode)) ? 1 : 0;
        $data['payout_mode'] = ($payMode) ? $payMode->payout_name : '';
        $data['display_name'] = ($payMode) ? $payMode->display_name : '';
        $data['withdrawl_limit'] = ($payMode) ? $payMode->pub_withdrawl_limit : 0;
        $data['image'] = ($payMode) ? config('app.url').'payout_methos'. '/' .$payMode->image : '';
        
        
        
        $total_earn = number_format($pay->amt+$user->pub_wallet, 2);
        $data['total_earning'] = $total_earn ? $total_earn : 0;
        
        $total_wit = number_format($pay->withdrawl_amt, 2);
        $data['total_withdrawl'] = $total_wit ? $total_wit : 0;
        
        $wltPubAmt = getPubWalletAmount($request->uid);
        $pubwltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
        
        $avl_amt = number_format($pubwltamt, 2);
        $data['avalable_amt'] = $avl_amt ? $avl_amt : 0;
        
        $data['upcoming_pay_list'] = $upc_list;
        $data['pay_list'] = $pay_list;
        
          
        $return['code']    = 200;
        $return['data']    = $data;
        $return['message'] = 'User Payout info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }

      return json_encode($return);
    }
  
    public function pay_info(Request $request)
    {
      $user = User::select('payout_method', 'withdrawl_limit', 'photo_verified', 'photo_id_verified', 'pub_wallet')
        ->where('uid', $request->uid)->first();
      $pay = DB::table('pub_payouts')->select(
          DB::raw('SUM(amount) as amt'),
          DB::raw("(SELECT SUM(amount) FROM ss_pub_payouts WHERE ss_pub_payouts.status = 1 AND ss_pub_payouts.publisher_id = '" . $request->uid . "') as withdrawl_amt")
        )->where('publisher_id', $request->uid)->first();
      $pay_list = DB::table('pub_payouts')->select('transaction_id', 'amount', 'release_date', 'status', 'remark', DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"))->where('publisher_id', $request->uid)->where('status', 1)->get();
      $upc_list = DB::table('pub_payouts')->select('transaction_id', 'amount', 'status', 'remark', DB::raw("DATE_FORMAT(release_date, '%d-%m-%Y') as release_date"), DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"))->where('publisher_id', $request->uid)->where('status', '!=', 1)->get();
  
      if ($user) {
        $payMode = DB::table('pub_user_payout_modes')
          ->select('pub_withdrawl_limit', 'payout_name', 'payout_id', 'pub_payout_methods.image', 'pub_payout_methods.display_name', 'pub_user_payout_modes.status')
          ->join('pub_payout_methods', 'pub_user_payout_modes.payout_id', 'pub_payout_methods.id')
          ->where('pub_user_payout_modes.publisher_id', $request->uid)->where('pub_user_payout_modes.status', 1)->first();
        if ($user->photo_verified == 0 && $user->photo_id_verified == 1) {
          $data['kyc_status'] =  0;
        } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 0) {
          $data['kyc_status'] =  0;
        } elseif ($user->photo_verified == 0 && $user->photo_id_verified == 3) {
          $data['kyc_status'] =  3;
        } elseif ($user->photo_verified == 3 && $user->photo_id_verified == 0) {
          $data['kyc_status'] =  3;
        } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 1) {
          $data['kyc_status'] =  1;
        } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 3) {
          $data['kyc_status'] =  3;
        } elseif ($user->photo_verified == 3 && $user->photo_id_verified == 1) {
          $data['kyc_status'] =  3;
        } else {
          $data['kyc_status'] = $user->photo_verified;
        }
        $data['pay_mode_status'] = (!empty($payMode)) ? 1 : 0;
        $data['payout_mode'] = ($payMode) ? $payMode->payout_name : '';
        $data['display_name'] = ($payMode) ? $payMode->display_name : '';
        $data['withdrawl_limit'] = ($payMode) ? $payMode->pub_withdrawl_limit : 0;
        $data['status'] = ($payMode) ? $payMode->status : 0;
        $data['image'] = ($payMode) ? config('app.url') . 'payout_methos' . '/' . $payMode->image : '';
  
  
  
        $total_earn = number_format($pay->amt + $user->pub_wallet, 2);
        $data['total_earning'] = $total_earn ? $total_earn : 0;
  
        $total_wit = number_format($pay->withdrawl_amt, 2);
        $data['total_withdrawl'] = $total_wit ? $total_wit : 0;
  
        $wltPubAmt = getPubWalletAmount($request->uid);
        $pubwltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
        
        $avl_amt = number_format($pubwltamt, 2);
        $data['avalable_amt'] = $avl_amt ? $avl_amt : 0;
  
        $data['upcoming_pay_list'] = $upc_list;
        $data['pay_list'] = $pay_list;
  
  
        $return['code']    = 200;
        $return['data']    = $data;
        $return['message'] = 'User Payout info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }
  
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	
  public function payout_list(Request $request)
    {
        $uid = $request->input('uid');
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $users = User::where('uid', $uid)->first();


        if (!empty($users)) {
            $trlog = DB::table('pub_payouts')
                ->select('transaction_id', 'amount', 'payout_method', 'release_date', 'status', 'remark','release_date')
                ->where('publisher_id', $uid)
                ->where('status', 1)
                ->orderBy('id', 'desc');
            $row = $trlog->count();
            $datas = $trlog->offset($start)->limit($limit)->get();

            $return['code']     = 200;
            $return['message']  = 'successfully';
            $return['data']     = $datas;
            $wltPubAmt = getPubWalletAmount($uid);
            $return['wallet']        = ($wltPubAmt) > 0 ? $wltPubAmt : $users->pub_wallet;
            $return['row']      = $row;
        } else {

            $return['code'] = 100;
            $return['message'] = 'Not Found User';
        }
        return json_encode($return);
    }
  
  
  	
  	public function balance_info(Request $request) {
      
    	$user = User::select('pub_wallet')->where('uid', $request->uid)->first();
           
       $pay = DB::table('pub_payouts')->
         		select(
         			DB::raw('SUM(amount) as amt'), 
                    DB::raw("(SELECT SUM(amount) FROM ss_pub_payouts WHERE ss_pub_payouts.status = 1 AND ss_pub_payouts.publisher_id = '". $request->uid ."') as withdrawl_amt")
       			)->where('publisher_id', $request->uid)->first();

      if ($user) {
        
        $total_earn = number_format($pay->amt+$user->pub_wallet, 2);
        $return['total_earning'] = $total_earn ? $total_earn : 0;
        
        $total_wit = number_format($pay->withdrawl_amt, 2);
        $return['total_withdrawl'] = $total_wit ? $total_wit : 0;
        
        $wltPubAmt = getPubWalletAmount($request->uid);
        $wltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
        
        $avl_amt = number_format($wltamt, 2);
        $return['avalable_amt'] = $avl_amt ? $avl_amt : 0;
        
          
        $return['code']    = 200;
     //   $return['data']    = $data;
        $return['message'] = 'User Payout info retrieved successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Data not found';
      }

      return json_encode($return);
    }
  
  	public function pubTokenUpdate (Request $request)
    {
      $validator = Validator::make($request->all(),
      [
       'uid' => 'required',
       'noti_token' => 'required',
      ]);
      if($validator->fails())
      {
        $return ['code']    = 100;
        $return ['error']   = $validator->errors();
        $return ['message'] = 'Validation Error!';
        return json_encode($return);
      }
      $userlog = User::where('uid', $request->uid)->first();
      if(!empty($userlog))
      {
        $userlog->pub_noti_token = $request->noti_token;
        if($userlog->save())
        {
           $return ['code']    = 200;
           $return ['message'] = 'Noti token updated successfully';
        }
        else
        {
          $return ['code']    = 101;
          $return ['message'] = 'Something went wrong!';
        }
      }
      else
      {
        $return ['code']    = 101;
        $return ['message'] = 'Something went wrong!';
      }
      	
      	return json_encode($return);
    }
    
    
    public function payoutselectedmethod(Request $request){
      $validator = Validator::make($request->all(), [
        'uid'             => 'required',
        'payout_id'       => 'required',
      ]);
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
      }
      $payMode = DB::table('pub_user_payout_modes')->select('pub_user_payout_modes.*')->where('publisher_id', $request->uid)->where('payout_id', $request->payout_id)->first();
      if($payMode)
      {
        $payMode->pay_account_id = (string) $payMode->pay_account_id;
         $return ['code']    = 200;
         $return['data']     = $payMode;
         $return ['message'] = 'Noti token updated successfully';
      }
      else
      {
        $return ['code']    = 101;
        $return ['message'] = 'Something went wrong!';
      }
      return json_encode($return);
    }
  
}
