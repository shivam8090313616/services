<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PaymentGatewayAdminController extends Controller
{
    // payment gateway create & update method
    public function create_update_gateway(Request $request)
    {
        $order = PaymentGateway::select('order_no')->orderByDesc('order_no')->first();
        $generate_order = (!empty($order)) ? $order->order_no + 1 : 1;
        
        $existgateway = PaymentGateway::select('id', 'image')->where('id', $request->id)->first();
        $validator = Validator::make(
            $request->all(),
            [

                'title' => $request->id ? "required|max:20|unique:payment_gateways,title," . $existgateway->id :
                    "required|max:20|unique:payment_gateways,title",
                'sub_title' => 'required|max:30',
                'image' => $request->id ? 'mimes:png|max:1024' :
                    'required|mimes:png|max:1024',
                'order_no' => !empty($request->id > 0) ? 'required' : '',
                'status' => !empty($request->id > 0) ? 'required' : '',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        if ($generate_order > 20) {
            return json_encode([
                'code' => 101,
                'message' => 'Maximum Generate Order 20 Limit!',
            ]);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '.' . $extension;
            $file->move(public_path('gateway-image'), $fileName);

            // Delete previous profile image if exists
            if (!empty($existgateway) && $existgateway->image) {
                $imagePath = public_path('gateway-image/' . $existgateway->image);
                if (File::exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $save_image = $fileName;
        } else {
            $save_image = $existgateway ? $existgateway->image : null;
        }

        $storegateway = PaymentGateway::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'title'     => $request->title,
                'sub_title' => $request->sub_title,
                'image'     => $save_image,
                'order_no' => ($request->id && !empty($request->order_no)) ? $request->order_no : $generate_order,
                'status' => $request->id > 0 ? $request->status : 0,
            ]
        );
        if (!empty($storegateway)) {
            $return['code']    = 200;
            $return['message'] = $request->id > 0 ? 'Payment Gateway Updated Successfully!' : 'Payment Gateway Added Successfully!';
        } else if (!$existgateway) {
            $return['code'] = 101;
            $return['message'] = "Invalid id";
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function gatewayList(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $getList = DB::table('payment_gateways');
        $row = $getList->count();
        $data = $getList->offset($start)->orderByDesc('id')->limit($limit)->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']    = $row;
            $return['message'] = 'Data Found Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Data Not Found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    // api for sent otp update payment gateway on crm
    public function sendOtpgateway()
    {
        $otp = mt_rand(100000, 999999);
        $email = ['deepaklogelite@gmail.com','ry0085840@gmail.com','rajeevgp1596@gmail.com'];
        $data['details'] = ['subject' => 'Your One-Time Password (OTP) for change payment gateway status. - 7Search PPC', 'otp' => $otp];
        $subject = 'Your One-Time Password (OTP) for change payment gateway status. - 7Search PPC';
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

    public function statusUpdate(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => "required",
                'status' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }
        if ($request->status == 0 || $request->status == 1) {
            $updateStatus = DB::table('payment_gateways')->where('id', $request->id)->update(['status' => $request->status]);
            if ($updateStatus) {
                $return['code']    = 200;
                $return['message'] = 'Status Updated Successfully!';
            } else {
                $return['code']    = 101;
                $return['message'] = 'Id Or Payment Gateway Status is Invalid!';
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Status Value is Invalid!';
            return json_encode($return);
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
