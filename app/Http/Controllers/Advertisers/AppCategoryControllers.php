<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Agent;

class AppCategoryControllers extends Controller
{

    public function categoryList()
    {
        $uid = $_GET['uid'];
        $userRecord = User::where('uid',$uid)->where('account_type', 0)->first();
        if(!empty($userRecord)){
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id','!=',113)->where('id','!=',64)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }else{
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id','!=',64)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
        $newValue = array("value" =>'64', "label"=>'All Categories');
        array_unshift($category, $newValue);
        if ($category) {
            $return['code']    = 200;
            $return['data']    = $category;
            $return['msg'] = 'NO Data Found!';
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'NO Data Found!';
        }
        return json_encode($return);
    }

    public function getCategoryList()
    {
        $category = Category::select('id as value', 'cat_name as label', 'status')
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function getUserfund(Request $request)
    {
        $getUserWallet = User::select('wallet','uid')->where('uid',$request->uid)->where('trash',0)->where('status',0)->first();
        if ($getUserWallet) {
            $return['code']    = 200;
            $return['data']    = $getUserWallet;
            $return['msg'] = 'Wallet retrieved!';
        } else {
            $return['code']    = 101;
            $return['msg'] = 'No Data Found!';
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
    
    public function getassignAgentdata(Request $request) {
        $uid = $request->uid;
        $validator = Validator::make($request->all(), [
            'uid'     => 'required'
        ]);

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['message'] = 'Validation Error';
            $return['error']   = $validator->errors();
            return json_encode($return, JSON_NUMERIC_CHECK);

        }
        $assignedAgent = Agent::select('agents.name', 'agents.email', 'agents.contact_no', 'agents.agent_id', 'agents.skype_id', 'agents.telegram_id', 'agents.profile_image')
        ->leftJoin('assign_clients', 'agents.agent_id', '=', 'assign_clients.aid')
        ->where('assign_clients.cid', $uid)
        ->first();

        if ($assignedAgent) {
            $return['code']    = 200;
            $return['data']    = $assignedAgent;
            $return['message'] = 'Data Found Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Data Not Found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
