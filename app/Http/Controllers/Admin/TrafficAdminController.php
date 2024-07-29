<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrafficChart;
use App\Models\Country;
use App\Imports\TrafficChartImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class TrafficAdminController extends Controller
{

    // get traffic data
    public function trafficList(Request $request)
    {

        $src = $request->src;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $getList = TrafficChart::select(
            'id',
            'traffic_type',
            'ad_type',
            'country',
            'device_os',
            'device_type',
            'traffic',
            'avg_bid',
            'high_bid',
            'created_at'
        )
            ->where('trash', 0);

        if ($src) {

            $getList->whereRaw('concat(ss_traffic_charts.traffic_type,ss_traffic_charts.ad_type,ss_traffic_charts.device_type,ss_traffic_charts.device_os, ss_traffic_charts.country) like ?', "%{$src}%");
        }
        $row = $getList->count();
        $data = $getList->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
        if (!empty($data)) {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row'] = $row;
            $return['message'] = 'Data retrieved successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // traffic update api
    public function trafficUpdate(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'traffic' => "required",
                'avg_bid' => 'required|numeric',
                'high_bid' => 'required|numeric',
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }

        $id                = $request->id;
        $traffic           = TrafficChart::find($id);
        $traffic->traffic  = $request->traffic;
        $traffic->avg_bid  = $request->avg_bid;
        $traffic->high_bid = $request->high_bid;

        if ($traffic->update()) {
            $return['code']    = 200;
            $return['data']    = $traffic;
            $return['message'] = 'Updated Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // add traffic api
    public function add_traffic(Request $request)
    {
        $traffic_Type = strtolower($request->traffic_type);
        $ad_Type = strtolower($request->ad_type);
        $country = strtolower($request->country);
        $device_Os = strtolower($request->device_os);
        $device_Type = strtolower($request->device_type);
        $traffic = $request->traffic;
        $avg_bid = $request->avg_bid;
        $high_bid = $request->high_bid;
        $uni_Traffic_Id = md5($traffic_Type . '-' . $ad_Type . '-' . $country . '-' . $device_Type . '-' . $device_Os);
        $matchad_Type = ['text', 'banner', 'social', 'native', 'popunder'];
        $matched_Device = ['desktop', 'tablet', 'mobile'];
        $matched_Os = ['android', 'apple', 'windows', 'linux'];
        $country_Check = Country::select('id', 'name', 'status', 'trash')->where('name', $country)->where('status', 1)->where('trash', 1)->first();
        $check_Unique_Combination = TrafficChart::select('uni_traffic_id')->where('uni_traffic_id', $uni_Traffic_Id)->exists();

        $validator = Validator::make($request->all(), [
            'traffic_type' => 'required|string',
            'ad_type' => 'required|string',
            'country' => 'required|string',
            'device_type' => 'required|string',
            'device_os' => 'required|string',
            'traffic' => 'required|numeric',
            'avg_bid' => 'required|numeric',
            'high_bid' => 'required|numeric',

        ]);

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }
        if ($check_Unique_Combination) {
            $return['code'] = 101;
            $return['message'] = "Admin tries to add a combination that already exists!";
        } else if ($traffic_Type != 'cpm' && $traffic_Type != 'cpc') {
            $return['code'] = 101;
            $return['message'] = "Invalid traffic type, allow only-  cpm or cpc!";
        } else if (!in_array($ad_Type, $matchad_Type)) {
            $return['code'] = 101;
            $return['message'] = "Invalid ad type, allow only- " . implode(',', $matchad_Type);
        } else if (!$country_Check && !empty($country)) {
            $return['code'] = 101;
            $return['message'] = "Country not exists!";
        } else if (!in_array($device_Type, $matched_Device)) {
            $return['code'] = 101;
            $return['message'] = "Invalid device type, allow only- " . implode(',', $matched_Device);
        } else if (!in_array($device_Os, $matched_Os)) {
            $return['code'] = 101;
            $return['message'] = "Invalid device os, allow only- " . implode(',', $matched_Os);
        } else if ($ad_Type == 'popunder' && $traffic_Type == 'cpc') {
            $return['code'] = 101;
            $return['message'] = "Invalid traffic type, allow only cpm on popunder ad.";
        } else {
            $trfData = new TrafficChart;
            $trfData->uni_traffic_id = $uni_Traffic_Id;
            $trfData->traffic_type = $traffic_Type;
            $trfData->ad_type = $ad_Type;
            $trfData->country = $country;
            $trfData->device_os = $device_Os;
            $trfData->device_type = $device_Type;
            $trfData->traffic = $traffic;
            $trfData->avg_bid = $avg_bid;
            $trfData->high_bid = $high_bid;

            if ($trfData->save()) {
                $return['code'] = 200;
                $return['message'] = "Traffic data added successfully.";
            } else {
                $return['code'] = 101;
                $return['message'] = "Something went wrong!";
            }
        }

        return json_encode($return);
    }

    // traffic delete api
    public function bulkDelete(Request $request)

    {

        $id = $request->id;

        $data = [];

        $validator = Validator::make(

            $request->all(),

            [
                'id' => "required",
            ]

        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);
        }


        foreach ($id as $Id) {

            $data[] = [

                'id' => $Id,

            ];
        }


        if (!empty($data)) {

            TrafficChart::select("id")->whereIn("id", $data)->delete();

            $return['code']    = 200;

            $return['message'] = 'Traffic Removed Successfully!';
        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';
        }


        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
     // import excel data 
    public function import_data(Request $request)
    {
        
     $getfile = $request->file('file');
     $validator = Validator::make(
            $request->all(),
            [
                'file' => "required|file|mimes:xlsx,xls",
            ]
        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);
        }
        
        if($getfile) {
             Excel::import(new TrafficChartImport, $getfile);
             $return['code'] = 200;
             $return['message'] = "Traffic data added successfully.";
        } else{
             $return['code'] = 101;
             $return['message'] = "Something went wrong during import file!";
        }
        return response()->json($return);
    }
}
