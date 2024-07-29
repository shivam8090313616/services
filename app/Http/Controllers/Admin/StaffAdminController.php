<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class StaffAdminController extends Controller
{
    public function create(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'name' => "required",
                'username' => "required",
                'role_id' => "required",
                'email' => "required",
                'password' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }

        $username =  $request->username;
        $count = Admin::where('username', $username)->count();
        if ($count <= 0) {

            $roleid = $request->role_id;
            $roleids = json_decode($roleid);
            $values = $roleids->value;
            $staff = new Admin();
            $staff->name = $request->name;
            $staff->username  = $request->username;
            $staff->role_id   = $values;
            $staff->roles_id  = $roleid;
            $staff->email   = $request->email;
            $staff->password  = Hash::make($request->password);
            $staff->status  = 1;
            if ($staff->save()) {
                $return['code']  = 200;
                $return['message'] = 'Added Sucessfully';
            } else {
                $return['code'] = 101;
                $return['message'] = 'Not Found ';
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Allready Inserted UserName ';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    public function list(Request $request)
    {

        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $getdata = Admin::orderBy('id', 'ASC');

        $getdata = Admin::select('rolepermissions.role_name', 'rolepermissions.permission', 'admins.id', 'admins.name', 'admins.role_id', 'admins.roles_id', 'admins.username', 'admins.email', 'admins.last_login', 'admins.status')
            ->join('rolepermissions', 'rolepermissions.id', '=', 'admins.role_id')
            ->orderBy('admins.id', 'ASC');
        $row = $getdata->count();
        $data = $getdata->offset($start)->limit($limit)->get();
        if ($data) {
            $return['code']  = 200;
            $return['data']  = $data;
            $return['row']  = $row;
            $return['message'] = 'Added Sucessfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updateStaff(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'id' => "required",
                'name' => "required",
                'username' => "required",
                'role_id' => "required",
                'roles_id' => "required",
                'email' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }

        $staffid = $request->id;
        $staff = Admin::where('id', $staffid)->first();
        if (empty($staff)) {
            $return['code']    = 102;
            $return['message'] = 'Staff id Not Found Data !';
            return json_encode($return);
        }

        $staff->name = $request->name;
        $staff->username  = $request->username;
        $staff->role_id   = $request->role_id;
        $staff->roles_id  = $request->roles_id;
        $staff->email   = $request->email;
        $staff->status  = 1;
        if ($staff->save()) {
            $return['code']  = 200;
            $return['message'] = 'Updated Successfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function StatusUpdate(Request $request)
    {
        $id = $request->id;
        $status = $request->sts;

        $staff = Admin::where('id', $id)->first();
        if (empty($staff)) {
            $return['code'] = 100;
            $return['message'] = 'No data found!';
            return json_encode($return);
        }
        $staff->status = $status;
        if ($staff->update()) {
            $return['code'] = 200;
            $return['message'] = 'Staff status update successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
