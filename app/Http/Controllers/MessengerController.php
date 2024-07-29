<?php







namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class MessengerController extends Controller



{



    public function MessengerList()
    {
       $result = DB::table('messengers')->select('messenger_name as id', 'messenger_name as value')->where('status', 1)->orderBy('id','desc')->get()->toArray();
       if (count($result)) {
        $return['code'] = 200;
        $return['data'] = $result;
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function addMessenger(Request $request){
        $result = DB::table('messengers')->find($request->id);
        if($request->id){
            $validator = Validator::make(
                $request->all(),
                [
                    'messenger_name' => 'required|unique:messengers,messenger_name,'.$result->id.'id',
                ]
            );
         }else{
            $validator = Validator::make(
                $request->all(),

                [

                    'messenger_name' => 'required|unique:messengers,messenger_name',

                ]

            );

        }

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        if($request->id){

             $insert =  DB::table('messengers')->where('id',$request->id)->update([

                'messenger_name' => $request->messenger_name

            ]);

            $return['code'] = 200;

            $return['msg'] = 'Updated Successfully !';

            return json_encode($return);

        }else{

            $insert =  DB::table('messengers')->insert([

                'messenger_name' => $request->messenger_name,

                'status' => true

            ]);

        }

            $return['code'] = 200;

            $return['msg'] = 'insert Data Successfully!';

            return json_encode($return, JSON_NUMERIC_CHECK);

    }

    public function deleteMessenger(Request $request){ 
        $deleted = DB::table('messengers')->where('id',$request->id)->update(['status' => $request->status]);
        if($deleted){
            $return['code'] = 200;
            $return['msg'] = 'Deleted Successfully!';
            return json_encode($return);
        }else{
            $return['code'] = 101;
            $return['msg'] = 'Deleted Not Successfully!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }

    public function updateStatusMessenger(Request $request){ 
        $update = DB::table('messengers')->where('id',$request->id)->update(['status' => $request->status]);
        if($update){
            $return['code'] = 200;
            $return['msg'] = 'Status Updated Successfully!';
            return json_encode($return);
        }else{
            $return['code'] = 101;
            $return['msg'] = 'Status Updated Not Successfully!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }
    public function MessengerListget(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $status = $request->status;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $result = DB::table('messengers')->select('messenger_name','id','status');
        if($src){
            $result->whereRaw('concat(ss_messengers.messenger_name) like ?', "%{$src}%");
        }
        if(strlen($status) > 0){  
            $result->where('status',$status);
        }
        else{
            $result->where('status','!=',2)->orderBy('id','asc')->offset($start)->limit($limit)->get()->toArray();
        }
        $res = $result->where('status','!=',2)->orderBy('id','asc')->offset($start)->limit($limit)->get()->toArray();
        $row = $result->get()->toArray();
       if (count($res)>0) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = count($row);
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

 

