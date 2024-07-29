<?php

namespace App\Http\Controllers\login;

use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Login_log;
use App\Models\PubWebsite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class PublisherLoginController extends Controller
{


    public function websiteStore(Request $request)
    {


        $website = new PubWebsite();
        $website->web_name = $request->input('site_url');
        $website->site_url = 'gdf';
        $website->web_code = $request->input('web_code');
        $website->auth_code = 'dfsf';
        $website->save();




        $return['code']    = 200;
        $return['data']    = $website;
        $return['message'] = 'Website added successfully!';



        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required'
        ]);
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['message'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $service_url = config('app.url');
        $email = base64_decode($request->email);
        $password = base64_decode($request->password);
        $user = User::where('email', $email)->first();
        if (!$user) {
            $return['code']    = 401;
            $return['message'] = 'This email ID is not registered with us';
        } elseif (!Hash::check($password, $user->password)) {
            $return['code']    = 401;
            $return['message'] = 'Cannot login with credentials';
        } else {
            if ($user->user_type == 1) {
                $return['code']    = 406;
                $return['message'] = 'Your account is not a publisher account !';
            } elseif ($user->ac_verified == '0') {
                $email = $user->email;
                $regDatauid = $user->uid;
                $fullname = "$user->first_name $user->last_name";
                $ticketno = $user->first_name;
                $urllink = base64_encode($user->uid);
                $link = "https://services.7searchppc.com/verification/user/$urllink";
              
              
                //$link = $service_url.'verification/user/'.$urllink;
                $data['details'] = ['subject' => 'User Created Successfully', 'email' => $email, 'user_id' => $regDatauid, 'full_name' => $fullname, 'link' => $link];
                /* User Section */
                $subject = 'Account Created Successfully - 7Search PPC';
                $body =  View('emailtemp.usercreate', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject, $body, $email);
                if ($sendmailUser == '1') {
                    $return['code']    = 402;
                    $return['message'] = 'Verification mail been sent to your email address please verify your account !';
                } else {
                    $return['code'] = 201;
                    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                }
                /* Admin Section  */
                $adminmail1 = 'advertisersupport@7searchppc.com';
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.useradmincreate', $data);
                $subjectadmin = 'Account Created Successfully - 7Search PPC';
                $sendmailadmin =  sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);
                if ($sendmailadmin == '1') {
                    $return['code']    = 402;
                    $return['message'] = 'We sent you a verification mail please verify your account before login !';
                } else {
                    $return['code'] = 201;
                    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                }
            } elseif ($user->trash == '1') {
                $return['code']    = 403;
                $return['message'] = 'Your account is removed !';
            } elseif ($user->status == '3') {
                $return['code']    = 405;
                $return['message'] = 'Your account is suspended !';
            } elseif ($user->status == '4') {
                $return['code']    = 405;
                $return['message'] = 'Your account is on hold !';
            } else {
                $clientIP = request()->ip();
                $loginactivity                   = new Login_log();
                $loginactivity->uid              = $user->uid;
                $loginactivity->browser_name     = $request->browser;
                $loginactivity->ip_name          = $clientIP;
                if($request->type == 'advertiser'){
                    $loginactivity->user_type         = 1;
                }
                if($request->type == 'publisher'){
                    $loginactivity->user_type         = 2;
                }
                if ($loginactivity->save()) {
                    // $token = $user->createToken($user->email . '_Token')->plainTextToken;
                  
                  $pay = DB::table('pub_payouts')->
                  select(
                      DB::raw('SUM(amount) as amt'), 
                      DB::raw("(SELECT SUM(amount) FROM ss_pub_payouts WHERE ss_pub_payouts.status = 1 AND ss_pub_payouts.publisher_id = '". $user->uid ."') as withdrawl_amt")
                  )->where('publisher_id', $user->uid)->first();
                    $token = base64_encode(str_shuffle('JbrFpMxLHDnbs' . rand(1111111, 9999999)));
                    $usertokenupdate = User::where('uid', $user->uid)->first();
                    $usertokenupdate->remember_token = $token;
                    $usertokenupdate->update();
                    $return['code']    = 200;
                    $return['token']   = $token;
                    $return['uid']     = $user->uid;
                    $return['fname']   = $user->first_name;
                    $return['lname']   = $user->last_name;
                    $return['email']   = $user->email;
                    $return['wallet']   = $user->pub_wallet;
                  	$return['account_type']   = $user->account_type;
                  	$return['urole']   		  = $user->user_type;
                      
                    $total_earn = number_format($pay->amt+$user->pub_wallet, 2);
                    $return['total_earning'] = $total_earn ? $total_earn : 0;

                    $total_wit = number_format($pay->withdrawl_amt, 2);
                    $return['total_withdrawl'] = $total_wit ? $total_wit : 0;

                    $avl_amt = number_format($user->pub_wallet, 2);
                    $return['avalable_amt'] = $avl_amt ? $avl_amt : 0;
                  
                    $return['message'] = 'Login Successfully';
                }
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function change_password(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'userid' => 'required',
                'current_password' => 'required',
                'new_password' => 'required',
                'confirm_password' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $userid = $request->input('userid');
        $users = Admin::where('remember_token', $userid)->first();
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
                $return['message'] = 'Not Match Password';
            }
        } else {
            $return['code']    = 102;
            $return['message'] = 'Not Match New Password & Confirm Password';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function mobileLogin(Request $request)
    {
        $token = Str::random(60);
        $apitoken = hash('sha256', $token);
        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required',
                'password' => 'required'
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $username = ($request->input('username'));
        $users = User::where('user_name', $username)->first();
        if (empty($users)) {
            $return['code'] = 101;
            $return['msg'] = 'User id is invalid or not registered!';
            return response()->json($return);
        }
        $password = $request->input('password');
        $mytime = Carbon::now();
        if (Hash::check($password, $users->password)) {
            $rendumnumber = rand(111111, 999999);
            $rendHash = Hash::make($rendumnumber);
            $userslog = User::where('user_name', $username)->first();
            $userslog->login_token = $rendHash;
            if ($userslog->id) {
                $userslog->remember_token = $apitoken;
                $userslog->last_login = $mytime;
                if ($userslog->save()) {
                    $return['code'] = 200;
                    $return['msg'] = 'Login Successfully.';
                    //$return['token'] = $apitoken;
                    $return['name'] =  $users->first_name . ' ' . $users->last_name;
                    $return['uid'] =  $users->uid;
                    $return['login_token'] =  $rendumnumber;
                    return response()->json($return);
                }
            }
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Username is invalid  Incorrect!';
            return response()->json($return);
        }
    }
}
