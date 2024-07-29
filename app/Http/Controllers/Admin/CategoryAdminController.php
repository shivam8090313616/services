<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;



class CategoryAdminController extends Controller
{
    public function getCategoryList(Request $request)
    {
        $src =  $request->src;
        $category = DB::table('categories')
            ->select(DB::raw("ss_categories.id as value, ss_categories.cat_name as label, ss_categories.status,ss_categories.cpm, ss_categories.cpc, ss_categories.cpa_imp, ss_categories.cpa_click, 
            ss_categories.video_adv, ss_categories.video_pub, ss_categories.pub_cpm, ss_categories.pub_cpc, ss_categories.display_brand,  
            (select count(id) from ss_users users where users.website_category = ss_categories.id AND users.account_type = '0') as client, 
            (select count(id) from ss_users users where users.website_category = ss_categories.id AND users.account_type = '1') as inhouse"))
            ->where('categories.trash', 0);

        if ($src) {
            $category->whereRaw('concat(ss_categories.cat_name) like ?', "%{$src}%");
        }

        $data = $category->orderBy('categories.id', 'desc')->get()->toArray();
        if ($category) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['message'] = 'Category list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }



        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function getCampCategoryList(Request $request)
    {
        $category = Category::select('id as value', 'cat_name as label', 'status', 'cpm', 'cpc')
            ->where('trash', 0)
            ->where('status', 1)
            ->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $src =  $request->src;
            if ($src) {
                $categy = Category::select('id as value', 'cat_name as label', 'status', 'cpm', 'cpc')
                    ->where('trash', 0)
                    ->whereRaw('concat(ss_categories.cat_name) like ?', "%{$src}%")
                    ->orderBy('label', 'asc')->get()->toArray();
                $return = $categy;
            } else {
                $return = $category;
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function droplist()
    {
        $category = Category::select('cat_name as label', 'id as value')
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $return['code']    = 200;
            $return['data']    = $category;
            $return['msg']     = 'Successfully found Data !';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function droplistweb()
    {
        $category = Category::select('cat_name as value', 'id')
        ->where('id', '!=',113)
        ->where('id','!=',64)
        ->where('status', 1)
        ->where('trash', 0)
        ->orderBy('cat_name', 'asc')->get()->toArray();
        $newValue = array("id" =>'64', "value"=>'All Categories');
        array_unshift($category, $newValue);
        if ($category) {
            $return['code']    = 200;
            $return['msg']     = 'Successfully found Data !';
            $return['data']    = $category;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return ($return);
    }

    public function regCatList()
    {
        $category = Category::select('id as id', 'cat_name as value')
            ->where('id', '!=',113)
            ->where('id', '!=',64)
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('cat_name', 'ASC')->get()->toArray();
        if ($category) {
            $return['code']    = 200;
            $return['msg']     = 'Successfully found Data !';
            $return['data']    = $category;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return ($return);
    }

    public function dropliscountrytweb()
    {
        $category = DB::table('countries')
            ->select(DB::raw('concat("+", left(numcode, 10) ) as `id`, concat("+", left(numcode, 10) ) as value'))
            ->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($category) {
            $return['code']    = 200;
            $return['msg']     = 'Successfully found Data !';
            $return['data']    = $category;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return ($return);
    }

    public function droplistwebcountry()
    {
        $countriedata = DB::table('countries')
            ->select(DB::raw('concat(name )as `id`, concat(name) as value'))
            ->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($countriedata) {
            $return['code']    = 200;
            $return['msg']     = 'Successfully found Data !';
            $return['data']    = $countriedata;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return ($return);
    }

    public function countryphncode(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }
        $name =  $request->input('name');
        $countriephn = Country::select('phonecode')->where('name', $name)->first();
        $varplush = '+';
        $countriephns = $countriephn->phonecode;
        $finalreturn = $varplush . $countriephns;
        if ($countriephn) {
            $return['code']    = 200;
            $return['msg']     = 'Successfully found Data !';
            $return['data']    = $finalreturn;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Not Found data Phone Code !';
        }
        return ($return);
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                // 'cat_name' => 'required',
                'cat_name' => "required|unique:categories|max:20",
                'cpm' => 'required|numeric|max:999',
                'cpc' => 'required|numeric|max:999',
              	'cpa_imp' => 'required|numeric|max:999',
              	'cpa_click' => 'required|numeric|max:999',
              	'video_adv' => 'required|numeric|max:999',
                'video_pub' => 'required|numeric|max:999',
              	'pub_cpm' => 'required|numeric|max:999',
                'pub_cpc' => 'required|numeric|max:999',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }

        $category            = new Category();
        $category->cat_name  = $request->cat_name;
        $category->cpm       = $request->cpm;
        $category->cpc       = $request->cpc;
      	$category->cpa_imp   = $request->cpa_imp;
      	$category->cpa_click = $request->cpa_click;
      	$category->video_adv = $request->video_adv;
      	$category->video_pub = $request->video_pub;
      	$category->pub_cpm   = $request->pub_cpm;
        $category->pub_cpc   = $request->pub_cpc;
        if ($category->save()) {
            $return['code']    = 200;
            $return['data']    = $category;
            $return['message'] = 'Category added successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }


        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'cat_name' => "required",
                // 'cmp' => 'required',
                'cpc' => 'required|numeric',
              	'pub_cpm' => 'required|numeric|max:999',
                'pub_cpc' => 'required|numeric|max:999',
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $id                 = $request->cid;
        $category           = Category::find($id);
        $category->cat_name = $request->cat_name;
        $category->cpm      = $request->cpm;
        $category->cpc      = $request->cpc;
      	$category->cpa_imp   = $request->cpa_imp;
      	$category->cpa_click = $request->cpa_click;
      	$category->video_adv = $request->video_adv;
      	$category->video_pub = $request->video_pub;
      	$category->pub_cpm  = $request->pub_cpm;
        $category->pub_cpc  = $request->pub_cpc;
        if ($category->update()) {
            $return['code']    = 200;
            $return['data']    = $category;
            $return['message'] = 'Updated Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        /* This will update category into Redis */
        updateCategory($id, 1);
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $category = Category::find($id);
        $category->trash = 1;

        if ($category->update()) {
            $return['code']    = 200;
            $return['message'] = 'Category deleted successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function categoryUpdateStatus(Request $request)
    {
        $id = $request->uid;
        $status = $request->status;
        $category  = Category::where('id', $request->uid)->first();
        $category->status = $request->status;
        if ($category->update()) {
            $return['code']    = 200;
            $return['data']    = $category;
            $return['message'] = 'Category Status updated!';
        /* This will update category into Redis */
        updateCategory($id, $status);
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function brandUpdateStatus(Request $request)
    {
        $category  = Category::where('id', $request->id)->first();
        $category->display_brand = $request->brand;
        if ($category->update()) {
            setDisplayBrand($request->id, $request->brand);
            $return['code']    = 200;
            $return['message'] = 'Category Brand Status updated!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
