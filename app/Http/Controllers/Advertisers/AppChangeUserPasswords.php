<?php

namespace App\Http\Controllers\Advertisers;

use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AppChangeUserPasswords extends Controller
{

    public function change_password(Request $request)
    {
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
            $return['msg'] = 'Your password must be minimum 8 characters long, should contain at least one capital letter, small letter, number and special character.';
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
                if (Hash::check($compassword, $users->password)) {
                    $return['code']    = 104;
                    $return['msg'] = 'No repeating passwords. Choose a new one.';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                }
                $newpass = Hash::make($npassword);
                $users->password = $newpass;
                if ($users->save()) {
                    $return['code']    = 200;
                    $return['msg'] = 'Password Chanage Successfully';
                } else {
                    $return['code']    = 103;
                    $return['msg'] = 'Not Match Password';
                }
            } else {
                $return['code']    = 103;
                $return['msg'] = 'Current Password Is Invalid';
            }
        } else {
            $return['code']    = 102;
            $return['msg'] = 'Not Match New Password & Confirm Password';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
