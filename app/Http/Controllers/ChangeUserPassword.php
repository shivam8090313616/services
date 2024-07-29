<?php



namespace App\Http\Controllers;



use Illuminate\Support\Str;



use App\Http\Controllers\Controller;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;





class ChangeUserPassword extends Controller

{



    public function change_password(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
                'current_password' => 'required|min:4',
                'new_password' => 'required|min:4',
                // 'new_password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])/'],
                'confirm_password' => ['required', 'string', 'min:4', 'same:new_password'],
            ],[
                'new_password.required' => 'The password field is required.',
                'new_password.min' => 'The password must be at least 4 characters.',
                // 'new_password.string' => 'The password must be a string.',
                // 'new_password.min' => 'The password must be at least 4 characters long.',
                // 'new_password.regex' => 'The password must contain at least one lowercase and one uppercase letter.',
                'confirm_password.required' => 'The password field is required.',
                // 'confirm_password.string' => 'The password must be a string.',
                'confirm_password.min' => 'The password must be at least 4 characters.',
                // 'confirm_password.regex' => 'The password must contain at least one lowercase and one uppercase letter.',
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
                 if($npassword == $password){
                    $return['code']    = 101;
                    $return['message'] = 'Old Password & New Password Match Not Allowed';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                 }
                $newpass = Hash::make($npassword);
                $users->password = $newpass;
                if ($users->save()) {
                    $usersdetils = User::select('first_name', 'last_name','email')->where('uid', $userid)->first();
                    $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;
                    $email = $usersdetils->email;
                    $data['details'] = ['fullname' => $fullname, 'userid' => $userid];
                    /* User Section  */
                    $subject = 'Password Updated - 7Search PPC';
                    $body =   View('emailtemp.userpasswordupdate', $data);
                    /* User Mail Section  */
                    $sendmailUser =  sendmailUser($subject,$body,$email);
                    if($sendmailUser == 1){
                        $return['code']    = 200;
                        $return['message'] = 'Mail sent & Password Change Successfully';
                    }else{
                        $return['code']    = 200;
                        $return['message'] = 'Mail Not sent & Password Change Successfully';
                    }
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
}

