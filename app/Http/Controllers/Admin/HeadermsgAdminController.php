<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HeaderMessage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class HeadermsgAdminController extends Controller
{
    // create & update HeaderMessage
    public function create_update_msg(Request $request)
    {
        $match = HeaderMessage::find($request->id);
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|numeric',
                'header_content' => 'required',
                'slider_content' => 'required|max:300',
                'content_speed' => 'required|numeric',
                'status' => 'required|numeric',
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        if (!$match && !empty($request->id)) {
            $return['code'] = 101;
            $return['message'] = 'Invalid update id!';
        } else {
            $storemsg = HeaderMessage::updateOrCreate(
                [
                    'id' => $request->id,
                ],
                [
                    'header_content' => $request->header_content,
                    'slider_content' => $request->slider_content,
                    'content_speed' => $request->content_speed,
                    'status' => $request->status ? $request->status : 0,
                ]
            );
            if (!empty($storemsg)) {
                $return['code'] = 200;
                $return['message'] = $request->id > 0 ? 'Record Updated Successfully!' : 'Record Added Successfully!';
            } else {
                $return['code'] = 101;
                $return['message'] = 'Something went wrong!';
            }
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function header_msg_list(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $getList = DB::table('header_messages');
        $row = $getList->count();
        $data = $getList->offset($start)->limit($limit)->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']    = $row;
            $return['message'] = 'Data Fetched Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Data Not Found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    // api for sent otp update payment gateway on crm
    public function sendOtpmsg()
    {
        $otp = mt_rand(100000, 999999);
        $email = ['deepaklogelite@gmail.com','ry0085840@gmail.com','rajeevgp1596@gmail.com'];
        $data['details'] = ['subject' => 'Your One-Time Password (OTP) for change header message. - 7Search PPC', 'otp' => $otp];
        $subject = 'Your One-Time Password (OTP) for change header message. - 7Search PPC';
        $body =  View('emailtemp.paymentVerificationMail', $data);
        $res = sendmailpaymentupdate($subject, $body, $email);
        if ($res == 1) {
            $return['code'] = 200;
            $return['data'] = base64_encode($otp);
            $return['msg'] = 'Otp Sent Successfully.';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Email Not Send.';
        }
        return response()->json($return);
    }
}
