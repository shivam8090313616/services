<?php

namespace App\Http\Controllers;

use App\Models\ProfileLogs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommanProfileController extends Controller
{
    public function userProfileLogList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid'       => 'required',
            ],[
                'uid.required'=>'The User ID field is required.'
            ]
        );
        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $profile_data = [];
        ($request->type == 'advertiser') ? $utype = 1 : $utype = 2;
        $data = ProfileLogs::select('uid','profile_data','user_type', DB::raw("DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as createdat"))->where('uid',$uid)
        ->where(function($query) use ($utype) {
            $query->where('user_type', $utype)
                  ->orWhere('user_type', 3);
        });
        //->where('user_type', $utype);
        $count = $data->count();
        $data = $data->offset($start)->limit($limit)->orderBy('id','DESC')->get();
        foreach ($data as $log) {
            $profile_data = [json_decode($log->profile_data)];
            $log->profile_data = $profile_data;
        }
        if ($data) {
            $return['data']    = $data;
            $return['row']     = $count;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }  
     public function appUserProfileLogList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid'       => 'required',
            ],[
                'uid.required'=>'The User ID field is required.'
            ]
        );
        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $profile_data = [];
        ($request->type == 'advertiser') ? $utype = 1 : $utype = 2;
        $data = ProfileLogs::select('uid','profile_data','user_type', DB::raw("DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as createdat"))->where('uid',$uid)
        ->where(function($query) use ($utype) {
            $query->where('user_type', $utype)
                  ->orWhere('user_type', 3);
        });
        //->where('user_type', $utype);
        $count = $data->count();
        $data = $data->orderBy('id','DESC')->get();
        foreach ($data as $log) {
            $profile_data = [json_decode($log->profile_data)];
            $log->profile_data = $profile_data;
        }
        if ($data) {
            $return['data']    = $data;
            $return['row']     = $count;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }  
    public function adminUserProfileLogList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid'       => 'required',
            ],[
                'uid.required'=>'The User ID field is required.'
            ]
        );

        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1; 
        $start = ($pg > 0) ? $limit * $pg : 0;
        $profile_data = [];
        $data = ProfileLogs::select('uid','profile_data','user_type','created_at','switcher_login')->where('uid',$uid);
        $count = $data->count();
        $data = $data->offset($start)->limit($limit)->orderBy('id','DESC')->get();
        foreach ($data as $log) {
            $profile_data = [json_decode($log->profile_data)];
            $log->profile_data = $profile_data;
        }

        if ($data) {
            $return['data']    = json_decode($data);
            $return['row']     = $count;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    } 
}
