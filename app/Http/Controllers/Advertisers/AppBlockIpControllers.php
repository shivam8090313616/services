<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use App\Models\BlockIp;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AppBlockIpControllers extends Controller
{
    public function store(Request  $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'ip_address' => 'required|ipv4',
            'description'       => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Validation error!';
            return json_encode($return);
        }
        $usersuid = $request->uid;
        $users = User::where('uid', $usersuid)->first();
        if (empty($users)) {
            $return['code'] = 101;
            $return['error'] = 'Invalid User ID';
            return json_encode($return);
        }
        $validateIP = file_get_contents('https://proxy.7searchppc.com/index.php?ip=' . $request->ip_address);
        if ($validateIP == 'n' || $validateIP == 'N') {
            $ipType = 'real';
        } else {
            $ipType = 'proxy';
        }
        $ip = new BlockIp();
        $ip->uid = $request->uid;
        $ip->ip = $request->ip_address;
        $ip->ip_type = $ipType;
        $ip->desc = $request->description;
        $ip->status = '0';
        // dd($ip);
        if ($ip->save()) {
            $activitylog = new Activitylog();
            $activitylog->uid    = $request->uid;
            $activitylog->type    = 'Ip Block';
            $activitylog->description    = '' . $ip->ip . ' is Added Succssfully';
            $activitylog->status    = '1';
            $activitylog->save();
            $return['code'] = 200;
            $return['data'] = $ip;
            $return['msg'] = 'Ip Block request successfully added!';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    public function user_block_ip(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required'
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Validation Error';
            return json_encode($return);
        }
        $useruid = $request->uid;
        $userdata = User::where('uid', $useruid)->first();
        if (empty($userdata)) {
            $return['code'] = 101;
            $return['msg'] = 'Invalid User ID';
        }

        $limit = $request->limit;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $getdata = BlockIp::where('uid', $useruid)->orderBy('id', 'DESC');
        $row = $getdata->count();
        $data = $getdata->offset($start)->limit($limit)->get();
        if (!empty($getdata)) {
            $return['code']  = 200;
            $return['data']  = $data;
            $return['row']     = $row;
            $return['msg'] = 'User Block IP';
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Not Found Ip Address';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
