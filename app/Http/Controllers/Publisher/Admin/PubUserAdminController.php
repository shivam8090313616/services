<?php
namespace App\Http\Controllers\Publisher\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Activitylog;
use App\Models\Publisher\PubDocumentLog;
use App\Models\Publisher\PubPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
class PubUserAdminController extends Controller

{
    public function usersList(Request $request)
    {

        $sort_order = $request->sort_order;

        $col = $request->col;

        $categ = $request->cat;

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

        //  totalwebsites

        $userlist = DB::table('users')
            ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, 
            (select count(id) from ss_pub_websites web_ck where web_ck.uid = ss_users.uid AND trash=0 ) as totalwebsites, 
            (select count(id) from ss_pub_adunits adunit_ck where adunit_ck.uid = ss_users.uid AND trash=0 ) as totaladunits,
            (select count(id) from ss_campaigns camp_ck where camp_ck.advertiser_code = ss_users.uid AND trash=0 ) as cmpcount,ss_users.created_at"), 'users.auth_provider', 'users.email', 'users.user_type', 'users.website_category', 'users.status', 'users.ac_verified', 'users.country', 'users.uid', 'categories.cat_name','users.messenger_name','users.messenger_type','users.phone','users.critical','users.pub_wallet','sources.title as source_title')
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

            $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$src}%"])

                  ->orWhere('email', 'like', "%{$src}%")

                  ->orWhere('uid', 'like', "%{$src}%")

                  ->orWhere('auth_provider', 'like', "%{$src}%")

                  ->orWhere('messenger_name', 'like', "%{$src}%")

                  ->orWhere('messenger_type', 'like', "%{$src}%")

                  ->orWhere('phone', 'like', "%{$src}%")

                  ->orWhere('country', 'like', "%{$src}%");

         });

        }

        if ($status_type > 0 && $status_type < 9) {

            $userlist->where('users.status', $status_type);
        }elseif ($status_type == '0'){
            $userlist->where('users.status', 0);

        }

      	$row = $userlist->count();

        if($col)

        {
          if($col == 'total_websites') {
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('totalwebsites', $sort_order)->get();
          }elseif($col == 'total_adunits'){
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('totaladunits', $sort_order)->get();
          }else{
            $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.'.$col, $sort_order)->get();
          }
        }else{
          $data = $userlist->offset( $start )->limit( $limit )->orderBy('users.created_at', 'desc')->get();
        }

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

      	$status = $request->status;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

      	$sort_order = $request->sort_order;

      	$col = $request->col;	
      
        $userdetail = DB::table('users')
            ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name"), 'users.id', 'users.email', 'users.user_type', 'users.website_category', 'users.status', 'users.pub_wallet', 'users.wallet', 'users.withdrawl_limit', 'users.phone', 'users.uid', 'users.address_line1', 'users.city', 'users.state', 'users.country', 'users.account_type', 'users.ac_verified', 'users.user_photo', 'users.user_photo_id', 'users.photo_verified', 'users.profile_lock', 'users.photo_id_verified', 'users.created_at', 'categories.cat_name','users.messenger_name','users.messenger_type')
            ->where('users.trash', 0)
            ->join('categories', 'users.website_category', '=', 'categories.id')
            ->where('users.uid', $uid)

            ->first();
      	$user_websites = DB::table('pub_websites')

            ->select(DB::raw("(select count(id) from ss_pub_adunits web_un where web_un.web_code = ss_pub_websites.web_code) as adunites"), 'pub_websites.id', 'pub_websites.web_code', 'pub_websites.uid','pub_websites.status', 'pub_websites.site_url', 'pub_websites.created_at', 'categories.cat_name')

           	->join('categories', 'pub_websites.website_category', '=', 'categories.id')

            ->where('pub_websites.trash', 0)

            ->where('uid', $uid)

            ->orderBy('pub_websites.id', 'desc');

        $row = $user_websites->count();

        $data = $user_websites->offset($start)->limit($limit)->get();


        $user_adunites = DB::table('pub_adunits')

        ->select('pub_adunits.id', 'pub_adunits.ad_name', 'pub_adunits.ad_code', 'pub_adunits.ad_type', 'pub_adunits.uid','pub_adunits.status', 'pub_adunits.site_url', 'pub_adunits.created_at', 'categories.cat_name',

            DB::raw('(select IFNULL(sum(impressions),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as impressions,

        (select IFNULL(sum(clicks),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as clicks'))

         ->join('categories', 'pub_adunits.website_category', '=', 'categories.id')

        ->where('pub_adunits.trash', 0)

        ->where('uid', $uid);
        $payoutdetails = DB::table('pub_user_payout_modes')->select('payout_id','payout_name','publisher_id','pay_account_id','pub_withdrawl_limit','bank_name','account_holder_name','account_number','ifsc_code','swift_code','iban_code','minimum_amount','status')->where('publisher_id',$userdetail->uid)->where('status',1)->first();
        
      	
      	if(strlen($status) > 0)

        {

        	$user_adunites->where('pub_adunits.status', $status);

        }

      	$row1 = $user_adunites->count();

      	if($col)

        {
          if($col == 'impressions'){
            $data1 = $user_adunites->offset( $start )->limit( $limit )->orderBy('impressions', $sort_order)->get();
          }elseif($col == 'clicks'){
              $data1 = $user_adunites->offset( $start )->limit( $limit )->orderBy('clicks', $sort_order)->get();
          }elseif($col == 'category'){
              $data1 = $user_adunites->offset( $start )->limit( $limit )->orderBy('categories.cat_name', $sort_order)->get();
          }elseif($col == 'created_at') {
              $data1 = $user_adunites->offset( $start )->limit( $limit )->orderBy('pub_adunits.created_at', $sort_order)->get();
          }else{
              $data1  = $user_adunites->offset( $start )->limit( $limit )->orderBy('pub_adunits.'.$col, $sort_order)->get();
          }
        }else{
          	 $data1       = $user_adunites->offset( $start )->limit( $limit )->orderBy('pub_adunits.id', 'DESC')->get();

        }
      
            
        if ($userdetail) {

            $return['code']        		= 200;

            $userdetail->websites  		= $data;

          	$userdetail->adunites 	 	= $data1;
            
            $return['data']        		= $userdetail;
            $return['payoutdetails']  = $payoutdetails;
          	$return['row']         		= $row;

          	$return['row1']         	= $row1;

            $return['message']     		= 'Users List retrieved successfully!';

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
        if ($user->user_type == 3) {
          if ($type == 1) {
            $user->user_type = 2;
          } else {
            $user->user_type = 1;
          }
        } else {
              $user->trash = 1;
        }
        if ($user->update()) {

            $return['code']    = 200;

            $return['message'] = 'User deleted successfully!';

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

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	public function profileLockUnlock(Request $request)

    {

        $uid = $request->id;

      	$profile_status = $request->profile_status;

        $user = User::where('uid', $uid)->first();

        $user->profile_lock = $profile_status;



        if ($user->update()) {

            $return['code']    = 200;

            $return['message'] = 'User profile updated successfully!';

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

      	$activityuserlog =  Activitylog::where('uid', $uid)->where('status', 4)->where('type', 'UserHoldLog')->get();

      	$activitycount = $activityuserlog->count();

      	//echo $activitycount; exit;

      	if($activitycount >= 2)

        {

        	$user->critical = 1;

        }
        $user->status = $newStatus;
        if ($user->update()) {
            if ($newStatus == 0) {

                /* Create Campaign Send Mail   */

                $email = $user->email;

                $sts = 'Active';

                $fullname = "$user->first_name $user->last_name";

                $data['details'] = array('subject' => 'Account Activated - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);

                $subject = 'Account Activated - 7Search PPC';

                $body =  View('emailtemp.userpubupdstatus', $data);
              	
              	/* User Hold Activity Log */

                $activitylog = new Activitylog();

                $activitylog->uid    = $user->uid;

                $activitylog->type    = 'UserUnHoldLog';

                $activitylog->description    = 'User / ' . $user->uid . ' is Unhold successfully';

                $activitylog->status    = '5';

                $activitylog->save();

                /* User Mail Section */

                $sendmailUser =  sendmailUser($subject,$body,$email);

                if($sendmailUser == '1') 

                {

                    $return['code'] = 200;

                    $return['data']    = $user;

                    $return['message']  = 'Mail Send Successfully !';
                }else{
                    $return['code'] = 200;

                    $return['data']    = $user;

                    $return['message'] = 'Mail Not Send !';

                }

                /* Create Send Mail */

            } elseif ($newStatus == 2) {



                $email = $user->email;

                $sts = 'Pending';

                $fullname = "$user->first_name $user->last_name";

                $data['details'] = array('subject' => 'Account on Pending - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);

                $subject = 'Account on Pending - 7Search PPC';

                $body =  View('emailtemp.userpubupdstatus', $data);

                /* User Mail Section */

                $sendmailUser =  sendmailUser($subject,$body,$email);

                if($sendmailUser == '1') 

                {

                    $return['code'] = 200;

                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }else{
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

                $body =  View('emailtemp.userpubupdstatus', $data);

                /* User Mail Section */

                $sendmailUser =  sendmailUser($subject,$body,$email);

                if($sendmailUser == '1') 

                {

                    $return['code'] = 200;

                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }else{
                    $return['code'] = 200;

                    $return['data']    = $user;

                    $return['message'] = 'Mail Not Send !';

                }

            } elseif ($newStatus == 4) {



                $email = $user->email;

                $sts = 'Hold';

                $fullname = "$user->first_name $user->last_name";

                $data['details'] = array('subject' => 'Account on Hold - 7Search PPC', 'email' => $email, 'user_id' => $user->uid, 'full_name' => $fullname, 'status' => $sts);

                $subject = 'Account on Hold - 7Search PPC';
                $body =  View('emailtemp.userpubupdstatus', $data);  
              	/* User Hold Activity Log */

                $activitylog = new Activitylog();

                $activitylog->uid    = $user->uid;

                $activitylog->type    = 'UserHoldLog';

                $activitylog->description    = 'User / ' . $user->uid . ' is hold successfully';

                $activitylog->status    = '4';

                $activitylog->save();
                
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1'){
                    $return['code'] = 200;

                    $return['data']    = $user;
                    $return['message']  = 'Mail Send Successfully !';
                }else{
                    $return['code'] = 200;

                    $return['data']    = $user;

                    $return['message'] = 'Mail Not Send !';

                }
            } else {
                $return['code']    = 200;
                $return['data']    = $user;
                $return['message'] = 'User Status updated!';
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	public function usersHoldLogList(Request $request)

    {

      	$sort_order = $request->sort_order;

      	$col = $request->col;

        $limit = $request->lim;

        $page = $request->page;

        $src = $request->src;

      	$pg = $page - 1;

        $start = ( $pg > 0 ) ? $limit * $pg : 0;

      	$startDate = $request->startDate;

        $nfromdate = date('Y-m-d', strtotime($startDate));

        $endDate = $request->endDate;

		$userlist = DB::table('users')

          ->select('users.uid', 'users.email', 'users.status as ustatus', 'users.first_name', 'users.last_name',

            DB::raw("(select count(id) from ss_activitylogs where status = 4 AND uid = ss_users.uid) as holdcount"),

            DB::raw("(select created_at from ss_activitylogs where status = 4 AND uid = ss_users.uid order by ss_activitylogs.id desc LIMIT 1) as holddate"),

            DB::raw("(select created_at from ss_activitylogs where status = 5 AND uid = ss_users.uid order by ss_activitylogs.id desc LIMIT 1) as activedate"),

            )

          ->join('activitylogs', 'users.uid', 'activitylogs.uid')

          ->groupBy('users.uid')

  		  ->having('holdcount', '>', 0);
      
      	if ($startDate && $endDate) {

            $userlist->whereDate('activitylogs.created_at', '>=', $nfromdate)

            ->whereDate('activitylogs.created_at', '<=', $endDate);

        }
      
      	if($src)

        {

          $userlist->whereRaw('concat(ss_users.uid,ss_users.email , ss_users.first_name," ", ss_users.last_name," ") like ?', "%{$src}%");

        }
        
      	$row  = $userlist->count();
      
      	if($col)

        {

          $data  = $userlist->offset( $start )->limit( $limit )->orderBy('users.'.$col, $sort_order)->get();

        }

        else

        {

          $data = $userlist->offset($start)->limit($limit)->orderBy('activitylogs.id', 'desc')->get();

        }



        if ($userlist) {

            $return['code']        = 200;

            $return['data']        = $data;

          	$return['row']         = $row;

            $return['message']     = 'Hold users log list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
    public function documentUsersList(Request $request)
    {
        // Get request parameters
        $accountType = $request->input('documentAccounttype'); // Account type for filter
        $country = $request->input('cont'); // Country name for searching
        $photoStatus = $request->input('dstsid'); // Photo status for searching
        $sortOrder = $request->input('sort_order');
        $column = $request->input('col');
        $limit = $request->input('lim');
        $page = $request->input('page');
        $search = $request->input('src');
        $carbonsDate = date('Y-m-d',strtotime($request->sdate));
        $carboneDate = date('Y-m-d',strtotime($request->edate));
        $offset = ($page - 1) * $limit;
        $userType = 1;
    
        // Base query
        $query = DB::table('users')
            ->select(
                DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name"),
                'users.email',
                'users.phone',
                'users.user_type',
                'users.status',
                'users.created_at',
                'users.updated_at',
                'users.uid',
                'users.user_photo',
                'users.user_photo_id',
                'users.photo_verified',
                'users.photo_id_verified',
                'users.country',
                'users.account_type'
            )
            ->where('users.trash', 0)
            ->where(function($query) {
              $query->where('photo_verified', '!=', 0)
                    ->orWhere('photo_id_verified', '!=', 0);
           });
            if (strlen($country) > 0) {
                $query->where('users.country', $country);
            }

            if($accountType == 3){
              $data = $query;
            } else if (strlen($accountType) > 0) {
              $query->where('users.account_type', $accountType);
            } else if(strlen($search)>0){
              $query->whereRaw('CONCAT(ss_users.first_name, " ", ss_users.last_name, " ", ss_users.email, " ", ss_users.uid) LIKE ?', ["%{$search}%"]);
            } else {
            $query->where('users.account_type', 0);
            }
    
        if (strlen($photoStatus) > 0) {
            $query->where(function ($q) use ($photoStatus) {
                $q->where('photo_id_verified', $photoStatus)
                    ->orWhere('photo_verified', $photoStatus);
            });
        }
    
        if ($carboneDate && $carbonsDate && !$search && !$accountType && !$photoStatus) {
          $query->whereDate('users.updated_at', '>=', $carbonsDate)
             ->whereDate('users.updated_at', '<=', $carboneDate);
        }
        // Ensure users with photo_id_verified = 1 are included if not filtered already
        $query->where(function ($q) use ($userType) {
            $q->where('users.photo_id_verified', 1)
                ->orWhere('users.user_type', '!=', $userType);
        });
    
        // Count total rows
        $totalRows = $query->count();
        // Apply sorting and pagination
        if ($column) {
            $query->orderBy('users.' . $column, $sortOrder);
        } else {
            $query->orderBy('users.id', 'desc');
        }
    
        $data = $query->offset($offset)->limit($limit)->get();
    
        // Prepare response
        if ($data->isNotEmpty()) {
            $response = [
                'code' => 200,
                'data' => $data,
                'row' => $totalRows,
                'message' => 'Users list retrieved successfully!',
            ];
        } else {
            $response = [
                'code' => 101,
                'message' => 'No users found!',
            ];
        }
        
        return response()->json($response, 200);
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

       
    public function updateKycPhotoIdStatus(Request  $request)
    {
      $phototype = $request->phototype;
      $acntupdate = User::where('uid', $request->uid)->first();
      $email = $acntupdate->email;
      $fullname = $acntupdate->first_name . ' ' . $acntupdate->last_name;
      $useridas = $request->uid;
      $statusphoto = $acntupdate->photo_verified;
      $statusid = $acntupdate->photo_id_verified;
      $statustype = $request->status_type;
      if($phototype == 1){
        $acntupdate->photo_id_verified = $request->status_type;
        $acntupdate->user_photo_id_remark = $request->remark;
      }else{
      	$acntupdate->photo_verified = $request->status_type;
        $acntupdate->user_photo_remark = $request->remark;
      }
      if ($phototype == 2 && $statustype == $statusphoto || $phototype == 1 && $statustype == $statusid) {
        return response()->json([
          'status' => false,
          'alrdmessage' => 'This status is already selected.'
        ]);
      }

      if ($acntupdate->update()) {
        $doclog = new PubDocumentLog();
        $doclog->uid = $request->uid;
        if($phototype == 1){	
        	$doclog->doc_type = 'Id Proof';
        }else{	
        	$doclog->doc_type = 'Selfie';
        }
        $doclog->status = $request->status_type;
        $doclog->remark = $request->remark;
        $doclog->save();
        if($request->status_type == 3){
          if($phototype == 1){	
            $noti_title = 'KYC Rejection Notice: ID proof Issue- 7Search PPC ';
            $noti_desc  = 'We regret to inform you that your Know Your Customer (KYC) submission has been rejected due to an issue with the provided ID proof. In order to proceed with your application, please resubmit your KYC documents with a clear and valid form of identification.';
            $data['details'] = array('subject' => 'KYC Rejection Notice For'.$doclog->doc_type.'- 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas,'doctype' =>$doclog->doc_type);
            $subject = 'KYC Rejection Notice: '.$doclog->doc_type.' Issue - 7Search PPC';
            $body =  View('emailtemp.pubdockycrejectidproof', $data);
            sendmailUser($subject,$body,$email);
          }else{	
            $noti_title = 'KYC Rejection Notice: Photo Issue- 7Search PPC ';
            $noti_desc  = 'We regret to inform you that your KYC submission has been rejected due to an issue with the photo provided. In order to proceed with your application, please resubmit your KYC documents with clear and valid photo identification.';
            $data['details'] = array('subject' => 'KYC Rejection Notice For Photo - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas,'doctype' => 'Photo');
            $subject = 'KYC Rejection Notice: Photo Issue - 7Search PPC';
            $body =  View('emailtemp.pubdockycrejectphoto', $data);
            sendmailUser($subject,$body,$email);
          }
      }else if($request->status_type == 2){                                
        if($phototype == 1){	
          $noti_title = 'Congratulations! Your ID Proof Verification Completed For KYC- 7Search PPC ';
          $noti_desc  = "We're reaching out to let you know that our administrative team has successfully accepted the ID proof provided for Know Your Customer (KYC) compliance purposes. We appreciate your prompt cooperation in supplying the necessary documentation to ensure regulatory adherence within our ad network.";
          $data['details'] = array('subject' => 'KYC Accepted Notice For'.$doclog->doc_type.'- 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas,'doctype' =>$doclog->doc_type);
          $subject = 'Congratulations! Your ID Proof Verification Completed For KYC - 7Search PPC';
          $body =  View('emailtemp.pubdockyacceptedidproof', $data);
          sendmailUser($subject,$body,$email);
        }else{	
          $noti_title = 'Congratulations! Your Photo Verification Completed For KYC- 7Search PPC';
          $noti_desc  = "We're thrilled to inform you that our administrative team has successfully accepted the photo provided for Know Your Customer (KYC) compliance purposes.";
          $data['details'] = array('subject' => 'KYC Accepted Notice For'.$doclog->doc_type.'- 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas,'doctype' =>$doclog->doc_type);
          $subject = 'Congratulations! Your Photo Verification Completed For KYC- 7Search PPC';
          $body =  View('emailtemp.pubdockyacceptedphoto', $data);
          sendmailUser($subject,$body,$email);
        }
      }else{
          $noti_title = 'KYC Pending For '.$doclog->doc_type.'- 7Search PPC ';
          $noti_desc  = 'Your KYC request is under process our team will update you soon.';
      }
        $notification = new Notification();
        $notification->notif_id = gennotificationuniq();
        $notification->title = $noti_title;
        $notification->noti_desc = $noti_desc;
        $notification->noti_type = 1;
        $notification->noti_for = 2;
        //$notification->display_url = 'N/A';
        $notification->all_users = 0;
        $notification->status = 1;
        if ($notification->save()) {
            $noti = new UserNotification();
            $noti->notifuser_id = gennotificationuseruniq();
            $noti->noti_id = $notification->id;
            $noti->user_id = $request->uid;
            $noti->user_type = 2;
            $noti->view = 0;
            $noti->created_at = Carbon::now();
            $noti->updated_at = now();
            $noti->save();
        }
        $return['code'] = 200;
        $return['data'] = $acntupdate;
        $return['message'] = 'User photo status updated successfully!';
      } else {
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function pubTransactionsList(Request $request)

    {

        $uid = $request->uid;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;



        $transaction = DB::table('pub_payouts')

            ->select('pub_payouts.id', 'pub_payouts.transaction_id', 'pub_payouts.publisher_id', 'pub_payouts.amount', 'pub_payouts.payout_method', 'pub_payouts.payout_transaction_id', 'pub_payouts.status', 'pub_payouts.release_date', 'pub_payouts.release_date', 'pub_payouts.created_at')

            ->where('pub_payouts.publisher_id', $uid)

            ->orderBy('pub_payouts.id', 'desc');

        $row = $transaction->count();

        $data = $transaction->offset($start)->limit($limit)->get();
      
      	$userTransfered = PubPayout::where('publisher_id', '=', $uid)->where('status', 1)->sum('amount');
      	
        $totalimp = DB::table('pub_stats')->select(DB::raw('SUM(ss_pub_stats.amount) AS amt'))->where('publisher_code', '=', $uid)->first();
        $userEarned = number_format($totalimp->amt, 2);



        if ($transaction) {

            $return['code']    			= 200;

            $return['data']    			= $data;

          	$return['user_transfered']  = $userTransfered;

          	$return['user_earned']  	= $userEarned;

          	$return['row']     			= $row;

            $return['message'] 			= 'Transaction list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }
    	
  	public function pubWithdrawlCron()

    {

      $withdrawlUser = DB::table('users')

          				->select('users.id', 'users.uid', 'users.pub_wallet', 'pub_user_payout_modes.pub_withdrawl_limit as amount')

        				->join('pub_user_payout_modes', 'users.uid', 'pub_user_payout_modes.publisher_id')

        				->whereRaw('ss_pub_user_payout_modes.pub_withdrawl_limit > 0')

          				->whereRaw('ss_users.pub_wallet >= ss_pub_user_payout_modes.pub_withdrawl_limit')

          				->where('users.account_type', '!=', 1)

        				->where('users.user_type', '!=', 1)

          				->where('users.status', 0)

          				->get();

      $row = $withdrawlUser->count();
      
      if($row != null)

      {

      	foreach($withdrawlUser  as $users)

        {

        	$currentDate = Carbon::now();

          	$newDate = $currentDate->addWeeks(2);
          	 
          	if ($newDate->day >= 15) {
             
            	$releaseDate = $newDate->addMonthNoOverflow()->startOfMonth();

            }

          	else

            {

               	$releaseDate = $newDate->startOfMonth()->addDays(14);
              	
            }
          	          	
          $pubTxnId = 'PUBTXN' . strtoupper(uniqid(15));
          
          $pubtransac 					= new PubPayout();

          $pubtransac->transaction_id 	= $pubTxnId;

          $pubtransac->publisher_id 	= $users->uid;

          $pubtransac->amount 			= $users->pub_wallet;

          $pubtransac->payout_method 	= 'Manual';

          $pubtransac->status 			= 0;

          $pubtransac->release_date 	= $releaseDate;

          DB::table('users')->where('uid', $users->uid)->decrement('pub_wallet', $users->pub_wallet);

          if($pubtransac->save())

          {

          	$return['code'] = 200;

       		$return['message'] = 'Data added to payout table';

          }

          else

          {

          	$return['code'] = 101;

        	$return['message'] = 'Something went wrong!';

          }
          
        }
       
      }

      else {

        $return['code'] = 101;

        $return['message'] = 'Something went wrong!';

      }
      
      	return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function pubMinWithdrawlAdminNotiCron()

    {

      $withdrawlUser = DB::table('users')

          				->select('users.id', 'users.uid', 'users.first_name', 'users.last_name', 'users.pub_wallet', 'pub_user_payout_modes.pub_withdrawl_limit as amount')

        				->join('pub_user_payout_modes', 'users.uid', 'pub_user_payout_modes.publisher_id')

        				->whereRaw('ss_pub_user_payout_modes.pub_withdrawl_limit > 0')

          				->whereRaw('ss_users.pub_wallet >= ss_pub_user_payout_modes.pub_withdrawl_limit')

          				->where('users.account_type', '!=', 1)

        				->where('users.user_type', '!=', 1)

          				->where('users.status', 0)

          				->get();

      $row = $withdrawlUser->count();

      //print_r($withdrawlUser); exit;

      if($row != null)

      {

      	foreach($withdrawlUser  as $users)

        {

         	$activitylog = new Activitylog();

            $activitylog->uid    = $users->uid;

            $activitylog->type    = 'Publisher' . $users->uid . ' earned the minimum withdrawl amount';

            $activitylog->description    = 'Publisher' . $users->uid . ' earned the minimum withdrawl amount. Please transfer withdrawl amount to the publisher account';

            $activitylog->status    = '1';

            //$activitylog->save();
            
            /* Send email to admin */
            
            $regDatauid = $users->uid;

            $fullname = "$users->first_name $users->last_name";

            $withdrawl_amount = $users->pub_wallet;

            $totalearned = $users->pub_wallet;

            $data['details'] = ['subject' => 'Publisher earned the minimum withdrawl amount', 'withdrawl_amount' => $withdrawl_amount, 'user_id' => $regDatauid, 'full_name' => $fullname, 'totalearned' => $totalearned];
            $adminmail1 = 'advertisersupport@7searchppc.com';
            // $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
            $adminmail2 = 'info@7searchppc.com';

            /*dminmail1 = 'sharif.logelite@gmail.com';

            $adminmail2 = 'ashraf.logelite@gmail.com';*/
            
            $bodyadmin =   View('emailtemp.publisheradminwithdrwalcron', $data);

            $subjectadmin = 'Publisher earned the minimum withdrawl amount - 7Search PPC';

            $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 

            if($sendmailadmin == '1') 

            {

                $return['code'] = 200;

                $return['message']  = 'Mail Send & Publisher earned the minimum withdrawl amount!';

            }

            else 

            {

                $return['code'] = 200;

                $return['message']  = 'Mail Not Send But Publisher earned the minimum withdrawl amount !';

            }

          }

       	}

        else {

          $return['code'] = 101;

          $return['message'] = 'Something went wrong!';

        }
      
      	return json_encode($return, JSON_NUMERIC_CHECK);

    }
  
  	
  	public function userAction(Request $request)
    {
      
      	$uid    = $request->uid;

        $type   = $request->type;

        $count  = 0;
                
        if ($type == 'active') {

          $sts = 0;

          $user = User::whereIn('uid', $uid)->where('status', '!=', 0)->where('status', '!=', 3)->update(['status' => 0]);

          $count++;

        } elseif ($type == 'hold') {

          $sts = 4;

          $user = User::whereIn('uid', $uid)->where('status', 0)->update(['status' => 4]);

          $count++;

        }



        else {

          $sts = 3;

          $user = User::whereIn('uid', $uid)->where('status','!=', 3)->update(['status' => 3]);

          $count++;

        } 

        
        if ($count > 0) {

            $return['code'] = 200;

            $return['data'] = $user;

            $return['rows'] = $count;

            $return['message'] = 'User updated successfully!';

        } else {

            $return['code'] = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    // get publisher kyc logs
    public function KycLogList(Request $request)
    {
      $validator = Validator::make(
        $request->all(),
        [
          'uid' => 'required',
        ],
        [
          'uid.required' => 'The User ID field is required.'
        ]
      );
  
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['message'] = 'Validation Error';
        $return['error'] = $validator->errors();
        return json_encode($return);
      }
  
      $uid = $request->uid;
      $limit = $request->lim;
      $page = $request->page;
      $pg = $page - 1;
      $start = ($pg > 0) ? $limit * $pg : 0;
      $data = PubDocumentLog::select('uid', 'doc_type', 'doc_name', 'status', 'created_at', 'remark')
        ->where('uid', $uid)
        ->offset($start)
        ->limit($limit)
        ->orderBy('id','DESC')
        ->get();

      $count = $data->count();
      if (!empty($data)) {
        $return['message'] = "Kyc log found successfully!";
        $return['data'] = $data;
        $return['row'] = $count;
      } else {
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
  
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
}

