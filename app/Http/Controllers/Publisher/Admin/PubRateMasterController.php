<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Publisher\PubRateMaster;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PubRateMasterController extends Controller
{

    public function storeRateMaster(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_id'    => 'required',
                'country_id'     => 'required',
                'cpc'            => 'required',
                'cpm'            => 'required',
              	'cpa_imp'        => 'required',
              	'cpa_click'      => 'required',
              	'video_adv'      => 'required',
              	'video_pub'      => 'required',
              	'pub_cpm'      	 => 'required',
              	'pub_cpc'        => 'required',
            ],
            [
                'category_id.required'    => 'Please select category',
                'country_id.required'     => 'Please select country',
                'cpc.required'            => 'Please enter cpc rate',
                'cpm.required'            => 'Please enter cpm rate',
              	'cpa_imp.required'        => 'Please enter cpa impression rate',
              	'cpa_click.required'      => 'Please enter cpa click rate',
              	'video_adv.required'      => 'Please enter video advertiser rate',
              	'video_pub.required'      => 'Please enter video publisher rate',
              	'pub_cpm.required'        => 'Please enter publisher cpm rate',
              	'pub_cpc.required'        => 'Please enter publisher cpc rate',
            ]
        );
        if ($validator->fails()) {
            $return['code'] 	= 100;
            $return['error'] 	= $validator->errors();
            $return['message'] 	= 'Valitation error!';
            return json_encode($return);
        }
        $exist_rate_master = PubRateMaster::where([['category_id', '=', $request->category_id], ['country_id', '=', $request->country_id]])->first();
        $category 		   = Category::where('id',$request->category_id)->select('cat_name')->first();
        $country 		   = Country::where('id',$request->country_id)->select('name')->first();
        if ($exist_rate_master == '') {
            $pubratemaster 					= new PubRateMaster;
            $pubratemaster->category_id 	= $request->category_id;
            $pubratemaster->category_name 	= $category->cat_name;
            $pubratemaster->country_id 		= $request->country_id;
            $pubratemaster->country_name 	= $country->name;
            $pubratemaster->cpc 			= $request->cpc;
            $pubratemaster->cpm 			= $request->cpm;
          	$pubratemaster->cpa_imp 		= $request->cpa_imp;
          	$pubratemaster->cpa_click 		= $request->cpa_click;
          	$pubratemaster->video_adv 		= $request->video_adv;
          	$pubratemaster->video_pub 		= $request->video_pub;
          	$pubratemaster->pub_cpm 		= $request->pub_cpm;
          	$pubratemaster->pub_cpc 		= $request->pub_cpc;
            if ($pubratemaster->save()) {
                $return['code'] 	= 200;
                $return['message']  = 'Data inserted successfully!';
            }
        } else {
            $return['code'] 	= 101;
            $return['message'] 	= 'Record Already exist!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updateRateMaster(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id'             => 'required',
                'cpc'            => 'required',
                'cpm'            => 'required',
              	'cpa_imp'        => 'required',
              	'cpa_click'      => 'required',
              	'video_adv'      => 'required',
              	'video_pub'      => 'required',
              	'pub_cpm'      	 => 'required',
              	'pub_cpc'        => 'required',
            ],
            [
                'id.required'    		  => 'Please enter id',
                'cpc.required'            => 'Please enter cpc rate',
                'cpm.required'            => 'Please enter cpm rate',
              	'cpa_imp.required'        => 'Please enter cpa impression rate',
              	'cpa_click.required'      => 'Please enter cpa click rate',
              	'video_adv.required'      => 'Please enter video advertiser rate',
              	'video_pub.required'      => 'Please enter video publisher rate',
              	'pub_cpm.required'        => 'Please enter publisher cpm rate',
              	'pub_cpc.required'        => 'Please enter publisher cpc rate',
            ]
        );
        if ($validator->fails()) {
            $return['code'] 	= 100;
            $return['error'] 	= $validator->errors();
            $return['message'] 	= 'Valitation error!';
            return json_encode($return);
        }
        $pubratemaster			 	= PubRateMaster::find($request->id);
        $category_id                = $pubratemaster->category_id;
        $country_id                = $pubratemaster->country_id;
        $pubratemaster->cpc 		= $request->cpc;
        $pubratemaster->cpm 		= $request->cpm;
        $pubratemaster->cpa_imp 	= $request->cpa_imp;
        $pubratemaster->cpa_click 	= $request->cpa_click;
        $pubratemaster->video_adv 	= $request->video_adv;
        $pubratemaster->video_pub 	= $request->video_pub;
        $pubratemaster->pub_cpm 	= $request->pub_cpm;
        $pubratemaster->pub_cpc 	= $request->pub_cpc;
        if ($pubratemaster->save()) {
            $return['code'] 	= 200;
            $return['data'] 	= $pubratemaster;
            $return['message'] 	= 'Data updated successfully!';
        /* This will update Ad Rate data into Redis */
        updateAdRate($category_id, $country_id, 0);
        } else {
            $return['code'] 	= 101;
            $return['message'] 	= 'Something wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function listRateMaster(Request $request)
    {
      	$sort_order = $request->sort_order;
      	$col = $request->col;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $searchValue = $request->src;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $ratemaster =DB::table('pub_rate_masters')
          			->select('pub_rate_masters.id', 'pub_rate_masters.category_id', 'pub_rate_masters.country_id', 'pub_rate_masters.created_at', 'pub_rate_masters.country_name', 'pub_rate_masters.cpc', 'pub_rate_masters.cpm', 
                             'pub_rate_masters.cpa_imp', 'pub_rate_masters.cpa_click', 'pub_rate_masters.video_adv', 'pub_rate_masters.video_pub', 'pub_rate_masters.pub_cpm', 'pub_rate_masters.pub_cpc', 'pub_rate_masters.status', 'categories.cat_name as category_name')
          			->join('categories', 'pub_rate_masters.category_id', '=', 'categories.id');
        if($request->category_id !='' && $request->country_id == ''){
            $ratemaster = $ratemaster->where('pub_rate_masters.category_id',$request->category_id)
            ;
        }
        if($request->category_id =='' && $request->country_id != ''){
            $ratemaster = $ratemaster->where('pub_rate_masters.country_id',$request->country_id)
            ;
        }
        if($request->category_id !='' && $request->country_id != ''){
            $ratemaster = $ratemaster->where([['pub_rate_masters.category_id',$request->category_id],['pub_rate_masters.country_id',$request->country_id]]);
        }
        if($searchValue){
            $ratemaster = $ratemaster->where('pub_rate_masters.country_id',$request->country_id)->orwhere('category_name', 'LIKE', '%'.$searchValue.'%')->orwhere('country_name', 'LIKE', '%'.$searchValue.'%');
        }
        $row  = $ratemaster->count();
      	if($col)
        {
          $data  = $ratemaster->offset( $start )->limit( $limit )->orderBy('pub_rate_masters.'.$col, $sort_order)->get();
        }
        else
        {
          $data  = $ratemaster->offset($start)->limit($limit)->orderBy('pub_rate_masters.id', 'DESC')->get();
        }
        //$data       = $ratemaster->offset($start)->limit($limit)->orderBy('id', 'DESC')->get();
        if (count($data) > 0) {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row']  = $row;
            $return['message'] = 'Data Successfully found!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Data Not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function rateMasterInfo(Request $request)
    {
        $ratemasterinfo = PubRateMaster::find($request->id);
        if ($ratemasterinfo != '') {
            $return['code'] = 200;
            $return['data'] = $ratemasterinfo;
            $return['message'] = 'Data Successfully found!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Data Not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function statusUpdate(Request $request)
    {
        $pubratemaster = PubRateMaster::find($request->id);
        $category_id = $pubratemaster->category_id;
        $country_id = $pubratemaster->country_id;
        $status = $request->status;
        $pubratemaster->status = $status;
        if ($pubratemaster->save()) {
            $return['code'] = 200;
            $return['data'] = $pubratemaster;
            $return['message'] = 'Status updated successfully!';
        /* This will update Ad Rate status into Redis */
        updateAdRate($category_id, $country_id, $status);
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
