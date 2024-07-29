<?php



namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\SupportType;

use App\Models\Supports;

use App\Models\SupportLog;

use App\Models\User;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Exception;



class PubSupportAdminController extends Controller

{

    public function pubListSupport(Request $request)

    {

        $limit  = $request->lim;

        $src    = $request->src;

        $page   = $request->page;

        $pg     = $page - 1;

        $start  = ($pg > 0) ? $limit * $pg : 0;

        $support = Supports::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as name"), 'supports.uid', 'supports.ticket_no', 'supports.category', 'supports.sub_category', 'supports.support_type', 'supports.subject', 'support_logs.message', 'supports.file', 'supports.status', 'supports.priority', 'support_logs.created_at', 'support_logs.updated_at')

        ->join('support_logs', 'support_logs.support_id', '=', 'supports.id')

        ->join('users', 'users.uid', '=', 'supports.uid')

        ->where('support_type', 'Publisher');

        if ($src) {
            $support->whereRaw("concat(ss_users.first_name, ' ', ss_users.last_name, ss_supports.ticket_no, ss_users.uid) like ?", "%{$src}%");
        }

        $support->orderBy('support_logs.id', 'DESC');

        // ->get();

        // print_r($support); exit;

        $row = $support->count();

        $data = $support->offset($start)->limit($limit)->groupBy('support_logs.ticket_no')->get();

        if ($data) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['row']     = $row;

            $return['message'] = 'Support list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function one_support(Request $request)

    {

        $ticketno   = $request->input('ticket_no');

        $support    = DB::table('supports')->where('ticket_no', $ticketno);

        $row        = $support->first();

        if ($row) {

            $return['code']    = 200;

            $return['data']    = $row;

            $return['message'] = 'Support list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }





    public function info(Request $request)

    {



        $validator = Validator::make($request->all(), [

            'ticket_no' => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']     = 100;

            $return['error']    = $validator->errors();

            $return['message']  = 'Valitation error!';

            return json_encode($return);

        }



        $ticketno   = $request->ticket_no;

        $support    = Supports::select('category', 'sub_category', 'support_type', 'subject', 'message', 'file', 'status')->where('ticket_no', $ticketno)->orderBy('id', 'DESC')->first();

        if ($support) {

            // $supportLog = SupportLog::select('user_name', 'created_by', 'ticket_no', 'user_id', 'message', 'file', 'created_at', 'status')->where('ticket_no', $ticketno)->orderBy('id', 'ASC')->get();
            $supportLog = SupportLog::select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) as user_name"), 'support_logs.created_by','support_logs.ticket_no','support_logs.user_id','support_logs.message','support_logs.file','support_logs.created_at','support_logs.status')
            ->join('users', 'users.uid', '=', 'support_logs.user_id')
            ->where('support_logs.ticket_no',$ticketno)
            ->orderBy("support_logs.id","ASC")
            ->get();

        }

        if ($supportLog) {

            $return['code']     = 200;

            $return['support']  = $support;

            $return['data']     = $supportLog;

            $return['message']  = 'Chat list retrieved successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function chat(Request $request)

    {

        $validator = Validator::make($request->all(), [

            'ticket_no' => 'required',

            'message' => 'required',

        ]);

        if ($validator->fails()) {

            $return['code']     = 100;

            $return['error']    = $validator->errors();

            $return['message']  = 'Valitation error!';

            return json_encode($return);

        }

        if ($request->file('file')) {

            $imagelogo          = $request->file('file');

            $logos              = time() . '.' . $imagelogo->getClientOriginalExtension();

            $destinationPaths   = base_path('public/images/support/');

            $imagelogo->move($destinationPaths, $logos);

        } else {

            $logos = '';

        }



        $ticketno   = $request->input('ticket_no');

        $support    = Supports::where('ticket_no', $ticketno)->first();

        $uid        = $support->uid;

        $msgstatus  = $request->input('msgstatus');

        if ($msgstatus == 1) {

            $msgsstatus = '1';

        } else if ($msgstatus == 2) {

            $msgsstatus = '2';

        } else if ($msgstatus == 5) {

            $msgsstatus = '5';

        } else {

            $msgsstatus =  $support->status;

        }

        $support->status = $msgsstatus;

        $support->save();

        $supportlog                    = new SupportLog();

        $supportlog->support_id        = $support->id;

        $supportlog->ticket_no         = $ticketno;

        $supportlog->message           = $request->input('message');

        $supportlog->file              = $logos;

        $supportlog->status            = 0;

        $supportlog->created_by        = '7Search PPC';

        $supportlog->user_id           = $uid;

        $supportlog->user_name         = '7Search PPC';

        if ($supportlog->save()) {

            $userdata = User::where('uid', $uid)->first();

            $fullname = $userdata->first_name . $userdata->last_name;

            $useridas = $uid;

            $data['usercmpdetils'] = $supportlog->message;

            $data["email"] = $userdata->email;

            $data["subjectadmin"] = "Complaint Response";

            $email = $userdata->email;





            $data['details'] = array('subject' => 'Your complaint registered', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'token_no' => $ticketno);

 

            $subject ="Reply from 7Search PPC #$ticketno";

            $body =  View('emailtemp.pubsupportreplyuser', $data);

            $sendmailUser =  sendmailUser($subject,$body,$email);

            if($sendmailUser == '1') 

            {

                $return['code'] = 200;

                $return['data']    = $supportlog;

                $return['message']  = 'Mail Send & Data Inserted Successfully !';

            }

            else 

            {

               $return['code'] = 200;

               $return['data']    = $supportlog;

               $return['message']  = 'Mail Not Send But Data Insert Successfully !';

            } 

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

}

