<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Country;
use App\Models\PopupMessage;
use Illuminate\Validation\Rule;
use App\Rules\CustomValidationRules;
use Illuminate\Http\Request;
use App\Models\Activitylog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\UserNotification;
use App\Models\Notification;
use Carbon\Carbon;
use App\Models\Publisher\PubDocumentLog;

class PubUserController extends Controller
{
  /**
   * @OA\Get(
   *     path="/api/user/pub/info/{uid}",
   *     summary="Get user profile information",
   *     tags={"Profile"},
   *     @OA\Parameter(
   *         name="uid",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="string")
   *     ),
   *     @OA\Parameter(
   *         name="x-api-key",
   *         in="header",
   *         required=true,
   *         description="x-api-key (Publisher)",
   *         @OA\Schema(
   *             type="string"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
   *             @OA\Property(property="data", type="object", description="User profile information"),
   *             @OA\Property(property="login_as", type="string", description="User type"),
   *             @OA\Property(property="wallet", type="string", description="Formatted wallet amount"),
   *             @OA\Property(property="message", type="string", description="Success message")
   *         )
   *     ),
   *     @OA\Response(
   *         response=101,
   *         description="Error response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=101, description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     )
   * )
   */
  public function profileInfo($uid)
  {
    $user = User::select('phonecode', 'first_name', 'last_name', 'email', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pub_wallet', 'profile_lock', 'messenger_name', 'messenger_type', 'del_request', 'user_type','pub_wallet as wallet')
      ->where('uid', $uid)->first();
    $login_as = ($user->user_type == 3) ? 'publisher' : '';
    if ($user) {
      $return['code']    = 200;
      $return['data']    = $user;
      $return['login_as'] = $login_as;
      $wltPubAmt = getPubWalletAmount($uid);
    //   $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($user->pub_wallet, 2);
      $user->wallet   = ($wltPubAmt) > 0 ? number_format($wltPubAmt,2) : number_format($user->wallet, 2);
      $return['message'] = 'User profile info retrieved successfully';
    } else {
      $return['code']    = 101;
      $return['message'] = 'Data not found';
    }
    return json_encode($return, JSON_NUMERIC_CHECK);
  }


  public function update(Request $request, $uid)
  {
    $getmessengertype =  DB::table('messengers')->where('messenger_name', $request->messenger_type)->where('status', 1)->first();
    if ($getmessengertype == null) {
      $return['code']     = 101;
      $return['message']  = 'Something went wrong in messenger name';
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

  private function updateProfileWithValidation(Request $request, $uid, $mname)
  {
    $user = User::select('id', 'uid', 'phone', 'first_name', 'last_name', 'email')->where('uid', $uid)->first();
    if ($mname === 'None') {
      $validator = Validator::make($request->all(), [
        'phone' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
        'messenger_type' => 'required',
      ], [
        'phone.required' => 'The phone no. must contain only numeric characters.',
        'phone.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
      ]);
    } else {
      $validator = Validator::make($request->all(), [
        'phone' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
        'messenger_type' => 'required',
        'messenger_name' => 'required',
      ], [
        'phone.required' => 'The phone no. must contain only numeric characters.',
        'phone.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
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
      'phone' => ['required', 'min:4', 'max:15', Rule::unique('users', 'phone')->ignore($user->id, 'id')],
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
    return json_encode($this->getSuccessResponse($user, $request), JSON_NUMERIC_CHECK);
  }
  private function getSuccessResponse($user, $request)
  {
    if($request->country){
      $count_name = Country::select('id', 'name', 'phonecode', 'status', 'trash')->where('name',$request->country)->where('status', 1)->where('trash', 1)->first();
      if(!$count_name){
          $return['code']      = 101;
          $return['message']   = 'Country Not Exist!';
          return json_encode($return);
      }
  }
    $res = $request->all();
    $type = 2;
    userUpdateProfile($res, $user->uid, $type);
    
    $userKycAcpt = User::select('id', 'uid', 'phone')
      ->where('uid', $user->uid)
      ->where(function ($query) {
        $query->where('photo_verified', 2)->orWhere('photo_id_verified', 2);
      })->first();
    if ($userKycAcpt) {
      $user                   = User::where('uid', $user->uid)->first();
      $user->phone            = $request->phone;
      $user->messenger_type   = $request->messenger_type;
      $user->messenger_name   = $request->messenger_name;
      $user->address_line2    = $request->address_line2;
    } else {
      $user                   = User::where('uid', $user->uid)->first();
      $user->first_name       = $request->first_name;
      $user->last_name        = $request->last_name;
      $user->phone            = $request->phone;
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
      sendmailUser($subject, $body, $email);
    }
    return [
      'code' => 200,
      'uid' => $user->uid,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'email' => $user->email,
      'message' => 'Updated Successfully',
    ];
  }


  /**
   * @OA\Post(
   *     path="/api/pub/user/change_password",
   *     summary="Change Publisher User Password",
   *     tags={"User"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="multipart/form-data",
   *             @OA\Schema(
   *                 required={"user_id", "current_password", "new_password", "confirm_password"},
   *                 @OA\Property(property="user_id", type="string", description="User ID"),
   *                 @OA\Property(property="current_password", type="string", description="Current password"),
   *                 @OA\Property(property="new_password", type="string", description="New password (at least 8 characters long, containing at least one lowercase and one uppercase letter)"),
   *                 @OA\Property(property="confirm_password", type="string", description="Confirm password (same as new password)")
   *             )
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="x-api-key",
   *         in="header",
   *         required=true,
   *         description="x-api-key [Publisher]",
   *         @OA\Schema(
   *             type="string"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", description="Status code"),
   *             @OA\Property(property="message", type="string", description="Message indicating success")
   *         )
   *     ),
   *     @OA\Response(
   *         response=100,
   *         description="Validation Error",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", description="Status code"),
   *             @OA\Property(property="error", type="object", description="Validation errors"),
   *             @OA\Property(property="message", type="string", description="Message indicating validation error")
   *         )
   *     ),
   *     @OA\Response(
   *         response=101,
   *         description="User id is invalid or not registered",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     ),
   *     @OA\Response(
   *         response=102,
   *         description="New Password & Confirm Password not matching",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     ),
   *     @OA\Response(
   *         response=103,
   *         description="Current Password is Invalid",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     )
   * )
   */
  public function change_password(Request $request)
  {

        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
                'current_password' => 'required',
                'new_password' => 'required',
                'confirm_password' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }
        $userid = $request->input('user_id');
        $users = User::where('uid', $userid)->first();
        if (empty($users)) {
            $return['code'] = 101;
            $return['msg'] = 'User id is invalid or not registered!';
            return response()->json($return);
        }
        $password = $request->input('current_password');
        $npassword = $request->input('new_password');
        $compassword = $request->input('confirm_password');
        if ($npassword == $compassword) {
            if (Hash::check($password, $users->password)) {
                $newpass = Hash::make($npassword);
                $users->password = $newpass;
                if ($users->save()) {
                    $return['code']    = 200;
                    $return['message'] = 'Password Chanage Successfully';
                } else {
                    $return['code']    = 103;
                    $return['message'] = 'Not Match Password';
                }
            } else {
                $return['code']    = 103;
                $return['message'] = 'Current Password Is Invalid';
            }
        } else {
            $return['code']    = 102;
            $return['message'] = 'Not Match New Password & Confirm Password';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	
    /**
    * @OA\Post(
    *     path="/api/user/pub/kyc",
    *     summary="Upload KYC Documents For Publisher",
    *     tags={"Kyc"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="uid",
    *                     type="string",
    *                     description="User ID"
    *                 ),
    *                 @OA\Property(
    *                     property="user_photo",
    *                     type="string",
    *                     description="User photo",
    *                     format="binary"
    *                 ),
    *                 @OA\Property(
    *                     property="user_photo_id",
    *                     type="string",
    *                     description="User photo ID",
    *                     format="binary"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Publisher]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
    *             @OA\Property(property="message", type="string", description="Success message")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Error response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101, description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
     public function pubKycUpload(Request $request)
    {
      $selfie = $request->user_photo;
      $idProof = $request->user_photo_id;
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
                'user_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'user_photo_id' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]
        ); 
       }
      
      if($request->user_photo && !$request->user_photo_id) {
       
           	$validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'user_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]
        ); 
       }
      
      
      if(!$request->user_photo && $request->user_photo_id) {
          	$validator = Validator::make(
              $request->all(),
              [
                  'uid' => 'required',
                  'user_photo_id' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
          $imageName = md5(Str::random(10)) . '.' .$request->user_photo->extension();  
          $request->user_photo->move(public_path('kycdocument'), $imageName);
          $user->user_photo  = $imageName;
          $user->photo_verified  		= 1;
          PubDocumentLog::create([
            'uid' => $request->uid,
            'doc_type' => 'Selfie',
            'status' => '1',
            'doc_name' => $imageName
          ]);
         }
      	if($request->user_photo_id) {
          $imageIdName = md5(Str::random(10)) . '.' .$request->user_photo_id->extension();  
          $request->user_photo_id->move(public_path('kycdocument'), $imageIdName);
          $user->user_photo_id  = $imageIdName;
          $user->photo_id_verified	= 1;
          PubDocumentLog::create([
            'uid' => $request->uid,
            'doc_type' => 'Id Proof',
            'status' => '1',
            'doc_name' => $imageIdName
          ]);
      	}
          if ($user->update()) {
            /* Adunit Activity Add & Generate Notification */
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->uid;
                $activitylog->type    = 'Kyc Documnet';
                $activitylog->description    = 'Kyc Document uploaded by user successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                 //user notification added code //
                $notification = new Notification();

                $notification->notif_id = gennotificationuniq();

                if ($selfie && !$idProof) {
                  $notification->title = 'KYC Uploaded For Photo Document - 7Search PPC ';
                  $notification->noti_desc = "Kyc Document uploaded successfully";
                  $notification->noti_for = 2;
                  $notification->all_users = 0;
                } else if ($idProof && !$selfie) {
                  $notification->title = 'KYC Uploaded For Id Proof Document - 7Search PPC ';
                  $notification->noti_desc = "Kyc Document uploaded successfully";
                  $notification->noti_for = 2;
                  $notification->all_users = 0;
                } else if ($selfie && $idProof) {
                  $notification->title = 'KYC Uploaded For Photo & Id Proof Document - 7Search PPC ';
                  $notification->noti_desc = "Kyc Document uploaded successfully";
                  $notification->noti_for = 2;
                  $notification->all_users = 0;
                }

                if ($notification->save()) {
                  $noti = new UserNotification();
                  $noti->notifuser_id = gennotificationuseruniq();
                  $noti->noti_id = $notification->id;
                  $noti->user_id = $request->uid;
                  $noti->user_type = 2;
                  $noti->view = 0;
                  $noti->created_at = Carbon::now();
                  $noti->updated_at = now();
                  $noti->save();
                }
                // user notification end code //
            	/* Admin Section  */ 
              	$email = $user->email;
                $fullname = $user->first_name . ' ' . $user->last_name;
                $useridas = $user->uid;
              	$data['details'] = array('subject' => 'Kyc Document uploaded by user successfully - Publisher 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
              	$subject = 'KYC Submission Confirmation - 7Search PPC';
                $body =  View('emailtemp.pubkycuploadeduser', $data);
                sendmailUser($subject,$body,$email);
                $adminmail1 = 'advertisersupport@7searchppc.com';
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.userkycuploadedadmin', $data);
                $subjectadmin = 'KYC Update Request successfully - Publisher 7Search PPC';
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
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  

  /**
   * @OA\Post(
   *     path="/api/user/pub/kyc/info",
   *     summary="Retrieve KYC Information For Publisher",
   *     tags={"Kyc"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="multipart/form-data",
   *             @OA\Schema(
   *                 required={"uid"},
   *                 @OA\Property(property="uid", type="string", description="User ID")
   *             )
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="x-api-key",
   *         in="header",
   *         required=true,
   *         description="x-api-key [Publisher]",
   *         @OA\Schema(
   *             type="string"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
   *             @OA\Property(property="data", type="object", description="User KYC information",
   *                 @OA\Property(property="user_photo", type="string", description="URL of user's photo"),
   *                 @OA\Property(property="user_photo_remark", type="string", description="Remark for user's photo"),
   *                 @OA\Property(property="user_photo_id_remark", type="string", description="Remark for user's photo ID"),
   *                 @OA\Property(property="user_photo_id", type="string", description="URL of user's photo ID"),
   *                 @OA\Property(property="photo_verified", type="integer", description="Status of photo verification"),
   *                 @OA\Property(property="photo_id_verified", type="integer", description="Status of photo ID verification")
   *             ),
   *             @OA\Property(property="message", type="string", description="Success message")
   *         )
   *     ),
   *     @OA\Response(
   *         response=101,
   *         description="Error response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=101, description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     )
   * )
   */
  public function pubKycInfo(Request $request)
  {
    $user = User::select('user_photo', 'user_photo_remark', 'user_photo_id_remark', 'user_photo_id', 'photo_verified', 'photo_id_verified')
      ->where('uid', $request->uid)->first();
    $user->user_photo = (strlen($user->user_photo) > 0) ? config('app.url') . 'kycdocument' . '/' . $user->user_photo : '';
    $user->user_photo_id = (strlen($user->user_photo_id) > 0) ? config('app.url') . 'kycdocument' . '/' . $user->user_photo_id : '';
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
    if (empty($user)) {
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


    return json_encode($return, JSON_NUMERIC_CHECK);
  }


  /**
   * @OA\Post(
   *     path="/api/user/pub/payout/info",
   *     summary="Retrieve payout details and withdrawal limit for a user",
   *     tags={"Payouts & Wallet"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="multipart/form-data",
   *             @OA\Schema(
   *                     required={"uid"},
   *                 @OA\Property(
   *                     property="uid",
   *                     type="string",
   *                     description="User ID"
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="x-api-key",
   *         in="header",
   *         required=true,
   *         description="x-api-key [Publisher]",
   *         @OA\Schema(
   *             type="string"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
   *             @OA\Property(property="data", type="object", description="User payout info", 
   *                 @OA\Property(property="payout_method", type="string", description="Payout method"),
   *                 @OA\Property(property="withdrawl_limit", type="number", format="float", description="Withdrawal limit")
   *             ),
   *             @OA\Property(property="message", type="string", description="Success message")
   *         )
   *     ),
   *     @OA\Response(
   *         response=101,
   *         description="Error response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=101, description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     )
   * )
   */
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

    return json_encode($return, JSON_NUMERIC_CHECK);
  }

  public function pay_infoTest(Request $request)
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
        ->select('pub_withdrawl_limit', 'payout_name', 'payout_id', 'pub_payout_methods.image', 'pub_payout_methods.display_name')
        ->join('pub_payout_methods', 'pub_user_payout_modes.payout_id', 'pub_payout_methods.id')
        ->where('publisher_id', $request->uid)->first();
      // $data['kyc_status'] = ($user->photo_verified == 2 && $user->photo_id_verified == 2) ? 1 : 0;
      $data['kyc_status'] = $user->photo_verified;
      $data['pay_mode_status'] = (!empty($payMode)) ? 1 : 0;
      $data['payout_mode'] = ($payMode) ? $payMode->payout_name : '';
      $data['display_name'] = ($payMode) ? $payMode->display_name : '';
      $data['withdrawl_limit'] = ($payMode) ? $payMode->pub_withdrawl_limit : 0;
      $data['image'] = ($payMode) ? config('app.url') . 'payout_methos' . '/' . $payMode->image : '';



      $total_earn = number_format($pay->amt + $user->pub_wallet, 2);
      $data['total_earning'] = $total_earn ? $total_earn : 0;

      $total_wit = number_format($pay->withdrawl_amt, 2);
      $data['total_withdrawl'] = $total_wit ? $total_wit : 0;

      $wltPubAmt = getPubWalletAmount($request->uid);
      $amtwallet    = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
      $avl_amt = number_format($amtwallet, 2);
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
      // if ($user->photo_verified == 0 && $user->photo_id_verified == 1) {
      //     $data['kyc_status'] = 0;
      // } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 0) {
      //     $data['kyc_status'] = 0;
      // } elseif ($user->photo_verified == 0 && $user->photo_id_verified == 3) {
      //     $data['kyc_status'] = 3;
      // } elseif ($user->photo_verified == 3 && $user->photo_id_verified == 0) {
      //     $data['kyc_status'] = 3;
      // } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 1) {
      //     $data['kyc_status'] = 1;
      // } elseif (($user->photo_verified == 1 && $user->photo_id_verified == 3) || 
      //           ($user->photo_verified == 3 && $user->photo_id_verified == 1)) {
      //     $data['kyc_status'] = 3;
      // } else {
      //     $data['kyc_status'] = $user->photo_verified;
      // }

      // if (($user->photo_verified == 0 && $user->photo_id_verified == 1) ||
      //     ($user->photo_verified == 1 && $user->photo_id_verified == 0) ||
      //     ($user->photo_verified == 1 && $user->photo_id_verified == 3) ||
      //     ($user->photo_verified == 3 && $user->photo_id_verified == 1)) {
      //     $data['kyc_status'] = 3;
      // } elseif ($user->photo_verified == 1 && $user->photo_id_verified == 1) {
      //     $data['kyc_status'] = 1;
      // } else {
      //     $data['kyc_status'] = $user->photo_verified;
      // }



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
      $amtwallet    = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
      $avl_amt = number_format($amtwallet, 2);
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



  /**
   * @OA\Post(
   *     path="/api/pub/user/payoutlist",
   *     summary="Retrieve a list of payouts for a user",
   *     tags={"Payouts & Wallet"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="multipart/form-data",
   *             @OA\Schema(
   *                 @OA\Property(
   *                     property="uid",
   *                     type="string",
   *                     description="User ID"
   *                 ),
   *                 @OA\Property(
   *                     property="lim",
   *                     type="integer",
   *                     description="Limit of records per page"
   *                 ),
   *                 @OA\Property(
   *                     property="page",
   *                     type="integer",
   *                     description="Page number"
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="x-api-key",
   *         in="header",
   *         required=true,
   *         description="x-api-key [Publisher]",
   *         @OA\Schema(
   *             type="string"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
   *             @OA\Property(property="message", type="string", description="Success message"),
   *             @OA\Property(property="data", type="array", @OA\Items(
   *                 @OA\Property(property="transaction_id", type="integer", description="Transaction ID"),
   *                 @OA\Property(property="amount", type="number", format="float", description="Amount"),
   *                 @OA\Property(property="payout_method", type="string", description="Payout method"),
   *                 @OA\Property(property="release_date", type="string", format="date-time", description="Release date"),
   *                 @OA\Property(property="status", type="integer", description="Status"),
   *                 @OA\Property(property="remark", type="string", description="Remark")
   *             )),
   *             @OA\Property(property="wallet", type="number", format="float", description="User wallet balance"),
   *             @OA\Property(property="row", type="integer", description="Total number of records")
   *         )
   *     ),
   *     @OA\Response(
   *         response=100,
   *         description="Error response",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="code", type="integer", example=100, description="Status code"),
   *             @OA\Property(property="message", type="string", description="Error message")
   *         )
   *     )
   * )
   */
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
        ->select('transaction_id', 'amount', 'payout_method', 'release_date', 'status', 'remark', 'release_date')
        ->where('publisher_id', $uid)
        ->where('status', 1)
        ->orderBy('id', 'desc');
      $row = $trlog->count();
      $datas = $trlog->offset($start)->limit($limit)->get();

      $return['code']        = 200;
      $return['message']    = 'successfully';
      $return['data']       = $datas;
      $wltPubAmt = getPubWalletAmount($uid);
      $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : $users->pub_wallet;
      $return['row']         = $row;
    } else {

      $return['code'] =  100;
      $return['message'] = 'Not Found User';
    }
    return json_encode($return, JSON_NUMERIC_CHECK);
  }



  public function balance_info(Request $request)
  {

    $user = User::select('pub_wallet')->where('uid', $request->uid)->first();

    $pay = DB::table('pub_payouts')->select(
        DB::raw('SUM(amount) as amt'),
        DB::raw("(SELECT SUM(amount) FROM ss_pub_payouts WHERE ss_pub_payouts.status = 1 AND ss_pub_payouts.publisher_id = '" . $request->uid . "') as withdrawl_amt")
      )->where('publisher_id', $request->uid)->first();

    if ($user) {

      $total_earn = number_format($pay->amt + $user->pub_wallet, 2);
      $return['total_earning'] = $total_earn ? $total_earn : 0;

      $total_wit = number_format($pay->withdrawl_amt, 2);
      $return['total_withdrawl'] = $total_wit ? $total_wit : 0;

      $wltPubAmt = getPubWalletAmount($request->uid);
      $amtwallet    = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
      $avl_amt = number_format($amtwallet, 2);
      $return['avalable_amt'] = $avl_amt ? $avl_amt : 0;


      $return['code']    = 200;
      //   $return['data']    = $data;
      $return['message'] = 'User Payout info retrieved successfully';
    } else {
      $return['code']    = 101;
      $return['message'] = 'Data not found';
    }

    return json_encode($return, JSON_NUMERIC_CHECK);
  }

  public function pubTokenUpdate(Request $request)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'uid' => 'required',
        'noti_token' => 'required',
      ]
    );
    if ($validator->fails()) {
      $return['code']    = 100;
      $return['error']   = $validator->errors();
      $return['message'] = 'Validation Error!';
      return json_encode($return);
    }
    $userlog = User::where('uid', $request->uid)->first();

    if (!empty($userlog)) {
      $userlog->pub_noti_token = $request->noti_token;
      if ($userlog->save()) {
        $return['code']    = 200;
        $return['message'] = 'Noti token updated successfully';
      } else {
        $return['code']    = 101;
        $return['message'] = 'Something went wrong!';
      }
    } else {
      $return['code']    = 101;
      $return['message'] = 'Something went wrong!';
    }

    return json_encode($return, JSON_NUMERIC_CHECK);
  }
  public function payoutselectedmethod(Request $request)
  {
    $payMode = DB::table('pub_user_payout_modes')
      ->select('pub_user_payout_modes.*')->where('publisher_id', $request->uid)->where('payout_id', $request->payout_id)->first();
    if ($payMode) {
      $return['code']    = 200;
      $return['data']     = $payMode;
      $return['message'] = 'Noti token updated successfully';
    } else {
      $return['code']    = 101;
      $return['message'] = 'Something went wrong!';
    }
    return json_encode($return, JSON_NUMERIC_CHECK);
  }

  // Api for delete account request for publisher
  public function request_delete_remark(Request $request)
  {
    $uid = $request->uid;
    $del_remark = $request->del_remark;
    $cancel_request = '';
    $del_type = '';
    $data = User::select('uid', 'user_type', 'del_request', 'del_remark')->where('uid', $uid)->whereIn('user_type', [2, 3])->first();
    if ($data == true) {
      if ($data->user_type == 3 && $data->del_request == 1) {
        $del_type = 3;
      } else if ($data->user_type == 3 && $data->del_request == 2) {
        $del_type = 3;
      } else if ($data->user_type == 3 && $data->del_request == 0) {
        $cancel_request = 1;
        $del_type = 2;
      } else if ($data->user_type == 3) {
        $cancel_request = 1;
      } else {
        $del_type = 2;
        $cancel_request = 0;
      }
      if ($uid && $del_remark) {
        $validator = Validator::make($request->all(), [
          'uid'     => 'required',
          'del_remark'     => 'required|max:300',
        ]);
      } else {
        $validator = Validator::make($request->all(), [
          'uid'     => 'required',
        ]);
      }
      if ($validator->fails()) {
        $return['code']    = 100;
        $return['msg'] = 'Validation Error';
        $return['error']   = $validator->errors();
        return json_encode($return);
      }

      if ($data == true && !empty($uid) && !empty($del_remark)) {
        // $res = $request->all();
        // $type = 2;
        // userUpdateProfile($res,$data->uid,$type);
        User::where('uid', $uid)->update(['del_remark' => $del_remark, 'del_request' => $del_type]);
        $remark = $request->del_remark;
        $full_name = $data->first_name . ' ' . $data->last_name;
        $data['details'] = ['subject' => 'Account Deletion Request', 'user_id' => $data->uid, 'full_name' => $full_name, 'remark' => $remark];
        // /* Admin Section  */
        $adminmail1 = 'info@7searchppc.com';
        //$adminmail1 = ['info@7searchppc.com','testing@7searchppc.com'];
        $adminmail2 = '';
        $bodyadmin =   View('emailtemp.pubaccountdeletionrequest', $data);
        $subjectadmin = 'Account Deletion Of Publisher Request - 7Search PPC';
        sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);
        $return['code'] = 200;
        $return['msg'] = 'Request sent successfully';
        return json_encode($return);
      }

      if ($data == true && !empty($uid)) {
        // $res = $request->all();
        // $res['del_remark'] = '';
        // $type = 2;
        // userUpdateProfile($res,$data->uid,$type);
        User::where('uid', $uid)->update(['del_remark' => 'Your Account is Reactivated successfully.', 'del_request' => $cancel_request]);
        $full_name = $data->first_name . ' ' . $data->last_name;
        $data['details'] = ['subject' => 'Account Deletion Request Withdrawn', 'user_id' => $data->uid, 'full_name' => $full_name];
        /* Admin Section  */
        $adminmail1 = 'info@7searchppc.com';
        //$adminmail1 = ['info@7searchppc.com','testing@7searchppc.com'];
        $adminmail2 = '';
        $bodyadmin =   View('emailtemp.pubaccountdeletionrequestwithdrawn', $data);
        $subjectadmin = 'Account Deletion Of Publisher Request Withdrawn - 7Search PPC';
        $sendmailadmin =  sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);
        $return['code'] = 200;
        $return['msg'] = 'Account is Reactivated successfully';
        return json_encode($return);
      }
    } else {
      $return['code']    = 101;
      $return['msg'] = 'This user type Data not found !';
      return json_encode($return);
    }
  }

  // get publisher header message data
   /**
    * @OA\Post(
    *     path="/api/pub/user/header-message",
    *     summary="Get Publisher Header Message",
    *     tags={"Publisher Header Message"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid"},
    *                 @OA\Property(property="uid", type="string", description="User ID")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Publisher Header Message Data Found Successfully!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Status is disable data not found!",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
  public function getpubHeadermsgdata() {
      $data = DB::table("header_messages")->select("header_content","slider_content","content_speed")->where(["status"=>1, "account_type"=>2])->first();
      if (!empty($data)) {
          $return['code']    = 200;
          $return['data']    = $data;
          $return['message'] = 'Publisher Header Message Data Found Successfully!';
      } else {
          $return['code']    = 101;
          $return['message'] = 'Status is disable data not found!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
  }

   /**
    * @OA\Post(
    *     path="/api/pub/user/popup-message-list",
    *     summary="Manage Popup Message",
    *     tags={"Payouts & Wallet"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                  required={"uid"},
    *                 @OA\Property(
    *                     property="uid",
    *                     type="string",
    *                     description="User ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Publisher]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
    *             @OA\Property(property="data", type="object", description="User payout info", 
    *                 @OA\Property(property="payout_method", type="string", description="Payout method"),
    *                 @OA\Property(property="withdrawl_limit", type="number", format="float", description="Withdrawal limit")
    *             ),
    *             @OA\Property(property="message", type="string", description="Success message")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Error response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101, description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
  public function listPopupMessagePub(Request $request)
  {
          $validator = Validator::make($request->all(), [
            'uid'     => 'required',
            'popup_type'     => 'required'
        ]);

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['message'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return, JSON_NUMERIC_CHECK);

        }

      $support = PopupMessage::select('title','sub_title','image','message','btn_content','btn_link')->where('account_type',2)->where('status',1)->where('popup_type',$request->popup_type)->first();
      if (!empty($support)) {
          $return['code']    = 200;
          $return['data']    = $support;
          $return['message'] = 'popup Message list retrieved successfully!';
      } else {
          $return['code']    = 101;
          $return['message'] = 'Something went wrong!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
  }
  
}                 
