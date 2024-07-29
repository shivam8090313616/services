<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PopupMessage;
use Illuminate\Support\Facades\File;

class PopupAdminMsgController extends Controller
{

    // create & update popmsgadmin
    public function createPopupMessage(Request $request)

    {
        $existImage = PopupMessage::find($request->input('id'));
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'sub_title' => 'required',
            'image' => $request->input('id') ? 'image|mimes:jpeg,jpg,png,gif' : 'required|image|mimes:jpeg,jpg,png,gif',
            // 'image' => $request->input('id') ? 'image|mimes:jpeg,jpg,png,gif|dimensions:width=400,height=300' : 'required|image|mimes:jpeg,jpg,png,gif|dimensions:width=400,height=300',
            'message' => 'required',
            'btn_content' => 'required',
            'btn_link' => 'required',
            'id' => 'nullable|exists:popup_messages,id',
            'status' => !empty($request->id > 0) ? 'required' : '',
        ], [
            'image.required' => 'The image field is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif.',
            // 'image.dimensions' => 'The image must be exactly 400x300 pixels.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 100,
                'message' => 'Validation Error',
                'error' => $validator->errors()
            ]);
        }
        if ($request->hasFile('image')) {

            if (!empty($existImage)) {

                $imagePath = public_path('popup-images/' . $existImage->image);

                if (File::exists($imagePath)) {

                    File::delete($imagePath);
                }
            }

            $file = $request->file('image');

            $extension = $file->getClientOriginalExtension();

            $fileName = uniqid() . '.' . $extension;

            $file->move(public_path('popup-images'), $fileName);
        }

        $data = [

            'title' => $request->input('title'),

            'sub_title' => $request->input('sub_title'),

            'message' => $request->input('message'),

            'btn_content' => $request->input('btn_content'),

            'btn_link' => $request->input('btn_link'),

            'status' => ($request->status && $request->id > 0) ? $request->status : 0

        ];
        if (isset($fileName)) {

            $data['image'] = $fileName;
        }
        // Update or create record

        PopupMessage::updateOrCreate(['id' => $request->input('id')], $data);
        // Prepare response
        $return = [
           'code' => $request->input('id') ? 200 : 200,

            'message' => $request->input('id') ? 'Record updated successfully!' : 'Record created successfully!'

        ];

        return response()->json($return);
    }

    public function listPopupMessage(Request $request)
    {
        $limit  = $request->lim;
        $page   = $request->page;
        $pg     = $page - 1;
        $start  = ($pg > 0) ? $limit * $pg : 0;
        $support = PopupMessage::offset($start)->limit($limit)->get();
        $row = PopupMessage::count();
        if (count($support) > 0) {
            $return['code']    = 200;
            $return['data']    = $support;
            $return['row']     = $row;
            $return['message'] = 'popup Message list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    static function randomcmpid()
    {
        $cpnid = mt_rand(100000, 999999);
        return $cpnid;
    }
    public function sendOtpPopupMessage(Request $request)
    {
        $otp = self::randomcmpid();
        // $email = ['info@7searchppc.com'];
        $email = ['deepaklogelite@gmail.com','ry0085840@gmail.com','rajeevgp1596@gmail.com'];
        $data['details'] = ['subject' => 'Your One-Time Password (OTP) for Popup Message. - 7Search PPC', 'otp' => $otp];
        /* User Section */
        $subject = 'Your One-Time Password (OTP) for Popup Message. - 7Search PPC';
        $body =  View('emailtemp.paymentVerificationMail', $data);
        /* User Mail Section */
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
