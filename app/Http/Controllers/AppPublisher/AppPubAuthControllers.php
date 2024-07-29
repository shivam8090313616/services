<?php



namespace App\Http\Controllers\AppPublisher;

use App\Http\Controllers\Controller;

use App\Models\Category;

use App\Models\User;

use App\Models\Login_log;

use App\Models\PopupMessage;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;



class AppPubAuthControllers extends Controller

{

    public function requestReleteRemark(Request $request){

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

            'remark'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userrecord = User::where('uid', $request->uid)->first(['uid','del_remark']);

        if (!empty($userrecord)) {

            User::where('uid', $userrecord->uid)->update(['del_remark' => $userrecord->remark]);

            $return['code'] = 200;

            $return['message'] = 'successfully';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data not found';

        }

        return json_encode($return);

    }



    public function register(Request $request)

    {



        $validator = Validator::make(

            $request->all(),

            [

                'first_name'       => 'required|max:120',

                'last_name'        => 'required|max:120',

                'email'            => 'required|email|unique:users,email',

                'password'         => 'required|confirmed',

                'website_category' => 'required',

                'user_type'        => 'required',

                'wallet'           => 'required',

            ]

        );



        if ($validator->fails()) {

            $return['code']      = 100;

            $return['message']   = 'Validation Error';

            $return['error']     = $validator->errors();

            return json_encode($return);

        }

        $data = new User();

        $data->uid              = 'ADV' . strtoupper(uniqid());

        $data->first_name       = $request->first_name;

        $data->last_name        = $request->last_name;

        $data->email            = $request->email;

        $data->phone            = $request->phone;

        $data->password         = Hash::make($request->password);

        $data->website_category = $request->website_category;

        $data->user_type        = $request->user_type;

        $data->account_type     = '0';

        $data->wallet           = $request->wallet;

        $data = User::create([

            'uid'              => 'ADV' . strtoupper(uniqid()),

            'first_name'       => $request->first_name,

            'last_name'        => $request->last_name,

            'email'            => $request->email,

            'phone'            => $request->phone,

            'password'         => Hash::make($request->password),

            'website_category' => $request->website_category,

            'user_type'        => $request->user_type,

            'account_type'     => '2',

            'wallet'           => $request->wallet,

        ]);

        //$token = $data->createToken($data->email . '_Token')->plainTextToken;

        if ($data) {

            $return['code']      = 200;

            //$return['token']     = $token;

            $return['data']      = $data;

            $return['message']   = 'Registered Successfully!';

        } else {

            $return['code']      = 101;

            $return['message']   = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function update(Request $request, $uid)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'first_name'       => 'required|max:120',

                'last_name'        => 'required|max:120',

                'address_line1'    => 'required',

                'city'             => 'required',

                'state'            => 'required',

                'country'          => 'required',

            ]

        );

        if ($validator->fails()) {

            $return['code']      = 100;

            $return['message']   = 'Validation Error';

            $return['error']     = $validator->errors();

            return json_encode($return);

        }

        $user                   = User::where('uid', $uid)->first();

        $user->first_name       = $request->first_name;

        $user->last_name        = $request->last_name;

        $user->phone            = $request->phone;

        $user->address_line1    = $request->address_line1;

        $user->address_line2    = $request->address_line2;

        $user->city             = $request->city;

        $user->state            = $request->state;

        $user->country          = $request->country;

        if ($user->update()) {

            $return['code']         = 200;

            $return['uid']          = $user->uid;

            $return['first_name']   = $user->first_name;

            $return['last_name']    = $user->last_name;

            $return['email']        = $user->email;

            $return['message']      = 'Updated Successfully';

        } else {

            $return['code']     = 101;

            $return['message']  = 'Something went wrong';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    /* Open Advertiser Login Section */

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

            return json_encode($return);

        }

        $service_url = config('app.url');

        $email = base64_decode($request->email);

        $password = base64_decode($request->password);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {

            $return['code']    = 401;

            $return['message'] = 'Cannot login with credentials';

        } else {

            if ($user->user_type == 2) {

                $return['code']    = 406;

                $return['message'] = 'Your account is not a advertiser account !';

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
                // $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
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

                if ($loginactivity->save()) {

                    // $token = $user->createToken($user->email . '_Token')->plainTextToken;

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

                    $return['wallet']   = $user->wallet;

                    $return['message'] = 'Login Successfully';

                }

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    /*############## End Advertiser Login Section  ############*/

    public function profileInfo($uid)

    {

        $user = User::select('first_name', 'last_name', 'email', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'country', 'wallet')

            ->where('uid', $uid)->first();

        if ($user) {

            $return['code']    = 200;

            $return['data']    = $user;

            $return['wallet']    = $user->wallet;

            $return['message'] = 'User profile info retrieved successfully';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data not found';

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    public function loginLog(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userlog = Login_log::where('uid', $request->uid)->orderBy('id', 'DESC')->offset(0)->limit(10)->get()->toArray();

        if ($userlog) {

            foreach ($userlog as $value) {

                $date = date('d M Y - H:i A', strtotime($value['created_at']));

                $browsername = $value['browser_name'];

                $ipname = $value['ip_name'];

                $data[] = array('browser' => $browsername, 'ip' => $ipname, 'date' => $date);

            }

            $return['code'] = 200;

            $return['message'] = 'successfully';

            $return['data'] = $data;

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data not found';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function loginasuser(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userid = $request->uid;

        $user = User::where('uid', $userid)->first();

        if (empty($user)) {

            $return['code']    = 401;

            $return['message'] = 'User Not Found!';

        } else {

            //$clientIP = request()->ip();

            // $loginactivity                   = new Login_log();

            // $loginactivity->uid              = $user->uid;

            // $loginactivity->browser_name     = $request->browser;

            // $loginactivity->ip_name          = $clientIP;

            //if ($loginactivity->save()) {

            // $token = $user->createToken($user->email . '_Token')->plainTextToken;

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

            $return['wallet']  = $user->wallet;

            $return['message'] = 'Login Successfully';

            // }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function tokenValidate(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'email'     => 'required',

            'token'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $user = User::where('remember_token', $request->token)->where('email', $request->email)->first();

        if (!empty($user)) {

            $return['code']    = 200;

            $return['status'] = 1;

            $return['uid']     = $user->uid;

            $return['fname']   = $user->first_name;

            $return['lname']   = $user->last_name;

            $return['email']   = $user->email;

            $return['wallet']   = $user->wallet;

            $return['message'] = 'Validated success!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Token Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }





    /* Open Publisher Login Section */

    public function publogin(Request $request)

    {



        $validator = Validator::make($request->all(), [

            'email'     => 'required',

            'password'  => 'required'

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            $return['code']    = 401;

            $return['message'] = 'Invalid credentials';

        } else {

            if ($user->user_type >= '2') {

                $clientIP = request()->ip();

                $loginactivity                   = new Login_log();

                $loginactivity->uid              = $user->uid;

                $loginactivity->browser_name     = $request->browser;

                $loginactivity->ip_name          = $clientIP;

                if ($loginactivity->save()) {

                    $token = $user->createToken($user->email . '_Token')->plainTextToken;

                    $return['code']    = 200;

                    $return['token']   = $token;

                    $return['uid']     = $user->uid;

                    $return['fname']   = $user->first_name;

                    $return['lname']   = $user->last_name;

                    $return['email']   = $user->email;

                    $return['message'] = 'Login Successfully';

                } else {

                    $return['code'] = 100;

                    $return['msg']   = 'Not Login, Please Try Again !';

                }

            } else {

                $return['code'] = 100;

                $return['msg']  = 'Not Publisher Account, Please Login Advertiser Login';

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function getIp(Request $request)

    {

        $clientIP = request()->ip();

        return $clientIP;

    }
    
    // publisher popup message list
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
            $return['message'] = 'Publisher Popup Message list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /* End Publisher Login Section */

}

