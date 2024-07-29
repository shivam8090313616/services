<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlockIpAdminController extends Controller
{
    /* Open Admin - All IPs List  */

    public function alliplist(Request $request)
    {
        $type = $request->type;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        if (strlen($type) > 0) {
            $getdata = BlockIp::where('status', $type)->orderBy('id', 'DESC');
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->get();
        } elseif (empty($type) > 0 && !empty($src)) {
            $getdata = BlockIp::where('ip', 'like', '%' . $src . '%')->orderBy('id', 'DESC');
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->get();
        } else {
            $getdata = BlockIp::orderBy('id', 'DESC');
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->get();
        }
        if (!empty($getdata)) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'All Block Ip';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found Ip Address';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function checkIpInfo(Request $request)
    {
        $ip = $request->ip;
        $ipInfo = getCountryName($ip);

        if ($ipInfo) {
            $return['code']    = 200;
            $return['data']    = $ipInfo;
            $return['message'] = 'Ip information fetched successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Invalid Ip!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /* Ip List Status Update */

    public function blockStatusUpdate(Request $request)
    {

        $blockip  = BlockIp::where('id', $request->id)->first();
        $ipname = $blockip->ip;
        $status = $request->sts;
        if ($status == 1) {
            $blockipcount  = BlockIp::where('ip', $ipname)->where('status', 1)->get()->count();
            if ($blockipcount == 0) {
                $blockip->status = $request->sts;
                $blockip->update();
                $return['code']    = 200;
                $return['message'] = 'Blockip Status updated!';
            } else {
                $return['code']    = 101;
                $return['message'] = 'This IP is already Blocked';
            }
        } else {
            $blockip->status = $request->sts;
            $blockip->update();
            $return['code']    = 200;
            $return['message'] = 'Blockip Status updated!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [

                'ip_addr'    => 'required|unique:block_ips,ip|ipv4',
                'desc'       => 'required',
            ],
            [
                'ip_addr.required' => 'Please Enter Ip address',
                'ip_addr.unique' => 'The Ip address already blocked',
                'ip_addr.ipv4' => 'The Ip address must be a valid IPv4 address.',
                'desc.required' => 'Please Enter Description',
            ]
        );

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }

        $ip = new BlockIp();
        $ip->uid = 'Admin';
        $ip->ip = $request->ip_addr;
        $ip->desc = $request->desc;
        $ip->status = '0';
        if ($ip->save()) {
            $return['code'] = 200;
            $return['data'] = $ip;
            $return['message'] = 'Ip Block request successfully added!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
