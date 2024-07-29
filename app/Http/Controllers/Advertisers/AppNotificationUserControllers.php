<?php

namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppNotificationUserControllers extends Controller
{

     public function view_notification_by_user_id_Old(Request $request)
     {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $userids =  $request->user_id;
        $usersdestils = User::where('uid', $userids)->first();
        $userid = $usersdestils->uid;
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.trash='0' ORDER BY un.id DESC";
        $queru = DB::select($sql);
        if (count($queru)) {
            $sql1 = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'AND un.view = '0' AND un.trash='0' ORDER BY un.id DESC";
        	$noticn = DB::select($sql1);
            $return['code'] = 200;
            $return['msg'] = 'All Data  User Notification';
            $return['data']    = $queru;
          	$return['count']    = count($noticn);
            $wltPubAmt = getPubWalletAmount($userids);
          	$pubwltamt        = ($wltPubAmt) > 0 ? $wltPubAmt : $usersdestils->pub_wallet;
            $return['wallet'] = number_format($pubwltamt, 2);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
     }
     
    public function view_notification_by_user_id(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => "required",
                'type' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $page   = $request->page;
        $limit  = $request->lim;
        $pg     = $page - 1;
        $start  = ($pg > 0) ? $limit * $pg : 0;
        $userids =  $request->user_id;
        $usersdestils = User::where('uid', $userids)->first();
        $userid = $usersdestils->uid;
        $users = DB::table('user_notifications')
            ->join('notifications', 'user_notifications.noti_id', '=', 'notifications.id')
            ->select('user_notifications.notifuser_id','user_notifications.view','notifications.title','notifications.noti_desc','notifications.noti_type','notifications.display_url','notifications.created_at')
            ->where('user_notifications.user_id', $userid)
            ->when($request->type == 1, function ($query) {
                return $query->where('user_notifications.user_type', 1);
            }, function ($query) {
                return $query->where('user_notifications.user_type', 2);
            })
            ->where('user_notifications.trash', 0)
            ->orderBy('user_notifications.id', 'desc');
            $row = $users->count();
            $queru =  $users->get();
            $read    = $queru->where('view',1)->count();
            $unread  = $queru->where('view',0)->count();
          
        // $queru =  $users->offset($start)->limit($limit)->get();
        
        if (count($queru)) {
            $return['code']     = 200;
            $return['msg']      = 'All Data  User Notification';
            $return['data']     = $queru;
            $return['row']      = $row;
            $return['count']    = $unread;
            $return['unread']   = $read;
            $wltPubAmt          = getPubWalletAmount($userids);
          	$pubwltamt          = ($wltPubAmt) > 0 ? $wltPubAmt : $usersdestils->pub_wallet;
            $return['wallet']   = number_format($pubwltamt, 2);
            } else {
            $return['code']     = 101;
            $return['msg']      = 'Data Not Found';
        }
        return json_encode($return);
    }

    public function countNotif(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $userids =  $request->user_id;
        $usersdestils = User::where('uid', $userids)->first();
        $userid = $usersdestils->uid;
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.view=0 AND  un.trash='0'  ORDER BY un.id DESC";
        $queru = DB::select($sql);
        if (count($queru)) {
            $return['code'] = 200;
            $return['msg'] = 'User Notification Count';
            $return['data']    = count($queru);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function unreadNotif(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $userids =  $request->user_id;
        $usersdestils = User::where('uid', $userids)->first();
        if (empty($usersdestils)) {
            $return['code'] = 101;
            $return['msg'] = 'User Not Found';
            return response()->json($return);
        } else { 
        $userid = $usersdestils->uid;
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'  AND un.trash= 0 ORDER BY un.id DESC LIMIT 3";
        $queru = DB::select($sql);
        if (count($queru)) {
            $sqld = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'  AND un.trash=0 ORDER BY un.id  DESC";
            $querus = DB::select($sqld);
            $return['code'] = 200;
            $return['msg'] = 'User Notification Count';
            $return['data']    = $queru;
            $return['count']    = count($querus);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
    }
    


        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function read(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'notifuser_id' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $notifuserid =  $request->notifuser_id;
        $notifdatau =  UserNotification ::where('notifuser_id',$notifuserid)->where('view',0)->first();
        if(!empty($notifdatau))
        { 
            $notifdatau->view = 1;
            $notifdatau->save();
            $return['code'] = 200;
            $return['msg'] = 'Notification Read Successfully';
        }
        else
        { 
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
      

      
       
      

    }



}
