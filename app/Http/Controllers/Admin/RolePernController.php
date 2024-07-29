<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rolepermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RolePernController extends Controller
{
    public function create(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'role_name' => "required",

            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $role = new Rolepermission();
        $role->role_name = $request->role_name;
        $role->permission = $request->permission;
        if ($role->save()) {
            $return['code']  = 200;
            $return['message'] = 'Added Sucessfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function list()
    {


        $data = Rolepermission::orderBy('id', 'DESC')->get();

        if ($data) {
            $return['code']  = 200;
            $return['data']  = $data;
            $return['message'] = 'Added Sucessfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function listget()
    {

        $data = DB::table('rolepermissions')
            ->select('rolepermissions.role_name as label', 'rolepermissions.id as value')
            ->get()->toArray();
        if ($data) {
            $return['code']  = 200;
            $return['data']  = $data;
            $return['message'] = 'Added Sucessfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
