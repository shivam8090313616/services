<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\PubAdunit;
use App\Models\PubWebsite;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PubAdUnitController extends Controller
{

    public function adCodeGenerator()
    {
        $adcode = '7SAD' . strtoupper(uniqid(15));
        $checkdata = PubAdunit::where('ad_code', $adcode)->count();
        if ($checkdata > 0) {
            $this->adCodeGenerator();
        } else {
            return $adcode;
        }
    }

    public function adUnitStore(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'ad_name' => 'required',
                'ad_type' => 'required',
                'web_code' => 'required',
            ],
            [
                'ad_name.required' => 'Please Enter Ad Name',
                'ad_type.required' => 'Please Select Ad Type',
                'web_code'         => 'Please enter website code',
            ]
        );

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }

        $websiteDetail = PubWebsite::select('website_category', 'site_url', 'category_name', 'uid')->where('web_code', $request->web_code)->first();
      	$user_email = User::where('uid', $request->uid)->first();
        $adnumber   = $this->adCodeGenerator();
        $adunit     = new PubAdunit();
        $adunit->ad_code            = $adnumber;
        $adunit->uid                = $websiteDetail->uid;
        $adunit->web_code           = $request->web_code;
        $adunit->ad_name            = $request->ad_name;
        $adunit->ad_type            = $request->ad_type;
        $adunit->ad_size            = $request->ad_size;
      	$adunit->grid_type          = $request->grid_type;
        $adunit->site_url           = $websiteDetail->site_url;
        $adunit->website_category   = $websiteDetail->website_category;
        $adunit->status      		= 2;
        $adunit->erotic_ads         = $request->erotic_ads;
      	$adunit->alert_ads          = $request->alert_ads;
        if ($adunit->save()) {
          /* This will update AdUnit Data into Redis*/
            updateWebData($adnumber, 2);
          	/* Adunit Activity Add & Generate Notification */
            $activitylog = new Activitylog();
            $activitylog->uid    = $websiteDetail->uid;
            $activitylog->type    = 'Adunit Added';
            $activitylog->description    = '' . $adunit->ad_code . ' is added Successfully';
            $activitylog->status    = '1';
            $activitylog->save();
          	
          	/* Send real time notification to admin */
          	sendFcmNotification($activitylog->type, $activitylog->description);
          	
          
            /* Admin Section  */
              
            $email = $user_email->email;
            $fullname = $user_email->first_name . ' ' . $user_email->last_name;
            $useridas = $websiteDetail->uid;
            $adcode = $adunit->ad_code;
            $adname = $adunit->ad_name;
          	$site_url = $adunit->site_url;
            $webcategory =   $adunit->website_category;


            $data['details'] = array('subject' => 'Ad Unit Created successfully - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas, 'adunitid' => $adcode, 'adunitname' => $adname, 'website_url' => $site_url, 'webcategory' => $webcategory);
            
            $subject = 'Ad Unit Created successfully - 7Search PPC';
            $body =  View('emailtemp.adunitcreateuser', $data);
            sendmailUser($subject,$body,$email); 

            $adminmail1 = 'advertisersupport@7searchppc.com';
            $adminmail2 = 'info@7searchppc.com';
            $bodyadmin =   View('emailtemp.adunitcreateadmin', $data);
            $subjectadmin = 'Ad Unit Created successfully - 7Search PPC';
            $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
            if($sendmailadmin == '1') 
            {
              $return['code'] = 200;
              $return['message']  = 'Mail Send & Website added successfully !';
            }
            else 
            {
              $return['code'] = 200;
              $return['message']  = 'Mail Not Send But Data Insert Successfully !';
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }


        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adunitDropdownList(Request $request)
    {
        $uid  = $request->uid;
      	$web_code  = $request->web_code;
      	$adlist = PubAdunit::select('id','ad_code','ad_name')
            		->where('uid', $uid)->where('web_code', $web_code)->where('trash', 0)->get();
      	$row = $adlist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $adlist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adUnitReSubmit(Request $request)
    {
        $uid = $request->uid;
      	$ad_code = $request->ad_code;
        $website = PubAdunit::where('uid', $uid)->where('ad_code', $ad_code)->first();
        $website->status = 2;
        if ($website->update()) {
            /* This will update AdUnit Data into Redis*/
          updateWebData($ad_code, 2);
            $return['code']    = 200;
            $return['message'] = 'Aunit Updated successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adUnitEdit(Request $request)
    {
        $uid = $request->uid;
      	$ad_code = $request->ad_code;
      	$adunit = PubAdunit::where('uid', $uid)->where('ad_code', $ad_code)->first();
        $adunit->ad_name = $request->ad_name;
      	$adunit->erotic_ads = $request->erotic_ads;
      	$adunit->alert_ads = $request->alert_ads;
        
        if ($adunit->update()) {
          /* This will update AdUnit Data into Redis*/
          updateWebData($ad_code, 2);
            $return['code']    = 200;
            $return['message'] = 'Aunit Updated successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adUnitEditInfo(Request $request)
    {
        $uid = $request->uid;
      	$ad_code = $request->ad_code;
      	$adunit = PubAdunit::where('uid', $uid)->where('ad_code', $ad_code)->first();
        
       	if ($adunit) {
            $return['code']    = 200;
          	$return['data']    = $adunit;
            $return['message'] = 'Adunit Updated successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
