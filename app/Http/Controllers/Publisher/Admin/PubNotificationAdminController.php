<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activitylog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PubNotificationAdminController extends Controller {
    
    public function adminUnreadNotification (Request $request)
    {
      //$notific = Activitylog::select('id','uid', 'description', 'type')->where('view', 0)->orderBy('id', 'DESC')->limit(4)->get();
      $notific = DB::table('activitylogs')
      			->select('activitylogs.id','activitylogs.uid', 'activitylogs.description', 'activitylogs.type', 'activitylogs.view', 'users.user_type')
        		->join('users', 'activitylogs.uid', '=', 'users.uid')
        		->orderBy('id', 'DESC')->limit(5)->get();
      $countnoti = DB::table('activitylogs')
      			->select('activitylogs.id','activitylogs.uid', 'activitylogs.description', 'activitylogs.type', 'activitylogs.view', 'users.user_type')
        		->join('users', 'activitylogs.uid', '=', 'users.uid')
        		->where('view', 0)->orderBy('id', 'DESC')->get();
      $row = $countnoti->count();
      //print_r($notific); exit;
      if(!empty($notific))
      {
        $return['code'] = 200;
        $return['data'] = $notific;
        $return['row'] = $row;
        $return['message'] = 'Fetched successfully';
      }
      else
      {
      	$return['code'] = 101;
        $return['message'] = 'Data not found!';
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
      
    }
  
  	public function adminNotificationReadUpdate (Request $request)
    {
    	$udpate = Activitylog::where('view', '=', 0)->update(['view' => 1]);

    }
}
