<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\CreateAdminMail;
use App\Mail\CreateUserMail;
use App\Mail\UserStatusMail;
use Illuminate\Support\Facades\Validator;

class UserAdminController extends Controller
{
    
    public function usersList(Request $request)
    {
        $sort_order = $request->sort_order;
        $col = $request->col;
        $categ = $request->cat;
        //$messengerType = $request->messenger_type; DATE_FORMAT(ss_users.created_at,'%d-%m-%Y %h:%i %p') as create_date
      	$type = $request->acnt_type;
        $status_type = $request->status_type;
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate =  date('Y-m-d',strtotime($request->endDate));
        $source = $request->source;
        $src = $request->src;
        $limit = $request->lim;
        $page = $request->pg;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        // $userlist = DB::table('users')
        //     ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, (select count(id) from ss_campaigns camp_ck where camp_ck.advertiser_code = ss_users.uid AND trash=0 ) as cmpcount,ss_users.created_at"), 'users.auth_provider', 'users.email', 'users.user_type', 'users.website_category', 'users.status', 'users.ac_verified', 'users.country', 'users.uid', 'categories.cat_name','users.messenger_name','users.messenger_type','users.phone')
        //     ->where('users.trash', 0)
        //     ->join('categories', 'users.website_category', '=', 'categories.id')
        //     ->where('users.account_type', $type)
        //     ->where('users.user_type', '!=', $request->usertype);
        $userlist = DB::table('users')
        ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, (select count(id) from ss_campaigns camp_ck where camp_ck.advertiser_code = ss_users.uid AND trash=0 ) as cmpcount"), 'users.auth_provider', 'users.email', 'users.user_type', 'users.website_category', 'users.status', 'users.ac_verified', 'users.country', 'users.uid', 'categories.cat_name','users.messenger_name','users.messenger_type','users.phone','users.wallet','users.created_at','sources.title as source_title')
        ->selectRaw('(SELECT COUNT(*) FROM ss_assign_clients WHERE ss_assign_clients.cid = ss_users.uid) as agent_count')
        ->where('users.trash', 0)
        ->join('categories', 'users.website_category', '=', 'categories.id')
        ->join('sources', 'users.auth_provider', '=', 'sources.source_type')
        ->where('users.account_type', $type)
        ->where('users.user_type', '!=', $request->usertype);

        if ($categ) {
            $userlist->where('users.website_category', $categ);
        }
        if ($source) {
            $userlist->where('users.auth_provider', $source);
        }
      	if ($categ && $status_type) {
            $userlist->where('users.website_category', $categ)->where('users.status', $status_type);
        }
        if($startDate && $endDate && !$src){
            $userlist->whereDate('users.created_at', '>=', $nfromdate)
            ->whereDate('users.created_at', '<=', $endDate);
      }
      	if ($src) {
            $userlist->where(function ($query) use ($src) {
                $query->where(function ($query) use ($src) {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$src}%"]);
                })
                ->orWhere('email', 'like', "%{$src}%")
                ->orWhere('uid', 'like', "%{$src}%")
                ->orWhere('auth_provider', 'like', "%{$src}%")
                ->orWhere('messenger_name', 'like', "%{$src}%")
                ->orWhere('messenger_type', 'like', "%{$src}%")
                ->orWhere('phone', 'like', "%{$src}%")
                ->orWhere('country', 'like', "%{$src}%");
            });
            // $userlist->whereRaw('concat(ss_users.first_name," ",ss_users.last_name," ",ss_users.email, ss_users.uid, ss_users.auth_provider, ss_users.phone, ss_users.country,ss_users.messenger_name, ss_users.messenger_type) like ?', "%{$src}%");
            // $userlist->whereRaw('concat(ss_users.first_name," ",ss_users.last_name," ",ss_users.email, ss_users.uid, ss_users.auth_provider,ss_users.messenger_name,ss_users.messenger_type,ss_users.phone,ss_users.country) like ?', "%{$src}%");
        }
        if ($status_type > 0 && $status_type < 9) {
            $userlist->where('users.status', $status_type);
        } elseif ($status_type == '0') {
            $userlist->where('users.status', 0);    
        }
      	$row = $userlist->count();
      	
    //   	if($col)
    //     {
    //         $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.'.$col, $sort_order)->get();
    //     }
    //     else
    //     {
    //         $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.id', 'desc')->get();
    //     }
        if($col)
        { 
            if($col == 'total_campcount'){
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('cmpcount', $sort_order)->get();
            }else{
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.'.$col, $sort_order)->get();
            }
        }
        else{
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.created_at', 'desc')->get();
        }
        //$data = $userlist->offset($start)->limit($limit)->orderBy('users.id', 'desc')->get();
        if ($userlist) {
            $return['code']        = 200;
            $return['data']        = $data;
          	$return['row']         = $row;
            $return['message']     = 'Users List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function userDetail(Request $request)
    {
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $userdetail = DB::table('users')
            ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name"), 
            'users.id', 'users.email', 'users.user_type', 'users.website_category', 'users.status', 'users.wallet', 'users.phone', 'users.uid', 'users.address_line1', 
            'users.city', 'users.state', 'users.country', 'users.account_type', 'users.ac_verified', 'users.created_at', 'categories.cat_name','users.messenger_name','users.messenger_type')
            ->where('users.trash', 0)
            ->join('categories', 'users.website_category', '=', 'categories.id')
            ->where('users.uid', $uid)
            ->first();
            $user_campaign = DB::table('campaigns')
            ->select(DB::raw("(select IFNULL(sum(clicks),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click,
            (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as imprs"), 'campaigns.campaign_id', 
            'campaigns.campaign_name', 'campaigns.ad_type', 'campaigns.campaign_type', 'campaigns.status', 'campaigns.daily_budget', 'campaigns.website_category', 
            'campaigns.country_name', 'categories.cat_name')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaigns.trash', 0)
            ->where('advertiser_code', $userdetail->uid)
            ->orderBy('campaigns.id', 'desc');
        $row = $user_campaign->count();
        $data = $user_campaign->offset($start)->limit($limit)->get();

        if ($userdetail) {
            $return['code']        = 200;
            $userdetail->campaigns  = $data;
            $return['data']        = $userdetail;
            $return['row']         = $row;
            $return['message']     = 'Users List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function deleteUser(Request $request)
    {
        $uid = $request->uid;
        $type = $request->user_tp;
        $user = User::where('uid', $uid)->first();
        
        if($user->user_type == 3)
        {
            if($type == 1)
            {
                $user->user_type = 2;
            }
            else{
                $user->user_type = 1;
            }
            
        }
        else
        {
            $user->trash = 1;
        }

        if ($user->update()) {
    /* This will remove all data of a user from Redis */
        updateUserCampsAdunits($uid, 1);
            $return['code']    = 200;
            $return['message'] = 'User deleted successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    //========== Performing bulk actions on multiple users ===============//

    public function bulkMultipleAction(Request $request){
        $actionType = $request->action;
        $ids = $request->uid;
        $count = 0;
        if($actionType == 'active'){
            $user = User::whereIn('uid', $ids)->update(['status' => 0]);
            $count++;
        } 
        else if($actionType == 'hold'){
            $user = User::whereIn('uid', $ids)->where('status','!=',3)->update(['status' => 4]);
            $count++;
        }
        else if($actionType =='suspend'){ 
            $user = User::whereIn('uid', $ids)->update(['status' => 3]);
            $count++;
        } 
        if ($count > 0) {
        /* This will bulk update users data into Redis */
            updateBulkUserCampsAdunits($ids, $actionType);
            $return['code']    = 200;
            $return['data']    = $user;
            $return['message'] = 'Updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    function updateUserStatus(Request $request)
    {
        $uid = $request->uid;
        $newStatus = $request->status;
        $user =  User::where('uid', $uid)->first();

        $user->status = $newStatus;

        if ($user->update()) {
            if ($newStatus == 0) {
                /* Create Campaign Send Mail   */
                $email = $user->email;
                $sts = 'Active';
                $fullname = "$user->first_name $user->last_name";
                $data['details'] = array('subject' => 'Account Activated - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);
                $subject = 'Account Activated - 7Search PPC';
                $body =  View('emailtemp.userupdstatus', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message'] = 'Mail Not Send !';
                }
                /* Create Send Mail */
            } elseif ($newStatus == 1) {

               $email = $user->email;
                $sts = 'Inactive';
                $fullname = "$user->first_name $user->last_name";
                $data['details'] = array('subject' => 'Account on Inactive - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);
                $subject = 'Account on Inactive - 7Search PPC';
                $body =  View('emailtemp.userupdstatus', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message'] = 'Mail Not Send !';
                }
            } elseif ($newStatus == 2) {

                $email = $user->email;
                $sts = 'Pending';
                $fullname = "$user->first_name $user->last_name";
                $data['details'] = array('subject' => 'Account on Pending - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);
                $subject = 'Account on Pending - 7Search PPC';
                $body =  View('emailtemp.userupdstatus', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message'] = 'Mail Not Send !';
                }
            } elseif ($newStatus == 3) {
                $email = $user->email;
                $sts = 'Suspended';
                $fullname = "$user->first_name $user->last_name";
                $data['details'] = array('subject' => 'Account Suspended - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);
                $subject = 'Account Suspended - 7Search PPC';
                $body =  View('emailtemp.userupdstatus', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message'] = 'Mail Not Send !';
                }
            } elseif ($newStatus == 4) {

                $email = $user->email;
                $sts = 'Hold';
                $fullname = "$user->first_name $user->last_name";
                $data['details'] = array('subject' => 'Accoun on Hold - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);
                $subject = 'Account on Hold - 7Search PPC';
                $body =  View('emailtemp.userupdstatus', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $user;
                    $return['message'] = 'Mail Not Send !';
                }
            } else {

                $return['code']    = 200;
                $return['data']    = $user;
                $return['message'] = 'User Status updated!';
            }
        /* This will remove all user data from Redis */
        updateUserCampsAdunits($uid, $newStatus);
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updateUserAcountType(Request  $request)
    {
        $acntupdate = User::where('uid', $request->uid)->first();
        $acntupdate->account_type = $request->acount_type;
        if ($acntupdate->update()) {
            $return['code'] = 200;
            $return['data'] = $acntupdate;
            $return['message'] = 'User acount type updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function emailVerificationUpdate(Request $request)
    {
        $uid = $request->uid;
      	$verification = $request->status;
        $user = User::where('uid', $uid)->first();
        $user->ac_verified = $verification;

        if ($user->update()) {
            $return['code']    = 200;
            $return['message'] = 'User email verification status update successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return);
    }


    public function addnewusers(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name'     => 'required|alpha|max:20',
                'last_name'  => 'required|max:20',
                'email'  => 'required|email|unique:users,email',
                'phone'  => 'required|numeric|unique:users,phone,max:15',
                'user_type'  => 'required',
                'website_category'  => 'required',
                'address_line1'  => 'required',
                'city'  => 'required',
                'state'  => 'required',
                'country_list'  => 'required',
                'password' => ['required','min:8','regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                
            ],
            [ 
                'first_name.required' => 'Please Enter First Name',
                'first_name.alpha' => 'First Name Only Contain Letters',
                'last_name.required' => 'Please Enter Last Name',
                'email.required' => 'Please Enter Email Address',
                'phone.required' => 'Please Enter Phone No.',
                'user_type.required' => 'Please Enter User Account Type',
                'website_category.required' => 'Please select website Category',
                'address_line1.required' => 'Please Enter address line 1',
                'city.required' => 'Please Enter city',
                'state.required' => 'Please Enter state',
                'country_list.required' => 'Please select country',
                'password.required' => 'Please enter password',
                'password.regex' => 'Password should contains both upper and lowercase and 1 special character and one number',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }
        $user_type = $request->input('user_type');
        if ($user_type == 1) {
            $advcode = 'AD';
            $uid = randomuid($advcode);
        } elseif ($user_type == 2) {
            $pubcode = 'PUB';
            $uid = randomuid($pubcode);
        } else {
            $bthcode = 'BTH';
            $uid = randomuid($bthcode);
        }
        $service_url = config('app.url');
        $regData = new User;
        $regData->auth_provider = 'admin';
        $regData->first_name = strtoupper($request->input('first_name'));
        $regData->last_name = strtoupper($request->input('last_name'));
        $regData->email  = $request->input('email');
        $regData->phone = $request->input('phone');
        $regData->phonecode = '+'.$request->input('phonecode');
        $regData->website_category = $request->input('website_category');
        $regData->address_line1 = $request->input('address_line1');
        $regData->address_line2 = $request->input('address_line2');
        $regData->city = $request->input('city');
        $regData->state = $request->input('state');
        $regData->country = $request->input('country_list');
        $regData->password = Hash::make($request->input('password'));
        $regData->user_type = $user_type;
        $regData->uid = $uid;
        if ($request->verifymailuser == 'yes') {
            $regData->status = 2;
            $regData->ac_verified = 0;
        } else {
            $regData->status = 0;
            $regData->ac_verified = 1;
        }
        if ($regData->save()) {

            if ($request->verifymailuser == 'yes') {
                
            $email = $regData->email;
            $regDatauid = $regData->uid;
            $fullname = "$regData->first_name $regData->last_name";
            $ticketno = $regData->first_name;
            $urllink = base64_encode($regData->uid);
            $link = $service_url."verification/user/$urllink";
            $data['details'] = ['subject' => 'User Created Successfully', 'email' => $email, 'user_id' => $regDatauid, 'full_name' => $fullname, 'link' => $link];
            /* User Section */
            $subject = 'Account Created Successfully - 7Search PPC';
            $body =  View('emailtemp.usercreate', $data);
            /* User Mail Section */
            $sendmailUser =  sendmailUser($subject,$body,$email);
            if($sendmailUser == '1') 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Send & Data Inserted Successfully !';
            }
            else 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send But Data Insert Successfully !';
            }
            /* Admin Section  */
            $adminmail1 = 'advertisersupport@7searchppc.com';
            $adminmail2 = 'info@7searchppc.com';
            $bodyadmin =   View('emailtemp.useradmincreate', $data);
            $subjectadmin = 'Account Created Successfully - 7Search PPC';
            $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
            if($sendmailadmin == '1') 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Send & Data Inserted Successfully !';
            }
            else 
            {
                $return['code'] = 200;
                $return['message']  = 'Mail Not Send But Data Insert Successfully !';
            }
                
            }
             $return['code']    = 200;
             $return['message'] = 'Data send Successfully!';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Something went wrong ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

   // export excel sheet data api for client advertiser users
   public function ExportExcelData(Request $request)
   {
       
       $type = 0; // for client
       $startDate = $request->startDate;
       $nfromdate = date('Y-m-d', strtotime($startDate));
       $endDate =   date('Y-m-d', strtotime($request->endDate));
       $source = $request->source;
       $categ = $request->cat;
       $status_type = $request->status_type;

       $userlist = DB::table('users')
           ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, (select count(id) from ss_campaigns camp_ck where      camp_ck.advertiser_code = ss_users.uid AND trash=0 ) as cmpcount"), 'users.auth_provider', 'users.uid', 'users.email', 'users.user_type',     'users.website_category', 'users.status', 'users.ac_verified', 'users.country', 'users.uid', 'categories.cat_name', 'users.messenger_name',     'users.messenger_type', 'users.phone', 'users.created_at', 'users.wallet','sources.title as source_title')
           ->selectRaw('(SELECT COUNT(*) FROM ss_assign_clients WHERE ss_assign_clients.cid = ss_users.uid) as agent_count')
           ->selectRaw("
           CASE 
               WHEN ss_users.status = 0 THEN 'Active'
               WHEN ss_users.status = 1 THEN 'Inactive'
               WHEN ss_users.status = 2 THEN 'Pending'
               WHEN ss_users.status = 3 THEN 'Suspended'
               WHEN ss_users.status = 4 THEN 'Hold'
               ELSE '--'
           END as status")
           ->where('users.trash', 0)
           ->join('categories', 'users.website_category', '=', 'categories.id')
           ->join('sources', 'users.auth_provider', '=', 'sources.source_type')
           ->where('users.account_type', $type)
           ->where('users.user_type', '!=', 2) // 2 => advertiser
           ->whereDate('users.created_at', '>=', $nfromdate)
           ->whereDate('users.created_at', '<=', $endDate);

        if ($categ) {
        $userlist->where('users.website_category', $categ);
        }
        if ($source) {
            $userlist->where('users.auth_provider', $source);
        }
        if ($status_type > 0) {
            $userlist->where('users.status', $status_type);
        } elseif ($status_type == '0') {
            $userlist->where('users.status', 0);
        }
       $userlist->orderBy('users.id', 'DESC');
       $row = $userlist->count();
       $data = $userlist->get();
       
       if ($userlist) {
           $return['code']    = 200;
           $return['data']    = $data;
           $return['row']     = $row;
           $return['message'] = 'Client Users list retrieved successfully!';
       } else {
           $return['code']    = 101;
           $return['message'] = 'Something went wrong!';
       }
       return json_encode($return, JSON_NUMERIC_CHECK);
   }
}
