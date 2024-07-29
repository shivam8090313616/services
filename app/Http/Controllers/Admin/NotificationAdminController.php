<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\CreateNotificationMail;

class NotificationAdminController extends Controller
{


    public function notimail($notismail = '', $details = '')
    {
        $email = $notismail;
        $useridas = $details['user_id'];
        $fullname = $details['full_name'];
        $displayurl = $details['display_url'];
        $descriptions = $details['description'];
        $mailsentdetals = ['subject' => 'Notification Created Successfully', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'display_url' => $displayurl, 'description' => $descriptions];
        // $hrmail = 'abul.logilite@gmail.com';
        $mailTo = [$email];
        try {
            Mail::to($mailTo)->send(new CreateNotificationMail($mailsentdetals));
            $return['code']    = 300;
            $return['mail_msg']    = 'Mail Send Successfully';
        } catch (Exception $e) {
            $return['code'] = 301;
            $return['mail_msg']  = 'detail added successfully!. But mail not send to the user.';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }



    public function create_notification(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => "required",
                'title' => "required",
                'noti_desc' => "required",
                'noti_type' => "required|numeric",
                'noti_for' => "required|numeric",
                //  'display_url' => "url",
                //'status' => "required|numeric",
            ],
            [
                'user_id.required' => 'User id required',
                'title.required' => 'Please enter title',
                'noti_desc.required' => 'Please enter description.',
                'noti_type.required' => 'Please select notification type',
                'noti_type.numeric' => 'Please notification type should be numeric',
                'noti_for.required' => 'Please select notification for',
                'noti_for.numeric' => 'Please notification for should be numeric',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $notification = new Notification();
        $notification->notif_id = gennotificationuniq();
        $notification->title = $request->title;
        $notification->noti_desc = $request->noti_desc;
        $notification->noti_type = $request->noti_type;
        $notification->noti_for = $request->noti_for;
        $notification->display_url = $request->display_url;
        $notification->all_users = ($request->user_id == 'All') ? 1 : 0;
        $notification->status = 1;
        if ($notification->save()) {
            $userid = $request->user_id;
            if ($userid == 'All') {
                $notifor = $request->noti_for;
                if ($notifor == '1') {
                    $advertiseruser  = User::where('user_type',  '!=', 2)->where('status', 0)->where('trash', 0)->get();
                    foreach ($advertiseruser as $valueadvertiser) {
                        $data[] =
                            [
                                'notifuser_id'=> gennotificationuseruniq(),
                                'noti_id' => $notification->id,
                                'user_id' => $valueadvertiser->uid,
                                'user_type' => $valueadvertiser->user_type,
                                'view' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => now()
                            ];
                    }
                } elseif ($notifor == '2') {
                    $advertiseruser  = User::where('user_type', '!=', 1)->where('status', 0)->where('trash', 0)->get();
                    foreach ($advertiseruser as $valueadvertiser) {
                        $data[] = [
                            'notifuser_id'=> gennotificationuseruniq(),
                            'noti_id' => $notification->id,
                            'user_id' => $valueadvertiser->uid,
                            'user_type' => $valueadvertiser->user_type,
                            'view' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => now()
                        ];

                        // $notismail = $valueadvertiser->email;
                        // $finalname = "$valueadvertiser->first_name $valueadvertiser->last_name";
                        // $details = array('full_name' => $finalname, 'user_id'=> $valueadvertiser->uid,'display_url'=>$notification->display_url,'description'=>$notification->noti_desc );
                        // $this->notimail($notismail, $details);
                    }
                } elseif ($notifor == '3') {
                    $advertiseruser  = User::where('trash', 0)->where('status', 0)->get();
                    foreach ($advertiseruser as $valueadvertiser) {
                        $data[] = [
                            'notifuser_id'=> gennotificationuseruniq(),
                            'noti_id' => $notification->id,
                            'user_id' => $valueadvertiser->uid,
                            'user_type' => $valueadvertiser->user_type,
                            'view' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => now()
                        ];
                        // $notismail = $valueadvertiser->email;
                        // $finalname = "$valueadvertiser->first_name $valueadvertiser->last_name";
                        // $details = array('full_name' => $finalname, 'user_id'=> $valueadvertiser->uid,'display_url'=>$notification->display_url,'description'=>$notification->noti_desc );
                        // $this->notimail($notismail, $details);
                    }
                } else {
                    $return['code'] = 101;
                    $return['msg'] = 'Please Choose the valid Noti for';
                    return response()->json($return);
                }
                if (UserNotification::insert($data)) {
                    $return['code'] = 200;
                    $return['data']    = $notification;
                    $return['msg'] = 'User Notification has been added.';
                } else {
                    $return['code'] = 101;
                    $return['msg'] = 'Error: Please contact administrator.';
                }
            } else {
                //   $sepuserid = explode(',', $userid);
                $pdata = json_decode($userid);
                $userids = array_column($pdata, 'value');

                foreach ($userids as $insertdata) {
                    $userdatasp = User::where('uid', $insertdata)->where('trash', 0)->where('status', 0)->first();
                  	$pubToken = [];
                    $fdata[] =
                        [
                            'notifuser_id'=> gennotificationuseruniq(),
                            'noti_id' => $notification->id,
                            'user_id' => $userdatasp->uid,
                            'user_type' => $userdatasp->user_type,
                      		'view' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => now()
                        ];
                  	$pubToken[] = $userdatasp->pub_noti_token;
                    // $notismail = $userdatasp->email;
                    // $finalname = "$userdatasp->first_name $userdatasp->last_name";
                    // $details = array('full_name' => $finalname, 'user_id'=> $userdatasp->uid,'display_url'=>$notification->display_url,'description'=>$notification->noti_desc );
                    // $this->notimail($notismail, $details);
                }
                if (UserNotification::insert($fdata)) {
                  	sendFcmPubNotification($request->title, $request->noti_desc, $pubToken);
                    $return['code'] = 200;
                    $return['data']    = $notification;
                    $return['msg'] = 'Notification Added has been Sucessfully.';
                } else {
                    $return['code'] = 101;
                    $return['msg'] = 'Error: Please contact administrator.';
                }
            }
            return json_encode($return);
        }
    }
    
    public function view_all_list_notification(Request $request)
    {
        $type = $request->type;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $startDate = $request->startDate;
        $sort_order = $request->sort_order;
      	$col = $request->col;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $getdata = DB::table('notifications')
                ->select(DB::raw(" IF(ss_notifications.all_users = 1, 'All',  ( SELECT GROUP_CONCAT(CONCAT(us.first_name, ' ', us.last_name)) FROM ss_user_notifications un, ss_users us WHERE un.noti_id = ss_notifications.id AND us.uid = un.user_id)) as uname"), 'id', 'title', 'noti_desc', 'noti_type', 'noti_for', 'display_url', 'status', 'created_at', 'updated_at')
                ->where('notifications.trash', 0);

        if ($startDate && $endDate) {
            $getdata->whereDate('notifications.created_at', '>=', $nfromdate)
            ->whereDate('notifications.created_at', '<=', $endDate);
        }

        if ($src) {
            $getdata->where('notifications.title', 'like', '%' . $src . '%');
        }

        if (strlen($type) > 0) {
            $getdata->where('notifications.noti_type', $type);
        }

        if (strlen($type) > 0 && $startDate && $endDate) {
            $getdata->where('notifications.noti_type', $type)->whereDate('notifications.created_at', '>=', $nfromdate)
            ->whereDate('notifications.created_at', '<=', $endDate);
        }
        $row = $getdata->count();
        if($col)
        {
          $data = $getdata->orderBy('notifications.'.$col, $sort_order)->offset($start)->limit($limit)->get();
        }
        else
        {
          $data = $getdata->orderBy('id', 'DESC')->offset( $start )->limit( $limit )->get();
        }
        if ($row != null) {
            $return['code'] = 200;
            $return['msg'] = 'All Data Notification';
            $return['row']     = $row;
            $return['data']    = $data;
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Error: Please contact administrator.';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    // public function view_all_list_notification(Request $request)
    // {
    //     $type = $request->type;
    //     $limit = $request->lim;
    //     $page = $request->page;
    //     $src = $request->src;
    //     $pg = $page - 1;
    //     $start = ($pg > 0) ? $limit * $pg : 0;

    //     if ($src) {
    //         $getdata = DB::table('notifications')
    //             ->select(DB::raw(" IF(ss_notifications.all_users = 1, 'All',  ( SELECT GROUP_CONCAT(CONCAT(us.first_name, ' ', us.last_name)) FROM ss_user_notifications un, ss_users us WHERE un.noti_id = ss_notifications.id AND us.uid = un.user_id)) as uname"), 'id', 'title', 'noti_desc', 'noti_type', 'noti_for', 'display_url', 'status', 'created_at', 'updated_at')
    //             ->where('notifications.trash', 0)
    //             ->where('notifications.title', 'like', '%' . $src . '%')
    //             ->orderBy('id', 'DESC');

    //         $row = $getdata->count();
    //         $data = $getdata->offset($start)->limit($limit)->get();
    //     }

    //     if (strlen($type) > 0) {
    //         $getdata = DB::table('notifications')
    //             ->select(DB::raw(" IF(ss_notifications.all_users = 1, 'All',  ( SELECT GROUP_CONCAT(CONCAT(us.first_name, ' ', us.last_name)) FROM ss_user_notifications un, ss_users us WHERE un.noti_id = ss_notifications.id AND us.uid = un.user_id)) as uname"), 'id', 'title', 'noti_desc', 'noti_type', 'noti_for', 'display_url', 'status', 'created_at', 'updated_at')
    //             ->where('notifications.noti_type', $type)
    //             ->where('notifications.trash', 0)
    //             ->orderBy('id', 'DESC');

    //         $row = $getdata->count();
    //         $data = $getdata->offset($start)->limit($limit)->get();
    //     }
    //     if (empty($type) && empty($src)) {
    //         $getdata = DB::table('notifications')
    //             ->select(DB::raw(" IF(ss_notifications.all_users = 1, 'All',  ( SELECT GROUP_CONCAT(CONCAT(us.first_name, ' ', us.last_name)) FROM ss_user_notifications un, ss_users us WHERE un.noti_id = ss_notifications.id AND us.uid = un.user_id)) as uname"), 'id', 'title', 'noti_desc', 'noti_type', 'noti_for', 'display_url', 'status', 'created_at', 'updated_at')
    //             ->where('notifications.trash', 0)
    //             ->orderBy('id', 'DESC');

    //         $row = $getdata->count();
    //         $data = $getdata->offset($start)->limit($limit)->get();
    //     }


    //     if ($getdata) {
    //         $return['code'] = 200;
    //         $return['msg'] = 'All Data Notification';
    //         $return['row']     = $row;
    //         $return['data']    = $data;
    //     } else {
    //         $return['code'] = 101;
    //         $return['msg'] = 'Error: Please contact administrator.';
    //     }
    //     return json_encode($return);
    // }
    public function view_notification_by_id(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'notification_id' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $notificationid =  $request->notification_id;
        $vewnotification = UserNotification::where('noti_id', $notificationid)->orderBy('id', 'DESC')->get();
        if (count($vewnotification)) {
            $return['code'] = 200;
            $return['msg'] = 'All Data Notification User';
            $return['data']    = $vewnotification;
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
        $userid = $usersdestils->id;
        $sql = "SELECT n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' ORDER BY un.id DESC";
        $queru = DB::select($sql);
        if (count($queru)) {
            $return['code'] = 200;
            $return['msg'] = 'All Data  User Notification';
            $return['data']    = $queru;
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function type_to_user(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'noti_for' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $notifor =  $request->noti_for;
        if ($notifor <= 3 && $notifor > 0) {
            if ($notifor == 3) {
                $usersdestils = DB::table('users')
                    ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as label"), 'users.uid as value')->where('users.trash', 0)->where('users.status', 0)
                    ->get()->toArray();
                if (!empty($usersdestils)) {
                    $return['code']     = 200;
                    $return['data']    = $usersdestils;
                    $return['msg']      = 'All Data  User Notification';
                } else {
                    $return['code']     = 101;
                    $return['msg']      = 'Not Fund Data';
                }
            } else {
                $usersdestil = DB::table('users')
                    ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as label"), 'users.uid as value')->where('users.trash', 0)->where('users.status', 0)
                    ->where('users.user_type', $notifor)
                    ->get()->toArray();
                if (!empty($usersdestil)) {
                    $return['code']     = 200;
                    $return['data']    = $usersdestil;
                    $return['msg']      = 'All Data  User Notification';
                } else {
                    $return['code']     = 101;
                    $return['msg']      = 'Not Fund Data';
                }
            }
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Invalid User Type';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function notificationStatusUpdate(Request $request)
    {
        $id = $request->id;
        $status = $request->sts;

        $notification = Notification::where('id', $id)->first();
        if (empty($notification)) {
            $return['code'] = 100;
            $return['message'] = 'No data found!';
            return json_encode($return);
        }
        $notification->status = $status;
        if ($notification->update()) {
            $return['code'] = 200;
            $return['message'] = 'Notification status update successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function notificationTrash(Request $request)
    {
        $id = $request->id;
        $notification = Notification::where('id', $id)->first();
        $notification->trash = 1;
        if ($notification->update()) {
            $notid = $notification->id;
            $notificationuser = UserNotification::where('noti_id', $notid)->get()->toArray();
            if (!empty($notificationuser)) {
                foreach ($notificationuser as $value) {
                    $unati = UserNotification::where('id', $value['id'])->first();
                    $unati->trash = 1;
                    $unati->update();
                }
                $return['code']    = 200;
                $return['message'] = 'Notification deleted successfully';
            } else {
                $return['code']    = 200;
                $return['message'] = 'Notification deleted successfully';
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function notiAction(Request $request)
    {
      	$id    = $request->id;
        $type   = $request->type;
        $count  = 0;
                
        if ($type == 'delete') {
          $trash = 1;
          $adminnoti = Notification::whereIn('id', $id)->update(['trash' => 1]);
          $user = UserNotification::whereIn('noti_id', $id)->update(['trash' => 1]);
          $count++;
        }
      	else {
          $trash = 0;
          $adminnoti = Notification::whereIn('id', $id)->update(['trash' => 1]);
          $user = UserNotification::whereIn('noti_id', $id)->update(['trash' => 1]);
          $count++;
        } 

        
        if ($count > 0) {
            $return['code'] = 200;
            $return['data'] = $adminnoti;
            $return['rows'] = $count;
            $return['message'] = 'Notification updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return);
    }


    /*
    public function type_to_user(Request $request){ 
            $validator = Validator::make(
                $request->all(),
                [
                    'noti_for' => "required",
                ]
            );
            if ($validator->fails()) {
                $return['code'] = 100;
                $return['msg'] = 'error';
                $return['err'] = $validator->errors();
                return response()->json($return);
            }  
            $notifor =  $request->noti_for;
            if($notifor <= 3 && $notifor > 0)
            {
                if($notifor == 3) {
                    $sql ="SELECT un.user_id FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) GROUP BY un.user_id";
                    $queru = DB::select($sql);
                    foreach($queru as $value){
                        $userid[] = $value->user_id;
                    }
                    $orders = [
                        'date' => $userid,
                    ];
                            array_column($userid, 'date');
                            $ids = implode(',',$orders['date']);
                            $newids = str_split(str_replace(',', '', $ids));
                            $username =  User::whereIn('id', $newids)->get();

                } else {
                $sql ="SELECT un.user_id FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE n.noti_for='$notifor' GROUP BY un.user_id";
                $queru = DB::select($sql);
                foreach($queru as $value){
                    $userid[] = $value->user_id;
                }
                $orders = [
                    'date' => $userid,
                ];
                        array_column($userid, 'date');
                        $ids = implode(',',$orders['date']);
                        $newids = str_split(str_replace(',', '', $ids));
                        $username =  User::whereIn('id', $newids)->get();
                }
                $return['code'] = 200;
                $return['data'] = $username;
                $return['msg'] = 'data Found';
            } else {
                $return['code'] = 101;
                $return['msg'] = 'Please Valid Data';
            }
            return response()->json($return);
        } */
}
