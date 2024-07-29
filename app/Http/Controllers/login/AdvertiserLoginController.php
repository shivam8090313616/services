<?php

namespace App\Http\Controllers\login;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\RoleManagement;
use App\Models\Admin;
use App\Models\Publisher\AdminLoginLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AdvertiserLoginController extends Controller
{

    public function login(Request $request)
    {
        $token = Str::random(60);
      	$ipadr = $_SERVER['REMOTE_ADDR'];
        $apitoken = hash('sha256', $token);
        $validator = Validator::make($request->all(),
         [
             'username' => 'required',
             'password' => 'required'
         ]);
         if($validator->fails())
         {
             $return ['code']    = 100;
             $return ['error']   = $validator->errors();
             $return ['message'] = 'Validation Error!';
             return json_encode($return, JSON_NUMERIC_CHECK);
         }
         $username = $request->input('username');
         $users = Admin::where('username', $username)->where('status',1)->first();
         if (empty($users)) 
         {
            $return['code'] = 101;
            $return['msg'] = 'User id is invalid or not registered!';
            return response()->json($return);
         }
         $password = $request->input('password');
         $mytime = Carbon::now();   
         if (Hash::check($password, $users->password)) 
         {
           if ($users->id) 
           { 
                $accessRole =RoleManagement::select('id','role_name','role_permission')->where('id',$users->role_id)->first();
                $users->remember_token= $apitoken.'.'.$users->id;
                $users->last_login = $mytime; 
                if($users->save())
                {
                  	$adminLog = new AdminLoginLog;
                  	$adminLog->admin_id = $users->id;
                  	$adminLog->username = $users->username;
                    $adminLog->email = $users->email;
                    $adminLog->ip_addrs = $ipadr;
                    $adminLog->auth_token = $apitoken;
                  	$adminLog->created_at = $mytime;
                  	$adminLog->save();
                    $return['code'] = 200;
                    $return['msg'] = 'Login Successfully.';
                    // $return['token'] = $apitoken.'.'.$users->id;
                    $return['token'] = $apitoken.'.'.base64_encode($users->id);
                    $return['acesspass'] = base64_encode($password);
                    $return['accessRole'] = $accessRole;
                    $return['name'] = $users->name;
                    $return['username'] = $users->username;
                    $return['email'] =  $users->email;
                    $return['utype'] =  $users->user_type;
                    return response()->json($return);
                }            
           }
         }
         else
         {
             $return['code'] = 101;
             $return['msg'] = 'Username is invalid  Incorrect!';
             return response()->json($return);
         }
    }

  	public function tokenUpdate (Request $request)

    {

      $validator = Validator::make($request->all(),

                                   [

                                     'access_token' => 'required',

                                     'noti_token' => 'required',

                                   ]);

      if($validator->fails())

      {

        $return ['code']    = 100;

        $return ['error']   = $validator->errors();

        $return ['message'] = 'Validation Error!';

        return json_encode($return, JSON_NUMERIC_CHECK);

      }

      $adminlog = AdminLoginLog::where('auth_token', $request->access_token)->first();

      

      if(!empty($adminlog))

      {

        $adminlog->noti_token = $request->noti_token;

        if($adminlog->save())

        {

           $return ['code']    = 200;

           $return ['message'] = 'Noti token updated successfully';

        }

        else

        {

          $return ['code']    = 101;

          $return ['message'] = 'Something went wrong!';

        }

      }

      else

      {

        $return ['code']    = 101;

        $return ['message'] = 'Something went wrong!';

      }

      	

      	return json_encode($return, JSON_NUMERIC_CHECK);

    }

    public function change_password(Request $request)

    {



         $validator = Validator::make($request->all(),

         [

             'userid' => 'required',

             'current_password' => 'required',

             'new_password' => 'required',

             'confirm_password' => 'required',

         ]);

         if($validator->fails())

         {

             $return ['code']    = 100;

             $return ['error']   = $validator->errors();

             $return ['message'] = 'Validation Error!';

             return json_encode($return, JSON_NUMERIC_CHECK);

         }

        $userid = $request->input('userid');
        $uid = explode(".",$userid);
        $uidn = base64_decode($uid[1]);
        $users = Admin::where('id', $uidn)->first();
         if (empty($users)) 

         {

             $return['code'] = 101;

             $return['msg'] = 'User id is invalid or not registered!';

             return response()->json($return);

         }

         $password = $request->input('current_password');

         $npassword = $request->input('new_password');

         $compassword = $request->input('confirm_password');

         if($npassword == $compassword)

         {

            if (Hash::check($password, $users->password)) 

              {

                $newpass = Hash::make($npassword);

                $users->password= $newpass;

                if($users->save())

                {

                    $return ['code']    = 200;

                    $return ['message'] = 'Password Chanage Successfully';

                }

                else

                {

                    $return ['code']    = 103;

                    $return ['message'] = 'Not Match Password';

                }

              } 

              else

              {

                 $return ['code']    = 103;

                 $return ['message'] = 'Not Match Password';

              }

         } 

         else 

         {

            $return ['code']    = 102;

            $return ['message'] = 'Not Match New Password & Confirm Password';

         }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function mobileLogin(Request $request)

    {

        $token = Str::random(60);

        $apitoken = hash('sha256', $token);

        $validator = Validator::make($request->all(),

         [

             'username' => 'required',

             'password' => 'required'

         ]);

         if($validator->fails())

         {

             $return ['code']    = 100;

             $return ['error']   = $validator->errors();

             $return ['message'] = 'Validation Error!';

             return json_encode($return);

         }

         $username = ($request->input('username'));

         $users = User::where('user_name', $username)->first();

         if (empty($users)) 

         {

            $return['code'] = 101;

            $return['msg'] = 'User id is invalid or not registered!';

            return response()->json($return);

         }

         $password = $request->input('password');

         $mytime = Carbon::now();     

       if (Hash::check($password, $users->password)) 

       { 

            $rendumnumber = rand(111111,999999);

            $rendHash = Hash::make($rendumnumber);

            $userslog = User::where('user_name', $username)->first();

            $userslog->login_token = $rendHash;

           if ($userslog->id) 

           {    

                $userslog->remember_token= $apitoken;

                $userslog->last_login = $mytime; 

                if($userslog->save())

                {

                    $return['code'] = 200;

                    $return['msg'] = 'Login Successfully.';

                    //$return['token'] = $apitoken;

                    $return['name'] =  $users->first_name .' '. $users->last_name;

                    $return['uid'] =  $users->uid;

                    $return['login_token'] =  $rendumnumber;

                    return response()->json($return);

                }            

           }



         }

         else

         {

             $return['code'] = 101;

             $return['msg'] = 'Username is invalid  Incorrect!';

             return response()->json($return);

         }

    }

}

