<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SupportType;


class SupportTypeAdminCtrl extends Controller
{
    public function create_support_type(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'support_type_name' => "required",
            ]
        );
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['msg'] = 'error';
            $return['err'] = $validator->errors();
            return response()->json($return);
        }
        $supporttype = new SupportType();
        $supporttype->support_type_name = $request->support_type_name;
        $supporttype->status = 1;
        if ($supporttype->save()) {
            $return['code']  = 200;
            $return['message'] = 'Support Type Added Sucessfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
