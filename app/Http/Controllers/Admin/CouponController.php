<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\ChangePass;
use Carbon\Carbon;
use App\Models\CountriesIps;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;


class CouponController extends Controller
{

    public function couponusersendmail()
    {
        $getCouponData = Coupon::where('mailsend', 1)->get()->toArray();

        if (!empty($getCouponData)) {

            foreach ($getCouponData as $value) {
                $userid = $value['user_ids'];
                $cpmids = $value['id'];
                $coupon =  Coupon::select('id', 'mailsend')->where('id', $cpmids)->first();
                $coupon->mailsend = 2;
                $coupon->save();

                if ($userid == 0) {
                    $udata = User::where('user_type', 1)->where('status', 0)->where('trash', 0)->get();

                    foreach ($udata as $uvalue) {
                        $uvalueid = $uvalue['id'];
                        $userdata = User::select('uid', 'email', 'first_name', 'last_name')->where('id', $uvalueid)->first();
                        /* User Section  */
                        $cpm['fullname'] = $userdata->first_name . ' ' . $userdata->last_name;
                        $cpm['uid'] = $userdata->uid;
                        $cpm['email'] = $userdata->email;
                        /* Coupon Deatils */
                        $cpm['cpmtitle'] = $value['title'];
                        $cpm['cpmcode'] = $value['coupon_code'];
                        $cpm['cpmtype'] = $value['coupon_type'];
                        $cpm['cpmminbilamt'] = $value['min_bil_amt'];
                        $cpm['cpmcouponvalue'] = $value['coupon_value'];
                        $cpm['cpmmaxdisc'] = $value['max_disc'];
                        $cpm['cpmstartdate'] = $value['start_date'];
                        $cpm['cpmenddate'] = $value['end_date'];
                        $cpm['subject'] = 'Coupon Received from 7Search PPC';
                        /* Coupon Deatils */
                        $email =$userdata->email;
                        $subject = 'Special Coupon from 7SearchPPC';
                        $body =  View('emailtemp.couponuser', $cpm);
                        /* User Mail Section */
                        sendmailUser($subject,$body,$email);  
                    }
                } else {
                    $udata =  explode(",", $userid);
                    foreach ($udata as $uvalue) {
                        $userdata = User::select('uid', 'email', 'first_name', 'last_name')->where('id', $uvalue)->first();
                        /* User Section  */
                        $cpm['fullname'] = $userdata->first_name . ' ' . $userdata->last_name;
                        $cpm['uid'] = $userdata->uid;
                        $cpm['email'] = $userdata->email;
                        /* Coupon Deatils */
                        $cpm['cpmtitle'] = $value['title'];
                        $cpm['cpmcode'] = $value['coupon_code'];
                        $cpm['cpmtype'] = $value['coupon_type'];
                        $cpm['cpmminbilamt'] = $value['min_bil_amt'];
                        $cpm['cpmcouponvalue'] = $value['coupon_value'];
                        $cpm['cpmmaxdisc'] = $value['max_disc'];
                        $cpm['cpmstartdate'] = $value['start_date'];
                        $cpm['cpmenddate'] = $value['end_date'];
                        $cpm['subject'] = 'Coupon Received from 7Search PPC';
                        /* Coupon Deatils */
                         $email =$userdata->email;
                        $subject = 'Special Coupon from 7SearchPPC';
                        $body =  View('emailtemp.couponuser', $cpm);
                        /* User Mail Section */
                        sendmailUser($subject,$body,$email);  
                    }
                }
            }
            $return['code']    = 200;
            $return['message'] = 'Mail Send Successfully!';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Not Found Data!';
        }
        echo json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function couponExpired(){
        $getCouponData = Coupon::whereDate('end_date', '<' , date('Y-m-d'))->get();
        $count = 0;
        foreach ($getCouponData as $key => $value) {
            Coupon::where('id',$value->id)->update(['status' => 2]);
            $count++;
        }
      if( $count > 0){
        $return['code']    = 200;
        $return['message'] = 'Coupon Expired Successfully!';
      }else{
        $return['code']    = 100;
        $return['message'] = 'Not Found Data!';
      }
      echo json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function userList()
    {

        $getdata = DB::table('users')
            ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name, ' - ', (ss_users.email) ) as label"), 'id as value')
            ->where('user_type', 1)
            ->where('status', 0)
            ->where('trash', 0)
            ->get();
        if (empty($query)) {
            $return['code'] = 200;
            $return['data'] = $getdata;
            $return['message'] = 'Successfully found !';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }

    public function randomcmpid()
    {
        $utype = 'CPN';
        $cpnid =  $utype . strtoupper(uniqid());
        $checkdata = Coupon::where('coupon_id', $cpnid)->count();
        if ($checkdata > 0) {
            $this->randomcmpid();
        } else {
            return $cpnid;
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'coupon_code' => 'required|unique:coupons,coupon_code|max:15',
                'coupon_type' => 'required',
                'min_bil_amt' => 'required|numeric',
                'coupon_value' => 'required',
                'max_disc' => 'required|numeric',
                'start_date' => 'required|date|date_format:Y-m-d',
                'end_date' => 'required|date|date_format:Y-m-d',
                'coupon_description' =>'max:205',
                'coupon_limit_type' => 'required',
                'coupon_limit_value' => $request->input('coupon_limit_type') == 'Limited' ? 'required|numeric' : '',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }
        if($request->coupon_limit_type == 'Limited' && $request->coupon_limit_value == 0){
            return json_encode([
                'code' => 101,
                'message' => 'Please enter coupon limit value must be greate than zero!'
            ]);
        }
        if($request->coupon_limit_type != 'Limited' && $request->coupon_limit_type !='Unlimited'){
            return json_encode([
                'code' => 101,
                'message' => 'Invalid coupon limit type!'
            ]);
        }
        $coupon_catdata = DB::table("coupon_categories")->select('id')->where('id',$request->coupon_cat)->where('status',1)->first();
        if(!$coupon_catdata && !empty($request->coupon_cat)){
            return json_encode([
                'code' => 101,
                'message' => 'Invalid coupon category id!'
            ]);
        }
        $coupon = new Coupon();
        $coupon->title         = $request->title;
        $coupon->coupon_cat = $request->coupon_cat;
        $coupon->coupon_description = $request->coupon_description;
        $coupon->coupon_color_code = $request->coupon_color_code ? $request->coupon_color_code : '#000';
        $coupon->coupon_id     =  $this->randomcmpid();
        $coupon->coupon_code   = strtoupper($request->coupon_code);
        $coupon->coupon_type   = $request->coupon_type;
        $coupon->coupon_limit_type = $request->coupon_limit_type;
        $coupon->coupon_limit_value = ($request->coupon_limit_type == 'Limited') ? $request->coupon_limit_value : 0;
        $userids      = $request->user_id;
        if ($userids == '0') {
            $coupon->user_ids      = $request->user_id;
        } else {
            $useridreq = json_decode($request->userid_details);
            $array = array_column($useridreq, 'value');
            $coupon->user_ids = implode(',', $array);
        }
        $coupon->min_bil_amt   = $request->min_bil_amt;
        $coupon->coupon_value  = $request->coupon_value;
        $coupon->max_disc      = $request->max_disc;
        $coupon->start_date    = $request->start_date;
        $coupon->end_date      = $request->end_date;
        $coupon->status        = 1;
        $coupon->userid_details =  $request->userid_details;
        $coupon->trash         = 0;
        if ($coupon->save()) {
            $return['code']    = 200;
            $return['message'] = 'Coupon added successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function list(Request $request)
    {
        $type = $request->type;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $getdata = DB::table('coupons')
            ->leftJoin('coupon_categories', 'coupons.coupon_cat', '=', 'coupon_categories.id')
            ->select(DB::raw("IF(ss_coupons.user_ids != 0, (SELECT GROUP_CONCAT(u.first_name, u.last_name) as uname FROM ss_users u WHERE FIND_IN_SET(u.id, ss_coupons.user_ids)), 'ALL' ) AS users, (SELECT COUNT(id) FROM ss_used_coupons cpnuse WHERE  cpnuse.coupon_id = ss_coupons.coupon_id) as cpncount"), 'coupons.id', 'coupons.coupon_id', 'coupons.title', 'coupons.coupon_code', 'coupons.coupon_type', 'coupons.min_bil_amt', 'coupons.coupon_value', 'coupons.max_disc', 'coupons.start_date', 'coupons.end_date', 'coupons.status', 'coupons.userid_details', 'coupons.created_at', 'coupons.updated_at','coupons.coupon_color_code','coupons.coupon_description','coupons.coupon_cat','coupon_categories.cat_name','coupons.coupon_limit_type','coupon_limit_value') 
            ->where('coupons.trash', 0);
        if (strlen($type) > 0) {
            $getdata->where('coupons.status', $type);
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
        } else if ($src) {
            $getdata->whereRaw('concat(ss_coupons.title,ss_coupons.coupon_id,ss_coupon_categories.cat_name) like ?', "%{$src}%");
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
        }else {
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
        }
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

    public function trace_coupon(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'coupon_id' => 'required|numeric|max:9999999',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }
        $couponid = $request->coupon_id;
        $coupondata = Coupon::where('id', $couponid)->first();
        if (empty($coupondata)) {
            $return['code']    = 101;
            $return['message'] = 'Coupon id Not Found Data !';
            echo json_encode($return);
        }
        $coupondata->trash   = 1;
        if ($coupondata->save()) {
            $return['code']    = 102;
            $return['message'] = 'Delete Coupon Successfully !';
        } else {
            $return['code']    = 102;
            $return['message'] = 'Not Delete Coupon  !';
        }
        echo json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function update_coupon(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|numeric|max:9999999',
                'title' => 'required',
                'coupon_code' => 'required',
                'coupon_type' => 'required',
                'min_bil_amt' => 'required|numeric',
                'coupon_value' => 'required',
                'coupon_description' =>'max:205',
                'coupon_limit_type' => 'required',
                'coupon_limit_value' => $request->input('coupon_limit_type') == 'Limited' ? 'required|numeric' : '',

            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }
        if($request->coupon_limit_type == 'Limited' && $request->coupon_limit_value == 0){
            return json_encode([
                'code' => 101,
                'message' => 'Please enter coupon limit value must be greate than zero!'
            ]);
        }
        if($request->coupon_limit_type != 'Limited' && $request->coupon_limit_type !='Unlimited'){
            return json_encode([
                'code' => 101,
                'message' => 'Invalid coupon limit type!'
            ]);
        }
        $coupon_catdata = DB::table("coupon_categories")->select('id')->where('id',$request->coupon_cat)->first();
        if(!$coupon_catdata && !empty($request->coupon_cat)){
            return json_encode([
                'code' => 101,
                'message' => 'Invalid coupon category id!'
            ]);
        }
        $couponid = $request->id;
        $coupon = Coupon::where('id', $couponid)->where('trash', 0)->first();
        if (empty($coupon)) {
            $return['code']    = 101;
            $return['message'] = 'Coupon id Not Found Data !';
            return json_encode($return);
        }

        $coupon->title         = $request->title;
        $coupon->coupon_code   = strtoupper($request->coupon_code);
        $coupon->coupon_type   = $request->coupon_type;
        //$userids               = $request->user_id;
        $userids               = $request->userid_details == null ? 0 : $request->userid_details;
        if ($userids == 0) {
            /*$coupon->user_ids   = $request->user_id;
            $coupon->userid_details      = $request->user_id;*/
            $coupon->user_ids   = 0;
            $coupon->userid_details      = 0;
        } else {
            $useridreq = json_decode($request->userid_details);
            $array = array_column($useridreq, 'value');
            $coupon->user_ids = implode(',', $array);
            $coupon->userid_details      = $request->userid_details;
        }
        $coupon->min_bil_amt   = $request->min_bil_amt;
        $coupon->coupon_value  = $request->coupon_value;
        $coupon->max_disc      = $request->max_disc;
        $coupon->start_date    = $request->start_date;
        $coupon->end_date      = $request->end_date;
        $coupon->coupon_cat = $request->coupon_cat;
        $coupon->coupon_description = $request->coupon_description;
        $coupon->coupon_color_code = $request->coupon_color_code;
        $coupon->coupon_limit_type = $request->coupon_limit_type;
        $coupon->coupon_limit_value = ($request->coupon_limit_type == 'Limited') ? $request->coupon_limit_value : 0;
        if($coupon->update()) {
            $return['code']    = 200;
            $return['data']    = $coupon;
            $return['message'] = 'Coupon Updated successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function couponStatusUpdate(Request $request)
    {
        $id = $request->id;
        $newStatus = $request->sts;
        $coupon =  Coupon::where('id', $id)->first();

        $coupon->status = $newStatus;

        if ($coupon->update()) {
            $return['code']    = 200;
            $return['data']    = $coupon;
            $return['message'] = 'User Status updated!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function delete_coupon(Request $request)
    {
        $id = $request->id;
        $coupon = Coupon::where('id', $id)->first();
        $coupon->trash = 1;
        if ($coupon->update()) {
            $return['code'] = 200;
            $return['data'] = 'Deleted Successfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function otprandom()
    {
        $randomno =  rand(1000, 9999);
        $checkdata = ChangePass::where('otp', $randomno)->count();
        if ($checkdata > 0) {
            $this->otprandom();
        } else {
            return $randomno;
        }
    }

    public function changepass(Request $request)
    {

        $cpass = $request->cpass;
        $newpass = $request->newpass;
        $reaptpass = $request->reaptpass;
        $otp = $request->otp;
        $uid = 'ADV627A2B929F1D5';
        $udata = User::where('uid', $uid)->first();
        if (empty($otp)) {

            if (Hash::check($cpass, $udata->password)) {

                $changepassword = new ChangePass();
                $changepassword->user_id  = $udata->id;
                $changepassword->otp   = $this->otprandom();
                $changepassword->save();
                /* Mail section  */
                $fullname = 'User';
                $sendotp =  $changepassword->otp;
                $mailsentdetals['details'] = ['fullname' => $fullname, 'otp' => $sendotp];
                $detail["email"] = $udata->email;
                $detail["title"] = 'Request Change Password OTP !';
                Mail::send('emailtemp.changepassword', $mailsentdetals, function ($message) use ($detail) {
                    $message->to($detail["email"])
                        ->subject($detail["title"]);
                });
                /* End Mail Section */
                $return['code']    = 201;
                $return['message'] = 'Send Successfully';
            } else {
                $return['code']    = 103;
                $return['message'] = 'Not Match Password';
            }
        } else {
            if (Hash::check($cpass, $udata->password)) {
                $changedata = ChangePass::where('user_id', $udata->id)->get();
                foreach ($changedata as $value) {
                    $vaotp = $value->otp;
                    if ($vaotp == $otp) {
                        $udata->password = Hash::make($newpass);
                        if ($udata->save()) {
                            $return['code']    = 200;
                            $return['message'] = 'Password Chanage Successfully';
                        }
                    } else {
                        $return['code']    = 103;
                        $return['message'] = 'Not Match OTP !';
                    }
                }
            } else {
                $return['code']    = 103;
                $return['message'] = 'Not Match Password';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function upload_cuntry_ip()
    {
        $data = file_get_contents('./ip_file.txt');
        $data = preg_replace('/\s+/', '-', $data);
        $data1 = explode('-', $data);
        // dd($data1);
        foreach ($data1 as $newline) {
            $country = new CountriesIps();
            $country->ip_addr = $newline;
            $country->country_code = 'US';
            $country->country_name = 'UNITED STATES';
            $country->state = '';
            if ($country->save()) {
                echo "Data Insert Successfully";
            } else {
                echo "Data Not Insert Successfully";
            }
        }
    }
}
  