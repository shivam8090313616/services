<?php



namespace App\Http\Controllers;



use App\Models\Country;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\Coupon;
use App\Models\CouponCategory;
use App\Models\UsedCoupon;

class CouponUserController extends Controller

{

    // public function getcalCoupon(Request $request)

    // {

    //     $validator = Validator::make(

    //         $request->all(),

    //         [

    //             'uid' => 'required',

    //             'coupon_code' => 'required',

    //             'coupon_amt' => 'required',

    //         ]

    //     );

    //     if ($validator->fails()) {

    //         $return['code']    = 101;

    //         $return['message'] = 'Please enter coupon code ';

    //         return $return;

    //     }
    //     $userid = $request->input('uid')
    //     $couponcode = $request->input('coupon_code');

    //     $couponamt = $request->input('coupon_amt');

    //     $getcalampdata = getCouponCal($userid, $couponcode, $couponamt);

    //     return json_encode($getcalampdata);
    // }


    /**
    * Get calculated coupon.
    *
    * @OA\Post(
    *     path="/api/user/apply/coupon",
    *     summary="Get calculated coupon",
    *     tags={"Coupon"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Provide user ID, coupon code, and coupon amount",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(property="uid", type="integer", description="User ID"),
    *                 @OA\Property(property="coupon_code", type="string", description="Coupon code"),
    *                 @OA\Property(property="coupon_amt", type="number", description="Coupon amount")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="object", description="Coupon calculation data")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message"),
    *             @OA\Property(property="errors", type="object", description="Validation errors")
    *         )
    *     )
    * )
    */
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
        // $usedcoupon = UsedCoupon::where('advertiser_code', $request->uid)->where('coupon_id', $couponid)->first();
        // if (!empty($usedcoupon)) {
        //     $return['code']    = 102;
        //     $return['message'] = 'Coupon is already used';
        //     return json_encode($return);
        // } else {
        //     $userid     = $request->input('uid');
        //     $couponamt  = $request->input('coupon_amt');
        //     $getcalampdata = getCouponCal($userid, $couponcode, $couponamt, $couponid);
        // }
        $userid     = $request->input('uid');
        $couponamt  = $request->input('coupon_amt');
        $getcalampdata = getCouponCal($userid, $couponcode, $couponamt, $couponid);
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

    /**
    * Get calculated coupon.
    *
    * @OA\Post(
    *     path="/api/user/category-wise-coupon-list",
    *     summary="Get Category Wise Sub-Coupon List",
    *     tags={"Coupon"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Provide user ID, coupon code, and coupon amount",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                   required={"user_id"},
    *                 @OA\Property(property="user_id", type="integer", description="User ID"),
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="object", description="Coupon calculation data")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message"),
    *             @OA\Property(property="errors", type="object", description="Validation errors")
    *         )
    *     )
    * )
    */

    public function categoryWiseCouponList(){
        $currentData = Date('Y-m-d');
        $cpnlist = CouponCategory::select('id','cat_name','visibility','status')->where('status',1)->where('trash', 0)->first();
        if($cpnlist->status == 1 && $cpnlist->visibility == 1){
            $coupondata = Coupon::select('coupon_code','coupon_color_code','coupon_description','end_date')->where('status', 1)->where('user_ids',0)->where('trash',0)
            ->whereDate('end_date','>=', $currentData)
            ->where('coupon_cat', $cpnlist->id)->orderBy('min_bil_amt','asc')->get();
            if(count($coupondata)>0){
                $return['code']    = 200;
                $return['data'][$cpnlist->cat_name]   = $coupondata;
                $return['message'] = ' Data successfully';
            }else{
                $return['code']    = 101;
                $return['message'] = 'Something went wrong!';
                return $return;
            }
        }else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

