<?php

namespace App\Http\Controllers\AppPublisher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Supports;
use App\Models\SupportLog;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\CreateSupportMail;
use Exception;


class AppPubSupportController extends Controller
{

    public function randomToken()
    {
        $ticketno =  'TK' . strtoupper(uniqid());
        $checkdata = Supports::where('ticket_no', $ticketno)->count();
        if ($checkdata > 0) {
            $this->randomToken();
        } else {
            return $ticketno;
        }
    }

    public function create_support(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'category' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        if ($request->file) {
            $base_str = explode(';base64,', $request->file);
            if($base_str[0] == 'data:application/pdf'){
                $ext = str_replace('data:application/', '', $base_str[0]);
                $image = base64_decode($base_str[1]);
                $imageName = Str::random(10) . '.' . $ext; ;
                $path = public_path('images/support/' . $imageName);
                 file_put_contents($path, $image); 
            }else{
                $ext = str_replace('data:image/', '', $base_str[0]);
                $image = base64_decode($base_str[1]);
                $imageName = Str::random(10) . '.' . $ext; ;
                file_put_contents('images/support/'.$imageName, $image); 
            }
        } else {
            $imageName = '';
        }
        // if ($request->file('file')) {
        //     $imagelogo = $request->file('file');
        //     $logos = time() . '.' . $imagelogo->getClientOriginalExtension();
        //     $destinationPaths = base_path('public/images/support/');
        //     $imagelogo->move($destinationPaths, $logos);
        // } else {
        //     $logos = '';
        // }
        $ticketno =  $this->randomToken();
        $uid = $request->input('uid');
        $usersdata = User::where('uid', $uid)->first();

        if (empty($usersdata)) {
            $return['code'] = 101;
            $return['msg'] = 'User Not Found !';
        } else {
            $fullname =  "$usersdata->first_name $usersdata->last_name";

            $support                   = new Supports();
            $support->uid              = $uid;
            $support->ticket_no        = $ticketno;
            $support->category         = $request->input('category');
            $support->sub_category     = $request->input('sub_category');
            $support->support_type     = $request->input('support_type');
            $support->subject          = $request->input('subject');
            $support->message          = $request->input('message');
            $support->file             = $imageName;
            $support->status           = 1;
            $support->priority         = $request->input('priority');
            if ($support->save()) {

                $supportlog                    = new SupportLog();
                $supportlog->support_id        = $support->id;
                $supportlog->ticket_no         = $ticketno;
                $supportlog->message           = $support->message;
                $supportlog->file              = $support->file;
                $supportlog->status            = 0;
                $supportlog->created_by        = 'User';
                $supportlog->user_id           = $support->uid;
                $supportlog->user_name         = $fullname;
                $supportlog->save();
                /* Activity Log  */
                $activitylog = new Activitylog();
                $activitylog->uid    = $uid;
                $activitylog->type    = 'Support';
                $activitylog->description    = 'Support Ticket' . $ticketno . ' is Added Successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                $email = $usersdata->email;
                $useridas = $usersdata->uid;
                $ticketno = $support->ticket_no;
                $data['userfullname'] = $fullname;
                $data['useridadmn'] = $useridas;
                $data['usercmpdetils'] = $support->message;
                $data['details'] = array('subject' => 'Your complaint registered', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'token_no' => $ticketno);
                /* User Section */
                $subject = "Your complaint registered $ticketno - 7Search PPC";
                $body =  View('emailtemp.supportcreate', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['msg']  = 'Mail Send & Data Inserted Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['msg']  = 'Mail Not Send But Data Insert Successfully !';
                }
                /* Admin Section  */
                $adminmail1 = 'advertisersupport@7searchppc.com';
                // $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.supportcreateadmin', $data);
                $subjectadmin ="Your complaint registered $ticketno - 7Search PPC";
                $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
                if($sendmailadmin == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['msg']  = 'Mail Send & Data Inserted Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['msg']  = 'Mail Not Send But Data Insert Successfully !';
                }
            } else {
                $return['code'] = 101;
                $return['msg'] = 'Something went wrong !';
            }
        }
        return json_encode($return);
    }

    public function list_support(Request $request)
    {
        $limit = $request->lim; 
        $page = $request->page;
        $uid = $request->uid;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $userdata = User::where('uid', $uid)->first();
      	if(empty($userdata))
        {
        	$return['code']    = 101;
            $return['msg'] = 'User not found!';
          	return json_encode($return);
        }
        $support = DB::table('supports')
          		   ->where('support_type', 'Publisher')
          		   ->where('uid', $uid)
          		   ->orderBy('id', 'DESC');
        $row = $support->count();
        $data = $support->offset($start)->limit($limit)->get();
        foreach ($data as $value) {
            $ticket_no = $value->id;
            $datamsg = SupportLog::where('support_id', $ticket_no)->orderBy('id', 'DESC')->first();
            $value->message = $datamsg->message;
            $value->message_by = $datamsg->user_name;
        }
      	if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $wltPubAmt = getPubWalletAmount($uid);
            $return['wallet']        = ($wltPubAmt) > 0 ? $wltPubAmt : $userdata->pub_wallet;
            $return['msg'] = 'Support list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return);
    }

    public function info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'ticket_no' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid = $request->uid;
        $ticketno = $request->ticket_no;
        $support = Supports::select('category', 'sub_category', 'support_type', 'subject', 'message', 'file', 'status')
            ->where('uid', $uid)->where('ticket_no', $ticketno)->orderBy('id', 'DESC')->first();
        if ($support) {
            $supportLog = SupportLog::select('user_name', 'created_by', 'ticket_no', 'user_id', 'message', 'file', 'created_at', 'status')
                ->where('user_id', $uid)->where('ticket_no', $ticketno)->orderBy('id', 'ASC')->get();
        }
        if ($supportLog) {
            $return['code']    = 200;
            $return['support']    = $support;
            $return['data']    = $supportLog;
            $return['msg'] = 'Chat list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return);
    }

    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'ticket_no' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }

        if ($request->file) {
            $base_str = explode(';base64,', $request->file);
            if($base_str[0] == 'data:application/pdf'){
                $ext = str_replace('data:application/', '', $base_str[0]);
                $image = base64_decode($base_str[1]);
                $imageName = Str::random(10) . '.' . $ext; ;
                $path = public_path('images/support/' . $imageName);
                 file_put_contents($path, $image); 
            }else{
                $ext = str_replace('data:image/', '', $base_str[0]);
                $image = base64_decode($base_str[1]);
                $imageName = Str::random(10) . '.' . $ext; ;
                file_put_contents('images/support/'.$imageName, $image); 
            }
        } else {
            $imageName = '';
        }
        // if ($request->file('file')) {
        //     $imagelogo = $request->file('file');
        //     $logos = time() . '.' . $imagelogo->getClientOriginalExtension();
        //     $destinationPaths = base_path('public/images/support/');
        //     $imagelogo->move($destinationPaths, $logos);
        // } else {
        //     $logos = '';
        // }
        $ticketno = $request->input('ticket_no');
        $uid = $request->input('uid');

        $usersdata = User::where('uid', $uid)->first();
        $fullname =  "$usersdata->first_name $usersdata->last_name";
        $support = Supports::where('uid', $uid)->where('ticket_no', $ticketno)->first();


        $supportlog                    = new SupportLog();
        $supportlog->support_id        = $support->id;
        $supportlog->ticket_no         = $ticketno;
        $supportlog->message           = $request->input('message');
        $supportlog->file              = $imageName;
        $supportlog->status            = 0;
        $supportlog->created_by        = 'User';
        $supportlog->user_id           = $uid;
        $supportlog->user_name         = $fullname;
        if ($supportlog->save()) {
          $email = $usersdata->email;
          $useridas = $usersdata->uid;
          $ticketno = $support->ticket_no;
          $data['userfullname'] = $fullname;
          $data['useridadmn'] = $useridas;
          $data['usercmpdetils'] = $support->message;
          $data['details'] = array('subject' => 'Your complaint registered', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'token_no' => $ticketno);
          
          /* Admin Section  */
          $adminmail1 = 'advertisersupport@7searchppc.com';
        //   $adminmail1 = ['advertisersupport@7searchppc.com','testing@7searchppc.com'];
          $adminmail2 = 'info@7searchppc.com';
          $bodyadmin =   View('emailtemp.supportcreateadmin', $data);
          $subjectadmin ="Your complaint registered $ticketno - 7Search PPC";
          $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
          if($sendmailadmin == '1') 
          {
            $return['code'] = 200;
            $return['data']    = $supportlog;
            $return['msg']  = 'Mail Send & Data Inserted Successfully !';
          }
          else 
          {
            $return['code'] = 200;
            $return['data']    = $supportlog;
            $return['msg']  = 'Mail Not Send But Data Insert Successfully !';
          }
          
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_no' => 'required',
            'uid' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $ticketno = $request->input('ticket_no');
        $uid = $request->input('uid');
        $support = Supports::where('uid', $uid)->where('ticket_no', $ticketno)->first();
        if ($support) {
            $delete =  $support->delete();
            if ($delete) {
                $return['code']    = 200;
                $return['msg'] = 'Deleted successfully!';
            } else {
                $return['code']    = 101;
                $return['msg'] = 'Something went wrong!';
            }
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Not Found !';
        }
        return json_encode($return);
    }
}
