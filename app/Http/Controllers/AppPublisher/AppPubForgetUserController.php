<?php

namespace App\Http\Controllers\AppPublisher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Forgetpasswod;
use App\Mail\ForgetPasswUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AppPubForgetUserController extends Controller
{

    public function random()
    {
        $randomno =  'FRGT' . strtoupper(uniqid());
        $checkdata = Forgetpasswod::where('key_auth', $randomno)->count();
        if ($checkdata > 0) {
            $this->random();
        } else {
            return $randomno;
        }
    }

    public function forgetpass(Request $request)
    {
        $email = $request->input('email');
        $advertiser_url = config('app.advertiser_url');
        $user = User::where('email', $email)->first();
        if (!empty($user)) {
            $number = $this->random();
            $finalnumber = base64_encode($number);
            $newDate = date('Y-m-d H:i:s');
            $forgetpass = new Forgetpasswod();
            $forgetpass->uid = $user['uid'];
            $forgetpass->key_auth = $finalnumber;
            $forgetpass->start    = $newDate;
            $forgetpass->end      = date('Y-m-d H:i:s', strtotime($newDate . ' +10 minutes'));
            $forgetpass->date     = date('Y-m-d');
            //$forgetpass->link_url = 'https://advertiser.7searchppc.com/reset-password/' . $finalnumber . '';
            $forgetpass->link_url = $advertiser_url.'reset-password/' . $user['uid'] . '/' . $finalnumber;
            $forgetpass->status   = '1';
            $fullname = "$user->first_name $user->last_name";
            $forgetpass->save();
            $data['details']= array('subject'=>"Reset Your Password - 7Search PPC",'username'=>$forgetpass->uid,'user_names'=>$fullname,'link'=>$forgetpass->link_url);
            $subject = 'Reset Your Password - 7Search PPC';
            $body =  View('emailtemp.changepassuser', $data);
            /* User Mail Section */
            $sendmailUser =  sendmailUser($subject,$body,$user->email);
            if($sendmailUser == '1') 
            {
                $return['code'] = 200;
                  $return['data'] = $forgetpass;
                $return['message']  = 'Mail Send Successfully !';
            }
            else 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send ';
            }
        }
        else {
            $return['code'] = 101;
            $return['message'] = 'Not Found User !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function pubforgetpass(Request $request)
    {
        $email = $request->input('email');
        $publisher_url = config('app.publisher_url');
        $user = User::where('email', $email)->first();
        if (!empty($user)) {
            $number = $this->random();
            $finalnumber = base64_encode($number);
            $newDate = date('Y-m-d H:i:s');
            $forgetpass = new Forgetpasswod();
            $forgetpass->uid = $user['uid'];
            $forgetpass->key_auth = $finalnumber;
            $forgetpass->start    = $newDate;
            $forgetpass->end      = date('Y-m-d H:i:s', strtotime($newDate . ' +10 minutes'));
            $forgetpass->date     = date('Y-m-d');
            $forgetpass->link_url = $publisher_url.'reset-password/' . $user['uid'] . '/' . $finalnumber;
            $forgetpass->status   = '1';
            $fullname = "$user->first_name $user->last_name";
            $forgetpass->save();
              $data['details']= array('subject'=>"Reset Your Password - 7Search PPC",'username'=>$forgetpass->uid,'user_names'=>$fullname,'link'=>$forgetpass->link_url);
            $subject = 'Reset Your Password - 7Search PPC';
            $body =  View('emailtemp.changepasspubuser', $data);
            /* User Mail Section */
            $sendmailUser =  sendmailUser($subject,$body,$user->email);
            if($sendmailUser == '1') 
            {
                $return['code'] = 200;
                  $return['data'] = $forgetpass;
                $return['message']  = 'Mail Send Successfully !';
            }
            else 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send ';
            }
           
         }
        else {
            $return['code'] = 101;
            $return['message'] = 'Not Found User !';
        }
        return json_encode($return,JSON_NUMERIC_CHECK);
    }
    
    public function forgetPassValidateAuthKey(Request $request)
    {
        $uid = $request->uid;
        $authkey = $request->authkey;
        $count = Forgetpasswod::where('key_auth', $authkey)->where('uid', $uid)->count();
        if($count > 0)
        {
           $return['code'] = 200;
           $return['status'] = 1;
           $return['message']  = 'Validate successfully!'; 
        }
        else {
            $return['code'] = 101;
            $return['status'] = 0;
            $return['message'] = 'Key Not found in database !';
        }
        
        return json_encode($return, JSON_NUMERIC_CHECK);
        
    }

    public function forgetpassword($value = '')
    {
        $usersf = Forgetpasswod::where('key_auth', $value)->first();
        if (!empty($usersf)) {
            $timestapm1 = strtotime($usersf['start']);
            $timestamp2 = strtotime($usersf['end']);
            $ctimestapm1 = date('Y-m-d H:i:s');
            $cutimestapm1 = strtotime($ctimestapm1);
            if ($cutimestapm1 < $timestamp2) {
                $return['code'] = 200;
                $return['data'] = $usersf;
            } else {
                $return['code'] = 101;
                $return['message'] = 'Expire Link, please again the recent link !';
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found URL !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function saveforgetord(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'new_pass' => 'required',
            'conf_pass' => 'required',
            'authkey'       => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $authkeys = $request->input('authkey');
        $usersf = Forgetpasswod::where('key_auth', $authkeys)->first();
        
        $uid = $usersf['uid'];
        $useredit = User::where('uid', $uid)->first();
        $newpass = $request->input('new_pass');
        $confpass = $request->input('conf_pass');
        if(Hash::check($newpass, $useredit->password))
        {
           $return['code'] = 201;
           $return['message'] = 'You can not use old password. Please try new password !'; 
           return json_encode($return, JSON_NUMERIC_CHECK);
        }
        if ($newpass == $confpass) {
            if ($usersf['status'] == 1) {
                $timestapm1 = strtotime($usersf['start']);
                $timestamp2 = strtotime($usersf['end']);
                $ctimestapm1 = date('Y-m-d H:i:s');
                $cutimestapm1 = strtotime($ctimestapm1);
                if ($cutimestapm1 < $timestamp2) {
                    $useredit->password =  Hash::make($newpass);
                    if ($useredit->save()) {
                        if($useredit->user_type == 2)
                        {
                            $fullname = "$useredit->first_name $useredit->last_name";
                            $data['details']= array('subject'=>"Password Change Sucessfully - 7Search PPC", 'uid'=>$useredit->uid,'user_names'=>$fullname);
                            $subject = 'Reset Your Password - 7Search PPC';
                            $body =  View('emailtemp.pubpasswordupdate', $data);
                            sendmailUser($subject,$body,$useredit->email);                
                        }
                        $usersf->status = 2;
                        $usersf->save();
                        $return['code']  = 200;
                        $return['message'] = 'Password Change Sucessfully';
                    }
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Expire Link, please again the recent link !';
                }
            } else {
                $return['code'] = 101;
                $return['message'] = 'Already Change Password !';
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not match New Password & Confirm Password !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
