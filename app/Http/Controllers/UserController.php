<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User_otp;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\CreateAdminMail;
use App\Mail\CreateUserMail;
use App\Mail\VerfiedAdminMail;
use App\Mail\VerfiedUserMail;
use Illuminate\Support\Str;
use App\Models\User;
use substr;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Redis;
use App\Models\PubAdunit;
use DateTime;
use DateTimeZone;

class UserController extends Controller
{
    public function addrRegistration(Request $request)
    {
        $countryName = $request->input('country_name');
        if($countryName){
                $count_name = DB::table('countries')->select('id', 'name', 'phonecode', 'status', 'trash')->where('name',$countryName)->where('status', 1)->where('trash', 1)->first();
                if(!$count_name){
                        $return['code']      = 101;
                        $return['message']   = 'Country Not Exist!';
                        return json_encode($return);
                }
        }
        $user_type = $request->input('user_type');
        if ($user_type == 1) {
            $advcode = 'ADV';
            $uid = randomuid($advcode);
        } elseif ($user_type == 2) {
            $pubcode = 'PUB';
            $uid = randomuid($pubcode);
        } else {
            $bthcode = 'BTH';
            $uid = randomuid($bthcode);
        }
        $userregister = User_otp::where('email',$request->email)->where('status',1)->first();
        $usermatch = User::select('email')->where('email',$request->email)->first();
        if($userregister){
        if($request->messenger_type != 'None'){
            $email_array = explode(".", $request->email);
            if($email_array[1] == 'com'){
                $validator = Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'last_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'email' => 'required|unique:users,email|max:70|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
                        'cat_id' => 'required',
                        'user_type' => "required",
                        'messenger_name' => 'required',
                        'messenger_type' => "required",
                        'state' => "required|regex:/^[a-z A-Z ]+$/",
                        'phone_number' => ['required','numeric', 'between:1000,999999999999999','unique:users,phone'],
                        // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                        // 'confirm' => ['required', 'string', 'min:8', 'same:password'],
                        'password' => ['required', 'string', 'min:4'],
                        'confirm' => ['required', 'string', 'min:4', 'same:password'],
                        'country_name' => 'required',
                        'agree' => 'required',
                    ],
                    [
                         'first_name.regex' => 'First name should be character only.',
                         'last_name.regex' => 'Last name should be character only.',
                         'cat_id.required' => 'Please select category',
                         'confirm.same' => 'Password and Confirm Password does not match',
                         'confirm.min' => 'The confirm password must be at least 4 characters.',
                          'state.regex' => 'State name should be character only.',
                          'phone_number.required' => 'The phone no. must contain only numeric characters.',
                          'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                          'messenger_name.required' => 'Please enter your nickname in messenger',
                          'password.required' => 'Please enter password',
                          'password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'confirm.required' => 'Please enter confirm password',
                          'confirm.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'agree.required' => 'Please agree to all the terms and conditions before proceeding',
                          'email.max'=> 'This value is too long. It should have 70 characters or less.'
                    ]);
            }else{

                $validator = Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'last_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'email' => 'required|unique:users,email|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/',
                        'cat_id' => 'required',
                        'user_type' => "required",
                        'messenger_name' => 'required',
                        'messenger_type' => "required",
                        'state' => "required|regex:/^[a-z A-Z ]+$/",
                        'phone_number' => ['required','numeric', 'between:1000,999999999999999','unique:users,phone'],
                        // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                        // 'confirm' => ['required', 'string', 'min:8', 'same:password'],
                        'password' => ['required', 'string', 'min:4'],
                        'confirm' => ['required', 'string', 'min:4', 'same:password'],
                        'country_name' => 'required',
                        'agree' => 'required',
                    ],
                    [
                        'first_name.regex' => 'First name should be character only.',
                        'last_name.regex' => 'Last name should be character only.',
                        'cat_id.required' => 'Please select category',
                        'confirm.same' => 'Password and Confirm Password does not match',
                        'confirm.min' => 'The confirm password must be at least 4 characters.',
                          'state.regex' => 'State name should be character only.',
                        'phone_number.required' => 'The phone no. must contain only numeric characters.',
                        'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                        'messenger_name.required' => 'Please enter your nickname in messenger',
                        'password.required' => 'Please enter password',
                        'password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                        'confirm.required' => 'Please enter confirm password',
                        'confirm.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                        'agree.required' => 'Please agree to all the terms and conditions before proceeding',
                        'email.max'=> 'This value is too long. It should have 70 characters or less.',
                    ]);
            }
                if ($validator->fails()) {
                    $return['code'] = 100;
                    $return['msg'] = 'error';
                    $return['err'] = $validator->errors();
                    return response()->json($return);
                }
        }else{
            $email_array = explode(".", $request->email);
            if($email_array[1] == 'com'){
                $validator = Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'last_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'email' => 'required|unique:users,email|max:70|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
                        'cat_id' => 'required',
                        'user_type' => "required",
                        'messenger_type' => "required",
                        'state' => "required|regex:/^[a-z A-Z ]+$/",
                        'phone_number' => ['required','numeric', 'between:1000,999999999999999','unique:users,phone'],
                        // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                        // 'confirm' => ['required', 'string', 'min:8', 'same:password'],
                        'password' => ['required', 'string', 'min:4'],
                        'confirm' => ['required', 'string', 'min:4', 'same:password'],
                        'country_name' => 'required',
                        'agree' => 'required',
                    ],
                    [
                        'first_name.regex' => 'First name should be character only.',
                        'last_name.regex' => 'Last name should be character only.',
                        'cat_id.required' => 'Please select category',
                        'confirm.same' => 'Password and Confirm Password does not match',
                        'confirm.min' => 'The confirm password must be at least 4 characters.',
                          'state.regex' => 'State name should be character only.',
                        'phone_number.required' => 'The phone no. must contain only numeric characters.',
                        'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                          'password.required' => 'Please enter password',
                        'password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'confirm.required' => 'Please enter confirm password',
                        'confirm.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'agree.required' => 'Please agree to all the terms and conditions before proceeding',
                          'email.max'=> 'This value is too long. It should have 70 characters or less.',
                        
                    ]);
            }else{
                $validator = Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'last_name' => 'required|regex:/^[a-z A-Z ]+$/',
                        'email' => 'required|unique:users,email|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/',
                        'cat_id' => 'required',
                        'user_type' => "required",
                        'messenger_type' => "required",
                        'state' => "required|regex:/^[a-z A-Z ]+$/",
                        'phone_number' => ['required','numeric', 'between:1000,999999999999999','unique:users,phone'],
                        // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                        // 'confirm' => ['required', 'string', 'min:8', 'same:password'],
                        'password' => ['required', 'string', 'min:4'],
                        'confirm' => ['required', 'string', 'min:4', 'same:password'],
                        'country_name' => 'required',
                        'agree' => 'required',
                    ],
                    [
                        'first_name.regex' => 'First name should be character only.',
                        'last_name.regex' => 'Last name should be character only.',
                        'cat_id.required' => 'Please select category',
                        'confirm.same' => 'Password and Confirm Password does not match',
                        'confirm.min' => 'The confirm password must be at least 4 characters.',
                          'state.regex' => 'State name should be character only.',
                        'phone_number.required' => 'The phone no. must contain only numeric characters.',
                        'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                          'password.required' => 'Please enter password',
                        'password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'confirm.required' => 'Please enter confirm password',
                        'confirm.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                          'agree.required' => 'Please agree to all the terms and conditions before proceeding',
                          'email.max'=> 'This value is too long. It should have 70 characters or less.',
                        
                    ]);
            }
            if ($validator->fails()) {
                $return['code'] = 100;
                $return['msg'] = 'error';
                $return['err'] = $validator->errors();
                return response()->json($return);
            }
        }
        $cat_name = DB::table('countries')->select('id', 'name', 'phonecode', 'status', 'trash')->where('name', $request->country_name)
            ->where('status', 1)->where('trash', 1)->first();
        if ($cat_name->phonecode == substr($request->code, 1)) {
            // $codes =  $request->input('code');
            $phone = $request->input('phone_number');
            $regData = new User;
            $regData->auth_provider = $request->input('source');
            $regData->first_name = strtoupper($request->input('first_name'));
            $regData->last_name = strtoupper($request->input('last_name'));
            $regData->email  = $request->input('email');
            $regData->state  = $request->input('state');
            $regData->messenger_name  = $request->input('messenger_name');
            $regData->messenger_type  = $request->input('messenger_type');
            $regData->phonecode = $count_name->phonecode;
          	$regData->phone = $phone;
            $regData->website_category = $request->input('cat_id');
            $regData->password = Hash::make($request->input('password'));
            $regData->country = $count_name->name;
            $regData->user_type = $user_type;
            $regData->uid = $uid;
            $regData->ac_verified = 1;
            $regData->status = 0;
            $regData->ip = $_SERVER['REMOTE_ADDR'];
            $regData->referal_code = $request->input('referal_code');
            
            if ($request->input('referal_code') != "") {
                $category = DB::table('categories')->select('id', 'cat_name')->where('id', $request->input('cat_id'))->first();
                $acc_type = $user_type == 1 ? "Advertiser" : "Publisher";
                $url = "http://refprogramserv.7searchppc.in/api/referral-user";
                $refData = [
                    'user_id' => $uid,
                    'acc_type' => $acc_type,
                    'country' => $acc_type,
                    'state' => $request->input('state'),
                    'website_category' => $category->cat_name,
                    'referral_code' => $request->input('referal_code'),
                    'status' => 0,
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
        } else {
            $return['code']    = 201;
            $return['message'] = 'Does Not Exist Country Code And Country Name!';
            return response()->json($return);
        }
        if ($regData->save()) {
            $profileusers = User::select('email','uid')->where('email',$request->email)->first();
            $res = $request->all();
            $type = ($request->user_type == 1) ? 1 : 2;
            userUpdateProfile($res,$profileusers->uid,$type);

            User_otp::where('email',$request->email)->where('status',1)->delete();
            $email = $regData->email;
            $regDatauid = $regData->uid;
            $fullname = "$regData->first_name $regData->last_name";
            $ticketno = $regData->first_name;
            $urllink = base64_encode($regData->uid);
            $link = "https://services.7searchppc.com/verification/user/$urllink";
            $data['details'] = ['subject' => 'User Created Successfully', 'email' => $email, 'user_id' => $regDatauid, 'full_name' => $fullname, 'email' => $email, 'link' => $link];
            /* User Section */
            $subject = 'Account Created Successfully - 7Search PPC';
            // $body =  View('emailtemp.usercreate', $data);
            // $body =  ($user_type == 1) ? View('emailtemp.usercreate', $data) : View('emailtemp.userpubcreate', $data);
            $body =  ($user_type == 1) ? View('emailtemp.userverify', $data) : View('emailtemp.userpublisherverify', $data);
            /* User Mail Section */
            $sendmailUser =  sendmailUser($subject,$body,$email);
            if($sendmailUser == '1') 
            {
                $token = base64_encode(str_shuffle('JbrFpMxLHDnbs'.rand(1111111,9999999)));
                $usertokenupdate = User::where('uid', $uid)->first();
                $usertokenupdate->remember_token = $token;
                $usertokenupdate->update();
                $return['code'] = 200;
                $return['message']  = 'Mail Send & Data Inserted Successfully !';
                $return['token'] = $token;
                $return['email'] = $email;
                $return['uid'] = $uid;
                $return['utype'] = $user_type;

            }
            else 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send But Data Insert Successfully !';
            }
            /* Admin Section  */
             $adminmail1 = 'advertisersupport@7searchppc.com';
            //$adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
            $adminmail2 = 'info@7searchppc.com';
            $bodyadmin =   View('emailtemp.useradmincreate', $data);
            $subjectadmin = 'Account Created Successfully - 7Search PPC';
            $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
            if($sendmailadmin == '1') 
            {
                $token = base64_encode(str_shuffle('JbrFpMxLHDnbs'.rand(1111111,9999999)));
                $usertokenupdate = User::where('uid', $uid)->first();
                $usertokenupdate->remember_token = $token;
                $usertokenupdate->update();
                $return['code'] = 200;
                $return['message']  = 'Mail Send & Data Inserted Successfully !';
                $return['token'] = $token;
                $return['email'] = $email;
                $return['uid'] = $uid;
                $return['utype'] = $user_type;
            }
            else {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send But Data Insert Successfully !';
            }
        } 
        else 
        {
            $return['code'] = 100;
            $return['msg'] = 'User Not Registered!';
        }
        return response()->json($return);
    }
    else if($usermatch){
        $return['code'] = 100;
        $return['msg'] = 'This Email Id is already registered !';
        return response()->json($return);
    }
    else{
        $return['code'] = 100;
        $return['msg'] = 'Please verify your email ID first.';
        return response()->json($return);
     }
    }


    public function verifyuser($value = '')
    {
        $uid = base64_decode($value);
        $advertiser_url = config('app.advertiser_url');
      	$publisher_url = config('app.publisher_url');
        $udata = User::where('uid', $uid)->first();
      	$targeturl = ($udata->user_type == 1) ? $advertiser_url : $publisher_url;
      	if (empty($udata)) {
            $return['code'] = 100;
            $return['msg'] = 'Not Found User';
        } else {
            $acverified = $udata->ac_verified;
            if ($acverified == 0) {
                $udata->ac_verified = 1;
                $udata->status = 0;
                if ($udata->save()) {
                    $uid = $udata->uid;
                    $fullname = "$udata->first_name $udata->last_name";
                    $email = $udata->email;
                    $data['details'] = ['subject' => 'Account Verified Successfully - 7Search PPC', 'email' => $email, 'user_id' => $uid, 'full_name' => $fullname];
                    
                    /* User Section */
                    $subject = 'Account Verified Successfully - 7Search PPC';
                    $body =  ($udata->user_type == 1) ? View('emailtemp.userverify', $data) : View('emailtemp.userpublisherverify', $data);
                    /* User Mail Section */
                    $sendmailUser =  sendmailUser($subject,$body,$email);
                    if($sendmailUser == '1') 
                    {
                        $return['code'] = 200;
                        $return['message']  = 'Your account is active successfully !';
                    }
                    else 
                    {
                        $return['code'] = 200;
                        $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                    }
                    /* Admin Section  */
                    $adminmail1 = 'advertisersupport@7searchppc.com';
                    $adminmail2 = 'info@7searchppc.com';
                    $bodyadmin =   View('emailtemp.useradminverify', $data);
                    $subjectadmin = 'Account Verified Successfully - 7Search PPC';
                    $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
                    if($sendmailadmin == '1') 
                    {
                        return redirect($targeturl.'auth-login');
                    }
                    else 
                    {
                        return redirect($targeturl.'auth-login');
                    }
                            
                  } else {
                    	return redirect($targeturl.'auth-login');
                }
            } else {
                
                  return redirect($targeturl.'auth-login');
            }
        }
        return response()->json($return);
    }

     // ================= start save otp table data =======================================
    
     static function randomcmpid()
     {
         $cpnid = mt_rand(100000, 999999);
         $checkdata = User_otp::where('otp', $cpnid)->count();
         if ($checkdata > 0) {
             self::randomcmpid();
         } else {
             return $cpnid;
         }
     }
 
 
     public function saveUserotp(Request $request){
            $validator = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email|max:70',
                ],[
                    'email.email'=> 'Please enter a valid email address'
                ]);
            if ($validator->fails()) {
                $return['code'] = 100;
                $return['msg'] = 'error';
                $return['err'] = $validator->errors();
                return response()->json($return);
            }
       // dd($request->all());
         $email = $request->email;
         $existuser = User::where('email',$email)->where('status',0)->first();
         if($existuser){
            $return['code'] = 101;
            $return['msg'] = 'This email already exists.';
            return response()->json($return);
         }else{
         $otp = self::randomcmpid();
         $user = User_otp::where('email', $email)->first();
         if($user){
            $email_array = explode(".", $email);
            if($email_array[1] == 'com'){
                $validator = Validator::make($request->all(),[
                    'email' => "required|max:70|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/",
                ],
                [
                    'email.max'=> 'This value is too long. It should have 70 characters or less.'   
                ]
            );
            }else{
                $validator = Validator::make($request->all(),[
                    'email' => "required|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/",
                ],[
                    'email.max'=> 'This value is too long. It should have 70 characters or less.'
                ]
            ); 
            }
            // $validator = Validator::make($request->all(),[
            //     'email' => "required|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/",
            // ]);
         }else{
            $email_array = explode(".", $email);
            if($email_array[1] == 'com'){
                $validator = Validator::make($request->all(),[
                    'email' => "required|max:70|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/",
                ],
                [
                    'email.max'=> 'This value is too long. It should have 70 characters or less.'   
                ]
            );
            }else{
                $validator = Validator::make($request->all(),[
                    'email' => "required|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/",
                ],
                [
                    'email.max'=> 'This value is too long. It should have 70 characters or less.'   
                ]
            ); 
            }
         }
        
         if($validator->fails()){
             $return['code'] = 100;
             $return['msg'] = 'error';
             $return['err'] = $validator->errors();
             return response()->json($return);
         }
         if($user){
            User_otp::where('email', $email)->update(['otp'=>$otp,'email_verified_at'=>'NULL','status'=>0]);
            $getotp = $otp;
            $data['details'] = ['subject' => 'Email Verification Code - 7Search PPC','otp'=>$getotp];
            /* User Section */
            $subject = 'Email Verification Code - 7Search PPC';
            $body =  View('emailtemp.otpverify', $data);
            /* User Mail Section */
             sendmailUser($subject,$body,$email);
             $return['code'] = 200;
             $return['msg'] = 'Otp Sent Successfully.';
            /* Admin Section  */
         }else{
         $userotp = new User_otp;
         $userotp->email = $email;
         $userotp->otp = $otp;
         if($userotp->save()){
             $getotp = $otp;
             $data['details'] = ['subject' => 'Email Verification Code - 7Search PPC','otp'=>$getotp];
             /* User Section */
             $subject = 'Email Verification Code - 7Search PPC';
             $body =  View('emailtemp.otpverify', $data);
             /* User Mail Section */
             sendmailUser($subject,$body,$email);
             /* Admin Section  */
             $return['code'] = 200;
             $return['msg'] = 'Otp Sent Successfully.';
            }else{
             $return['code'] = 101;
             $return['msg'] = 'Otp Not Sent!..';
            }
         }
         return response()->json($return);
        }
     }
 
     public function verifyUserStayus(Request $request){
        $email_array = explode(".", $request->email);
        if($email_array[1] == 'com'){
            $validator = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email|max:70|regex:/^([a-z\d\.\+-]+)@([a-z\d-]+)\.([a-z]{2,8})(\.[a-z]{2,8})?$/',
                    'getotp' => 'required',
                ],[
                   'getotp.required' => 'Please enter OTP',
                //    'getotp.numeric' => 'The OTP no. must contain only numeric characters',
                //    'getotp.digits' => 'The OTP no. must contain minimum 6 digits',
                   
               ]);
        }else{
            $validator = Validator::make($request->all(),[
                'email' => "required|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/",
            ]); 
        }
             if ($validator->fails()) {
                 $return['code'] = 100;
                 $return['msg'] = 'error';
                 $return['err'] = $validator->errors();
                 return response()->json($return);
             }
             $verifyusers =  User_otp::where('email',$request->email)->where('otp',$request->getotp)->first();
             $expire = User_otp::where('email',$request->email)->first();
             $to = Carbon::createFromFormat('Y-m-d H:i:s', $expire->created_at);
             $from = Carbon::now()->format('Y-m-d H:i:s');
             $diff_in_hours = $to->diffInMinutes($from);
 
             if($diff_in_hours >= 15){
              $verifyusers->status=2;
              $verifyusers->update();
              $return['code'] = 101;
              $return['msg'] = 'The OTP has been expired.';
             }
             elseif($expire->status==1 && $expire->otp==$request->getotp){
                 $return['code'] = 200;
                 $return['msg'] = 'Email is already verified.';
             }
             elseif($verifyusers){
                 $verifyusers->status = 1;
                 $verifyusers->email_verified_at = date('d-m-Y H:i:s');
                 $verifyusers->update();
                //  if($verifyusers->update()){
                //     User_otp::where('email',$request->email)->where('status',1)->delete();
                //  }
                 $return['code'] = 200;
                 $return['status'] = 1;
                 $return['msg'] = 'OTP Verified';
             }else{
                 $return['code'] = 401;
                 $return['msg'] = 'Invalid OTP Code.';
             }

             return response()->json($return);
     }
   public function resendotp(Request $request){
    $email_array = explode(".", $request->email);
        if($email_array[1] == 'com'){
            $validator = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email|max:70|regex:/^([a-z\d\.\+-]+)@([a-z\d-]+)\.([a-z]{2,8})(\.[a-z]{2,8})?$/',
                ]);
        }else{
            $validator = Validator::make($request->all(),[
                'email' => "required|max:70|regex:/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/",
            ]); 
        }
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $getotp = self::randomcmpid();
        $res = User_otp::where('email',$request->email)->where("status",'!=',1)->update(['otp' => $getotp,'status'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        if(!empty($res)){
         $data['details'] = ['subject' => 'Email Verification Code - 7Search PPC','otp'=>$getotp];
         /* User Section */
         $subject = 'Email Verification Code - 7Search PPC';
         $body =  View('emailtemp.otpverify', $data);
         /* User Mail Section */
         sendmailUser($subject,$body,$request->email);
         $return['code'] = 200;
         $return['status'] = 1;
         $return['msg'] = 'OTP resent successfully.';
        }else{
         $return['code'] = 101;
         $return['msg'] = 'Resent otp not sent email is already verified.';
        }
        return response()->json($return);
   }
     // #################### end otp save code ############################################

  public function phoneNumberValidation(Request $request){
    $validator = Validator::make(
        $request->all(),
        [
            'phone_number' => ['required','numeric', 'between:1000,999999999999999','unique:users,phone'],
            'country'     => 'required',
            'state'     => 'required|regex:/^[a-z A-Z ]+$/',
            'web_cat'     => 'required',
            'msg_type'     => 'required',
            'nick_name'   => 'required',
        ],[ 
            'phone_number.required' => 'Please enter your phone number.',
            'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
            'country.required' => 'Please select country name.',
            'state.required' => 'Please enter state name.',
            'state.regex' => 'State name should be character only.',
            'web_cat.required' => 'Please select website category.',
            'msg_type.required' => 'Please select messenger type.',
            'nick_name.required' => 'Please enter nickname in messenger.',
        ]);
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['msg'] = 'error';
        $return['err'] = $validator->errors();
        return response()->json($return);
    }
    if(!empty($request)){
    $return['code'] = 200;
    $return['status'] = 1;
    $return['msg'] = 'Mobile Number Validated Successfully!.';
   }else{
    $return['code'] = 101;
    $return['msg'] = 'Resent otp not sent email is already verified.';
   }
   return response()->json($return);
  }
  public function updateWebData() {
    $adunits = PubAdunit::select('website_category', 'uid', 'web_code', 'ad_code', 'grid_type', 'site_url', 'erotic_ads', 'alert_ads','ad_size')->where('status', 2)->get()->toArray();
    $data = json_encode($adunits);
    Redis::set('webdata', $data);
 }
 
    public function getipinfos() {
        
        $data = DB::table('pub_stats')->where('uni_pub_imp_id','=', NULL)->limit(10)->get();
        foreach($data as $row) {
                // echo $row->publisher_code . $row->adunit_id . $row->device_type . $row->device_os . $row->country . $row->udate;
            $pub_uni_id = md5($row->publisher_code . $row->adunit_id . $row->device_type . $row->device_os . $row->country . $row->udate);    
            $pdata = DB::table('pub_stats')->where('uni_pub_imp_id','=', $pub_uni_id)->first();
            if(empty($pdata)) {
                DB::table('pub_stats')->where('id', $row->id)->update(['uni_pub_imp_id' => $pub_uni_id]);
            } else {
                DB::table('pub_stats')->where('id', $pdata->id)->update(['impressions' => $pdata->impressions + $row->impressions, 'amount' => $pdata->amount + $row->amount]);
                DB::table('pub_stats')->where('id', $row->id)->update(['uni_pub_imp_id' => '1']);
                
            }
                
                // echo '<br/>';
            // print_r($row);
        }
        // print_r($data);
            
    }
 
 public function adsdata() {
     
//      $dateTime = new DateTime();
//   $dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
//  echo $uniDate = $dateTime->format('Y-m-d h:i:s');
//  echo '<br/>';
    
    // $dateTime = new DateTime($uniDate);
    //   $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
    //  echo $uniDate = $dateTime->format('Y-m-d h:i:s');

    //  exit;
     $redisCon = Redis::connection('default');
    //  $res = $redisCon->rawCommand('json.get', 'clicks');
    // // $redisCon->rawCommand('json.arrinsert', "impressions", '$', '0', json_encode([ "name" => "Harshan Goel", "Phone" => 87456465]));
    // //  $data = $redisCon->rawCommand('info');
    $res = $redisCon->rawCommand('json.get', 'impressions');
        // $res = $redisCon->rawCommand('hset', 'adv_wallet', 'ADV66716A79B584B', 225);
    // $res = $redisCon->rawCommand('hget', 'adv_wallet', 'ADV66716A79B584B');
    $data = json_decode($res);
    // // print_r($data);
    // echo $res;
    // $db = new PDO("mysql:host=localhost;dbname=7s_test", '7s_test', '5R7AhY3mxSe3Rd4t');
    // $qry = $db->query("SELECT * from ss_ad_impressions limit 100");
    // $data = $qry->fetchAll(PDO::FETCH_ASSOC);
    // $data = DB::table('ad_impressions')->select("*")->orderBy('id', 'desc')->limit(1000)->get()->toArray();
    // $data = DB::table('ad_impressions')->orderBy('id', 'desc')->chunk(100, function ($users) {
    //     foreach ($users as $user) {
    //          print_r($user);
    //     }
    // });
    print_r($data);
    // echo 'asd';
    
 }
}