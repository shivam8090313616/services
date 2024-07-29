<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PubWebsite;
use App\Models\PubAdunit;
use App\Models\WebsiteLogs;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\UserNotification;
use Carbon\Carbon;

class PubWebsiteAdminController extends Controller {
    
    public function websiteList( Request $request ) {
        $limit = $request->lim;
        $page = $request->page;
        $usertype = $request->usertype;
        $src = $request->src;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
        $startDate = $request->start_date;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate =  date('Y-m-d',strtotime($request->end_date));

       // DATE_FORMAT(ss_pub_websites.created_at,'%d-%m-%Y %h:%i:%s') as create_date ,DATE_FORMAT(ss_pub_websites.updated_at,'%d-%m-%Y %h:%i:%s') as resubmit_date ,ss_pub_websites.remark
        $list = PubWebsite::selectRaw(
            "ss_pub_websites.id,ss_pub_websites.site_url,ss_pub_websites.website_category,ss_pub_websites.web_code,
            (select email from ss_users users where users.uid = ss_pub_websites.uid) as user_email,
            (select uid from ss_users users where users.uid = ss_pub_websites.uid) as user_id,
            (select account_type from ss_users users where users.uid = ss_pub_websites.uid) as account_type,
            ss_pub_websites.status as website_status,
            (select cat_name from ss_categories category where category.id = ss_pub_websites.website_category) as category_name,
            (select count(id) from ss_pub_adunits adunits where adunits.web_code = ss_pub_websites.web_code AND adunits.trash = 0) as adunites,
            ss_pub_websites.remark,ss_pub_websites.created_at,ss_pub_websites.updated_at, ss_pub_websites.resubmit_date"
        )
        ->join('users', 'users.uid', '=', 'pub_websites.uid');
        if ( $request->website_status != '' && $request->website_category == '' ) {
           $list->where( 'pub_websites.status', $request->website_status );
        }
        if ( $request->website_category != '' && $request->website_status == '' ) {
            $list->where( 'pub_websites.website_category', $request->website_category );
        }
        if ( $request->website_category != '' && $request->website_status != '' ) {
            $list->where( 'pub_websites.status', $request->website_status );
            $list->where( 'pub_websites.website_category', $request->website_category );
        }
        if (strlen($usertype) > 0) {
            $list->where('users.account_type', $usertype);
        }
        if ($src) {
          	$list->whereRaw( 'concat(ss_pub_websites.web_code,ss_pub_websites.site_url, ss_pub_websites.uid,ss_pub_websites.u_email) like ?', "%{$src}%" );
        }
        
        if($startDate && $endDate && !$src){
            $list->whereDate('pub_websites.created_at', '>=', $nfromdate)
            ->whereDate('pub_websites.created_at', '<=', $endDate);
        }
        
        $row        = $list->count();
        $data       = $list->offset( $start )->limit( $limit )->where( 'pub_websites.trash', 0 )->orderBy( 'pub_websites.id', 'desc' )->get()->toArray();
      	foreach($data as $website)
        {
        $currentDate = Carbon::now();
        $webadunitlist = PubAdUnit::selectRaw("ss_pub_adunits.id, ss_pub_adunits.web_code, ss_pub_adunits.erotic_ads, ss_pub_adunits.ad_code, ss_pub_adunits.ad_name, 
        ss_pub_adunits. ad_type, ss_pub_adunits.site_url, ss_pub_adunits.status, ss_pub_adunits.website_category, ss_pub_adunits.created_at as created,
       (IF(DATEDIFF( '".$currentDate."', ss_pub_adunits.created_at) < 8, 1, 0)) as badge,
       IFNULL(sum(ss_pub_stats.impressions),0)  as impressions,
       IFNULL(sum(ss_pub_stats.clicks),0) as clicks")
       ->leftJoin("pub_stats","pub_adunits.ad_code","=","pub_stats.adunit_id")
       ->where('pub_adunits.web_code', $website['web_code'])
       ->where('pub_adunits.trash', 0)
       ->orderBy('pub_adunits.id', 'DESC')
       ->groupBy('pub_adunits.ad_code')
       ->get()
       ->toArray();
          	$website['adunit_list'] = $webadunitlist;
          	$wres[] = $website;
        }
        if ( count( $data ) > 0 ) {
            $return[ 'code' ] = 200;
            $return[ 'data' ] = $wres;
            $return[ 'row' ]  = $row;
            $return[ 'message' ] = 'Data Successfully found!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Data Not found!';
        }
        return json_encode( $return, JSON_NUMERIC_CHECK );
    }
  
  	public function webAdminDropdownList(Request $request)
    {
        $weblist = PubWebsite::select('id','web_code','site_url as webname')
            		->where('trash', 0)->get();
      	$row = $weblist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $weblist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function publisherStatusUpdate( Request $request ) {
        $validator = Validator::make(
            $request->all(),
            [
                'id'            => 'required',
                'status'            => 'required',
            ],
            [
                'id.required'            => 'Please enter valid website site',
                'status.required'            => 'Please enter status',
            ]
        );
        if ( $validator->fails() ) {
            $return[ 'code' ] = 100;
            $return[ 'error' ] = $validator->errors();
            $return[ 'message' ] = 'Valitation error!';
            return json_encode( $return );
        }
        if ($request->status == 1) {
            $remark = "Your website is In Review!";
        } elseif ($request->status == 2) {
            $remark = "Your website is Verified.";
        } elseif ($request->status == 3) {
            $remark = "Your website is on Hold!";
        } elseif ($request->status == 4) {
            $remark = "Congratulations your website is Approved.";
        } elseif ($request->status == 5) {
            $remark = "Your website is Suspended!";
        } elseif ($request->status == 6) {
            $remark = "Your website is Rejected!";
        }
        $pub_code = PubWebsite::select('uid')->where( 'web_code', $request->id )->first();
        $user = User::select('first_name', 'last_name', 'email')->where( 'uid', $pub_code->uid )->first();
        $update = PubWebsite::where('web_code', $request->id)->update(['status'=>$request->status,'remark' => $remark]);
        if($request->status === 5){
            $email = $user->email;
            $fullname = $user->first_name . ' ' . $user->last_name;
            $useridas = $pub_code->uid;
            $noti_title = 'Website Suspend - 7Search PPC ';
            $noti_desc  = 'The admin has Suspend your request to add a website to your publisher account. 
            This decision was made due to non-compliance with one or more of our policies and terms.';
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 2;
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save()) {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $pub_code->uid;
                $noti->user_type = 2;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }
            $data['details'] = array('subject' => 'Website Suspend - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
            $subject = 'Website Suspend - 7Search PPC';
            $body =  View('emailtemp.pubwebsitesuspenduser', $data);
            sendmailUser($subject,$body,$email); 
          
              $adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
            foreach($adunitupdate as $adunit)
            {
                $adunit->status = 1;	
                $adunit->save();
            }
        }else if($request->status === 3){
            $email = $user->email;
            $fullname = $user->first_name . ' ' . $user->last_name;
            $useridas = $pub_code->uid;
            $noti_title = 'Website Hold - 7Search PPC ';
            $noti_desc  = 'The admin has Hold your request to add a website to your publisher account. 
            This decision was made due to non-compliance with one or more of our policies and terms.';
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 2;
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save()) {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $pub_code->uid;
                $noti->user_type = 2;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }
            $data['details'] = array('subject' => 'Website Hold - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
            $subject = 'Website Hold - 7Search PPC';
            $body =  View('emailtemp.pubwebsiteholduser', $data);
            sendmailUser($subject,$body,$email); 
          
            $adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
            foreach($adunitupdate as $adunit)
            {
                $adunit->status = 1;	
                $adunit->save();
            }
        }else if($request->status === 4){
            $email = $user->email;
            $fullname = $user->first_name . ' ' . $user->last_name;
            $useridas = $pub_code->uid;
            $noti_title = 'Website approved - 7Search PPC ';
            $noti_desc  = 'Congratulations! Your request to add a new website has been approved by our moderation team. You can now run 7Search PPC ad campaigns on your newly added website.';
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 2;
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save()) {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $pub_code->uid;
                $noti->user_type = 2;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }        
            $data['details'] = array('subject' => 'Website Approved - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
            $subject = 'Website Approved - 7Search PPC';
            $body =  View('emailtemp.pubwebsiteactive', $data);
            sendmailUser($subject,$body,$email);   
            $adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
            foreach($adunitupdate as $adunit)
            {
                $adunit->status = 1;	
                $adunit->save();
            }
        }
        // else if($request->status === 6){
        //     $noti_title = 'Website Reject - 7Search PPC ';
        //     $noti_desc  = 'The admin has Reject your request to add a website to your publisher account. 
        //     This decision was made due to non-compliance with one or more of our policies and terms.';
        //     $notification = new Notification();
        //     $notification->notif_id = gennotificationuniq();
        //     $notification->title = $noti_title;
        //     $notification->noti_desc = $noti_desc;
        //     $notification->noti_type = 1;
        //     $notification->noti_for = 2;
        //     $notification->all_users = 0;
        //     $notification->status = 1;
        //     if ($notification->save()) {
        //         $noti = new UserNotification();
        //         $noti->notifuser_id = gennotificationuseruniq();
        //         $noti->noti_id = $notification->id;
        //         $noti->user_id = $pub_code->uid;
        //         $noti->user_type = 2;
        //         $noti->view = 0;
        //         $noti->created_at = Carbon::now();
        //         $noti->updated_at = now();
        //         $noti->save();
        //     }          
        //     $adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
        //     foreach($adunitupdate as $adunit)
        //     {
        //         $adunit->status = 1;	
        //         $adunit->save();
        //     }
        // }


      	if ( $update ) {
          	$adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
          	foreach($adunitupdate as $adunit)
            {
                $ad_code = $adunit->ad_code;
            	if($request->status == 4 )
                {
                    $status = 2;
                	$adunit->status = 2;	
                }
                else
                {
                    $status = 1;
                	$adunit->status = 1;
                }
              	$adunit->save();
                /* This will update AdUnit Data into Redis */
              	updateWebData($ad_code, $status);
            }
            $return[ 'code' ] = 200;
            $return[ 'message' ] = 'Data updated successfully!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Something wrong!';
        }
        return json_encode( $return, JSON_NUMERIC_CHECK );
    }

    public function publisherWebsiteRejected( Request $request ) {
        $validator = Validator::make(
            $request->all(),
            [
                'id'            => 'required',
                'status'            => 'required',
                'remark'            => 'required',
            ],
            [
                'id.required'            => 'Please Enter id',
                'status.required'            => 'Please Enter status',
                'remark.required'            => 'Please Enter remark',
            ]
        );
        if ( $validator->fails() ) {
            $return[ 'code' ] = 100;
            $return[ 'error' ] = $validator->errors();
            $return[ 'message' ] = 'Valitation error!';
            return json_encode( $return );
        }
        
        $pub_code = PubWebsite::select('uid')->where( 'web_code', $request->id )->first();
      	$user = User::select('first_name', 'last_name', 'email')->where( 'uid', $pub_code->uid )->first();
      	
        if ( $request->status == 6 ) {
            $update = PubWebsite::where( 'web_code', $request->id )->where( 'trash', 0 )->update( [ 'status'=>$request->status, 'remark'=>$request->remark ] );
          	if ( $update ) {
          	    $email = $user->email;
                $fullname = $user->first_name . ' ' . $user->last_name;
                $useridas = $pub_code->uid;

                $noti_title = 'Website Rejected - 7Search PPC ';
                $noti_desc  = 'We regret to inform you that your recent request to add a new website has been declined. This decision was made due to non-compliance with our terms and guidelines. 
                We encourage you to thoroughly review your website, ensuring alignment with our criteria, before resubmitting your request.';
                
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
                    $noti->user_id = $pub_code->uid;
                    $noti->user_type = 2;
                    $noti->view = 0;
                    $noti->created_at = Carbon::now();
                    $noti->updated_at = now();
                    $noti->save();
                }
                
              	$data['details'] = array('subject' => 'Website Rejected - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas , 'remark'=>$request->remark);

                $subject = 'Website Rejected - 7Search PPC';
                $body =  View('emailtemp.pubwebsiterejectuser', $data);
                sendmailUser($subject,$body,$email); 
              
              	$adunitupdate = PubAdunit::where( 'web_code', $request->id )->get();
                foreach($adunitupdate as $adunit)
                {
                    $adunit->status = 1;	
                    $adunit->save();
                /* This will update AdUnit Data into Redis */
              	updateWebData($adunit->ad_code, 1);
                }

                $return[ 'code' ] = 200;
                $return[ 'message' ] = 'Data updated successfully!';
            } else {
                $return[ 'code' ] = 101;
                $return[ 'message' ] = 'Something wrong!';
            }
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Something wrong!';
        }

        return json_encode( $return, JSON_NUMERIC_CHECK );
    }

    public function publisherAdUnits( Request $request ) {
        $validator = Validator::make(
            $request->all(),
            [
                'web_code'            => 'required',
            ],
            [
                'web_code.required'            => 'Please Enter id',
            ]
        );
        if ( $validator->fails() ) {
            $return[ 'code' ] = 100;
            $return[ 'error' ] = $validator->errors();
            $return[ 'message' ] = 'Valitation error!';
            return json_encode( $return );
        }
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
        $ad_units = PubAdunit::selectRaw(
            "ss_pub_adunits.id,ss_pub_adunits.site_url,ss_pub_adunits.uid,ss_pub_adunits.web_code,ss_pub_adunits.ad_name,ss_pub_adunits.erotic_ads,ss_pub_adunits.website_category,ss_pub_adunits.status,ss_pub_adunits.created_at,
             (select cat_name from ss_categories categories where categories.id = ss_pub_adunits.website_category) as category_name" )
        ->where( 'pub_adunits.trash', 0 )
        ->where( 'web_code', $request->web_code )
        ->offset( $start )->limit( $limit )
        ->get();
        if ( count( $ad_units ) > 0 ) {
            $return[ 'code' ] = 200;
            $return[ 'data' ] = $ad_units;
            $return[ 'message' ] = 'Data Found successfully!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Something wrong!';
        }
        return json_encode( $return, JSON_NUMERIC_CHECK );
    }
    public function websiteLogs(Request $request)
    {
        $page = $request->page;
        $limit = $request->lim;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $logs = WebsiteLogs::select('pub_websites.site_url', 'website_logs.web_code', 'website_logs.status', 'website_logs.trash', 'website_logs.remark', 'website_logs.created_at')
            ->join('pub_websites', 'website_logs.web_code', '=', 'pub_websites.web_code')
            ->where('website_logs.web_code', $request->web_code);
        $row  = $logs->count();
        $data = $logs->offset($start)->limit($limit)->orderBy('website_logs.id','desc')->get();

        foreach ($data as $log) {
            $phpdate = strtotime($log->created_at);
            $date = date('d-m-Y H:i:s', $phpdate);
            $time = date('H:i', $phpdate);
            $site['url'] = $log->site_url;
            $log['date'] = $date;
            // $log['time'] = $time;
        }
        if (count($data) > 0) {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['site'] = $site;
            $return['row']  = $row;
            $return['message'] = 'Logs Successfully found!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Logs Not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
