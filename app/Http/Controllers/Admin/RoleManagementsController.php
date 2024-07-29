<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RoleManagementsController extends Controller
{
    public function get_role_list(){
        $data = RoleManagement::select('role_name as label','id as value')->where("role_permission",'!=',0)->get();
        if(count($data)>0){
            $return['code'] = 200;
            $return['message'] = 'Record Fetched.';
            $return['data'] = $data;
            return json_encode($return);
        }else{
            $return['code'] = 401;
            $return['message'] = 'Record Not Found!';
            return json_encode($return);
        }
    }

    // add role permission
    public function add_role_permission(Request $request){

        $validator = Validator::make(
            $request->all(),
            [
                'role_name'=>'required',
                'role_permission'=>'required',
            ],
        );

        if($validator->fails()){
            $return['code'] = 100;
            $return['message'] = 'Validation Error!';
            $return['error'] = $validator->errors();
            return json_encode($return);
        }
        $roleid =  base64_decode($request->id);
        $roleData = ($roleid) ? RoleManagement::find($roleid):new RoleManagement();
        $message = ($request->id) ? 'Role & Permission Updated Successfully.' : 'Role & Permission Added Successfully.';
        $roleData->role_name = $request->role_name;
        $roleData->role_permission = implode(',',$request->role_permission);
        return ($roleData->save()) ? response()->json([
            'code'=>200,
            'message'=>$message,
        ]) : response()->json([
            'code'=>401,
            'message'=>'Something Went Wrong!',
        ]);
    }

// display role list data
    public function role_list(Request $request){

        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $status = $request->status;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $result = DB::table('role_managements')->where('role_permission','!=',0);
        $row = $result->count();
        if($src){
            $result->whereRaw('concat(ss_role_managements.role_name) like ?', "%{$src}%");
        }
        $res = $result->offset($start)->limit($limit)->orderByDesc('id')->get();
       if (count($res)>0) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = $row;
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return);
    }

    public function edit_role_data(Request $request){
        $roleid = base64_decode($request->id);
        if($roleid){
            $data = RoleManagement::where('id',$roleid)->first();
            return ($data) ? response()->json([
                'message' => 'Role Data Fethced.',
                'code' => 200,
                'data' => $data,
            ]) : json_encode(['message'=>'Record Not Found!'],401) ;
        }
        return json_encode(['message'=>'Something went wrong!'],400);
    }

}
