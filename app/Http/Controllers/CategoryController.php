<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // public function index()
    // {
    //     $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
    //     if ($category) {
    //         $return = $category;
    //     } else {
    //         $return['code']    = 101;
    //         $return['message'] = 'Something went wrong!';
    //     }

    //     return json_encode($return, JSON_NUMERIC_CHECK);
    // }
    
    public function index()
    {
        $uid = $_GET['uid'];
        $userRecord = User::where('uid',$uid)->where('account_type', 0)->first();
        if(!empty($userRecord)){
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id','!=',113)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }else{
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return);
    }
    
    public function pubCategoryList(Request $request)
    {
      	$user = User::select('account_type')->where('uid', $request->uid)->first();
      	if($user->account_type == 0 )
        {
        	$category = Category::select('id as value', 'cat_name as label')->where('id', '!=', 113)->where('id', '!=', 64)->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
      	else{
        	$category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id', '!=', 64)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
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
