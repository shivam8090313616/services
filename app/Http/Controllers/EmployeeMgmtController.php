<?php

namespace App\Http\Controllers;

use App\Models\Employee_mgmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class EmployeeMgmtController extends Controller
{

    // display employee list
    public function employee_list(Request $request){

        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $status = $request->status;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $result = DB::table('admins')
            ->join('role_managements', 'admins.role_id', '=', 'role_managements.id')
            ->where("admins.user_type",2)
            ->select('admins.id','admins.name','admins.username','admins.role_id','admins.email','admins.status','admins.created_at', 'role_managements.role_name');
        $row = $result->count();
        if($src){
            $result->whereRaw('concat(ss_admins.name," ",ss_admins.email," ",ss_role_managements.role_name," ",ss_admins.username) like ?', "%{$src}%");
        }
        if(strlen($status) > 0){  
            $result->where('status',$status);
        }
        $res = $result->offset($start)->limit($limit)->orderByDesc('admins.id')->get();
       if (count($res)>0) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = $row;
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }

    // add employee
    public function add_employee(Request $request)
    {
        if($request->id){
            $result = Employee_mgmt::find($request->id);
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'username' => 'required|unique:admins,username,'.$result->id.'id',
                    'role_id' => 'required',
                    'email' => 'required|max:50|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/|unique:admins,email,'.$result->id.'id',
                    // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                ],
                [
                    'name.required' => 'Please Enter Name',
                    'username.required' => 'Please Enter User Name',
                    'role_id.required' => 'Please Select Role Id',
                    'email.required' => 'Please Enter Email',
                    // 'password.required' => 'Please Enter Password',
                    // 'password.min' => 'Password must be at least 8 characters',
                    // 'password.regex' => ' Password should be lowercase & uppercase with special characters',
                ],
            );
        }else{
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'username' => 'required|unique:admins,username',
                    'role_id' => 'required',
                    'email' => 'required|unique:admins,email|max:50|regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
                    // 'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'],
                   'password' => ['required','string','min:8'],
                ],
                [
                    'name.required' => 'Please Enter Name',
                    'username.required' => 'Please Enter User Name',
                    'role_id.required' => 'Please Select Role Id',
                    'email.required' => 'Please Enter Email',
                    'password.required' => 'Please Enter Password',
                    'password.min' => 'Password must be at least 8 characters',
                    // 'password.regex' => ' Password should be lowercase & uppercase with special characters',
                ],
            );
        }

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['message'] = 'Validation Error!';
            $return['error'] = $validator->errors();
            return json_encode($return);
        }
        
        $empData = ($request->id) ? Employee_mgmt::find($request->id):new Employee_mgmt;
        $message = ($request->id) ? 'Employee Updated Successfully.' : 'Employee Added Successfully.';
        $empData->name = $request->name;
        $empData->username = $request->username;
        $empData->user_type = 2;
        $empData->role_id = $request->role_id;
        $empData->email = $request->email;
        $empData->status = 1;
        $empData->password = (empty($request->password)) ? $result->password : Hash::make($request->password);
        
        if($empData->save()){
          if(empty($request->id)){
            $data['details'] = ['subject' => 'Employee Credential Details - 7Search PPC','username'=>$request->username,'password'=>$request->password];
             /* User Section */
             $subject = 'Employee Credential Details - 7Search PPC';
             $body =  View('emailtemp.empdetail', $data);
             /* User Mail Section */
             sendmailUser($subject,$body,$request->email);
            }
            return response()->json(['code'=>200,'message'=>$message]);
        }
        else{
            return response()->json(['code' => '401', 'msg' => 'Something went wrong!']);
        }
        
    }

    public function update_employee_status(Request $request){ 
        $data = Employee_mgmt::where('id',$request->id)->update(['status' => $request->status]);
        if($data){
            $return['code'] = 200;
            $return['msg'] = 'Status Updated Successfully!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }else{
            $return['code'] = 101;
            $return['msg'] = 'Status Updated Not Successfully!';
            return json_encode($return);
        }
    }

  
}
