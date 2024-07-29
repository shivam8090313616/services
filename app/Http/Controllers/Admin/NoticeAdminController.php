<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoticeAdminController extends Controller
{
    public function getNoticeList(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        if ($src) {
            $getdata = Notice::whereRaw('concat(ss_notices.title) like ?', "%{$src}%")
                ->where('trash', 0)->orderBy('id', 'DESC');
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->get();
        } else {
            $getdata = Notice::where('trash', 0)->orderBy('id', 'DESC');
            $row = $getdata->count();
            $data = $getdata->offset($start)->limit($limit)->get();
        }
        if (!empty($getdata)) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'All Notices';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Not Found ';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => "required",
                'link' => 'required|url',
                'description' => 'required',
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        $notice               = new Notice();
        $notice->title        = $request->title;
        $notice->link         = $request->link;
        $notice->description  = $request->description;
        $notice->status       = 2;
        $notice->trash        = 0;
        if ($notice->save()) {
            $return['code']    = 200;
            $return['data']    = $notice;
            $return['message'] = 'Notice added successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
            //$return ['error'] = $validator->errors();
        }


        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'title' => "required",
                'link' => 'required|url',
                'description' => 'required',
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $id                     = $request->id;
        $notice                 = Notice::find($id);
        $notice->title          = $request->title;
        $notice->description    = $request->description;
        $notice->link           = $request->link;
        if ($notice->update()) {
            $return['code']    = 200;
            $return['data']    = $notice;
            $return['message'] = 'Updated Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
            $return['error'] = $validator->errors();
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $notice = Notice::find($id);
        $notice->trash = 1;

        if ($notice->update()) {
            $return['code']    = 200;
            $return['message'] = 'Notice deleted successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function noticeUpdateStatus(Request $request)
    {
        $notice  = Notice::where('id', $request->id)->first();
        $notice->status = $request->sts;
        if ($notice->update()) {
            $return['code']    = 200;
            $return['data']    = $notice;
            $return['message'] = 'Category Status updated!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
