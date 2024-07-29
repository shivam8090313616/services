<?php

namespace App\Http\Controllers\AppPublisher;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppPublisherCategoryController extends Controller
{
    public function index()
    {
        $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function pubCategoryListOld(Request $request)
    {
      	$user = User::select('account_type')->where('uid', $request->uid)->first();
      	if(isset($user->account_type) && $user->account_type == 0 )
        {
        	$category = Category::select('id as value', 'cat_name as label')->where('id', '!=', 113)->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
      	else{
        	$category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function pubCategoryList(Request $request)
    {
      	$user = User::select('account_type')->where('uid', $request->uid)->first();
      	if(isset($user->account_type) && $user->account_type == 0 ){
            $category = Category::select('cat_name as label', 'id as value')
            ->where('id', '!=',113)
            ->where('id','!=',64)
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('cat_name', 'asc')->get()->toArray();
        }else{
        	$category = Category::select('id as value', 'cat_name as label')->where('id','!=',64)->where('status', 1)->where('trash', 0)->orderBy('cat_name', 'asc')->get()->toArray();
        }
        $newValue = array("value" =>'64', "label"=>'All Categories');
        array_unshift($category, $newValue);
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function getCategoryList()
    {
        $category = Category::select('id as value', 'cat_name as label', 'status')
            ->where('trash', 0)
            ->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
