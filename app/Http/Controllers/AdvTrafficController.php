<?php

namespace App\Http\Controllers;

use App\Models\TrafficChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdvTrafficController extends Controller
{

    // api for get traffic list data
    public function get_traffic_data(Request $request)
    {
        $limit = $request->lim ?? 10;
        $page = $request->page ?? 1;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $country = $request->country;
        $device_Type = strtolower($request->device_type);
        $device_Os = strtolower($request->device_os);
        $ad_Type = strtolower($request->ad_type);
        $traffic_Type = strtolower($request->traffic_type);
        $countries = [];
        $validator = Validator::make($request->all(), [
            'traffic_type' => 'required|string|exists:traffic_charts,traffic_type',
            'country' => 'array',
        ]);
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        if(!empty($country)){
            foreach ($country as $cnt) {
                $countries[] = [
                    'country' => $cnt,
                ];
            }
        }

        $query = DB::table('traffic_charts')
            ->select(
                'country',
                DB::raw('MAX(ss_traffic_charts.avg_bid) as avg_bid'),
                DB::raw('MAX(ss_traffic_charts.high_bid) as high_bid'),
                DB::raw('SUM(ss_traffic_charts.traffic) as total_traffic')
            )
            ->where('traffic_type', $traffic_Type);

        if (!empty($ad_Type)) {
            $query->where('ad_Type', $ad_Type);
        }
        if (!empty($device_Type)) {
            $query->where('device_Type', $device_Type);
        }
        if (!empty($device_Os)) {
            $query->where('device_os', $device_Os);
        }
        if (!empty($countries)) {
            $query->whereIn('country', $countries);
        }

        $row = $query->groupBy('country')->get();
        $data = $query->offset($start)->limit($limit)->orderBy('traffic', 'desc')->groupBy('country')->get();
        if ($data->isNotEmpty()) {
            $return['code'] = 200;
            $return['message'] = "Traffic data fetched successfully.";
            $return['data'] = $data;
            $return['row'] = count($row);
        } else {
            $return['code'] = 101;
            $return['message'] = 'Traffic data not found!';
            $return['data'] = [];
        }
        return json_encode($return);
    }

    /*
        ###########   Traffic Chart API #####################
    */

    public function trafficChart(Request $request){

        $validator = Validator::make(
            $request->all(),
            [
                'traffic_type' => "required",
                'ad_type' => "required",
                'min_bid' => "required",
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        $trafficType = $request->traffic_type;
        $adType      = $request->ad_type;
        $minBid      = $request->min_bid;
        $country     = $request->country;
        $deviceos     = $request->device_os;
        $devicetype     = $request->device_type;
        $totaltraffic = 0;

        $getDataTrafficSql = TrafficChart::select("id",'traffic','avg_bid','high_bid')->where("traffic_type", $trafficType)->where("ad_type", $adType);
      
        
        if($country[0] != 'All' )   {
             $getDataTrafficSql->whereIn("country", $country);
        } 

        if(count($devicetype)  < 3 ){
           $getDataTrafficSql->whereIn("device_type", $devicetype);
        }

        if(count($deviceos)  < 4 ){
             $getDataTrafficSql->whereIn("device_os", $deviceos);
        } 
        $getDataTraffic =  $getDataTrafficSql->get();
        $maxHighBid = null;
        $avgHighBid = null;
        foreach($getDataTraffic as $valueTraffic){
            $totaltraffic += $valueTraffic['traffic'];
            if ($maxHighBid === null || $valueTraffic['high_bid'] > $maxHighBid) {
                $maxHighBid = $valueTraffic['high_bid'];
            }
            if ($avgHighBid === null || $valueTraffic['avg_bid'] > $avgHighBid) {
                $avgHighBid = $valueTraffic['avg_bid'];
            }
        }
        $data['total_data'] = [
            'get_total_traffic' => $totaltraffic,
            'max_bid'           => $maxHighBid,
            'avg_bid'           => $avgHighBid,
            'min_bid'           => $minBid
        ];

        $bidArray = [];
        $maxBid   = $maxHighBid;
        $avgBid   = $avgHighBid;
        $minBid   = $minBid;
        $clickImp = $totaltraffic;
        $bidArray = $this->generateList($maxBid, 9, $maxBid, $minBid, $bidArray);
        $bidArray[] = $maxBid;
        $percentageArray = $bidArray;
        $percentageFactor = 3;
        $lastPercentage = 0;

        for ($i = 0; $i < count($bidArray); $i++) {
          
            if ($i == 0) {
                $percentageArray[$i] = 0;
                $tx1[] = $bidArray[$i];
                $tx2[] = $bidArray[$i];
            } elseif ($bidArray[$i] == $avgBid) {
                $value = floor($clickImp * 85 / 100);
                $percentageArray[$i] = $value;
                $lastPercentage = 85;
                $tx1[] = $bidArray[$i]; 
                $tx2[]  = $value; 
            } elseif ($i == count($bidArray) - 1) {
                $percentageArray[$i] = $clickImp;
                    $tx1[]= $bidArray[$i];
                    $tx2[]= $clickImp;
            } else {
                $newPercentage = $lastPercentage + $percentageFactor + $i;
                if ($newPercentage > 100) {
                    $percentageArray[$i] = $clickImp;
                    $lastPercentage = 100;
                    $tx1[]= $bidArray[$i]; 
                    $tx2[]= $clickImp; 
                } elseif ($newPercentage < $lastPercentage) {
                    $value = floor($clickImp * $lastPercentage / 100);
                    $percentageArray[$i] = $value;
                    $tx1[]= $bidArray[$i];
                    $tx2[]= $value; 
                } else {
                    $value = floor($clickImp * $newPercentage / 100);
                    $percentageArray[$i] = $value;
                    $lastPercentage = $newPercentage;
                    $tx1[] = $bidArray[$i];
                    $tx2[] = $value; 
                }
            }
        }
        $finalData['graph']= [
            'bid'    => $tx1,
            'traffic' => $tx2
        ];
        if (!is_null($finalData['graph']['bid'][0])) {
            $return = [
                'code' => 200,
                'data' => $finalData,
                'message' => 'Traffic Successfully!'
            ];
        } else {
            $return = [
                'code' => 101,
                'data' => [],
                'message' => "Data not found!"
            ];
        }
        return json_encode($return, JSON_NUMERIC_CHECK);

    }
    
    function generateList($maxBid, $interval, $remainingAmt, $minBid, &$bidArray) {

        $divide = round($maxBid / $interval, 6);
        $outputBid = round($remainingAmt - $divide, 6);
        if (!($outputBid <= 0)) {
            $bidArray = [];
           $this->generateList($maxBid, $interval, $outputBid, $minBid, $bidArray);
        }
        if ($outputBid < 0) {
            $bidArray[0] =0;
        } elseif ($outputBid > $minBid) {
            $bidArray[]= $outputBid;
        }
        return $bidArray;        
    }
}
