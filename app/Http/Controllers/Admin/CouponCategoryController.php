<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\CouponCategory;
use App\Models\Coupon;
class CouponCategoryController extends Controller
{
    /**
    * @OA\Post(
    *     path="/api/admin/coupon/category/list",
    *     summary="Get Coupon Category list",
    *     tags={"Coupon Category"},
       *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"lim,page"},
    *             @OA\Property(property="lim", type="integer", description="Limit"),
    *             @OA\Property(property="page", type="integer", description="Page"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Coupon Category List Successful",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="cat_name", type="string"),
    *                     @OA\Property(property="status", type="integer"),
    *                     @OA\Property(property="created_at", type="string")
    *                 )
    *             ),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */
    public function couponCategoryList(Request $request){
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $cpnlist = CouponCategory::select('id', 'cat_name', 'status', 'created_at', 'visibility')
        ->selectRaw('(SELECT COUNT(*) FROM ss_coupons WHERE ss_coupons.coupon_cat = ss_coupon_categories.id) as cpncount')
        ->where('trash', 0)
        ->offset($start)
        ->limit($limit)
        ->orderByDesc('id')
        ->get();
        $row = count($cpnlist);
        if (count($cpnlist) > 0) {
          $return['code']    = 200;
          $return['data']    = $cpnlist;
          $return['row']     = $row;
          $return['message'] = ' Data successfully';
      } else {
          $return['code']    = 101;
          $return['message'] = 'Something went wrong!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /**
    * @OA\Post(
    *     path="/api/admin/coupon/category/store",
    *     summary="Create And Update Coupon Category",
    *     tags={"Coupon Category"},
       *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"category_name,id"},
    *              @OA\Property(property="category_name", type="string", description="Category Name"),
    *              @OA\Property(property="id", type="string", description="Category id"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Add & Update Coupon Category",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */
    public function addCouponCategory(Request $request){
        $id= $request->cid;
        $validator = Validator::make(
            $request->all(),
            [
                'category_name' => 'required|unique:coupon_categories,cat_name,'. $id,
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $couponCategoryList= CouponCategory::find($id);
        if(is_null($couponCategoryList)){
            $couponCategoryList     = new CouponCategory();
            $couponCategoryList->cat_name = $request->category_name;
            $msg = 'Coupon Category Added Successfully!';
        }else{
            $couponCategoryList->cat_name = $request->category_name;
            $msg = 'Coupon Category Updated Successfully!';
        }
         if($couponCategoryList->save()){
              $return['code'] = 200;
              $return['message']  = $msg;
         }else{
             $return['code']    = 101;
             $return['message'] = 'Something went wrong!';
         }
         return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /**
    * @OA\Get(
    *     path="/api/admin/get/coupon/category/list",
    *     summary="Get Coupon Category list Drapdown",
    *     tags={"Coupon Category"},
       *     @OA\RequestBody(
    *         required=true,
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Coupon Category List Successful",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="value", type="integer"),
    *                     @OA\Property(property="lable", type="string")
    *                 )
    *             ),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */
    public function getCouponCategoryList(){
        $cpnlist = CouponCategory::select('id as value','cat_name as label')->where('status', 1)->where('trash', 0)->get();
        if (count($cpnlist) > 0) {
         $return['code']    = 200;
         $return['data']    = $cpnlist;
         $return['message'] = ' Data successfully';
     } else {
         $return['code']    = 101;
         $return['message'] = 'Something went wrong!';
     }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }

       /**
    * @OA\Post(
    *     path="/api/admin/get/coupon/category/update-status",
    *     summary="Status Update Coupon Category",
    *     tags={"Coupon Category"},
       *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"cid"},
    *              @OA\Property(property="cid", type="integer", description="Category id"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Status Update Successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */
    
   public function couponCatStatusUpdate(Request $request){
    $cid = $request->cid;
    $validator = Validator::make(
        $request->all(),
        [
            'cid' => 'required',
        ],
    );
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    if(CouponCategory::find($cid)){
        $updatedData = CouponCategory::where('status',0)->where('id', $cid)->first();
        if($updatedData){
            CouponCategory::where('status',1)->update(['status' => 0,'visibility'=>0]);
            CouponCategory::where('id',$cid)->update(['status' => 1]);
            $return['code']    = 200;
            $return['message'] = 'Status Updated successfully';
        }else{
            $return['code']    = 101;
            $return['message'] = 'Please make one category at least active.';
        }
    }else{
            $return['code']    = 101;
            $return['message'] = 'Category id is Something went wrong!';
    } 
    
return json_encode($return, JSON_NUMERIC_CHECK);
}


// display coupon offers

 /**
    * @OA\Post(
    *     path="/api/admin/get/coupon/category/update-visibility",
    *     summary="Coupon Visibility Status Update Coupon Category",
    *     tags={"Coupon Category"},
       *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"cid,visibility"},
    *              @OA\Property(property="cid", type="integer", description="Category id"),
    *              @OA\Property(property="visibility", type="integer", description="Visibility Status"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Status Update Successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */

public function displayoffersupdate(Request $request){
    $cid = $request->cid;
    $visibility = $request->visibility;
    $validator = Validator::make(
        $request->all(),
        [
            'cid' => 'required',
            'visibility' => 'required',
        ],
    );
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    if(CouponCategory::find($cid)){
        $countcoupon = Coupon::where("coupon_cat",$cid)->count();
        if($countcoupon == 0){
            $return['code']    = 101;
            $return['message'] = 'Please first add coupon in this category!';
        } else if($countcoupon > 0){       
            $updatedData = CouponCategory::where('status',1)->where('id', $cid)->first();
            if($updatedData){
            CouponCategory::where('status',1)->update(['visibility' => $visibility]);
            $return['code']    = 200;
            $return['message'] = 'Status Updated successfully';
            }
            else{
            $return['code']    = 101;
            $return['message'] = 'Please first active coupon category status!';
            }
        }
    }else{
            $return['code']    = 101;
            $return['message'] = 'Category id is Something went wrong!';
    } 

    return json_encode($return, JSON_NUMERIC_CHECK);
    }

}
