<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PubNotificationUserController extends Controller
{

    public function view_notification_by_user_id(Request $request)
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
            $return['code'] = 200;
            $return['msg'] = 'All Data  User Notification';
            $return['data']    = $queru;
          	// $return['wallet']    = number_format($usersdestils->pub_wallet, 2);
            $wltPubAmt = getPubWalletAmount($request->user_id);
            $return['wallet']        = ($wltPubAmt) > 0 ? number_format($wltPubAmt, 2) : number_format($usersdestils->pub_wallet, 2);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
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
