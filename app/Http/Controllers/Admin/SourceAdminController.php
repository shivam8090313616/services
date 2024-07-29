<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Source;
use Carbon\Carbon;

   class SourceAdminController extends Controller
{
    public function list(){
        $sourcelist = Source::select('source_type as value', 'title as label')->where('status', 1)->orderBy('title', 'asc')->get()->toArray();
        if($sourcelist){
            $return  =  $sourcelist;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
     public function createAndUpdateSource(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'source_type' => 'required|unique:sources,source_type,' . $request->id . '|max:60',
                'title' => 'required|unique:sources,title,' . $request->id . '|max:60',
            ],
            [
                'source_type.required' => 'Please Enter Source Type',
                'source_type.unique' => 'Source type already exists. Please enter a unique source type.',
                'title.required' => 'Please Enter Source Title',
                'title.unique' => 'Source Title already exists. Please enter a unique source Title.',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
          Source::updateOrCreate(
            ['id' => $request->id],
            [
                'source_type' => $request->source_type,
                'title' => $request->title,
                'updated_at' => Carbon::now()
            ]
        );
        if($request->id){
            $return['code']  = 200;
            $return['message'] = 'Updated Successfully';
        }else{
            $return['code']  = 200;
            $return['message'] = 'Added Successfully';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function sourceList(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'lim' => 'required',
                'page' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $type = $request->type;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
    if (strlen($type) > 0 && !$src) {
        $data = Source::where('status', $type)->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
    } else if ($src && !$type) {
        $data = Source::where('source_type', 'like', '%' . $src . '%')->orwhere('title', 'like', '%' . $src . '%')->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
    }else if ($src && $type){
        $data = Source::where('status', $type)->where('source_type', 'like', '%' . $src . '%')->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
    }else {
        $data = Source::offset($start)->limit($limit)->orderBy('id', 'desc')->get();
    }
    $row = Source::count();
    if ($row === 0) {
        $return['code'] = 101;
        $return['message'] = 'Not Found Data!';
    }else{
        $return['code'] = 200;
        $return['data'] = $data;
        $return['row'] = $row;
        $return['message'] = 'Successfully!';
    }
    return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function statusUpdate(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
                'status' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $res =  DB::table('sources')->where('id', $request->id)->update(['status' => $request->status]);
        if($request->id && $res == 1){
            $return['code']  = 200;
            $return['message'] = 'Updated Successfully';
        }else{
            $return['code']  = 200;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function sendOtp(){
        $otp = self::randomcmpid();
        $email = ['ry0085840@gmail.com','rajeevgp1596@gmail.com'];
        $data['details'] = ['subject' => 'Your One-Time Password (OTP) for Source Message. - 7Search PPC', 'otp' => $otp];
        /* User Section */
        $subject = 'Your One-Time Password (OTP) for Source Message. - 7Search PPC';
        $body =  View('emailtemp.paymentVerificationMail', $data);
        /* User Mail Section */
        $res = sendmailpaymentupdate($subject, $body, $email);
        if ($res == 1) {
            $date = date('Y-m-d H:i:s');
            $eamilExist  = DB::table('user_otps')->where('email','ry0085840@gmail.com')->first();
            if($eamilExist){
                DB::table('user_otps')->where('email','ry0085840@gmail.com')->update(['otp'=>$otp, 'updated_at'=>$date]);
            }else{
                DB::table('user_otps')->insert(['email'=>'ry0085840@gmail.com','otp'=>$otp,'created_at'=>$date, 'updated_at'=>$date]);
            }
            $return['code'] = 200;
            $return['data'] = base64_encode($otp);
            $return['message'] = 'Otp Sent Successfully.';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Email Not Send.';
        }
        return response()->json($return);
    }
    static function randomcmpid()
    {
        $cpnid = mt_rand(100000, 999999);
        return $cpnid;
    } 
    public function verifyOTP(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'otp' => 'required|min:6|max:6',
            ],
            [
                'otp.required' => 'This Field OTP is Required',
                'otp.max'      => 'This Field OTP must be exactly 6 digits',
                'otp.min'      => 'This Field OTP must be exactly 6 digits',
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Validation error!';
            return response()->json($return);
        }
        $res = DB::table('user_otps')->where('otp', $request->otp)->first();
        if ($res) {
            $now = now();
            $otpCreatedTime = Carbon::parse($res->updated_at);
            if ($now->diffInMinutes($otpCreatedTime) <= 15) {
                DB::table('user_otps')->where('id', $res->id)->delete();
                $return['code'] = 200;
                $return['message'] = 'Verify Otp Successfully.';
            } else {
                DB::table('user_otps')->where('id', $res->id)->delete();
                $return['code'] = 102;
                $return['message'] = 'OTP has expired!';
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return response()->json($return);
    }
}
