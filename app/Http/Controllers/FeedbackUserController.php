<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Support\Facades\Validator;

class FeedbackUserController extends Controller
{
    // create for advertiser feedback api
    public function create_adv_feedback(Request $request){

        $validator = Validator::make(
            $request->all(),
            [
                'user_id'=> 'required',
                'subject'=>'required|max:50',
                'message'=> 'required|max:305',
                'rating'=> 'required|numeric|max:5|min:1',
                'attachment'=> 'file|mimes:jpeg,png,pdf,jpg|max:2048',
                'type' => 'required|numeric|min:1|max:1'
            ]);

            if ($validator->fails()) {
                $return['code'] = 100;
                $return['error'] = $validator->errors();
                $return['message'] = 'Validation error!';
                return json_encode($return);
            }
            
            $uid = $request->user_id;
            $usersdata = User::where('uid', $uid)->first();
            if(!empty($usersdata) && $request->file('attachment')){
                $image = $request->file('attachment');
                $attachment = 'adv-' . time() . '.' . $image->getClientOriginalExtension();
                $destinationPaths = base_path('public/images/feedback');
                $image->move($destinationPaths, $attachment);
            }else{
                $attachment = '';
            }
            if(empty($usersdata)){
                $return['code'] = 101;
                $return['message'] = 'User Not Found !';
            }else{
                $feedback = new Feedback();
                $feedback->user_id = $request->user_id;
                $feedback->subject = $request->subject;
                $feedback->message = $request->message;
                $feedback->rating = $request->rating;
                $feedback->attachment = $attachment;
                $feedback->type = $request->type;

                if($feedback->save()){
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->user_id;
                $activitylog->type    = 'Advertiser Feedback';
                $activitylog->description    = $usersdata->first_name . ' ' . $usersdata->last_name . ' '. 'submitted the feedback form successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                $return['code'] = 200;
                $return['message'] = 'Feedback Added Successfully.';
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Something went wrong !';
                }
            }

            return json_encode($return, JSON_NUMERIC_CHECK);
    }



    // create for publisher feedback api
    public function create_pub_feedback(Request $request){
         $validator = Validator::make(
            $request->all(),
            [
                'user_id'=> 'required', 
                'subject'=>'required|max:50',
                'message'=> 'required|max:305',
                'rating'=> 'required|numeric|max:5|min:1',
                'attachment'=> 'file|mimes:jpeg,png,pdf,jpg|max:2048',
                'type' => 'required|numeric|min:2|max:2'
            ]);

            if ($validator->fails()) {
                $return['code'] = 100;
                $return['error'] = $validator->errors();
                $return['message'] = 'Validation error!';
                return json_encode($return);
            }
            $uid = $request->user_id;
            $usersdata = User::where('uid', $uid)->first();
            if(!empty($usersdata) && $request->file('attachment')){
                $image = $request->file('attachment');
                $attachment = 'pub-' . time() . '.' . $image->getClientOriginalExtension();
                $destinationPaths = base_path('public/images/feedback');
                $image->move($destinationPaths, $attachment);
            }else{
                $attachment = '';
            }
            if(empty($usersdata)){
                $return['code'] = 101;
                $return['message'] = 'User Not Found !';
            }else{
                $feedback = new Feedback();
                $feedback->user_id = $request->user_id;
                $feedback->subject = $request->subject;
                $feedback->message = $request->message;
                $feedback->rating = $request->rating;
                $feedback->attachment = $attachment;
                $feedback->type = $request->type;

                if($feedback->save()){
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->user_id;
                $activitylog->type    = 'Publisher Feedback';
                $activitylog->description    = $usersdata->first_name . ' ' . $usersdata->last_name . ' '. 'submitted the feedback form successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                $return['code'] = 200;
                $return['message'] = 'Feedback Added Successfully.';
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Something went wrong !';
                }
            }

            return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
