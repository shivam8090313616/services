<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User_otp;

class ManageAdminPaymentSettingController extends Controller
{
    public function managePaymentList()
    {
        $data = DB::table('panel_customizations')->select('payment_title','payment_header','payment_min_amt','payment_description','id','placeholder','info_desc','desc_status')->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']    = count($data);
            $return['message'] = 'get List Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function updateManagePayment(Request $request)
    { 
        $validator = Validator::make($request->all(),
        [
            'payment_title' => 'required',
            'payment_min_amt' => 'required',
        ]);
        if($validator->fails())
        {
            $return ['code']    = 100;
            $return ['error']   = $validator->errors();
            $return ['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
         if(empty($request->id)){
            $return['code']    = 101;
            $return['message'] = 'Something went wrong payment id!';
            return json_encode($return, JSON_NUMERIC_CHECK);
         }
         $validator = Validator::make(
            $request->all(),
            [
                'payment_min_amt' => 'required|numeric|min:20',
                'payment_title' => 'required',
            ],[
                'payment_min_amt.required' => 'The payment Minimum Amount field is required.',
                'payment_min_amt.min' => 'The payment Minimum Amount must be at least $20.',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $profileLog = [];
        $getRecords =  DB::table('panel_customizations')->where('id', $request->id)->first();
        if($getRecords->payment_title != $request->payment_title){
            $profileLog['paymentTitle']['previous'] =  $getRecords->payment_title;
            $profileLog['paymentTitle']['updated']  =  $request->payment_title;
        }
        if($getRecords->payment_header != $request->payment_header){
          $profileLog['paymentHeader']['previous'] = $getRecords->payment_header;
          $profileLog['paymentHeader']['updated']  =  $request->payment_header;
        }
          if($getRecords->payment_min_amt != $request->payment_min_amt){
            $profileLog['paymentMinAmt']['previous'] = $getRecords->payment_min_amt;
            $profileLog['paymentMinAmt']['updated']  =  $request->payment_min_amt;
          }
          if($getRecords->payment_description != $request->payment_description){
            $profileLog['paymentDescription']['previous'] = $getRecords->payment_description;
            $profileLog['paymentDescription']['updated']  =  $request->payment_description;
          }
          if($getRecords->placeholder != $request->placeholder){
            $profileLog['placeholder']['previous'] = $getRecords->placeholder;
            $profileLog['placeholder']['updated']  =  $request->placeholder;
          }
          if($getRecords->info_desc != $request->info){
            $profileLog['infoDesc']['previous'] = $getRecords->info_desc;
            $profileLog['infoDesc']['updated']  =  $request->info;
          }
          if($getRecords->desc_status != $request->status){
            $profileLog['descStatus']['previous'] = $getRecords->desc_status;
            $profileLog['descStatus']['updated']  =  $request->status;
          }
          if(count($profileLog) > 0){
            $profileLog['message'] =  "Record updated successfully.";
            $data = json_encode($profileLog);
            DB::table('common_logs')->insert(['uid' => 1,'type_module'=>'payment-Min-Amt','description'=>$data, 'created_at'=>date('Y-m-d H:i:s')]);
          }
           $res =  DB::table('panel_customizations')
            ->where('id', $request->id)
            ->update(['payment_title' => $request->payment_title,'payment_header' => $request->payment_header,'payment_min_amt' => $request->payment_min_amt,'payment_description' => $request->payment_description,'placeholder' => $request->placeholder,'info_desc' => $request->info,'desc_status' => $request->status]); 
        if($res > 0){   
            $return['code']    = 200;
            $return['message'] = 'Payment Updated Successfully!';
        }else{
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!'; 
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function userGetPaymentList()
    {
        $data = DB::table('panel_customizations')->select('payment_title','payment_header','payment_min_amt','payment_description','placeholder','info_desc','desc_status')->first();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['message'] = 'Get List Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    static function randomcmpid()
    {
        $cpnid = mt_rand(100000, 999999);
        $checkdata = User_otp::where('otp', $cpnid)->count();
        if ($checkdata > 0) {
            self::randomcmpid();
        } else {
            return $cpnid;
        }
    } 
    // api for sent otp update payment page on crm
    public function sendOtpUpdateAmt(Request $request){
        $otp = self::randomcmpid();
        $email = ['deepaklogelite@gmail.com','ry0085840@gmail.com','rajeevgp1596@gmail.com'];
        $data['details'] = ['subject' => 'Your One-Time Password (OTP) for min. pay amount limit. - 7Search PPC','otp'=>$otp];
            /* User Section */
            $subject = 'Your One-Time Password (OTP) for min. pay amount limit. - 7Search PPC';
            $body =  View('emailtemp.paymentVerificationMail', $data);
            /* User Mail Section */
            $res = sendmailpaymentupdate($subject,$body,$email);
            if($res == 1){
                $return['code'] = 200;
                $return['data'] = base64_encode($otp);
                $return['msg'] = 'Otp Sent Successfully.';
            }else{
                $return['code'] = 101;
                $return['msg'] = 'Email Not Send.';
            }
             return response()->json($return);
    }
    public function PaymentLogsList(Request $request){
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $data = DB::table('common_logs')->orderBy('id','desc')->offset($start)->limit($limit)->get();
        $row = $data->count();
         foreach ($data as $log) {
            $description = [json_decode($log->description)];
            $log->description = $description;
        }
        if ($data) {
            $return['code']    = 200;
            $return['row']     = $row;
            $return['data']    =  $data;
            $return['message'] = 'Get List Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!'; 
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}