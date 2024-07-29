<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Feedback;

class FeedbackAdminController extends Controller
{
    // fetch advertiser feedback list
    public function get_advertiser_list(Request $request){
    $limit = $request->lim;
    $page = $request->page;
    $src = $request->src;
    $rating = $request->rating;
    $pg = $page - 1;
    $start = ($pg > 0) ? $limit * $pg : 0;

    $result = Feedback::join('users','feedbacks.user_id','=', 'users.uid')
              ->where('type',1)
              ->select('feedbacks.*','users.email');
    if($rating){
    $result->where('feedbacks.rating',$rating);
   }

    if ($src) {
       $result->where('feedbacks.user_id', 'like', "%{$src}%");
       $result->orWhere('users.email', 'like', "%{$src}%");
    }

    $row = $result->count();
    $res = $result->offset($start)->limit($limit)->orderByDesc('id')->get();

    if ($res->isNotEmpty()) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = $row;
        $return['msg'] = 'Data Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }



    // fetch publisher feedback list
    public function get_publisher_list(Request $request){
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $rating = $request->rating;
        $start = ($pg > 0) ? $limit * $pg : 0;
    
        $result = Feedback::join('users','feedbacks.user_id','=', 'users.uid')
                  ->where('type',2)
                  ->select('feedbacks.*','users.email');
        if($rating){
            $result->where('feedbacks.rating',$rating);
        }

        if ($src) {
           $result->where('feedbacks.user_id', 'like', "%{$src}%");
           $result->orWhere('users.email', 'like', "%{$src}%");
        }
    
        $row = $result->count();
        $res = $result->offset($start)->limit($limit)->orderByDesc('id')->get();
    
        if ($res->isNotEmpty()) {
            $return['code'] = 200;
            $return['data'] = $res;
            $return['row'] = $row;
            $return['msg'] = 'Data Successfully found !';
        } else {
            $return['code'] = 100;
            $return['msg'] = 'Data Not found !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
