<?php

namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AppPaymentCoinQrController extends Controller
{

    public function bitcoin_qrcode(Request $request)
    {
         $validator = Validator::make(
            $request->all(),
            [
                'uid' => "required",
                'amount' => "required",
                'payble' => "required",
                'fee' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $uid = $request->input('uid');
        $users = DB::table('users')->select(DB::raw("CONCAT(ss_users.first_name,' ',ss_users.last_name) AS full_name, email, phone, uid,website_category"))
            ->where('uid',$uid)
            ->first();
        if (empty($users)) {
            $return['code'] = 101;
            $return['msg'] = 'Not found User ';
            return response()->json($return);
        }
        else
        {
            $amt = $request->payble;
            $adfund                  = new Transaction();
            $txnid                   = 'TXN'.strtoupper(uniqid());
            $adfund->advertiser_code = $users->uid;
            $adfund->transaction_id  = $txnid;
            $adfund->payment_mode    = 'bitcoin';
            $adfund->amount          = $request->amount;
            $adfund->payble_amt      = $amt;
            $adfund->fee             = $request->fee;
          	$adfund->fees_tax        = $request->fee_tax;
            $adfund->gst             = $request->gst;
            $adfund->cpn_amt         = $request->cpn_amt;
            $adfund->cpn_code        = $request->cpn_code;
            $adfund->cpn_id          = $request->cpn_id;
            $adfund->status          = 0;
            $adfund->category        = $users->website_category;
            $adfund->payment_resource = ($request->request_for == 'App') ? 'app' : 'web';
            if($adfund->save())
            {
                $transactionid =  $adfund->transaction_id;
                $return ['code']        = 200;
                $return ['msg']     = 'Paymnet Successful';
                $return ['transaction'] = $transactionid;
            }
            else
            {
                $return ['code']    = 101;
                $return ['msg'] = 'Something went wrong!';
            }
            /* $data['user'] =  $users;
              $data['txnids'] =  $txnid;
              $data['amounts'] =  $amtinr; */
        }
        return response()->json($return);
    }
    public function upload_screen(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'transaction_id' => "required",
                'screenshot' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
         $trxid = $request->transaction_id;
         $trxdata = Transaction::where('transaction_id',$trxid)->first();
         if (empty($trxdata)) 
         {
            $return['code'] = 100;
            $return['msg'] = 'Not found Transaction';
            return response()->json($return);
         }
         else
         {
            $img = $request->screenshot;
            //$logos = '';
            if($request->file('screenshot')) 
            {
                $imagelogo = $request->file('screenshot');
                $logos = time().'.'.$imagelogo->getClientOriginalExtension();
                $destinationPaths = base_path('public/images/bitcoin/');
                $imagelogo->move($destinationPaths, $logos);
            }
            $trxdata->screenshot = $logos;
            if ($trxdata->save())
            {
                $return['code'] = 200;
                $return['msg'] = 'Informatiom has been Updated.';
            }
            else 
            {
                $return['code'] = 101;
                $return['msg'] = 'Error: ';
            }
         return response()->json($return);
     }

    }

    public function upload_screen_mobile(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'transaction_id' => "required",
                'screenshot' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
         $trxid = $request->transaction_id;
         $trxdata = Transaction::where('transaction_id',$trxid)->first();
         if (empty($trxdata)) 
         {
            $return['code'] = 100;
            $return['msg'] = 'Not found Transaction';
            return response()->json($return);
         }
         else
         {
            $img = $request->screenshot;
            //$logos = '';
            if($request->screenshot) 
            {
                $base_str = explode(';base64,', $request->screenshot);
                $ext = str_replace('data:image/', '', $base_str[0]);
                $image = base64_decode($base_str[1]);
                $file = base64_decode($base_str[1]);
                $safeName = md5(Str::random(10)) . '.' . $ext;
                $success = file_put_contents(public_path().'/images/bitcoin/'.$safeName, $file);
            }
            $trxdata->screenshot = $safeName;
            if ($trxdata->save())
            {
                $return['code'] = 200;
                $return['msg'] = 'Informatiom has been Updated.';
            }
            else 
            {
                $return['code'] = 101;
                $return['msg'] = 'Error: ';
            }
         return response()->json($return);
     }

    }
    
}
