<?php

namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;

use App\Models\AdBannerImage;

use App\Models\Campaign;

use App\Models\Category;

use App\Models\Activitylog;

use App\Models\Country;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;

use App\Mail\CreateCampMail;

use App\Mail\CreateCampMailAdmin;

use App\Models\User;

use Exception;



class AppCampaignControllers extends Controller

{



    /* Text Campaign Funtions */

    public function storeText(Request $request)

    {

        $validator = Validator::make($request->all() , ['uid' => 'required', 'ad_type' => 'required', 'campaign_name' => 'required',

        //'campaign_type'     => 'required',

        'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'ad_title' => 'required', 'ad_description' => 'required', 'target_url' => 'required', 'daily_budget' => 'required', 'countries' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $campaign = new Campaign();

        $campaign->ad_type = $request->ad_type;

        if ($request->ad_type == 'text')

        {

            $aType = 'CMPT';

        }

        elseif ($request->ad_type == 'banner')

        {

            $aType = 'CMPB';

        }

        elseif ($request->ad_type == 'native')

        {

            $aType = 'CMPN';

        }

        elseif ($request->ad_type == 'video')

        {

            $aType = 'CMPV';

        }

        elseif ($request->ad_type == 'popup')

        {

            $aType = 'CMPP';

        }

        elseif ($request->ad_type == 'social')

        {

            $aType = 'CMPS';

        }

        else

        {

            $aType = 'Invalid';

        }

        $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

            ->where('uid', $request->uid)

            ->first();

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        //dd($campaign->country_ids);

        $campaign->advertiser_id = $user->id;

        $campaign->advertiser_code = $request->uid;

        $campaign->campaign_name = $request->campaign_name;

        $campaign->campaign_id = $aType . strtoupper(uniqid());

        $campaign->campaign_type = $request->campaign_type;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        $campaign->target_url = $request->target_url;

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        //dd($campaign);

        if ($campaign->save())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Add Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is added Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $campaign->advertiser_code;

            $campsid = $campaign->campaign_id;

            $campsname = $campaign->campaign_name;

            $campadtype = $campaign->ad_type;

            /* Send to Admin */

            $data['details'] = array(

                'subject' => 'Campaign Created successfully - 7Search PPC ',

                'fullname' => $fullname,

                'usersid' => $useridas,

                'campaignid' => $campsid,

                'campaignname' => $campsname,

                'campaignadtype' => $campadtype

            );

            $subject = 'Campaign Created successfully - 7Search PPC';

            $body = View('emailtemp.campaigncreate', $data);

            /* User Mail Section */

            // $sendmailUser =  sendmailUser($subject,$body,$email);

            $sendmailUser = sendmailUser($subject, $body, $email);

            if ($sendmailUser == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigncreateadmin', $data);

            $subjectadmin = 'Campaign Created successfully - 7Search PPC';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Campaign Create Send Mail */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }

    public function updateText(Request $request)

    {

        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'device_type' => 'required', 'ad_title' => 'required', 'ad_description' => 'required', 'website_category' => 'required', 'daily_budget' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation  Error!';

            return json_encode($return);

        }

        $cid = $request->cid;

        $campaign = Campaign::where('campaign_id', $cid)->first();

        $array1 = explode(',', $request->device_type);

        $array2 = explode(',', $campaign->device_type);

        // Sort the arrays

        sort($array1);

        sort($array2);

        if ($array1 == $array2 && $request->ad_title == $campaign->ad_title && $request->ad_description == $campaign->ad_description && $request->website_category == $campaign->website_category && $request->pricing_model == $campaign->pricing_model && $request->cpc_amt == $campaign->cpc_amt && $campaign->countries == $request->countries)

        {

            if ($request->campaign_name != $campaign->campaign_name || $request->daily_budget != $campaign->daily_budget)

            {

                if ($campaign->status == 2)

                {
                    $status = 2;
                    $campaign->status = 2;

                }

            }

        }

        else

        {
            $status = 1;
            if ($campaign->status == 2)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 4)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 5)

            {

                $campaign->status = 1;

            }

        }

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->campaign_name = $request->campaign_name;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        if ($request->target_url != '')

        {

            $campaign->target_url = $request->target_url;

        }

        $campaign->conversion_url = $request->conversion_url;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        //$campaign->status           = 1;

        if ($campaign->update())

        {
/* This will update campaign data and status into Redis */
        updateCamps($cid, $status);
            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Edit Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            /* Update Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)

                ->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = $campaign->campaign_name;

            $campid = $campaign->campaign_id;

            $status = $campaign->status;

            $subjects = 'Campaign Update Successfully';

            $data['details'] = array(

                'fullname' => $fullname,

                'userid' => $userid,

                'campname' => $campname,

                'status' => $status,

                'campid' => $campid

            );

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaignupdatedmin', $data);

            $subjectadmin = 'Campaign Updated Successfully';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Campaign Updated Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

        }

        else

        {

            /* End Email Section  */

            $return['code'] = 101;

            $return['msg'] = 'No Data Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    /* PopUnder Campaign Funtions */

    public function storePopunder(Request $request)

    {

        $validator = Validator::make($request->all() , ['uid' => 'required', 'ad_type' => 'required', 'campaign_name' => 'required',

        //'campaign_type'     => 'required',

        'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'daily_budget' => 'required', 'countries' => 'required',

        // 'pricing_model'     => 'required',

        ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $campaign = new Campaign();

        $campaign->ad_type = $request->ad_type;

        if ($request->ad_type == 'text')

        {

            $aType = 'CMPT';

        }

        elseif ($request->ad_type == 'banner')

        {

            $aType = 'CMPB';

        }

        elseif ($request->ad_type == 'native')

        {

            $aType = 'CMPN';

        }

        elseif ($request->ad_type == 'video')

        {

            $aType = 'CMPV';

        }

        elseif ($request->ad_type == 'popup')

        {

            $aType = 'CMPP';

        }

        elseif ($request->ad_type == 'social')

        {

            $aType = 'CMPS';

        }

        else

        {

            $aType = 'Invalid';

        }

        $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

            ->where('uid', $request->uid)

            ->first();

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->advertiser_id = $user->id;

        $campaign->advertiser_code = $request->uid;

        $campaign->campaign_name = $request->campaign_name;

        $campaign->campaign_id = $aType . strtoupper(uniqid());

        $campaign->campaign_type = $request->campaign_type;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->target_url = $request->target_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = 'CPM';

        $campaign->cpc_amt = $request->cpc_amt;

        // $cat = Category::where('id', $request->website_category)->first();

        // $campaign->cpc_amt          = $cat->cpm;

        if ($campaign->save())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Added Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is Added Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $campaign->advertiser_code;

            $campsid = $campaign->campaign_id;

            $campsname = $campaign->campaign_name;

            $campadtype = $campaign->ad_type;

            /* Send to Admin */

            $data['details'] = array(

                'subject' => 'Campaign Created successfully - 7Search PPC ',

                'fullname' => $fullname,

                'usersid' => $useridas,

                'campaignid' => $campsid,

                'campaignname' => $campsname,

                'campaignadtype' => $campadtype

            );

            $subject = 'Campaign Created successfully - 7Search PPC';

            $body = View('emailtemp.campaigncreate', $data);

            /* User Mail Section */

            $sendmailUser = sendmailUser($subject, $body, $email);

            if ($sendmailUser == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigncreateadmin', $data);

            $subjectadmin = 'Campaign Created successfully - 7Search PPC';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Campaign Create Send Mail */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }

    public function updatePopUnder(Request $request)

    {

        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'device_type' => 'required', 'website_category' => 'required', 'daily_budget' => 'required',

        //'pricing_model'     => 'required',

        ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation  Error!';

            return json_encode($return);

        }

        $cid = $request->cid;

        $campaign = Campaign::where('campaign_id', $cid)->first();

        $array1 = explode(',', $request->device_type);

        $array2 = explode(',', $campaign->device_type);

        // Sort the arrays

        sort($array1);

        sort($array2);

        if ($array1 == $array2 && $request->website_category == $campaign->website_category && $request->pricing_model == $campaign->pricing_model && $request->cpc_amt == $campaign->cpc_amt)

        {

            if ($request->campaign_name != $campaign->campaign_name || $request->daily_budget != $campaign->daily_budget && $campaign->countries == $request->countries)

            {
                $status = 2;
                if ($campaign->status == 2)

                {

                    $campaign->status = 2;

                }



            }

        }

        else

        {
            $status = 1;
            if ($campaign->status == 2)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 4)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 5)

            {

                $campaign->status = 1;

            }

        }

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->campaign_name = $request->campaign_name;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        if ($request->target_url != '')

        {

            $campaign->target_url = $request->target_url;

        }

        $campaign->pricing_model = 'CPM';

        $campaign->cpc_amt = $request->cpc_amt;

        // $cat = Category::where('id', $request->website_category)->first();

        // $campaign->cpc_amt          = $cat->cpm;

        //$campaign->status           = 1;

        if ($campaign->update())

        {
/* This will update campaign data and status into Redis */
        updateCamps($cid, $status);
            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Edit Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            /* Update Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)

                ->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = $campaign->campaign_name;

            $campid = $campaign->campaign_id;

            $status = $campaign->status;

            $subjects = 'Campaign Update Successfully';

            $data['details'] = array(

                'fullname' => $fullname,

                'userid' => $userid,

                'campname' => $campname,

                'status' => $status,

                'campid' => $campid

            );

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaignupdatedmin', $data);

            $subjectadmin = 'Campaign Updated Successfully';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Campaign Updated Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* End Email Section  */

            $return['code'] = 200;

            $return['data'] = $campaign;

            $return['msg'] = 'Campaign updated successfully!';

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }

    /* ----------------------------- Banner Campaign Funtions ---------------------------------- */

    public function storeBanner(Request $request)

    {

        $validator = Validator::make($request->all() , ['uid' => 'required', 'ad_type' => 'required', 'campaign_name' => 'required',

        //'campaign_type'     => 'required',

        'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'daily_budget' => 'required', 'countries' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $campaign = new Campaign();

        $campaign->ad_type = $request->ad_type;

        if ($request->ad_type == 'text')

        {

            $aType = 'CMPT';

        }

        elseif ($request->ad_type == 'banner')

        {

            $aType = 'CMPB';

        }

        elseif ($request->ad_type == 'native')

        {

            $aType = 'CMPN';

        }

        elseif ($request->ad_type == 'video')

        {

            $aType = 'CMPV';

        }

        elseif ($request->ad_type == 'popup')

        {

            $aType = 'CMPP';

        }

        elseif ($request->ad_type == 'social')

        {

            $aType = 'CMPS';

        }

        else

        {

            $aType = 'Invalid';

        }

        $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

            ->where('uid', $request->uid)

            ->first();

        $campaign->countries = $request->countries;

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->advertiser_id = $user->id;

        $campaign->advertiser_code = $request->uid;

        $campaign->campaign_name = $request->campaign_name;

        $campaign->campaign_id = $aType . strtoupper(uniqid());

        $campaign->campaign_type = $request->campaign_type;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        $campaign->target_url = $request->target_url;

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        $images = $request->images;

        if ($campaign->save())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Added Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is added Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    $arr = ['campaign_id' => $campaign->campaign_id, 'advertiser_code' => $campaign->advertiser_code, 'image_type' => $image['type'], 'image_path' => $image['img'], ];

                    // dd($arr);

                    AdBannerImage::insert($arr);

                }

            }

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $campaign->advertiser_code;

            $campsid = $campaign->campaign_id;

            $campsname = $campaign->campaign_name;

            $campadtype = $campaign->ad_type;

            /* Send to Admin */

            $data['details'] = array(

                'subject' => 'Campaign Created successfully - 7Search PPC ',

                'fullname' => $fullname,

                'usersid' => $useridas,

                'campaignid' => $campsid,

                'campaignname' => $campsname,

                'campaignadtype' => $campadtype

            );

            $subject = 'Campaign Created successfully - 7Search PPC';

            $body = View('emailtemp.campaigncreate', $data);

            /* User Mail Section */

            $sendmailUser = sendmailUser($subject, $body, $email);

            if ($sendmailUser == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigncreateadmin', $data);

            $subjectadmin = 'Campaign Created successfully - 7Search PPC';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Campaign Create Send Mail */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function updateBanner(Request $request)

    {

        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'countries' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $cid = $request->cid;

        $campaign = Campaign::where('campaign_id', $cid)->first();

        $array1 = explode(',', $request->device_type);

        $array2 = explode(',', $campaign->device_type);

        // Sort the arrays

        sort($array1);

        sort($array2);



        if ($array1 == $array2 && $request->ad_title == $campaign->ad_title && $request->ad_description == $campaign->ad_description && $request->website_category == $campaign->website_category && $request->pricing_model == $campaign->pricing_model && $request->cpc_amt == $campaign->cpc_amt && $campaign->countries == $request->countries)

        {

            if ($request->campaign_name != $campaign->campaign_name || $request->daily_budget != $campaign->daily_budget)

            {

                if ($campaign->status == 2)

                {
                    $status = 2;
                    $campaign->status = 2;

                }



            }

        }

        else

        {
            $status = 1;
            if ($campaign->status == 2)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 4)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 5)

            {

                $campaign->status = 1;

            }

        }

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->campaign_name = $request->campaign_name;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        if ($request->target_url != '')

        {

            $campaign->target_url = $request->target_url;

        }

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        //$campaign->status           = 1;

        $images = $request->images;

        if ($campaign->update())

        {
/* This will update campaign data and status into Redis */
        updateCamps($cid, $status);
            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Edit Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    // $filePath = 'banner-image/' . $image;

                    // if (Storage::disk('public')->exists($filePath)) {

                    //     Storage::disk('public')->delete($filePath);

                    // }

                    // $img = AdBannerImage::where([

                    //     ['campaign_id', '=', $campaign->campaign_id],

                    //     ['advertiser_code', '=', $campaign->advertiser_code],

                    //     ['image_type', '=', $image['type']],

                    // ])->update(['image_path' => $image['img']]);

                    $img = AdBannerImage::where([['campaign_id', '=', $campaign->campaign_id], ['advertiser_code', '=', $campaign->advertiser_code], ['image_type', '=', $image['type']], ])->first();

                    if ($img == null)

                    {

                        AdBannerImage::create(['campaign_id' => $campaign->campaign_id, 'advertiser_code' => $campaign->advertiser_code, 'image_type' => $image['type'], 'image_path' => $image['img']]);

                    }

                    else

                    {

                        $img->image_path = $image['img'];

                        $img->save();

                    }

                }

            }

            /* Update Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)

                ->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = $campaign->campaign_name;

            $campid = $campaign->campaign_id;

            $status = $campaign->status;

            $subjects = 'Campaign Update Successfully';

            $data['details'] = array(

                'fullname' => $fullname,

                'userid' => $userid,

                'campname' => $campname,

                'status' => $status,

                'campid' => $campid

            );

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaignupdatedmin', $data);

            $subjectadmin = 'Campaign Updated Successfully';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Campaign Updated Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* End Email Section  */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }

    /* --------------------------------- Social Campaign Funtions -------------------------------- */

    public function storeSocial(Request $request)

    {

        $validator = Validator::make($request->all() , ['uid' => 'required', 'ad_type' => 'required', 'social_ad_type' => 'required', 'ad_title' => 'required', 'ad_description' => 'required', 'campaign_name' => 'required',

        //'campaign_type'     => 'required',

        'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'daily_budget' => 'required', 'countries' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $campaign = new Campaign();

        $campaign->ad_type = $request->ad_type;

        if ($request->ad_type == 'text')

        {

            $aType = 'CMPT';

        }

        elseif ($request->ad_type == 'banner')

        {

            $aType = 'CMPB';

        }

        elseif ($request->ad_type == 'native')

        {

            $aType = 'CMPN';

        }

        elseif ($request->ad_type == 'video')

        {

            $aType = 'CMPV';

        }

        elseif ($request->ad_type == 'popup')

        {

            $aType = 'CMPP';

        }

        elseif ($request->ad_type == 'social')

        {

            $aType = 'CMPS';

        }

        else

        {

            $aType = 'Invalid';

        }

        $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

            ->where('uid', $request->uid)

            ->first();

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->advertiser_id = $user->id;

        $campaign->advertiser_code = $request->uid;

        $campaign->campaign_name = $request->campaign_name;

        $campaign->campaign_id = $aType . strtoupper(uniqid());

        $campaign->campaign_type = $request->campaign_type;

        $campaign->social_ad_type = $request->social_ad_type;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        $campaign->target_url = $request->target_url;

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        $images = $request->images;

        if ($campaign->save())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Added Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is added Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    $arr = [

                    'campaign_id' => $campaign->campaign_id,

                    'advertiser_code' => $campaign->advertiser_code,

                    'image_type' => $image['type'],

                    'image_path' => $image['img'],

                    ];

                    AdBannerImage::insert($arr);

                }

            }

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $campaign->advertiser_code;

            $campsid = $campaign->campaign_id;

            $campsname = $campaign->campaign_name;

            $campadtype = $campaign->ad_type;

            /* Send to Admin */

            $data['details'] = array(

                'subject' => 'Campaign Created successfully - 7Search PPC ',

                'fullname' => $fullname,

                'usersid' => $useridas,

                'campaignid' => $campsid,

                'campaignname' => $campsname,

                'campaignadtype' => $campadtype

            );

            $subject = 'Campaign Created successfully - 7Search PPC';

            $body = View('emailtemp.campaigncreate', $data);

            /* User Mail Section */

            $sendmailUser = sendmailUser($subject, $body, $email);

            if ($sendmailUser == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }else{

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigncreateadmin', $data);

            $subjectadmin = 'Campaign Created successfully - 7Search PPC';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }else{

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Campaign Create Send Mail */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function updateSocial(Request $request)

    {

        $validator = Validator::make(

        $request->all() ,

        [

        'campaign_name' => 'required',

        'website_category' => 'required',

        'device_type' => 'required',

        'social_ad_type' => 'required',

        'device_os' => 'required',

        'countries' => 'required',

        'pricing_model' => 'required',

        ]);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $cid = $request->cid;

        $campaign = Campaign::where('campaign_id', $cid)->first();

        $array1 = explode(',', $request->device_type);

        $array2 = explode(',', $campaign->device_type);

        // Sort the arrays

        sort($array1);

        sort($array2);

        if ($array1 == $array2 && $request->ad_title == $campaign->ad_title && $request->ad_description == $campaign->ad_description && $request->website_category == $campaign->website_category && $request->pricing_model == $campaign->pricing_model && $request->cpc_amt == $campaign->cpc_amt && $campaign->countries == $request->countries)

        {

            if ($request->campaign_name != $campaign->campaign_name || $request->daily_budget != $campaign->daily_budget)

            {
                $status =2;
                if ($campaign->status == 2)

                {

                    $campaign->status = 2;

                }



            }

        }

        else

        {
            $status =1;
            if ($campaign->status == 2)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 4)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 5)

            {

                $campaign->status = 1;

            }

        }

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->campaign_name = $request->campaign_name;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->ad_description = $request->ad_description;

        if ($request->target_url != '')

        {

            $campaign->target_url = $request->target_url;

        }

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->social_ad_type = $request->social_ad_type;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt; 

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        //$campaign->status           = 1;

        $images = $request->images;

        if ($campaign->update())

        {
/* This will update campaign data and status into Redis */
        updateCamps($cid, $status);
            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Edit Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    $img = AdBannerImage::where([

                    ['campaign_id', '=', $campaign->campaign_id],

                    ['advertiser_code', '=', $campaign->advertiser_code],

                    ['image_type', '=', $image['type']],

                    ])->update(['image_path' => $image['img']]);

                }

            }

            /* Update Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = $campaign->campaign_name;

            $campid = $campaign->campaign_id;

            $status = $campaign->status;

            $subjects = 'Campaign Update Successfully';

            $data['details'] = array(

                'fullname' => $fullname,

                'userid' => $userid,

                'campname' => $campname,

                'status' => $status,

                'campid' => $campid

            );

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaignupdatedmin', $data);

            $subjectadmin = 'Campaign Updated Successfully';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Campaign Updated Successfully !';

            }else {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* End Email Section  */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    /* --------------------------- Native Campaign Funtions ----------------------------- */



    public function storeNative(Request $request)

    {

        $validator = Validator::make(

        $request->all() ,

        [

        'uid' => 'required',

        'ad_type' => 'required',

        'ad_title' => 'required',

        //'ad_description'    => 'required',

        'campaign_name' => 'required',

        // 'campaign_type'     => 'required',

        'website_category' => 'required',

        'device_type' => 'required',

        'device_os' => 'required',

        'target_url' => 'required',

        'daily_budget' => 'required',

        'countries' => 'required',

        'pricing_model' => 'required',

        ]

);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $campaign = new Campaign();

        $campaign->ad_type = $request->ad_type;

        if ($request->ad_type == 'text')

        {

            $aType = 'CMPT';

        }

        elseif ($request->ad_type == 'banner')

        {

            $aType = 'CMPB';

        }

        elseif ($request->ad_type == 'native')

        {

            $aType = 'CMPN';

        }

        elseif ($request->ad_type == 'video')

        {

            $aType = 'CMPV';

        }

        elseif ($request->ad_type == 'popup')

        {

            $aType = 'CMPP';

        }

        elseif ($request->ad_type == 'social')

        {

            $aType = 'CMPS';

        }

        else

        {

            $aType = 'Invalid';

        }

        $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

            ->where('uid', $request->uid)

            ->first();

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }else{

            $campaign->countries = $request->countries;

        }

        $campaign->advertiser_id = $user->id;

        $campaign->advertiser_code = $request->uid;

        $campaign->campaign_name = $request->campaign_name;

        $campaign->campaign_id = $aType . strtoupper(uniqid());

        $campaign->campaign_type = $request->campaign_type;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        $campaign->target_url = $request->target_url;

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        $images = $request->images;

        if ($campaign->save())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Added Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is added Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    $arr = [

                    'campaign_id' => $campaign->campaign_id,

                    'advertiser_code' => $campaign->advertiser_code,

                    'image_type' => $image['type'],

                    'image_path' => $image['img'],

                    ];

                    AdBannerImage::insert($arr);

                }

            }

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $campaign->advertiser_code;

            $campsid = $campaign->campaign_id;

            $campsname = $campaign->campaign_name;

            $campadtype = $campaign->ad_type;

            /* Send to Admin */

            $data['details'] = array(

                'subject' => 'Campaign Created successfully - 7Search PPC ',

                'fullname' => $fullname,

                'usersid' => $useridas,

                'campaignid' => $campsid,

                'campaignname' => $campsname,

                'campaignadtype' => $campadtype

            );

            $subject = 'Campaign Created successfully - 7Search PPC';

            $body = View('emailtemp.campaigncreate', $data);

            /* User Mail Section */

            $sendmailUser = sendmailUser($subject, $body, $email);

            if ($sendmailUser == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }else{

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigncreateadmin', $data);

            $subjectadmin = 'Campaign Created successfully - 7Search PPC';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            /* Campaign Create Send Mail */

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function updateNative(Request $request)

    {

        $validator = Validator::make(

        $request->all() ,

        [

        'campaign_name' => 'required',

        'website_category' => 'required',

        'device_type' => 'required',

        'device_os' => 'required',

        'countries' => 'required',

        'pricing_model' => 'required',

        ]

);

        if ($validator->fails())

        {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        $cid = $request->cid;

        $campaign = Campaign::where('campaign_id', $cid)->first();

        $array1 = explode(',', $request->device_type);

        $array2 = explode(',', $campaign->device_type);

        // Sort the arrays

        sort($array1);

        sort($array2);

        if ($array1 == $array2 && $request->ad_title == $campaign->ad_title && $request->device_os == $campaign->device_os && $request->website_category == $campaign->website_category && $request->pricing_model == $campaign->pricing_model && $request->cpc_amt == $campaign->cpc_amt && $campaign->countries == $request->countries && !empty($request->images))

        {

            if ($request->campaign_name != $campaign->campaign_name || $request->daily_budget != $campaign->daily_budget)

            {
                $status = 2;
                if ($campaign->status == 2)

                {

                    $campaign->status = 2;

                }



            }

        }

        else

        {
            $staus = 1;
            if ($campaign->status == 2)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 4)

            {

                $campaign->status = 1;

            }

            if ($campaign->status == 5)

            {

                $campaign->status = 1;

            }

        }

        if ($request->countries != 'All')

        {

            $targetCountries = json_decode($request->countries);

            foreach ($targetCountries as $value)

            {

                $cuntry_list = Country::where('id', $value)->first();

                $res[] = $cuntry_list->name;

                $ress[] = $cuntry_list->id;

                $cuntry_lists[] = Country::select('id as value', 'name as label', 'phonecode as phonecode')->where('id', $value)->first();

            }

            $campaign->country_name = implode(",", $res);

            $campaign->country_ids = implode(",", $ress);

            $campaign->countries = json_encode($cuntry_lists);

        }

        else

        {

            $campaign->countries = $request->countries;

        }

        $campaign->campaign_name = $request->campaign_name;

        $campaign->device_type = $request->device_type;

        $campaign->device_os = $request->device_os;

        $campaign->ad_title = $request->ad_title;

        if ($request->target_url != '')

        {

            $campaign->target_url = $request->target_url;

        }

        $campaign->conversion_url = $request->conversion_url;

        $campaign->website_category = $request->website_category;

        $campaign->daily_budget = $request->daily_budget;

        $campaign->pricing_model = $request->pricing_model;

        $campaign->cpc_amt = $request->cpc_amt;

        // if ($request->pricing_model == 'CPM') {

        //     $cat = Category::where('id', $request->website_category)->first();

        //     $cpm = $cat->cpm;

        //     $campaign->cpc_amt          = $cpm;

        // } else {

        //     $campaign->cpc_amt          = $request->cpc_amt;

        // }

        //$campaign->status           = 1;

        $images = $request->images;

        if ($campaign->update())

        {
            /* This will update campaign data and status into Redis */
        updateCamps($cid, $status);
            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Edit Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            if ($images)

            {

                foreach ($images as $image)

                {

                    $img = AdBannerImage::where([

                    ['campaign_id', '=', $campaign->campaign_id],

                    ['advertiser_code', '=', $campaign->advertiser_code],

                    ['image_type', '=', $image['type']],

                    ])->update(['image_path' => $image['img']]);

                }

            }

            /* Update Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)

                ->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = $campaign->campaign_name;

            $campid = $campaign->campaign_id;

            $status = $campaign->status;

            $subjects = 'Campaign Update Successfully';

            $data['details'] = array(

                'fullname' => $fullname,

                'userid' => $userid,

                'campname' => $campname,

                'status' => $status,

                'campid' => $campid

            );

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaignupdatedmin', $data);

            $subjectadmin = 'Campaign Updated Successfully';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Send & Campaign Updated Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['data'] = $campaign;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }



            /* End Email Section  */

            // $return['code']    = 200;

            // $return['data']    = $campaign;

            // $return['msg'] = 'Campaign detail updated successfully!';

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function imageUpload(Request $request)



    {

        $base_str = explode(';base64,', $request->img);

        $ext = str_replace('data:image/', '', $base_str[0]);

        $image = base64_decode($base_str[1]);

        $safeName = md5(Str::random(10)) . '.' . $ext;

        $imgUpload = Storage::disk('public')->put('banner-image/' . $safeName, $image);

        $src = '/banner-image/' . $safeName;

        //$image_path = Storage::url('app/public') . $src;

        //$imagepathS = "../$image_path";

        $imagepath = config('app.url') . 'image' . $src;

        //$imagepath = 'https://services.7searchppc.com/' . $imagepathS;

        if ($imagepath)

        {

            $return['code'] = 200;

            $return['image_path'] = $imagepath;

            $return['msg'] = 'Image Uploaded successfully!';

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    /* -------------------------------- Common Campaign Funtions ---------------------------- */

    public function showAd(Request $request)

    {

        $cid = $request->cid;

        $advertiser_code = $request->uid;

        $campaign = DB::table('campaigns')

            ->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, SUM(ss_adv_stats.impressions) as imprs, SUM(ss_adv_stats.clicks) as click"), 'campaigns.id', 'campaigns.campaign_name', 'campaigns.campaign_id', 'campaigns.device_type', 

            'campaigns.device_os', 'campaigns.ad_title', 'campaigns.ad_description', 'campaigns.advertiser_code', 'campaigns.website_category', 'campaigns.status', 'campaigns.ad_type', 'campaigns.daily_budget', 

            'campaigns.country_ids', 'campaigns.pricing_model', 'campaigns.cpc_amt', 'campaigns.country_name', 'campaigns.countries', 'campaigns.social_ad_type', 'campaigns.created_at', 'campaigns.target_url', 

            'campaigns.conversion_url', 'categories.cat_name')

            ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')

            ->Leftjoin('adv_stats', 'campaigns.campaign_id', '=', 'adv_stats.camp_id')

            ->join('categories', 'campaigns.website_category', '=', 'categories.id')

            ->where('campaigns.trash', 0)

            ->where('campaigns.campaign_id', $cid)

            ->where('campaigns.advertiser_code', $advertiser_code)

            ->first();

        if ($campaign) {

            if ($campaign->ad_type == 'text') {

                $return['data'] = $campaign;

            } elseif ($campaign->ad_type == 'banner') {

                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)->get();

                $return['data'] = $campaign;

                $i = 0;

                foreach ($images as $img) {

                    $i++;

                    $return['images']['ad' . $img['image_type']] = $img['image_path'];

                }

            } elseif ($campaign->ad_type == 'social') {

                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)->get();



                $return['data'] = $campaign;

                $i = 0;

                foreach ($images as $img) {

                    $i++;

                    $return['images']['ad' . $i] = $img['image_path'];

                }

            } elseif ($campaign->ad_type == 'native') {

                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)->get();



                $return['data'] = $campaign;

                $i = 0;

                foreach ($images as $img) {

                    $i++;

                    // $return['images']['ad' . $i] = $img['image_path'];

                    $return['images']['ad' .  $img['image_type']] = $img['image_path'];

                }

            } elseif ($campaign->ad_type == 'popup') {

                $return['data'] = $campaign;

            }





            $return['code'] = 200;

            $return['message'] = 'Campaign data retrieved!';

        } else {

            $return['code'] = 101;

            $return['message'] = 'Something went wrong!';

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    public function delete(Request $request)

    {

        $cid = $request->cid;

        $uid = $request->uid;

        $campaign = Campaign::where('campaign_id', $cid)->where('advertiser_code', $uid)->first();

        $campaign->trash = 1;

        if ($campaign->update())

        {

            $activitylog = new Activitylog();

            $activitylog->uid = $request->uid;

            $activitylog->type = 'Delete Campaign';

            $activitylog->description = '' . $campaign->campaign_id . ' is deleted Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            /* Delete Campaign Email Section */

            $usersdetils = User::select('first_name', 'last_name')->where('uid', $request->uid)->first();

            $fullname = $usersdetils->first_name . ' ' . $usersdetils->last_name;

            $userid = $request->uid;

            $campname = '';

            $campid = $cid;

            $subjects = 'Delete Campaign Successfully';

            $data['details'] = ['fullname' => $fullname, 'userid' => $userid, 'campname' => $campname, 'campid' => $campid];

            /* Admin Section  */

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $bodyadmin = View('emailtemp.campaigndeletedmin', $data);

            $subjectadmin = 'Campaign Deleted Successfully !';

            $sendmailadmin = sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);

            if ($sendmailadmin == '1')

            {

                $return['code'] = 200;

                $return['msg'] = 'Mail Send & Data Inserted Successfully !';

            }

            else

            {

                $return['code'] = 200;

                $return['msg'] = 'Mail Not Send But Data Insert Successfully !';

            }

            $return['code'] = 200;

            $return['msg'] = 'Campaign deleted successfully!';

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function listTest(Request $request)

    {

        $type = $request->type;

        $uid = $request->uid;

        $limit = $request->lim;

        $page = $request->page;

        $status = $request->status;

        $src = $request->src;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $date = date('Y-m-d');

        $user = User::where('uid', $uid)->first();

        $campaign = Campaign::selectRaw("ss_campaigns.campaign_name,ss_campaigns.campaign_id,ss_campaigns.status,ss_campaigns.ad_type, ss_campaigns.daily_budget,

        (select IFNULL(sum(clicks),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click, 

        (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id) as imprs,

        DATE_FORMAT(ss_campaigns.created_at, '%d %b %Y') as createdat, ss_categories.cat_name, ((select sum(amount) from 

        ss_camp_budget_utilize ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id AND DATE(ad_imp.udate) = DATE('" . $date . "') )+(select sum(amount)

         from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id AND DATE(camp_ck.udate) = DATE('" . $date . "'))) as spent_amt")->join('categories', 'campaigns.website_category', '=', 'categories.id')

            ->where('campaigns.advertiser_code', $uid)->where('campaigns.trash', 0);

        if (strlen($type) > 0 and empty($status))

        {

            $campaign = $campaign->where('campaigns.ad_type', $type);

        }

        if ($src)

        {

            $campaign = $campaign->whereRaw('concat(ss_campaigns.campaign_id,ss_campaigns.campaign_name,ss_campaigns.campaign_type) like ?', "%{$src}%")->orderBy('campaigns.id', 'desc');

        }

        if (strlen($type) > 0 and !empty($status))

        {

            $campaign = $campaign->where('campaigns.ad_type', $type)->where('campaigns.status', $status);

        }

        if (strlen($type) <= 0 and !empty($status))

        {

            $campaign = $campaign->where('campaigns.status', $status);

        }

        $campaign->orderBy('campaigns.id', 'desc');

        $row = $campaign->count();

        $data = $campaign->offset($start)->limit($limit)->get();

        if (count($data) > 0)

        {

            $return['code'] = 200;

            $return['data'] = $data;

            $return['row'] = $row;

            // $return['wallet'] = $user->wallet;

            $wltAmt = getWalletAmount($uid);
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : $user->wallet;

            $return['msg'] = 'Campaigns list retrieved successfully!';

        }

        else

        {

            if ($row)

            {

                $return['code'] = 103;

                $return['message'] = 'Not Found Data !';

                return json_encode($return, JSON_NUMERIC_CHECK);

            }

            $return['code'] = 101;

            $return['msg'] = 'No Data Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    // {

    //     $type = $request->type;

    //     $uid = $request->uid;

    //     $limit = $request->lim;

    //     $page = $request->page;

    //     $status = $request->status;

    //     $src = $request->src;

    //     $pg = $page - 1;

    //     $start = ($pg > 0) ? $limit * $pg : 0;

    //     $date = date('Y-m-d');

    //     $user = User::where('uid', $uid)->first();

    //     $campaign = Campaign::selectRaw("ss_campaigns.campaign_name,ss_campaigns.campaign_id,ss_campaigns.status,ss_campaigns.ad_type, ss_campaigns.daily_budget,

    //             (select IFNULL(sum(clicks),0) from ss_camp_budget_utilize  camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click, 

    //             (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize  ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id) as imprs,

    //             DATE_FORMAT(ss_campaigns.created_at, '%d %b %Y') as createdat, ss_categories.cat_name, 

    //             ((select IFNULL(sum(amount),0) from ss_camp_budget_utilize ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id AND DATE(ad_imp.udate) = DATE('".$date."') )) as spent_amt")

    //             ->join('categories', 'campaigns.website_category', '=', 'categories.id')

    //             ->where('campaigns.advertiser_code', $uid)->where('campaigns.trash', 0);



                

    //     if (strlen($type) > 0 and empty($status)) {

    //         $campaign = $campaign->where('campaigns.ad_type', $type);

    //     }

    //     if ($src) {

    //         $campaign = $campaign->whereRaw('concat(ss_campaigns.campaign_id,ss_campaigns.campaign_name,ss_campaigns.campaign_type) like ?', "%{$src}%")->orderBy('campaigns.id', 'desc');

    //     }

    //     if (strlen($type) > 0 and !empty($status)) {

    //         $campaign = $campaign->where('campaigns.ad_type', $type)->where('campaigns.status', $status);

    //     }

    //     if (strlen($type) <= 0 and !empty($status)) {

    //         $campaign = $campaign->where('campaigns.status', $status);

    //     }

    //         $campaign->orderBy('campaigns.id', 'desc');

    //         $row = $campaign->count();

    //         $data = $campaign->offset($start)->limit($limit)->get();

    //     if ($row !== null) {

    //         $return['code']    = 200;

    //         $return['data']    = $data;

    //         $return['row']     = $row;

    //       	$return['wallet']  = number_format($user->wallet, 3, '.', '');

    //         $return['message'] = 'Campaigns list retrieved successfully!';

    //     } else {

    //         $return['code'] = 101;

    //         $return['message'] = 'Something went wrong!';

    //     }

    //     return json_encode($return, JSON_NUMERIC_CHECK);

    // }

    public function list(Request $request)

    {

        $type = $request->type;

        $uid = $request->uid;

        $limit = $request->lim;

        $page = $request->page;

        $status = $request->status;

        $src = $request->src;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $date = date('Y-m-d');

      	$user = User::where('uid', $uid)->first();

        $campaign = Campaign::selectRaw("ss_campaigns.campaign_name,ss_campaigns.campaign_id,ss_campaigns.status,ss_campaigns.ad_type, ss_campaigns.daily_budget,

                (select IFNULL(sum(clicks),0) from ss_camp_budget_utilize  camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click, 

                (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize  ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id) as imprs,

                DATE_FORMAT(ss_campaigns.created_at, '%d %b %Y - %h:%i %p') as createdat, ss_categories.cat_name, 

                ((select IFNULL(sum(amount),0) from ss_camp_budget_utilize ad_imp where ad_imp.camp_id = ss_campaigns.campaign_id AND DATE(ad_imp.udate) = DATE('".$date."') )) as spent_amt")

                ->join('categories', 'campaigns.website_category', '=', 'categories.id')

                ->where('campaigns.advertiser_code', $uid)->where('campaigns.trash', 0);



                

        if (strlen($type) > 0 and empty($status)) {

            $campaign = $campaign->where('campaigns.ad_type', $type);

        }

        if ($src) {

            $campaign = $campaign->whereRaw('concat(ss_campaigns.campaign_id,ss_campaigns.campaign_name,ss_campaigns.campaign_type) like ?', "%{$src}%")->orderBy('campaigns.id', 'desc');

        }

        if (strlen($type) > 0 and !empty($status)) {

            $campaign = $campaign->where('campaigns.ad_type', $type)->where('campaigns.status', $status);

        }

        if (strlen($type) <= 0 and !empty($status)) {

            $campaign = $campaign->where('campaigns.status', $status);

        }

            $campaign->orderBy('campaigns.id', 'desc');

            $row = $campaign->count();

            $data = $campaign->offset($start)->limit($limit)->get();

        // if ($row !== null) {

        //     $return['code']    = 200;

        //     $return['data']    = $data;

        //     $return['row']     = $row;

        //   	$return['wallet']  = number_format($user->wallet, 3, '.', '');

        //     $return['message'] = 'Campaigns list retrieved successfully!';

        // } else {

        //     $return['code'] = 101;

        //     $return['message'] = 'Something went wrong!';

        // }

        // return json_encode($return, JSON_NUMERIC_CHECK);

        if ($row > 0) {

            if(count($data) > 0){

                $return['code']    = 200;

                $return['data']    = $data;

                $return['row']     = $row;

                // $return['wallet']  = number_format($user->wallet, 3, '.', '');

                $wltAmt = getWalletAmount($uid);
                $return['wallet']        = ($wltAmt) > 0 ? number_format($wltAmt, 3, '.', '') : number_format($user->wallet, 3, '.', '');

                $return['message'] = 'Campaigns list retrieved successfully!';

            }else{

                $return['code'] = 103;

                $return['message'] = 'End of the line! No more listings available for now.';

            }

        } else {

            $return['code'] = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    public function campaignStatusUpdate(Request $request)

    {

        $cid = $request->cid;

        $adv_code = $request->uid;

        $status = $request->status;

        if ($status == 2)

        {

            $obj = Campaign::where('campaign_id', $cid)->where('advertiser_code', $adv_code);

        }

        elseif ($status == 4)

        {

            $obj = Campaign::where('campaign_id', $cid)->where('advertiser_code', $adv_code);

        }

        else

        {

            $return['code'] = 102;

            $return['msg'] = 'Something went wrong!';

            return json_encode($return);

        }

        $cnt = $obj->count();

        if ($cnt > 0)

        {

            $cStatus = $obj->first();

            $cStatus->status = $status;

            if ($cStatus->update())

            {

                $return['code'] = 200;

                $return['msg'] = 'Campaign status updated successfully!';

            }

            else

            {

                $return['code'] = 101;

                $return['msg'] = 'Something went wrong!';

            }

        }

        else

        {

            $return['code'] = 103;

            $return['msg'] = 'Something went wrong!';

            return json_encode($return);

        }

        return json_encode($return);

    }



    public function campaignAction(Request $request)

    {

        $cid = $request->cid;

        $uid = $request->uid;

        $type = $request->type;

        $count = 0;

        $trs = 0;

        if ($type == 'active')

        {

            $sts = 2;

            $campaign = Campaign::where('campaign_id', $cid)->where('advertiser_code', $uid)->where('status', 4)

                ->first();

            $activitylog = new Activitylog();

            $activitylog->uid = $uid;

            $activitylog->type = $type;

            $activitylog->description = '' . $cid . ' is ' . $type . ' Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            $msg = 'Campaign Active successfully!';

        }

        elseif ($type == 'pause')

        {

            $sts = 4;

            $campaign = Campaign::where('campaign_id', $cid)->where('advertiser_code', $uid)->where('status', 2)

                ->first();

            $activitylog = new Activitylog();

            $activitylog->uid = $uid;

            $activitylog->type = $type;

            $activitylog->description = '' . $cid . ' is ' . $type . ' Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            $msg = 'Campaign Pause successfully!';

        }

        if ($type == 'delete')

        {

            $trs = 1;

            $campaign = Campaign::where('campaign_id', $cid)->where('advertiser_code', $uid)->where('trash', 0)

                ->first();

            $activitylog = new Activitylog();

            $activitylog->uid = $uid;

            $activitylog->type = $type;

            $activitylog->description = '' . $cid . ' is ' . $type . ' Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            $msgs = 'Campaign Deleted Successfully!';

        }

        else

        {

            $trs = 0;

            $msgs = $msg;

        }

        if ($campaign)

        {

            if ($trs == 0)

            {

                $campaign->status = $sts;

            }

            else

            {
                $sts = 1;
                $campaign->trash = 1;

            }

            $campaign->update();

            $count++;

        }

        if ($count > 0)

        {
            /* This will update campaign into Redis */
            updateCamps($cid, $sts);
            $return['code'] = 200;

            $return['data'] = $campaign;

            $return['rows'] = $count;

            $return['msg'] = $msgs;

        }

        else

        {

            $return['code'] = 101;

            $return['msg'] = 'Something went wrong!';

        }

        return json_encode($return);

    }



    public function duplicateCampaign(Request $request)



    {

        $cid = $request->cid;

        $uid = $request->uid;

        $copyCampaign = Campaign::select('*')->where('campaign_id', $cid)->where('advertiser_code', $uid)->first()

            ->toArray();

        $adType = $copyCampaign['ad_type'];

        $pref = getCampPrefix($adType);

        $copyCampaign['campaign_id'] = $pref . strtoupper(uniqid());

        $copyCampaign['status'] = 1;

        $copyCampaign['trash'] = 0;

        unset($copyCampaign['id']);

        $copyCampaign['created_at'] = date('Y-m-d H:i:s');

        $copyCampaign['updated_at'] = date('Y-m-d H:i:s');

        $res = Campaign::insert($copyCampaign);

        if ($res)

        {

            if ($adType == 'banner' || $adType == 'native' || $adType == 'social')

            {

                $images = AdBannerImage::where('advertiser_code', $uid)->where('campaign_id', $cid)->get()

                    ->toArray();

                foreach ($images as $img)

                {

                    $img['campaign_id'] = $copyCampaign['campaign_id'];

                    $img['created_at'] = date('Y-m-d H:i:s');

                    $img['updated_at'] = date('Y-m-d H:i:s');

                    unset($img['id']);

                    AdBannerImage::insert($img);

                }

            }

            $user = DB::table('users')->select('id', 'first_name', 'last_name', 'email')

                ->where('uid', $request->uid)

                ->first();

            /* Create Campaign Send Mail   */

            $email = $user->email;

            $fullname = $user->first_name . ' ' . $user->last_name;

            $useridas = $copyCampaign['advertiser_code'];

            $campsid = $copyCampaign['campaign_id'];

            $campsname = $copyCampaign['campaign_name'];

            $campadtype = $copyCampaign['ad_type'];

            /* Send to Admin */

            $mailsentdetals = ['subject' => 'Campaign Creation Request ', 'fullname' => $fullname, 'usersid' => $useridas, 'campaignid' => $campsid, 'campaignname' => $campsname, 'campaignadtype' => $campadtype];

            $adminmail1 = 'advertisersupport@7searchppc.com';

         // $adminmail1 = ['advertisersupport@7searchppc.com', 'testing@7searchppc.com'];

            $adminmail2 = 'info@7searchppc.com';

            $mailTo = [$adminmail1, $adminmail2];

            try

            {

                Mail::to($mailTo)->send(new CreateCampMailAdmin($mailsentdetals));

                Mail::to($email)->send(new CreateCampMail($mailsentdetals));

                $return['code'] = 200;

                $return['data'] = $copyCampaign;

                $return['message'] = 'Campaign detail added successfully!';

            }

            catch(Exception $e)

            {

                $return['code'] = 200;

                $return['data'] = $copyCampaign;

                $return['msg'] = 'Campaign detail added successfully!. But mail not send to the user.';

            }

            /* Campaign Create Send Mail */



            $return['code'] = 200;

            $activitylog = new Activitylog();

            $activitylog->uid = $uid;

            $activitylog->type = 'Copy Campaign';

            $activitylog->description = '' . $cid . ' is copy Successfully';

            $activitylog->status = '1';

            $activitylog->save();

            $return['camp_id'] = $copyCampaign['campaign_id'];

            $return['adtype'] = $adType;

            $return['message'] = 'Campaign Saved!';

        }

        else

        {

            $return['code'] = 101;

            $return['message'] = 'Something went wrong!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // public function onchangecpcold(Request $request)

    // {

    //     $typename = $request->type;

    //     $catname = $request->cat_name;

    //     if ($typename == 'CPC')

    //     {

    //         $catid = Category::where('cat_name', $catname)->where('status', 1)

    //             ->where('trash', 0)

    //             ->first();

    //         if (empty($catid))

    //         {

    //             $return['code'] = 101;

    //             $return['message'] = 'Not Found Category Name!';

    //             return json_encode($return);

    //         }

    //         $cpcamt = $catid->cpc;

    //         $cpcid = $catid->id;

    //         // $query = DB::table('campaigns')

    //         //     ->select('cpc_amt')

    //         //     ->whereRaw('cpc_amt in (select max(cpc_amt) from ss_campaigns group by (website_category))')

    //         //     ->where('website_category', $cpcid)

    //         //     ->where('status', 2)

    //         //     ->where('pricing_model', $typename)

    //         //     ->first();

    //         $query = DB::table('campaigns')->select(DB::raw('MAX(cpc_amt) as cpc_amt'))

    //             ->where('website_category', $cpcid)->where('trash', 0)

    //             ->where('status', 2)

    //             ->where('pricing_model', $typename)->first();

    //         if (empty($query))

    //         {

    //             $return['code'] = 200;

    //             $return['base_amt'] = $cpcamt;

    //             $return['high_amt'] = $cpcamt;

    //             $return['message'] = 'Successfully found !';

    //             return json_encode($return, JSON_NUMERIC_CHECK);

    //         }

    //         else

    //         {

    //             $campcpcamt = $query->cpc_amt;

    //             $return['code'] = 200;

    //             $return['base_amt'] = $cpcamt;

    //             $return['high_amt'] = $campcpcamt;

    //             $return['message'] = 'Successfully found !';

    //         }

    //         return json_encode($return, JSON_NUMERIC_CHECK);



    //         /*  $campcpcamt = $query->cpc_amt;

    //          if($cpcamt <= $campcpcamt) {

    //             $return ['code'] = 200;

    //             $return ['Amount'] = $campcpcamt;

    //             $return ['message'] = 'Successfully found !';

    //          }  else {

    //             $return ['code'] = 200;

    //             $return ['Amount'] = $cpcamt;

    //             $return ['message'] = 'Successfully found !';

    //          } */

    //     }

    //     elseif ($typename == 'CPM')

    //     {

    //         $catid = Category::where('cat_name', $catname)->where('status', 1)

    //             ->where('trash', 0)

    //             ->first();

    //         if (empty($catid))

    //         {

    //             $return['code'] = 101;

    //             $return['message'] = 'Not Found Category Name!';

    //             return json_encode($return, JSON_NUMERIC_CHECK);

    //         }

    //         $cpmamt = $catid->cpm;

    //         $cpcid = $catid->id;

    //         // $query = DB::table('campaigns')

    //         //     ->select(Max('cpc_amt'))

    //         //     //->whereRaw('cpc_amt in (select max(cpc_amt) from ss_campaigns group by (website_category))')

    //         //     ->where('website_category', $cpcid)

    //         //     ->where('status', 2)

    //         //     ->where('pricing_model', $typename)

    //         //     ->first();

    //         $query = DB::table('campaigns')->select(DB::raw('MAX(cpc_amt) as max_cpc_amt'))

    //             ->where('website_category', $cpcid)->where('trash', 0)

    //             ->where('status', 2)

    //             ->where('pricing_model', $typename)->first();

    //         if (empty($query))

    //         {

    //             $return['code'] = 200;

    //             $return['base_amt'] = $cpmamt;

    //             $return['high_amt'] = $cpmamt;

    //             $return['message'] = 'Successfully found !';

    //             return json_encode($return, JSON_NUMERIC_CHECK);

    //         }

    //         else

    //         {

    //             $campcpcamt = $query->max_cpc_amt;

    //             $return['code'] = 200;

    //             $return['base_amt'] = $cpmamt;

    //             $return['high_amt'] = $campcpcamt;

    //             $return['message'] = 'Successfully found !';

    //         }



    //         /*     if(empty($query))

    //          {

    //             $return ['code'] = 200;

    //             $return ['amount'] = $cpmamt;

    //             $return ['message'] = 'Successfully found !';

    //             return json_encode($return); 

    //          }

    //          $campcpcamt = $query->cpc_amt;

    //          if($cpmamt <= $campcpcamt) {

    //             $return ['code'] = 200;

    //             $return ['Amount'] = $campcpcamt;

    //             $return ['message'] = 'Successfully found !';

    //          } else {

    //             $return ['code'] = 200;

    //             $return ['Amount'] = $cpmamt;

    //             $return ['message'] = 'Successfully found !';

    //          } */

    //     }

    //     else

    //     {

    //         $return['code'] = 101;

    //         $return['message'] = 'Invalid Format';

    //     }

    //     return json_encode($return, JSON_NUMERIC_CHECK);

    // }

    public function onchangecpc(Request $request)
    {
        $typename   = $request->type;
        $catname   = $request->cat_name;
        $country   = $request->country;

        $catid = Category::where('cat_name', $catname)->where('status', 1)->where('trash', 0)->first();
        if (empty($catid)) {
            $return['code'] = 101;
            $return['message'] = 'Not Found Category Name!';
            return json_encode($return);
        }
        if($typename != 'CPM' && $typename != 'CPC'){
            $return['code'] = 101;
            $return['message'] = 'Invalid type name, allow only- CPM or CPC!';
            return json_encode($return);
        }
        $cpcid = $catid->id;
        $query = DB::table('campaigns')
        ->select(DB::raw('MAX(cpc_amt) as cpc_amt'))
        ->where('website_category', $cpcid)
        ->where('trash', 0)
        ->where('status', 2)
        ->where('pricing_model', $typename)
        ->first();
        if (empty($country)) {
            if ($typename == 'CPC') {
                $cpcamt = $catid->cpc;
                if (empty($query)) {
                    $return['code'] = 200;
                    $return['base_amt'] = $cpcamt;
                    $return['high_amt'] = $cpcamt;
                    $return['message'] = 'Successfully found !';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                } else {
                    $campcpcamt = $query->cpc_amt;
                    $return['code'] = 200;
                    $return['base_amt'] = $cpcamt;
                    $return['high_amt'] = $campcpcamt;
                    $return['message'] = 'Successfully found !';
                }
                return json_encode($return, JSON_NUMERIC_CHECK);
            } elseif ($typename == 'CPM') {

                $cpmamt = $catid->cpm;

                if (empty($query)) {
                    $return['code'] = 200;
                    $return['base_amt'] = $cpmamt;
                    $return['high_amt'] = $cpmamt;
                    $return['message'] = 'Successfully found !';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                } else {
                    $campcpcamt = $query->cpc_amt;
                    $return['code'] = 200;
                    $return['base_amt'] = $cpmamt;
                    $return['high_amt'] = $campcpcamt;
                    $return['message'] = 'Successfully found !';
                }
            } else {
                $return['code'] = 101;
                $return['message'] = 'Invalid Format';
            }
        }
        else
        {

            $pubrateData = DB::table('pub_rate_masters')
            ->select(
                'category_id',
                'category_name',
                'country_name',
                DB::raw('MAX(ss_pub_rate_masters.cpm) as cpm_amt'),
                DB::raw('MAX(ss_pub_rate_masters.cpc) as cpc_amt')
            )
            ->where("category_name", $catname)
            ->whereIn("country_name", $country)
            ->where('status', 0)->first();
            $cpmamt = $pubrateData->cpm_amt;
            $cpcamt = $pubrateData->cpc_amt;
            $cpcid = $pubrateData->category_id;
            $query2 = DB::table('campaigns')
                            ->select(DB::raw('MAX(cpc_amt) as max_amt'))
                            ->where('website_category', $cpcid)
                            ->where('trash', 0)
                            ->where('status', 2)
                            ->where('pricing_model', $typename)
                            ->whereIn('country_name',$country)
                            ->first();
            if(!empty($pubrateData) && !empty($cpmamt) && !empty($cpcamt))
            {
                if($typename == 'CPM')
                {
                $return['base_amt'] = $cpmamt;
                $return['high_amt'] = ($query2->max_amt > $cpmamt) ? $query2->max_amt : $cpmamt ;
                $return['message'] = "CPM Data found successfully.";
                }
                else {
                $return['code'] = 200;
                $return['base_amt'] = $cpcamt;
                $return['high_amt'] = ($query2->max_amt > $cpcamt) ? $query2->max_amt : $cpcamt ;
                $return['message'] = "CPC Data found successfully.";
            } 
          }
            else
            {
                if ($typename == 'CPC') {
                    $cpcamt = $catid->cpc;
                    if (empty($query)) {
                        $return['code'] = 200;
                        $return['base_amt'] = $cpcamt;
                        $return['high_amt'] = $cpcamt;
                        $return['message'] = 'Successfully found !';
                        return json_encode($return, JSON_NUMERIC_CHECK);
                    } else {
                        $campcpcamt = $query->cpc_amt;
                        $return['code'] = 200;
                        $return['base_amt'] = $cpcamt;
                        $return['high_amt'] = $campcpcamt;
                        $return['message'] = 'Successfully found !';
                    }
                    return json_encode($return, JSON_NUMERIC_CHECK);
                } elseif ($typename == 'CPM') {
    
                    $cpmamt = $catid->cpm;
    
                    if (empty($query)) {
                        $return['code'] = 200;
                        $return['base_amt'] = $cpmamt;
                        $return['high_amt'] = $cpmamt;
                        $return['message'] = 'Successfully found !';
                        return json_encode($return, JSON_NUMERIC_CHECK);
                    } else {
                        $campcpcamt = $query->cpc_amt;
                        $return['code'] = 200;
                        $return['base_amt'] = $cpmamt;
                        $return['high_amt'] = $campcpcamt;
                        $return['message'] = 'Successfully found !';
                    }
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Invalid Format';
                }
            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }



    public function deleteCampaignImage(Request $request)

    {

        foreach ($request->images as $key => $value)

        {

            $path = 'banner-image/' . $value;

            if (Storage::disk('public')->exists($path))

            {

                Storage::disk('public')->delete($path);

            }

        }

        AdBannerImage::whereIn('image_type', $request->images)

            ->where('campaign_id', $request->cid)

            ->delete();

        $return['code'] = 200;

        $return['message'] = 'Campaign updated successfully!';

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    public function deleteOldImage()

    {

        $path = 'banner-image/' . '0326b48b7c4cfe95ce0ef396742bccad.png';

        if (Storage::disk('public')->exists($path))

        {

            Storage::disk('public')->delete($path);

            return redirect()->back()

                ->with('success', 'Image deleted successfully.');

        }

        else

        {

            return redirect()

                ->back()

                ->with('error', 'Image not found.');

        }

    }

    public function get_size(Request $request)

    {

        $url = $request->url;

        if (!empty($url))

        {

            list($width, $height) = getimagesize($url);

            return json_encode(['width' => $width, 'height' => $height, 'message' => 'Image Size Fetched.', 'code' => 200]);

        }

        else

        {

            return "Url not found!..";

        }

    }



}



