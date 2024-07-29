<?php



namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;

use App\Models\Category;

use App\Models\User;

use App\Models\Login_log;

use App\Models\PopupMessage;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Validator;



class AppAuthControllers extends Controller

{

    public function requestReleteRemark(Request $request){

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

            'remark'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['msg'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userrecord = User::where('uid', $request->uid)->first(['uid','del_remark']);

        if (!empty($userrecord)) {

            User::where('uid', $userrecord->uid)->update(['del_remark' => $userrecord->remark]);

            $return['code'] = 200;

            $return['msg'] = 'successfully';

        } else {

            $return['code']    = 101;

            $return['msg'] = 'Data not found';

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

            $return['msg']   = 'Validation Error';

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

            $return['msg']   = 'Registered Successfully!';

        } else {

            $return['code']      = 101;

            $return['msg']   = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function update(Request $request, $uid)

    {   
     $getmessengertype =  DB::table('messengers')->where('messenger_name',$request->messenger_type)->where('status',1)->first();
       if($getmessengertype == null){
                $return['code']     = 101;
                $return['message']  = 'Something went wrong in messenger Type';
               return json_encode($return, JSON_NUMERIC_CHECK);
       }
       
        $user = User::select('id','uid','phone')->where('uid', $uid)->first();
        if($request->phone_number){
            $validator = Validator::make(
                $request->all(),
                [
                    'phone_number'       => [ 'required',
                    'numeric',
                    'between:1000,999999999999999',
                      Rule::unique('users', 'phone')->ignore( $user->id, 'id'),
                  ],
                   'first_name'       => 'required|max:120',
                    'last_name'        => 'required|max:120',
                    'address_line1'    => 'required',
                    'city'             => 'required',
                    'state'            => 'required',
                    'country'          => 'required',
                    'messenger_type'   => 'required',
                    'messenger_name'   => 'required',
                    ],[
                    'phone_number.required' => 'The phone no. must contain only Phone numeric characters.',
                    'phone_number.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                ]
            );
            if ($validator->fails()) {
                $return['code']      = 100;
                $return['msg']   = 'Validation Error';
                $return['error']     = $validator->errors();
                return json_encode($return);
            } 
        }
        $validator = Validator::make(
            $request->all(),
            [
                'first_name'       => 'required|max:120',
                'last_name'        => 'required|max:120',
                'address_line1'    => 'required',
                'city'             => 'required',
                'state'            => 'required',
                'country'          => 'required',
                'messenger_type'   => 'required',
                'messenger_name'   => 'required',
                'phone_number'     => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']      = 100;
            $return['msg']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $countryName = $request->country;
        if($request->country){
            $count_name = DB::table('countries')->select('id', 'name', 'phonecode', 'status', 'trash')->where('name',$countryName)->where('status', 1)->where('trash', 1)->first();
            if(!$count_name){
                $return['code']      = 101;
                $return['message']   = 'Country Not Exist!';
                return json_encode($return);
            }
        }
        $res = $request->all();
        $data['first_name'] =  $res['first_name'];
        //$data['profile_lock'] =  $res['profile_lock'];
       // $data['kyc_lock'] =  $res['kyc_lock'];
        $data['last_name'] =  $res['last_name'];
        $data['country'] =  $res['country'];
        $data['phone'] =  $res['phone_number'];
        $data['messenger_type'] =  $res['messenger_type'];
        $data['address_line1'] =  $res['address_line1'];
        $data['address_line2'] =  $res['address_line2'];
        $data['state'] =  $res['state'];
        $data['city'] =  $res['city'];
        $data['phonecode'] =  $res['phonecode'];
        $data['messenger_name'] =  $res['messenger_name'];
        $type = 1;
        userUpdateProfile($data,$uid,$type);
        $user                   = User::where('uid', $uid)->first();
        $user->first_name       = $request->first_name;
        $user->last_name        = $request->last_name;
        $user->phone            = $request->phone_number;
        $user->phonecode        = $count_name->phonecode;
        $user->address_line1    = $request->address_line1;
        $user->address_line2    = $request->address_line2;
        $user->city             = $request->city;
        $user->state            = $request->state;
        $user->country          = $count_name->name;
        $user->messenger_type   = $getmessengertype->messenger_name;
        $user->messenger_name   = $request->messenger_name;
        if ($user->update()) {
            $return['code']         = 200;
            $return['uid']          = $user->uid;
            $return['first_name']   = $user->first_name;
            $return['last_name']    = $user->last_name;
            $return['email']        = $user->email;
            $return['msg']      = 'Updated Successfully';
        } else {
            $return['code']     = 101;
            $return['msg']  = 'Something went wrong';
        }
        return json_encode($return);
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

            $return['msg'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $service_url = config('app.url');

        $email = base64_decode($request->email);

        $password = base64_decode($request->password);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {

            $return['code']    = 401;

            $return['msg'] = 'Cannot login with credentials';

        } else {

            if ($user->user_type == 2) {

                $return['code']    = 406;

                $return['msg'] = 'Your account is not a advertiser account !';

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

                    // $return['wallet']   = $user->wallet;

                    $wltAmt = getWalletAmount($user->uid);
                    $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

                    $return['msg'] = 'Login Successfully';

                }

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    /*############## End Advertiser Login Section  ############*/

    public function profileInfo($uid)

    {

        $user = User::select('first_name', 'last_name', 'email', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pub_wallet', 'profile_lock','phonecode','messenger_type','messenger_name','user_type')
        ->where('uid', $uid)->first();

        if ($user) {

            $return['code']    = 200;

            $return['data']    = $user;

            // $return['wallet']    = $user->wallet;

            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

            $return['msg'] = 'User profile info retrieved successfully';

        } else {

            $return['code']    = 101;

            $return['msg'] = 'Data not found';

        }



        return json_encode($return);

    }

    public function loginLog(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['msg'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userlog = Login_log::where('uid', $request->uid)->orderBy('id', 'DESC')->offset(0)->limit(10)->get()->toArray();

        if ($userlog) {

            foreach ($userlog as $value) {

                $date = date('d M Y - h:i A', strtotime($value['created_at']));

                $browsername = $value['browser_name'];

                $ipname = $value['ip_name'];

                $data[] = array('browser' => $browsername, 'ip' => $ipname, 'date' => $date);

            }

            $return['code'] = 200;

            $return['msg'] = 'successfully';

            $return['data'] = $data;

        } else {

            $return['code']    = 101;

            $return['msg'] = 'Data not found';

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

            $return['msg'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $userid = $request->uid;

        $user = User::where('uid', $userid)->first();

        if (empty($user)) {

            $return['code']    = 401;

            $return['msg'] = 'User Not Found!';

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

            // $return['wallet']  = $user->wallet;

            $wltAmt = getWalletAmount($user->uid);
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

            $return['msg'] = 'Login Successfully';

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

            $return['msg'] = 'Validation Error';

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

            // $return['wallet']   = $user->wallet;

            $wltAmt = getWalletAmount($user->uid);
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

            $return['msg'] = 'Validated success!';

        } else {

            $return['code']    = 101;

            $return['msg'] = 'Token Not Found!';

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

            $return['msg'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return);

        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            $return['code']    = 401;

            $return['msg'] = 'Invalid credentials';

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

                    $return['msg'] = 'Login Successfully';

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



    public function MessengerList()

    {

       $result = DB::table('messengers')->select('id','messenger_name')->where('status', 1)->orderByDesc('messenger_name')->get()->toArray();

       if (count($result)) {

        $return['code'] = 200;

        $return['data'] = $result;

        $return['msg'] = 'Successfully found !';

    } else {

        $return['code'] = 100;

        $return['msg'] = 'Data Not found !';

    }

     return json_encode($return);

    }
    
    // show advertiser popup message
    public function listPopupMessage(Request $request)
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

        $support = PopupMessage::select('title','sub_title','image','message','btn_content','btn_link')->where('account_type',1)->where('status',1)->where('popup_type',$request->popup_type)->first();
        if (!empty($support)) {

            $return['code']    = 200;
            $return['data']    = $support;
            $return['message'] = 'Advertiser Popup Message list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }



    /* End Publisher Login Section */

}

