<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activitylog;

class ActivitylogAdminController extends Controller
{

    public function all_list(Request $request)
    {
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $activitylogs = Activitylog::select('id', 'uid', 'type', 'description', 'status', 'view', 'created_at');
        if ($src) {
            $activitylogs = Activitylog::select('id', 'uid', 'type', 'description', 'status', 'view', 'created_at')->whereRaw('concat(ss_activitylogs.uid) like ?', "%{$src}%");
        }
        if ($startDate && $endDate) {
            $activitylogs = Activitylog::select('id', 'uid', 'type', 'description', 'status', 'view', 'created_at')->whereRaw('concat(ss_activitylogs.uid) like ?', "%{$src}%")->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }
        // if ($startDate && $endDate) {
        //     $activitylogs = Activitylog::select('id', 'uid', 'type', 'description', 'status', 'view', 'created_at')->whereRaw('concat(ss_activitylogs.uid) like ?', "%{$src}%")->whereDate('created_at', '>=', $nfromdate)
        //         ->whereDate('created_at', '<=', $endDate);
        // }

        $row    = $activitylogs->count();
        $data   = $activitylogs->offset($start)->limit($limit)->orderBy('id', 'DESC')->get();
        if (empty($activitylogs)) {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
            return json_encode($return);
        } else {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'data successfully!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function importReportExcelActivity(Request $request){
       
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate));
        $endDate = $request->endDate;
     
        $activitylogs = Activitylog::select('uid', 'type', 'description', 'status', 'view', 'created_at');

        if ($startDate && $endDate) {
            $activitylogs = Activitylog::select( 'uid', 'type', 'description', 'status', 'view', 'created_at')->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }

        $row    = $activitylogs->count();
        $data   = $activitylogs->orderBy('id', 'DESC')->get();
        if (empty($activitylogs)) {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
            return json_encode($return);
        } else {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'data successfully!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
