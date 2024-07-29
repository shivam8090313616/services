<?php

namespace App\Http\Controllers\AppPublisher;
use App\Http\Controllers\Controller;
use App\Models\Login_log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;


class AppPublisherLoginsController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required',
            'browser'   => 'required',
        ]);
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['msg'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return);
        }
        $service_url = config('app.url');
        $email = $request->email;
        $password = $request->password;
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            $return['code']    = 401;
            $return['msg'] = 'Cannot login with credentials';
        } else {
            if ($user->user_type == 1) {
                $return['code']    = 406;
                $return['msg'] = 'Your account is not a publisher account !';
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
                    $return['msg'] = 'Verification mail been sent to your email address please verify your account !';
                } else {
                    $return['code'] = 201;
                    $return['msg']  = 'Mail Not Send But Data Insert Successfully !';
                }
                /* Admin Section  */
                $adminmail1 = 'advertisersupport@7searchppc.com';
                // $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.useradmincreate', $data);
                $subjectadmin = 'Account Created Successfully - 7Search PPC';
                $sendmailadmin =  sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);
                if ($sendmailadmin == '1') {
                    $return['code']    = 402;
                    $return['msg'] = 'We sent you a verification mail please verify your account before login !';
                } else {
                    $return['code'] = 201;
                    $return['msg']  = 'Mail Not Send But Data Insert Successfully !';
                }
            } elseif ($user->trash == '1') {
                $return['code']    = 403;
                $return['msg'] = 'Your account is removed !';
            } elseif ($user->status == '3') {
                $return['code']    = 405;
                $return['msg'] = 'Your account is suspended !';
            } elseif ($user->status == '4') {
                $return['code']    = 405;
                $return['msg'] = 'Your account is on hold !';
            } else {
                $clientIP = request()->ip();
                $loginactivity                   = new Login_log();
                $loginactivity->uid              = $user->uid;
                $loginactivity->browser_name     = $request->browser;
                $loginactivity->user_type        = $request->user_type;
                $loginactivity->ip_name          = $clientIP;
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
                    // $return['token']   = Crypt::encryptString($user->login_token);
                    $return['token']   = $user->login_token;
                    $return['uid']     = $user->uid;
                    $return['fname']   = $user->first_name;
                    $return['lname']   = $user->last_name;
                    $wltPubAmt = getPubWalletAmount($user->uid);
                    $return['wallet']        = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
                  	$return['account_type']   = $user->account_type;
                      
                    $total_earn = number_format($pay->amt+$user->pub_wallet, 2);
                    $return['total_earning'] = $total_earn ? $total_earn : 0;

                    $total_wit = number_format($pay->withdrawl_amt, 2);
                    $return['total_withdrawl'] = $total_wit ? $total_wit : 0;

                    $wltPubAmt = getPubWalletAmount($user->uid);
                    $pubwltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $user->pub_wallet;
                    
                    $avl_amt = number_format($pubwltamt, 2);
                    $return['avalable_amt'] = $avl_amt ? $avl_amt : 0;
                  
                    $return['msg'] = 'Login Successfully';
                }
            }
        }
        return json_encode($return);
    }
    public function change_password(Request $request)
    {
        if($request->current_password === $request->new_password){
            $return['code']    = 103;
            $return['msg'] = 'No repeating passwords. Choose a new one.';
            return json_encode($return);
        }
         $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
                'current_password' => 'required',
                'new_password' => 'required|min:4',
                //'new_password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                'confirm_password' => ['required', 'string', 'min:4', 'same:new_password'],
            ],[
                'current_password.required' => 'Please enter password',
                //'current_password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
                'confirm_password.required' => 'Please enter confirm password',
                //'confirm_password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['msg']   = $validator->errors();
            // $return['msg'] = 'Your password must be minimum 8 characters long, should contain at least one capital letter, small letter, number and special character.';
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
                    $return['msg'] = 'Password Chanage Successfully';
                } else {
                    $return['code']    = 103;
                    $return['msg'] = 'Incorrect current password';
                }
            } else {
                $return['code']    = 103;
                $return['msg'] = 'Incorrect current password';
            }
        } else {
            $return['code']    = 102;
            $return['msg'] = 'Not Match New Password & Confirm Password';
        }
        return json_encode($return);
    }
}
