<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Coupon;
use App\Models\UsedCoupon;

class AppCouponUserController extends Controller
{
    public function getcalCoupon(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'coupon_code' => 'required',
                'coupon_amt' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 101;
            $return['message'] = 'Please enter coupon code ';
            return json_encode($return);
        }
        $couponcode = $request->input('coupon_code');
        $coupondata = Coupon::where('coupon_code', $couponcode)->where('status', 1)->where('trash', 0)->first();
        if ($coupondata) {
            $couponid  = $coupondata->coupon_id;
        } else {
            $coupondatas = Coupon::where('coupon_code', $couponcode)->where('status', 2)->where('trash', 0)->first();
            if($coupondatas){
                $return['code']    = 101;
                $return['message'] = 'Coupon Expired.';
                return json_encode($return);
            }
            $coupondatainactive= Coupon::where('coupon_code', $couponcode)->where('status', 0)->where('trash', 0)->first();
            if( $coupondatainactive){
                $return['code']    = 101;
                $return['message'] = 'Coupon InActive.';
                return json_encode($return);
            } 
            $return['code']    = 101;
            $return['message'] = 'Please enter valid coupon code.';
            return json_encode($return);
        }
        $usedcoupon = UsedCoupon::where('advertiser_code', $request->uid)->where('coupon_id', $couponid)->first();
        if (!empty($usedcoupon)) {
            $return['code']    = 102;
            $return['message'] = 'Coupon is already used';
            return json_encode($return);
        } else {
            $userid     = $request->input('uid');
            $couponamt  = $request->input('coupon_amt');
            $getcalampdata = getCouponCal($userid, $couponcode, $couponamt, $couponid);
        }
        return json_encode($getcalampdata, JSON_NUMERIC_CHECK);
    }

    public function tdaycpmdeactive()
    {
        $tdates = date('Y-m-d');
        $tdate =  date('Y-m-d', (strtotime('-1 day', strtotime($tdates))));
        $coupondata = Coupon::where('status', 1)->whereDate('end_date', $tdate)->get()->toArray();
        if (!empty($coupondata)) {
            foreach ($coupondata as $value) {
                $coupon =  Coupon::where('id', $value['id'])->first();
                $coupon->status = 0;
                $coupon->update();
            }
            $return['code'] = 101;
            $return['msg'] = 'Coupon is Deactive Successfully !';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Not Found Coupon !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
