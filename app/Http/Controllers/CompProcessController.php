<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Models\UserCampClickLog;
use Illuminate\Http\Request;

class CompProcessController extends Controller
{
    public function deductAmt(Request $request, $campids)
    {
        $campid =  base64_decode($campids);
        $campdetails = Campaign::select('campaign_id', 'advertiser_id', 'advertiser_code', 'target_url', 'categories.cpc')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaign_id', $campid)
            ->first();
        if (empty($campdetails)) {
            $return['code'] = 404;
            $return['msg'] = 'Not campaign found!';
            return response()->json($return);
        }
        $uid = $campdetails->advertiser_code;
        $user = User::where('uid', $uid)->first();
        $wallet = $user->wallet;
        $final =  $wallet - $campdetails->cpc;
        if (empty($user)) {
            $return['code'] = 101;
            $return['msg'] = 'Not found!';
            return response()->json($return);
        }
        $user->wallet = $final;
        if ($user->save()) {
            $campclick = new UserCampClickLog();
            $campclick->campaign_id = $campdetails->campaign_id;
            $campclick->advertiser_code = $campdetails->advertiser_code;
            $campclick->device_type = 'TYPE';
            $campclick->device_os = 'OS';
            $campclick->ad_type = 'AD TYPE';
            $campclick->country = 'COUNTRY';
            $campclick->ip_address = $request->ip();
            $campclick->amount =   $campdetails->cpc;
            if ($campclick->save()) {
                $return['code'] = 200;
                $return['message'] = 'successfully added!';
            } else {
                $return['code'] = 101;
                $return['message'] = 'Something went wrong!';
            }
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }
}
