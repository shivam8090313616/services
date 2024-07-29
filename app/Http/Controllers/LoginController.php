<?php



namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Login_log;
use App\Models\User;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class LoginController extends Controller

{

    public function mobileLogin(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'username'     => 'required',
            'password'  => 'required',
            'browser_name'  => 'required',
        ]);
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['msg'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return);
        }
        $service_url = config('app.url');
        $email = $request->username;
        $password = $request->password;
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            $return['code']    = 401;
            $return['msg'] = 'Invalid login credentials';
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

                $clientIP = request()->ip();
                $loginactivity                   = new Login_log();
                $loginactivity->uid              = $user->uid;
                $loginactivity->browser_name     = $request->browser_name;
                $loginactivity->ip_name          = $clientIP;
                $loginactivity->user_type        = 1;
                //user_type
                $loginactivity->save(); 
                $return['code'] = 200;
                $return['name'] =  $user->first_name . ' ' . $user->last_name;
                $return['uid'] =  $user->uid;
                $return['login_token'] = '7SAPPI3209';
                $return['msg'] = 'Login Successfully.';

                $adminmail1 = 'advertisersupport@7searchppc.com';
                //$adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
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
                $loginactivity->browser_name     = $request->browser_name;
                $loginactivity->ip_name          = $clientIP;
                $loginactivity->user_type        = 1;
                $loginactivity->save(); 
                $firstLogin = DB::table('transaction_logs')->where('advertiser_code', $user->uid)->count();
                if($firstLogin == 0){
                    $llogin = 1;
                }else{
                    $llogin = 2;
                }
                $return['code'] = 200;
                $return['name'] =  $user->first_name . ' ' . $user->last_name;
                $return['uid'] =  $user->uid;
                $return['login_token'] = '7SAPPI3209';
                $return['firstlogin']    = $llogin;
                $return['msg'] = 'Login Successfully.';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function minimumEnterAmount(){
         $amount = manageMinimumPayment();
        if ($amount) { 
            $return['code'] = 200;
            $return['data'] = $amount;
            $return['msg'] = 'Data Fund successfully!';
        } 
        return response()->json($return);
    }
    public function appVersionAdvertiser(){
        $version = '1.1.8';
       if ($version) { 
           $return['code'] = 200;
           $return['data'] = $version;
       } 
       return response()->json($return);
   }
   public function appVersionpub(){
        $version = '1.0.8';
    if ($version) { 
        $return['code'] = 200;
        $return['data'] = $version;
    } 
    return response()->json($return);
 }
 public function userTypeStatusUpdate(Request $request){
    date_default_timezone_set('Asia/Kolkata');
    $timestamp = date("Y-m-d H:i:s");
    $token = base64_encode(str_shuffle('JbrFpMxLHDnbs'.rand(1111111,9999999)));
    $updateddata = DB::table('users')->where('uid', $request->uid)->update(['user_type' => $request->utype,'remember_token' =>  $token]);
     $ulist = User::where('uid', $request->uid)->first();
     $profilelog = DB::table('profile_logs')->where('uid', $request->uid)->whereNotNull('switcher_login')->count();
     if($profilelog == 0){
            if($request->user_type == 1){
                $type = 3;
                $profileLog['switch_remark']['previous'] =  'Advertiser';
                $profileLog['switch_remark']['updated']  =  'Publisher';
                $profileLog['message']                   =  "You have registered as a publisher using your advertiser account.";
            }else{
                $type = 3;
                $profileLog['switch_remark']['previous'] =  'Publisher';
                $profileLog['switch_remark']['updated']  =  'Advertiser';
                $profileLog['message']                   =  "You have registered as an advertiser using your publisher account.";
            }
            $data = json_encode($profileLog);
            DB::table('profile_logs')->insert(['uid' => $ulist->uid,'profile_data'=>$data,'user_type'=>$type,'switcher_login'=>$timestamp,'created_at'=>$timestamp]);
     }
    if ($updateddata) { 
        $return['code'] = 200;
        $return['token']   = $token;
        $return['email']   = $ulist->email;
        $return['message'] = 'Updated Successfully Status!';
    } else {
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
    }
    return response()->json($return);
    }
    public function switchUserAccount(Request $request){
        $token = base64_encode(str_shuffle('JbrFpMxLHDnbs'.rand(1111111,9999999)));
        $updateddata = DB::table('users')->where('uid', $request->uid)->update(['remember_token' => $token]);
        $ulist = User::where('uid', $request->uid)->first();
       if ($updateddata) { 
           $return['code'] = 200;
           $return['token']   = $token;
           $return['email']   = $ulist->email;
           $return['message'] = 'Switch Accout Successfully!';
       } else {
           $return['code'] = 101;
           $return['message'] = 'Something went wrong!';
       }
       return response()->json($return);
       }
    public function getUserAccountwallet(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'uid' => "required"
            ]
        );

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $updateddata = DB::table('users')->select('pub_wallet')->where('uid', $request->uid)->first();
        if($updateddata){
            $return['code'] = 200;
            // $return['data']   = number_format($updateddata->pub_wallet, 2);
            $wltPubAmt = getPubWalletAmount($request->uid);
            $return['data']        = ($wltPubAmt) > 0 ? number_format($wltPubAmt,2) : number_format($updateddata->pub_wallet, 2);
            $return['message'] = 'Get Amount Wallet!';
        }else{
            $return['code'] = 200;
            $return['message'] = 'Something went wrong!';
        }
        
        return response()->json($return);
    }
    
    public function manageAppVersion(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'app_version_name' => 'required',
                'app_version_value' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $aid = $request->id;
        $appVersionData = [
            'app_version_name' => $request->app_version_name,
            'app_version_value' => $request->app_version_value,
            'updated_at' => date("Y-m-d H:i:s")
        ];
        AppVersion::updateOrCreate(['id' => $aid], $appVersionData);
        $return['code'] = 200;
        $return['message'] = $aid ? 'Version Updated successfully' : 'Version Created successfully';
    
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function manageAppVersionList(){
        $res = AppVersion::get();
        if($res){
            $return['data'] = $res;
            $return['row'] = count($res);
            $return['code'] = 200;
            $return['message'] = 'Data Found Successfully!';
        }else{
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function getManageAppVersion(Request $request){
        $res = AppVersion::select('id','app_version_name','app_version_value','created_at')->where('id',$request->version_id)->first();
        if($res){
            $return['data'] = $res;
            $return['code'] = 200;
            $return['message'] = 'Data Found Successfully!';
        }else{
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

}

