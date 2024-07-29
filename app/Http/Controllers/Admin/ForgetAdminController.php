<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Forgetpasswod;
use App\Mail\ForgetPassw;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ForgetAdminController extends Controller
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
        $admin_url = config('app.admin_url');

        $user = Admin::where('email', $email)->first();
        //    print_r($user); exit;
        if (!empty($user)) {
            $number = $this->random();
            $finalnumber = base64_encode($number);
            $newDate = date('Y-m-d H:i:s');
            $forgetpass = new Forgetpasswod();
            $forgetpass->uid = $user['id'];
            $forgetpass->key_auth = $finalnumber;
            $forgetpass->start    = $newDate;
            $forgetpass->end      = date('Y-m-d H:i:s', strtotime($newDate . ' +10 minutes'));
            $forgetpass->date     = date('Y-m-d');
            $forgetpass->link_url = $admin_url.'reset-password/' . $finalnumber . '';
            $forgetpass->status   = '1';
            $forgetpass->save();
            $details = [
                'subject' => 'Change Password !',
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'link' => $forgetpass->link_url,
            ];
            $usermail =  $user->email;
            //   $mailTo = ['abul.logilite@gmail.com'];
            $mailTo = [$usermail];
            Mail::to($mailTo)->send(new ForgetPassw($details));
            $return['code'] = 200;
            $return['data'] = $forgetpass;
            $return['msg'] = 'Mail send Successfully.';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found User !';
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
        $newpass = $request->input('new_pass');
        $confpass = $request->input('conf_pass');
        if ($newpass == $confpass) {
            $authkeys = $request->input('authkey');
            $usersf = Forgetpasswod::where('key_auth', $authkeys)->first();
            if ($usersf['status'] == 1) {
                $timestapm1 = strtotime($usersf['start']);
                $timestamp2 = strtotime($usersf['end']);
                $ctimestapm1 = date('Y-m-d H:i:s');
                $cutimestapm1 = strtotime($ctimestapm1);
                if ($cutimestapm1 < $timestamp2) {
                    $uid = $usersf['uid'];
                    $useredit = Admin::where('id', $uid)->first();
                    $useredit->password =  Hash::make($newpass);
                    if ($useredit->save()) {
                        $usersf->status = 2;
                        $usersf->save();
                        $return['code']  = 200;
                        $return['data']  = 4555;
                        $return['message'] = 'Password Change Sucessfully';
                    }
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Expire Link, please again the recent link !';
                }
            } else {
                $return['code'] = 101;
                $return['message'] = 'Allready Change Password !';
            }
        } else {

            $return['code'] = 101;
            $return['message'] = 'Not match New Password & Confirm Password !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
