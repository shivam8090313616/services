<?php



namespace App\Http\Controllers;



use App\Models\Category;
use App\Models\User;
use App\Models\Country;
use App\Models\Login_log;
use App\Models\Agent;
use App\Models\PopupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;



class AuthController extends Controller
{
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
        $user = User::select('id','uid','phone')->where('uid', $uid)->first();
        if($request->phone){
            $validator = Validator::make(
                $request->all(),
                [
                    'phone'       => [ 'required','min:1000',
                    'numeric',
                    'between:1000,999999999999999',
                      Rule::unique('users', 'phone')->ignore( $user->id, 'id'),
                  ],
                    'first_name'       => 'required|max:120',
                    'last_name'        => 'required|max:120',
                    'address_line1'    => 'required',
                    'city'             => 'required',
                    'state'            => 'required',
                    'messenger_name'   => 'required',
                    'messenger_type'   => 'required',
                    'country'          => 'required',
                ],[
                    'phone.required' => 'The phone no. must contain only numeric characters.',
                    'phone.between' => 'The phone no. must contain minimum 4 and maximum 15 digits.',
                    'phone.min' => 'Ensures the phone number has at least 4 digits.',
                ]
            );
        }else{
        $validator = Validator::make(
            $request->all(),
            [
                'first_name'       => 'required|max:120',
                'last_name'        => 'required|max:120',
                'address_line1'    => 'required',
                'city'             => 'required',
                'state'            => 'required',
                'messenger_name'   => 'required',
                'messenger_type'   => 'required',
                'country'          => 'required',
                'phone'            => 'required|min:4',
            ]
        );
    }
        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
         if($request->country){
            $count_name = Country::select('id', 'name', 'phonecode', 'status', 'trash')->where('name',$request->country)->where('status', 1)->where('trash', 1)->first();
            if(!$count_name){
                $return['code']      = 101;
                $return['message']   = 'Country Not Exist!';
                return json_encode($return);
            }
        }
        $res = $request->all();
        $type = 1;
        userUpdateProfile($res,$uid,$type);
        $user                   = User::where('uid', $uid)->first();
        $user->first_name       = $request->first_name;
        $user->last_name        = $request->last_name;
      	$user->phonecode        = $count_name->phonecode;
        $user->phone            = $request->phone;
        $user->messenger_name   = $request->messenger_name;
        $user->messenger_type   = $request->messenger_type;
        $user->address_line1    = $request->address_line1;
        $user->address_line2    = $request->address_line2;
        $user->city             = $request->city;
        $user->state            = $request->state;
        $user->country          = $count_name->name;
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
  	
  	public function userFetch(Request $request)
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
      	$user = User::where('uid', $request->uid)->first();
      	if($user){
        	$userCountry = User::select('country')->where('uid', $request->uid)->first();
          	$return['code']    = 200;
          	$return['data']    = $userCountry;
            $return['message'] = 'User Country fetched!';
        }else{
          	$return['code']    = 101;
            $return['message'] = 'User Does not exist!';
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

        // if (!$user || !Hash::check($password, $user->password)) {

        //     $return['code']    = 401;

        //     $return['message'] = 'Cannot login with credentials';

        // } 

        if (!$user) {

            $return['code']    = 401;

            $return['message'] = 'This email ID is not registered with us';

        } elseif (!Hash::check($password, $user->password)) {

            $return['code']    = 401;

            $return['message'] = 'Cannot login with credentials';

        } else {

            if ($user->user_type == 2) {

                $return['code']    = 406;

                $return['message'] = 'Your account is not a advertiser account!';

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

                    $return['message'] = 'Verification mail been sent to your email address please verify your account!';

                } else {

                    $return['code'] = 201;

                    $return['message']  = 'Mail Not Send But Data Insert Successfully!';

                }

                /* Admin Section  */

                $adminmail1 = 'advertisersupport@7searchppc.com';

                $adminmail2 = 'info@7searchppc.com';

                $bodyadmin =   View('emailtemp.useradmincreate', $data);

                $subjectadmin = 'Account Created Successfully - 7Search PPC';

                $sendmailadmin =  sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

                if ($sendmailadmin == '1') {

                    $return['code']    = 402;

                    $return['message'] = 'We sent you a verification mail please verify your account before login!';

                } else {

                    $return['code'] = 201;

                    $return['message']  = 'Mail Not Send But Data Insert Successfully!';

                }

            } elseif ($user->trash == '1') {

                $return['code']    = 403;

                $return['message'] = 'Your account is removed!';

            } elseif ($user->status == '3') {

                $return['code']    = 405;

                $return['message'] = 'Your account is suspended!';

            } elseif ($user->status == '4') {

                $return['code']    = 405;

                $return['message'] = 'Your account is on hold!';

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

                    $token = base64_encode(str_shuffle('JbrFpMxLHDnbs' . rand(1111111, 9999999)));

                    $usertokenupdate = User::where('uid', $user->uid)->first();

                    $usertokenupdate->remember_token = $token;

                    $usertokenupdate->update();

                    $firstLogin = DB::table('transaction_logs')->where('advertiser_code', $user->uid)->count();

                    if($firstLogin == 0){

                        $llogin = 1;

                    }else{

                        $llogin = 2;

                    }

                    $return['code']          = 200;

                    $return['token']         = $token;

                    $return['uid']           = $user->uid;

                    $return['fname']         = $user->first_name;

                    $return['lname']         = $user->last_name;

                    $return['email']         = $user->email;

                    $return['firstlogin']    = $llogin;
                    $return['authorization'] = base64_encode($user->password);
                    $wltAmt = getWalletAmount($user->uid);

                    $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

                    $return['user_type']     = $user->account_type;

                  	$return['user_access']   = $user->user_type;

                    $return['message']       = 'Login Successfully';

                }

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    /*############## End Advertiser Login Section  ############*/

    public function profileInfo($uid)

    {
        $user = User::select('first_name', 'last_name','messenger_name','messenger_type', 'email', 'phone', 'phonecode', 'address_line1', 'address_line2', 'city', 'state', 'country', 'wallet', 'profile_lock','del_request','user_type')
            ->where('uid', $uid)->first();
      	//$user->phonecode = '+'.$user->phonecode;
        $login_as = ($user->user_type == 3) ? 'advertiser' : '';
        if ($user) {
            $return['code']    = 200;
            $return['data']    = $user;
            $return['login_as'] = $login_as;
        //   	$return['wallet']    =  number_format($user->wallet, 3, '.', '');
              $wltAmt = getWalletAmount($uid);
            $return['data']['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($user->wallet, 3, '.', '');
            $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($user->wallet, 3, '.', '');

            $return['message'] = 'User profile info retrieved successfully';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data not found';

        }

        return json_encode($return);

    }



    public function advKycInfoSwitcher(Request $request)
    {
      $user = User::select( 'photo_verified', 'photo_id_verified')
        ->where('uid', $request->uid)->where('user_type', 3)->first();
      //$user->user_photo = (strlen($user->user_photo) > 0) ? config('app.url').'kycdocument'. '/' .$user->user_photo : '';
     // $user->user_photo_id = (strlen($user->user_photo_id) > 0) ? config('app.url').'kycdocument'. '/' .$user->user_photo_id : '';
      if ($user) {
        $return['code']    = 200;
        $return['data']    = $user;
        $return['message'] = 'User Kyc info retrieved successfully';
      } else {
        $users = array('photo_verified'=>'','photo_id_verified'=> '');
        $return['code']    = 101;
        $return['data']    = $users;
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

        if($request->type == 'advertiser'){

            $userlog = Login_log::where('uid', $request->uid)->where('user_type', 1)->orderBy('id', 'DESC')->offset(0)->limit(10)->get()->toArray();

        }else if($request->type == 'publisher'){

            $userlog = Login_log::where('uid', $request->uid)->where('user_type', 2)->orderBy('id', 'DESC')->offset(0)->limit(10)->get()->toArray();

        }else{

            $userlog = Login_log::where('uid', $request->uid)->orderBy('id', 'DESC')->offset(0)->limit(10)->get()->toArray();

        }

        if ($userlog) {

            foreach ($userlog as $value) {

                $date = date('d M Y - h:i A', strtotime($value['created_at']));

                $browsername = $value['browser_name'];

                $ipname = $value['ip_name'];

                $data[] = array('browser' => $browsername, 'ip' => $ipname, 'date' => $date ,'type' =>$value['user_type']);

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
            // $clientIP = request()->ip();
            // $loginactivity                   = new Login_log();
            // $loginactivity->uid              = $user->uid;
            // $loginactivity->browser_name     = 'Chrome';
            // $loginactivity->ip_name          = $clientIP;
            // if ($loginactivity->save()) {
                $token = $user->createToken($user->email . '_Token')->plainTextToken;
                $token = base64_encode(str_shuffle('JbrFpMxLHDnbs'.rand(1111111,9999999)));

              	$usertokenupdate = User::where('uid', $user->uid)->first();

                $usertokenupdate->remember_token = $token;

                $usertokenupdate->update();

                $return['code']    = 200;

                $return['token']   = $token;

                $return['uid']     = $user->uid;

                $return['fname']   = $user->first_name;

                $return['lname']   = $user->last_name;

                $return['email']   = $user->email;
                $wltAmt = getWalletAmount($user->uid);
                $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;
                $return['user_type'] = $user->account_type;

                $return['user_access']   = $user->user_type;

                $return['message'] = 'Login Successfully';
          }
        //}
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
        // $switcherCreatedAt = User::where('remember_token', $request->token)->where('email', $request->email)->whereNull('switcher_created_at')->first();
        // if(!empty($switcherCreatedAt)){
        //     DB::table('users')->where('remember_token', $request->token)->where('email', $request->email)->update(['switcher_created_at' => date('Y-m-d H:i:s')]);
        // }
        if (!empty($user)) {
            if($request->type){
            $clientIP = request()->ip();
            $loginactivity                   = new Login_log();
            $loginactivity->uid              = $user->uid;
            $loginactivity->browser_name     = $request->browser;
            $loginactivity->ip_name          = $clientIP;
            if($request->type == 'advertiser'){
                $loginactivity->user_type         = 1;
                $loginactivity->save();
            }
            if($request->type == 'publisher'){
                $loginactivity->user_type         = 2;
                $loginactivity->save();
            }
            }
            $firstLogin = DB::table('transaction_logs')->where('advertiser_code', $user->uid)->count();
            if($firstLogin == 0){
                $llogin = 1;
            }else{
                $llogin = 2;
            } 
            $return['code']    = 200;

            $return['status'] = 1;
 
            $return['uid']     = $user->uid;

            $return['fname']   = $user->first_name;
            $return['firstlogin']    = $llogin;
            $return['lname']   = $user->last_name;
            $return['firstlogin']    = $llogin;
            $return['email']   = $user->email;
            $return['authorization'] = base64_encode($user->password);
            $wltAmt = getWalletAmount($user->uid);
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

            $return['user_access']   = $user->user_type;

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

            return json_encode($return, JSON_NUMERIC_CHECK);

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

                if($request->type == 'advertiser'){

                    $loginactivity->user_type         = 1;

                }

                if($request->type == 'publisher'){

                    $loginactivity->user_type         = 2;

                }

                if ($loginactivity->save()) {

                    $token = $user->createToken($user->email . '_Token')->plainTextToken;

                    $return['code']    = 200;

                    $return['token']   = $token;

                    $return['uid']     = $user->uid;

                    $return['fname']   = $user->first_name;

                    $return['lname']   = $user->last_name;

                    $return['email']   = $user->email;

                  	$return['account_type']   = $user->account_type;

                  	$return['user_access']   = $user->user_type;

                    $return['urole']   		  = $user->user_type;

                    $return['message'] = 'Login Successfully';

                } else {

                    $return['code'] = 100;

                    $return['msg']   = 'Not Login, Please Try Again !';

                }

            } else {

                $return['code'] = 100;

                $return['msg']  = 'Not Publisher Account, Please login on advertiser panel';

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	public function loginAsPubUser(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'uid'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return, JSON_NUMERIC_CHECK);

        }

        $userid = $request->uid;

        $user = User::where('uid', $userid)->first();

        if (empty($user)) {

            $return['code']    = 401;

            $return['message'] = 'User Not Found!';

        } else {

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
            $wltAmt = getPubWalletAmount($user->uid);
            $return['pub_wallet']   = ($wltAmt) > 0 ? $wltAmt : $user->pub_wallet;
          	$return['account_type']  = $user->account_type;

          	$return['user_access']   = $user->user_type;

            $return['message'] = 'Login Successfully';

            // }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	public function tokenPubValidate(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'email'     => 'required',

            'token'     => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return, JSON_NUMERIC_CHECK);

        }

        $user = User::where('remember_token', $request->token)->where('email', $request->email)->first();

        if (!empty($user)) {

            if(!empty($request->type)){

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

            $loginactivity->save();

        }

            $return['code']    = 200;

            $return['status'] = 1;

            $return['uid']     = $user->uid;

            $return['fname']   = $user->first_name;

            $return['lname']   = $user->last_name;

            $return['email']   = $user->email;
            $return['authorization'] = base64_encode($user->password);
            $wltAmt = getPubWalletAmount($user->uid);
            $return['pub_wallet']   = ($wltAmt) > 0 ? $wltAmt : $user->pub_wallet;
          	$return['account_type']  = $user->account_type;

          	//$return['user_access']   =  $loginactivity->user_type;

            $return['urole']   		  = $user->user_type;

            $return['message'] = 'Validated success!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Token Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
    public function updatePhonecode()

    {

      //$user = DB::table('users')->select('phone')->where('phone', '!=', '')->get();

      $users = User::where('phone', 'like', '%-%')->get();

      foreach($users as $user)

      {

        $parts = explode("-", $user->phone);

        $user->where('uid', '=', $user->uid)->where('phone', 'like', '%-%')->update([

        'phonecode' => $parts[0], // value before the "-"

        'phone'     => $parts[1], // value after the "-"

    	]);

      }

    }



    public function getIp(Request $request)

    {

      $clientIP = request()->ip();
       
      $curl = curl_init();

      curl_setopt_array($curl, array(

      CURLOPT_URL => 'http://ip-api.com/json/'. $clientIP,

      CURLOPT_RETURNTRANSFER => true,

      CURLOPT_ENCODING => '',

      CURLOPT_MAXREDIRS => 10,

      CURLOPT_TIMEOUT => 0,

      CURLOPT_FOLLOWLOCATION => true,

      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

      CURLOPT_CUSTOMREQUEST => 'GET',

      ));

      $response = curl_exec($curl);

      $getCodes =  json_decode($response);

      print_r($getCodes); exit;

    }



    public function getIps()
    { 
      $ip = '183.82.163.125';
      $loc = getCountryNameAdScript($ip);

      print_r($loc);

    }

    /* End Publisher Login Section */

    public function getUidEmail(Request $request){

        $validator = Validator::make($request->all(), [

            'email'     => 'required'

        ]);

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['message'] = 'Validation Error';

            $return['error']   = $validator->errors();

            return json_encode($return, JSON_NUMERIC_CHECK);

        }

        $user = User::select('uid','email')->where('email', $request->email)->where('status',0)->where('trash',0)->first();

        if(!empty($user)){

            $return['code']    = 200;

            $return['data']    = base64_encode($user->uid);

            $return['message'] = 'Data Found success!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Email Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    // Api for delete account request for publisher
    public function request_delete_remark(Request $request){
        $uid = $request->uid;
        $del_remark = $request->del_remark;
        $cancel_request ='';
        $del_type = '';
        $data = User::select('uid','user_type','del_request','del_remark')->where('uid', $uid)->whereIn('user_type', [1, 3])->first();
        if($data == true){
        if($data->user_type == 3 && $data->del_request == 1){
            $del_type = 3;
        }
        else if($data->user_type == 3 && $data->del_request == 2){
            $del_type = 3;
        }
        else if($data->user_type == 3 && $data->del_request == 0){
            $cancel_request = 2;
            $del_type = 1;
        }
        else if($data->user_type == 3){
            $cancel_request = 2;
        }
        else{
            $del_type = 1;
            $cancel_request = 0;
        }
                if($uid && $del_remark){
                    $validator = Validator::make($request->all(), [
                        'uid'     => 'required',
                        'del_remark'     => 'required|max:300',
                    ]);
                }else{
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

            if($data == true && !empty($uid) && !empty($del_remark)){
                // $res = $request->all();
                // $type = 1;
                // userUpdateProfile($res,$data->uid,$type);

                User::where('uid', $uid)->update(['del_remark' => $del_remark,'del_request'=>$del_type]);
                $remark = $request->del_remark;
                $full_name = $data->first_name . ' '. $data->last_name;
                  $data['details'] = ['subject' => 'Account Deletion Request', 'user_id' => $data->uid, 'full_name' => $full_name , 'remark'=>$remark];
                  $adminmail1 = 'info@7searchppc.com';
                  // $adminmail1 = ['info@7searchppc.com','testing@7searchppc.com'];
                   $adminmail2 = '';
                  $bodyadmin =   View('emailtemp.advaccountdeletionrequest', $data);
                  $subjectadmin = 'Account Deletion Of Advertiser Request - 7Search PPC';
                 sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2);
                $return['code'] = 200;
                $return['msg'] = 'Request sent successfully';
                return json_encode($return);
            }

            if($data == true && !empty($uid)){
                // $res = $request->all();
                // $res['del_remark'] = '';
                // $type = 1;
                // userUpdateProfile($res,$data->uid,$type);

                User::where('uid', $uid)->update(['del_remark' => 'Your Account is Reactivated successfully.','del_request'=>$cancel_request]);
                $full_name = $data->first_name . ' '. $data->last_name;
                $data['details'] = ['subject' => 'Account Deletion Request Withdrawn', 'user_id' => $data->uid, 'full_name' => $full_name];
                $adminmail1 = 'info@7searchppc.com';
               // $adminmail1 = ['info@7searchppc.com','testing@7searchppc.com'];
                $adminmail2 = '';
               $bodyadmin =   View('emailtemp.advaccountdeletionrequestwithdrawn', $data);
               $subjectadmin = 'Account Deletion Of Advertiser Request Withdrawn - 7Search PPC';
               sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2);
                $return['code'] = 200;
                $return['msg'] = 'Account is Reactivated successfully';
                return json_encode($return);
            }
        }else{
            $return['code']    = 101;
            $return['msg'] = 'This user type Data not found !';
            return json_encode($return);
        }
    }

    // get assign agent data on advertiser & publisher
      /**
    * @OA\Post(
    *     path="/api/user/assigned-agent",
    *     summary="Get Assign Agent data on advertiser & publisher",
    *     tags={"Assign Agents Adveriser & Publisher"},
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
    *             @OA\Property(property="message", type="string", description="Data Found Successfully!")
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
    *         description="Data Not Found!",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function getassignAgentdata(Request $request) {
        $uid = $request->uid;
        $validator = Validator::make($request->all(), [
            'uid'     => 'required'
        ]);

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['message'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return, JSON_NUMERIC_CHECK);

        }
        $assignedAgent = Agent::select('agents.name', 'agents.email', 'agents.contact_no', 'agents.agent_id', 'agents.skype_id', 'agents.telegram_id', 'agents.profile_image')
        ->leftJoin('assign_clients', 'agents.agent_id', '=', 'assign_clients.aid')
        ->where('assign_clients.cid', $uid)
        ->first();

        if ($assignedAgent) {
            $return['code']    = 200;
            $return['data']    = $assignedAgent;
            $return['message'] = 'Data Found Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Data Not Found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // get advertiser header message data
     /**
    * @OA\Post(
    *     path="/api/user/header-message",
    *     summary="Get Advertiser Header Message",
    *     tags={"Advertiser Header Message"},
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
    *             @OA\Property(property="message", type="string", description="Advertiser Header Message Data Found Successfully!")
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
    public function getHeadermsgdata() {

        $data = DB::table("header_messages")->select("header_content","slider_content","content_speed")->where(["status"=>1, "account_type"=>1])->first();
        if (!empty($data)) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['message'] = 'Advertiser Header Message Data Found Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Status is disable data not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
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
            $return['message'] = 'popup Message list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

