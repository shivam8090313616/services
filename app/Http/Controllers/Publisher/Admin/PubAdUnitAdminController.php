<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PubAdunit;
use App\Models\PubWebsite;
use App\Models\AdImpression;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PubAdUnitAdminController extends Controller {
    
    public function adUnitList(Request $request) {
      	$sort_order = $request->sort_order;
      	$col = $request->col;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
        $currentDate = Carbon::now();
        $startDate = $request->start_date;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate =  date('Y-m-d', strtotime($request->end_date));
      	$data = DB::table('pub_adunits')
        ->join('users', 'users.uid', '=', 'pub_adunits.uid')
        ->join('categories', 'categories.id', '=', 'pub_adunits.website_category')
        ->select('pub_adunits.id','pub_adunits.ad_name','pub_adunits.site_url','pub_adunits.ad_code','pub_adunits.ad_type','pub_adunits.status','pub_adunits.website_category', 
        'users.email as user_email' , 'users.uid as user_id', 'categories.cat_name as category',
        DB::raw('ss_pub_adunits.created_at as create_date, (IF(DATEDIFF( "'.$currentDate.'", ss_pub_adunits.created_at) < 8, 1, 0)) as badge,
        (select IFNULL(sum(impressions),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as impressions,
        (select IFNULL(sum(clicks),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as clicks'));
        if ( $request->status != '' && $request->category == '' && $request->ad_type == '' ) {
           	$data->where( 'pub_adunits.status', $request->status );
        }
        if ( $request->website_category != '' && $request->website_status == '' && $request->ad_type == '' ) {
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }
        if ($request->ad_type != '' && $request->website_category == '' && $request->website_status == '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
        }
        if ( $request->website_category != '' && $request->status != '' ) {
            $data->where( 'pub_adunits.status', $request->status );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }if ( $request->website_category != '' && $request->ad_type != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }if ( $request->status != '' && $request->ad_type != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.status', $request->status );
        }if ( $request->status != '' && $request->ad_type != '' && $request->website_category != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.status', $request->status );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }
        
        if ($startDate && $endDate && !$src) {
            $data->whereDate('pub_adunits.created_at', '>=', $nfromdate)
                ->whereDate('pub_adunits.created_at', '<=', $endDate);
        }
        
        if ( $src ) {
            $data->whereRaw( 'concat(ss_pub_adunits.site_url,ss_pub_adunits.status, ss_pub_adunits.ad_name, ss_users.email,ss_pub_adunits.uid,ss_pub_adunits.ad_code) like ?', "%{$src}%" );
        }
      	
      	$row        = $data->count();
      
      	if($col)
        {
          if($col == 'impressions')
          {
            $data = $data->offset( $start )->limit( $limit )->orderBy('impressions', $sort_order)->get();
          }
          elseif($col == 'clicks')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('clicks', $sort_order)->get();
          }
          elseif($col == 'email')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('user_email', $sort_order)->get();
          }
          elseif($col == 'category')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('category', $sort_order)->get();
          }
          elseif($col == 'created_at')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('create_date', $sort_order)->get();
          }
          else
          {
              $data  = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.'.$col, $sort_order)->get();
          }
        }
        else
        {
          $data       = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.id', 'DESC')->get();
        }
        //$data       = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.id', 'DESC')->get();
      
//       foreach($data as $value)
//       {
//           $date = Carbon::now()->subDays(7);
// 		   $imprtblcount = AdImpression::where('adunit_id', $value->ad_code)->whereDate('created_at', '>=', $date)->count();
//           $createdDate = Carbon::parse($value->create_date);
//             $currentDate = Carbon::now();
// 			// Calculate the difference in days
//             $daysDifference = $createdDate->diffInDays($currentDate);
//           	if ($daysDifference < 7) {
//               // Return the new badge
//               $value->badge = 'New';
//             } else {
//               // Return a different response if the condition is not met
//                 $value->badge = '';
//             }
//       }
       
      	if ( count( $data ) > 0 ) {
            $return[ 'code' ] = 200;
            $return[ 'data' ] = $data;
          	$return[ 'row' ]  = $row;
          	$return[ 'message' ] = 'Data Successfully found!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Data Not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
    public function dailyInactiveAdunit(Request $request)
    {
        $inactiveStatus = 1;
        $lastDay = Carbon::now()->subDays(7);
        // $adUnits = PubAdunit::where('status',2)->where("trash", 0)->where("impressions", 0)->whereDate('created_at', '<', $lastDay)->get();
        $adUnits = PubAdunit::where('status',2)->where("trash", 0)->whereDate('created_at', '<', $lastDay)->get();
        if ($adUnits->isEmpty()) {
            return response()->json([
                'message' => 'Data not found within 7 days after created website!',
                'code' => 101
            ]);
        }
        $updatedAdUnits = 0;
        foreach ($adUnits as $adUnit) {
            $impressionCount = DB::table('pub_stats')->where('adunit_id', $adUnit->ad_code)
                ->whereDate('udate', '>', $lastDay)
                ->count();
                
            if ($impressionCount == 0) {
                $adUnit->status = $inactiveStatus;
                $adUnit->save();
              $inactiveWebCodes = PubAdUnit::select('web_code')
                    ->groupBy('web_code')
                    ->havingRaw('SUM(CASE WHEN status != 1 THEN 1 ELSE 0 END) = 0')
                    ->pluck('web_code');
                PubWebsite::whereIn('web_code', $inactiveWebCodes)->where('status',4)
                    ->update(['status' => 7]);
               // PubWebsite::where(['web_code' => $adUnit->web_code, 'status' => 4])->update(['status' => 7]);
                //This will remove adunits from Redis set
                updateWebData($adUnit->ad_code, 1);
                $updatedAdUnits++;
            }
        }
        if ($updatedAdUnits > 0) {
            return response()->json([
                'message' => 'Website & Adunit Inactive successfully.',
                'code' => 200
            ]);
        } else {
            return response()->json([
                'message' => 'No ad units were updated.',
                'code' => 101
            ]);
        }
   }
  
  	public function adUnitStatusUpdate(Request  $request)
    {
        $adunit = PubAdunit::where('id', $request->id)->first();
        $ad_code = $adunit->ad_code;
        $status = $request->status;
        $adunit->status = $status;
        if ($adunit->update()) {
        /* This will update AdUnit Data into Redis */
            updateWebData($ad_code, $status);
            $return['code'] = 200;
            $return['message'] = 'Ad Unit updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adAdminUnitList(Request $request)
    {
        $web_code  = $request->web_code;
      	$adlist = PubAdunit::select('id','ad_code','ad_name')->where('web_code', $web_code)->where('trash', 0)->get();
      	
      	//print_r($adlist); exit;
      	$row = $adlist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $adlist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
