<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\AdImpression;
use App\Models\CountriesIps;
use App\Models\Category;
use App\Models\AdBannerImage;
use App\Models\UserCampClickLog;
use App\Models\Activitylog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DateTime;
use Illuminate\Support\Facades\Mail;
use App\Mail\StatusCampMail;
use App\Models\CampaignLogs;
use App\Models\Notification;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class CampaignAdminController extends Controller
{
    public function adminCampaignList(Request $request)
    {
        $type = $request->type;
        $src = $request->src;
        $categ = $request->cat;
        $status = $request->status;
        $usertype = $request->usertype;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $campaign = DB::table('campaigns')->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, 
        (select IFNULL(sum(clicks),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click , 
        (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as imprs") , 'campaigns.id', 'campaigns.campaign_name', 'campaigns.campaign_id', 'campaigns.advertiser_code', 'campaigns.website_category', 'campaigns.status', 'campaigns.ad_type', 'campaigns.countries as cmp_country_ids', 'campaigns.campaign_type', 'campaigns.daily_budget', 'campaigns.created_at', 'campaigns.target_url', 'categories.cat_name', 'users.account_type', 'users.email', 'users.country')
            ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaigns.trash', 0);
        if (strlen($type) > 0)
        {
            $campaign->where('campaigns.ad_type', $type);
        }
        if ($categ)
        {
            $campaign->where('campaigns.website_category', $categ);
        }
        if (strlen($usertype) > 0)
        {
            $campaign->where('users.account_type', $usertype);
        }
        if ($src)
        {
            $campaign->whereRaw('concat(ss_campaigns.campaign_id,ss_campaigns.campaign_name,ss_users.uid, ss_users.first_name, ss_users.last_name,ss_campaigns.campaign_type,ss_users.country,ss_users.email) like ?', "%{$src}%");
        }
        if ($status > 0 && $status < 9)
        {
            $campaign->where('campaigns.status', $status);
        }
        elseif ($status == 9)
        {
            $campaign->where('campaigns.status', 0);
        }
        $campaign->orderBy('campaigns.id', 'desc');
        $row = $campaign->count();
        $data = $campaign->offset($start)->limit($limit)->get();
        if ($campaign)
        {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row'] = $row;
            $return['message'] = 'Campaigns list retrieved successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    //========== Performing bulk actions on multiple Campaign ===============//
    public function campaignBulkMultipleAction(Request $request)
    {
        $actionType = $request->action;
        $ids = $request->cid;
        $count = 0;
        $campLogData = [];
        if ($actionType == 'active') {
            $Camp = Campaign::whereIn('campaign_id', $ids)->where('status', '!=', 6)->get();
            $status = ['status' => 'Active'];
            foreach ($Camp as $val) {
                if ($val->status == 1) {
                    $val->status = 'In Review';
                } elseif ($val->status == 2) {
                    $val->status = 'Active';
                } elseif ($val->status == 3) {
                    $val->status = 'In Active';
                } elseif ($val->status == 4) {
                    $val->status = 'Paused';
                } elseif ($val->status == 5) {
                    $val->status = 'Hold';
                } else {
                    $val->status = 'Suspend';
                }
                $oldData = $val->only([
                    'status'
                ]);
                $newData = $status;
                foreach ($oldData as $property => $value) {
                    if ($value != $newData[$property]) {
                        $campLogData[$property]['previous'] = $value;
                        $campLogData[$property]['updated'] = $newData[$property];
                    }
                }
                $campLogData['message'] = 'Admin has changed the status!';
                $camp_data = json_encode($campLogData);
                $camp_log = new CampaignLogs();
                $camp_log->uid = $val->advertiser_code;
                $camp_log->campaign_id = $val->campaign_id;
                $camp_log->campaign_type = $val->ad_type;
                $camp_log->campaign_data = $camp_data;
                $camp_log->action = 2;
                $camp_log->user_type = 2;
                $camp_log->save();
            }
            $Campaigns = Campaign::whereIn('campaign_id', $ids)->where('status', '!=', 6)->update(['status' => 2]);
            $count++;
        } else if ($actionType == 'hold') {
            $Camp = Campaign::whereIn('campaign_id', $ids)->get();
            $status = ['status' => 'Hold'];
            foreach ($Camp as $val) {
                if ($val->status == 1) {
                    $val->status = 'In Review';
                } elseif ($val->status == 2) {
                    $val->status = 'Active';
                } elseif ($val->status == 3) {
                    $val->status = 'In Active';
                } elseif ($val->status == 4) {
                    $val->status = 'Paused';
                } elseif ($val->status == 5) {
                    $val->status = 'Hold';
                } else {
                    $val->status = 'Suspend';
                }
                $oldData = $val->only([
                    'status'
                ]);
                $newData = $status;
                foreach ($oldData as $property => $value) {
                    if ($value != $newData[$property]) {
                        $campLogData[$property]['previous'] = $value;
                        $campLogData[$property]['updated'] = $newData[$property];
                    }
                }
                $campLogData['message'] = 'Admin has changed the status!';
                $camp_data = json_encode($campLogData);
                $camp_log = new CampaignLogs();
                $camp_log->uid = $val->advertiser_code;
                $camp_log->campaign_id = $val->campaign_id;
                $camp_log->campaign_type = $val->ad_type;
                $camp_log->campaign_data = $camp_data;
                $camp_log->action = 2;
                $camp_log->user_type = 2;
                $camp_log->save();
            }
            $Campaigns = Campaign::whereIn('campaign_id', $ids)->update(['status' => 5]);
            $count++;
        } else if ($actionType == 'suspend') {
            $Camp = Campaign::whereIn('campaign_id', $ids)->get();
            $status = ['status' => 'Suspend'];
            foreach ($Camp as $val) {
                if ($val->status == 1) {
                    $val->status = 'In Review';
                } elseif ($val->status == 2) {
                    $val->status = 'Active';
                } elseif ($val->status == 3) {
                    $val->status = 'In Active';
                } elseif ($val->status == 4) {
                    $val->status = 'Paused';
                } elseif ($val->status == 5) {
                    $val->status = 'Hold';
                } else {
                    $val->status = 'Suspend';
                }
                $oldData = $val->only([
                    'status'
                ]);
                $newData = $status;
                foreach ($oldData as $property => $value) {
                    if ($value != $newData[$property]) {
                        $campLogData[$property]['previous'] = $value;
                        $campLogData[$property]['updated'] = $newData[$property];
                    }
                }
                $campLogData['message'] = 'Admin has changed the status!';
                $camp_data = json_encode($campLogData);
                $camp_log = new CampaignLogs();
                $camp_log->uid = $val->advertiser_code;
                $camp_log->campaign_id = $val->campaign_id;
                $camp_log->campaign_type = $val->ad_type;
                $camp_log->campaign_data = $camp_data;
                $camp_log->action = 2;
                $camp_log->user_type = 2;
                $camp_log->save();
            }
            $Campaigns = Campaign::whereIn('campaign_id', $ids)->update(['status' => 6]);
            $count++;
        } else if ($actionType == 'paused') {
            $Camp = Campaign::whereIn('campaign_id', $ids)->get();
            $status = ['status' => 'Paused'];
            foreach ($Camp as $val) {
                if ($val->status == 1) {
                    $val->status = 'In Review';
                } elseif ($val->status == 2) {
                    $val->status = 'Active';
                } elseif ($val->status == 3) {
                    $val->status = 'In Active';
                } elseif ($val->status == 4) {
                    $val->status = 'Paused';
                } elseif ($val->status == 5) {
                    $val->status = 'Hold';
                } else {
                    $val->status = 'Suspend';
                }
                $oldData = $val->only([
                    'status'
                ]);
                $newData = $status;
                foreach ($oldData as $property => $value) {
                    if ($value != $newData[$property]) {
                        $campLogData[$property]['previous'] = $value;
                        $campLogData[$property]['updated'] = $newData[$property];
                    }
                }
                $campLogData['message'] = 'Admin has changed the status!';
                $camp_data = json_encode($campLogData);
                $camp_log = new CampaignLogs();
                $camp_log->uid = $val->advertiser_code;
                $camp_log->campaign_id = $val->campaign_id;
                $camp_log->campaign_type = $val->ad_type;
                $camp_log->campaign_data = $camp_data;
                $camp_log->action = 2;
                $camp_log->user_type = 2;
                $camp_log->save();
            }
            $Campaigns = Campaign::whereIn('campaign_id', $ids)->update(['status' => 4]);
            $count++;
        } else {
            $Camp = Campaign::whereIn('campaign_id', $ids)->get();
            $status = ['status' => 'In Review'];
            foreach ($Camp as $val) {
                if ($val->status == 1) {
                    $val->status = 'In Review';
                } elseif ($val->status == 2) {
                    $val->status = 'Active';
                } elseif ($val->status == 3) {
                    $val->status = 'In Active';
                } elseif ($val->status == 4) {
                    $val->status = 'Paused';
                } elseif ($val->status == 5) {
                    $val->status = 'Hold';
                } else {
                    $val->status = 'Suspend';
                }
                $oldData = $val->only([
                    'status'
                ]);
                $newData = $status;
                foreach ($oldData as $property => $value) {
                    if ($value != $newData[$property]) {
                        $campLogData[$property]['previous'] = $value;
                        $campLogData[$property]['updated'] = $newData[$property];
                    }
                }
                $campLogData['message'] = 'Admin has changed the status!';
                $camp_data = json_encode($campLogData);
                $camp_log = new CampaignLogs();
                $camp_log->uid = $val->advertiser_code;
                $camp_log->campaign_id = $val->campaign_id;
                $camp_log->campaign_type = $val->ad_type;
                $camp_log->campaign_data = $camp_data;
                $camp_log->action = 2;
                $camp_log->user_type = 2;
                $camp_log->save();
            }
            $Campaigns = Campaign::whereIn('campaign_id', $ids)->update(['status' => 1]);
            $count++;
        }
        /* This will bulk update Campaign into Redis */
        updateBulkCamps($ids, $actionType);
        if ($count > 0) {
            $return['code']    = 200;
            $return['data']    = $Campaigns;
            $return['message'] = 'Updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function adminDeletedCampaignList(Request $request)
    {
        $type = $request->type;
        $limit = $request->lim;
        $page = $request->page;
        $search = $request->src;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $campaign = DB::table('campaigns')->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, 
            (select IFNULL(sum(clicks),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as click ,
            (select IFNULL(sum(impressions),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id) as imprs") , 'campaigns.id', 'campaigns.campaign_name', 'campaigns.campaign_id', 'campaigns.advertiser_code', 'campaigns.website_category', 'campaigns.status', 'campaigns.ad_type', 'campaigns.campaign_type', 'campaigns.daily_budget', 'campaigns.created_at', 'campaigns.updated_at as deleted', 'campaigns.target_url', 'categories.cat_name', 'users.account_type')
            ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaigns.trash', 1);
        if ($search)
        {
            $campaign = $campaign->whereRaw('concat(ss_users.first_name," ",ss_users.last_name," ",ss_campaigns.campaign_id,ss_campaigns.campaign_name,ss_campaigns.advertiser_code) like ?', "%{$search}%");
        }
        if (strlen($type) > 0)
        {
            $campaign->where('campaigns.ad_type', $type);
        }
        $campaign->orderBy('campaigns.id', 'desc');
        $row = $campaign->count();
        $data = $campaign->offset($start)->limit($limit)->get();

        if ($campaign)
        {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row'] = $row;
            $return['message'] = 'Deleted Campaigns list retrieved successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function deleteCampaign(Request $request)
    {
        $camp_id = $request->cid;
        $camp = Campaign::where('campaign_id', $camp_id)->first();
        $campLogData = [];
        $trash = ['trash' => 'Deleted'];
        $oldData = $camp->only([
            'trash'
        ]);
        $newData = $trash;
        foreach ($oldData as $property => $value) {
            if ($value != $newData[$property]) {
                $campLogData[$property]['previous'] = '----';
                $campLogData[$property]['updated'] = 'Deleted';
            }
        }
        $campLogData['message'] = 'Admin has removed the campaign';
        $camp_data = json_encode($campLogData);

        $camp_log = new CampaignLogs();
        $camp_log->uid = $camp->advertiser_code;
        $camp_log->campaign_id = $camp_id;
        $camp_log->campaign_type = $camp->ad_type;
        $camp_log->campaign_data = $camp_data;
        $camp_log->action = 2;
        $camp_log->user_type = 2;
        $camp_log->save();
        $camp->trash = 1;
        if ($camp->update())
        {
            /* This will remove Campaign from Redis */
            updateCamps($camp_id, 0);
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Campaign';
            $activitylog->description = 'Campaign is deleted Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            $return['code'] = 200;
            $return['data'] = $camp;
            $return['message'] = 'Campaign deleted successfully';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function campaignUpdateStatus(Request $request)
    {
        $camp_id = $request->cid;
        $camp_status = $request->status;
        $camp = Campaign::where('campaign_id', $camp_id)->first();
        $campLogData = [];
        $oldData = $camp->only([
            'status'
        ]);

        $newData = $request->only([
            'status'
        ]);

        $old_status = '';
        $new_status = '';
        foreach ($oldData as $property => $value) {
            if ($value == 1) {
                $old_status = 'In Review';
            } elseif ($value == 2) {
                $old_status = 'Active';
            } elseif ($value == 3) {
                $old_status = 'In Active';
            } elseif ($value == 4) {
                $old_status = 'Paused';
            } elseif ($value == 5) {
                $old_status = 'Hold';
            } else {
                $old_status = 'Suspend';
            }
            if ($newData[$property] == 1) {
                $new_status = 'In Review';
            } elseif ($newData[$property] == 2) {
                $new_status = 'Active';
            } elseif ($newData[$property] == 3) {
                $new_status = 'In Active';
            } elseif ($newData[$property] == 4) {
                $new_status = 'Paused';
            } elseif ($newData[$property] == 5) {
                $new_status = 'Hold';
            } else {
                $new_status = 'Suspend';
            }
            if ($value != $newData[$property]) {
                $campLogData[$property]['previous'] = $old_status;
                $campLogData[$property]['updated'] = $new_status;
            }
        }
        $campLogData['message'] = 'Admin has changed the status!';
        $camp_data = json_encode($campLogData);

        $camp_log = new CampaignLogs();
        $camp_log->uid = $camp->advertiser_code;
        $camp_log->campaign_id = $camp_id;
        $camp_log->campaign_type = $camp->ad_type;
        $camp_log->campaign_data = $camp_data;
        $camp_log->action = 2;
        $camp_log->user_type = 2;
        $camp_log->save();

        $camp->status = $camp_status;

        if ($camp->update())
        {
            if ($camp_status == 0)
            {
                $campstatus = 'incomplete';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently incomplete';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' is currently incomplete. If you want to activate it please complete the detail of campaign..';
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                
            }
            elseif ($camp_status == 1)
            {
                $campstatus = 'inReview';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently in review';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' is currently in review. If you want to reactivate it please contact to support..';
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //    $return['code'] = 200;
                //   $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //   $return['code'] = 200;
                //   $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                
            }
            elseif ($camp_status == 2)
            {
                $campstatus = 'active';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently active';
                $noti_desc = 'Congratulation your campaign id #' . $camp_id . ' has been activated.';
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus  - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                /* Campaign Create Send Mail */
            }
            elseif ($camp_status == 3)
            {
                $campstatus = 'inActive';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently is inActive';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' is currently inActive. If you want to reactivate it please contact to support..';
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                
            }
            elseif ($camp_status == 4)
            {
                $campstatus = 'paused';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently paused';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' is currently paused. If you want to reactivate it, please contact our support team.';
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                
            }
            elseif ($camp_status == 5)
            {
                $campstatus = 'onHold';
                $noti_title = 'Your Campaign #' . $camp_id . ' is currently on hold';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' is currently on hold. If you want to reactivate it please contact to support..';
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                /* Campaign Create Send Mail */
            }
            elseif ($camp_status == 6)
            {
                $campstatus = 'suspended';
                $noti_title = 'Your Campaign #' . $camp_id . ' has been suspended';
                $noti_desc = 'Dear advertiser your campaign id #' . $camp_id . ' has been suspended due to some suspecious activity. If you want to reactivate it please contact to support..';
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $camp->advertiser_code)
                    ->first();
                $email = $user->email;
                $fullname = "$user->first_name $user->last_name";
                $useridas = $camp->advertiser_code;
                $campsid = $camp->campaign_id;
                $campsname = $camp->campaign_name;
                $campadtype = $camp->ad_type;
                $subjects = "Campaign is $campstatus - 7Search PPC";
                $data['details'] = array(
                    'subject' => $subjects,
                    'email' => $email,
                    'full_name' => $fullname,
                    'usersid' => $useridas,
                    'campaignid' => $campsid,
                    'campaignname' => $campsname,
                    'campaignadtype' => $campadtype,
                    'status' => $campstatus
                );
                $body = View('emailtemp.campaignstatus', $data);
                $sendmailUser = sendmailUser($subjects, $body, $email);
                // if($sendmailUser == '1')
                // {
                //     $return['code'] = 200;
                //    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                // }
                // else
                // {
                //    $return['code'] = 200;
                //    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                // }
                /* Campaign Create Send Mail */
            }
            else
            {
                $campstatus = 'N/A';
            }
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 1;
            //$notification->display_url = 'N/A';
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save())
            {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $camp->advertiser_code;
                $noti->user_type = 1;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = $campstatus;
            $activitylog->description = ' ' . $camp_id . ' Campaign is ' . $campstatus . ' Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            $return['code'] = 200;
            $return['data'] = $camp;
            $return['message'] = 'Updated Successfully';
        /* This will update campaigns into Redis */
        updateCamps($camp_id, $camp_status);
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function campaignAction(Request $request)
    {
        $cid = $request->cid;
        $uid = $request->uid;
        $type = $request->type;
        $count = 0;
        $trs = 0;

        foreach ($cid as $camp_id)
        {
            if ($type == 'active')
            {
                $sts = 2;
                $campaign = Campaign::where('campaign_id', $camp_id)->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $campaign->advertiser_code)
                    ->first();
                $email = $user->email;
                $useridas = $campaign->advertiser_code;
                $campsid = $campaign->campaign_id;
                $campsname = $campaign->campaign_name;
                $campadtype = $campaign->ad_type;
                $subjects = "Campaign Active Successfully";
                $mailsentdetals = ['subject' => $subjects, 'email' => $email, 'usersid' => $useridas, 'campaignid' => $campsid, 'campaignname' => $campsname, 'campaignadtype' => $campadtype, 'status' => $type];
                $hrmail = 'sharif.logilite@gmail.com';
                $mailTo = [$email, $hrmail];
                //  Mail::to($mailTo)->send(new StatusCampMail($mailsentdetals));
                /* Campaign Create Send Mail */
            }
            elseif ($type == 'pause')
            {
                $sts = 4;
                $campaign = Campaign::where('campaign_id', $camp_id)->where('status', 2)
                    ->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
            }
            elseif ($type == 'inreview')
            {
                $sts = 1;
                $campaign = Campaign::where('campaign_id', $camp_id)->where('status', 2)
                    ->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
            }
            elseif ($type == 'inactive')
            {
                $sts = 3;
                $campaign = Campaign::where('campaign_id', $camp_id)->where('status', 2)
                    ->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
            }
            elseif ($type == 'onhold')
            {
                $sts = 5;
                $campaign = Campaign::where('campaign_id', $camp_id)->where('status', 2)
                    ->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $campaign->advertiser_code)
                    ->first();
                $email = $user->email;
                $useridas = $campaign->advertiser_code;
                $campsid = $campaign->campaign_id;
                $campsname = $campaign->campaign_name;
                $campadtype = $campaign->ad_type;
                $subjects = "Campaign is Hold";
                $mailsentdetals = ['subject' => $subjects, 'email' => $email, 'usersid' => $useridas, 'campaignid' => $campsid, 'campaignname' => $campsname, 'campaignadtype' => $campadtype, 'status' => $type];
                $hrmail = 'abul.logilite@gmail.com';
                $mailTo = [$email, $hrmail];
                //   Mail::to($mailTo)->send(new StatusCampMail($mailsentdetals));
                /* Campaign Create Send Mail */
            }
            elseif ($type == 'suspend')
            {
                $sts = 6;
                $campaign = Campaign::where('campaign_id', $camp_id)->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
                /* Create Campaign Send Mail   */
                $user = User::where('uid', $campaign->advertiser_code)
                    ->first();
                $email = $user->email;
                $useridas = $campaign->advertiser_code;
                $campsid = $campaign->campaign_id;
                $campsname = $campaign->campaign_name;
                $campadtype = $campaign->ad_type;
                $subjects = "Campaign Is Suspended ";
                $mailsentdetals = ['subject' => $subjects, 'email' => $email, 'usersid' => $useridas, 'campaignid' => $campsid, 'campaignname' => $campsname, 'campaignadtype' => $campadtype, 'status' => $type];
                $hrmail = 'abul.logilite@gmail.com';
                $mailTo = [$email, $hrmail];
                //  Mail::to($mailTo)->send(new StatusCampMail($mailsentdetals));
                /* Campaign Create Send Mail */
            }

            if ($type == 'delete')
            {
                $trs = 1;
                $campaign = Campaign::where('campaign_id', $camp_id)->where('trash', 0)
                    ->first();
                $activitylog = new Activitylog();
                $activitylog->uid = 'Admin';
                $activitylog->type = $type;
                $activitylog->description = '' . $camp_id . ' Campaign is ' . $type . ' Successfully';
                $activitylog->status = '1';
                $activitylog->save();
            }
            else
            {
                $trs = 0;
            }

            if ($campaign)
            {
                if ($trs == 0)
                {
                    $campaign->status = $sts;
                }
                else
                {
                    $campaign->trash = 1;
                }
                $campaign->update();
                $count++;
            }
        }

        if ($count > 0)
        {
            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['rows'] = $count;
            $return['message'] = 'Campaign updated Successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function getcampid(Request $request)
    {
        $todaye = date('Y-m-d');
        $validator = Validator::make($request->all() , ['campaign_id' => 'required', ]);
        if ($validator->fails())
        {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $campid = $request->input('campaign_id');
        $campdata = Campaign::where('campaign_id', $campid)->first();
        if (empty($campdata))
        {
            $return['code'] = 101;
            $return['message'] = 'Campaign Not found !';
        }
        else
        {
            // $campaign = DB::table('campaigns')
            //     ->select(DB::raw("(select sum(amount) from ss_user_camp_click_logs camp_ck where camp_ck.campaign_id = ss_campaigns.campaign_id AND Date(created_at) = CURDATE()) as clickamt, (select sum(amount) from ss_ad_impressions ad_imp where ad_imp.campaign_id = ss_campaigns.campaign_id AND Date(created_at) = CURDATE()) as imprsamt"),'campaigns.campaign_id', 'campaigns.advertiser_code', 'campaigns.country_name', 'campaigns.countries', 'campaigns.daily_budget', 'campaigns.ad_type', 'categories.cpc', 'users.wallet', 'categories.cpm')
            //     ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
            //     ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            //     ->where('campaigns.campaign_id', $campid)
            //     ->where('users.status', 0)
            //     ->first(); cpc
            $campaign = DB::table('campaigns')->select(DB::raw("(select IFNULL(sum(amount),0) from ss_camp_budget_utilize camp_ck where camp_ck.camp_id = ss_campaigns.campaign_id AND Date(udate) = Date('" . $todaye . "')) as imprsamt") , 'campaigns.campaign_id', 'campaigns.advertiser_code', 'campaigns.country_name', 'campaigns.countries', 'campaigns.daily_budget', 'campaigns.ad_type', 'campaigns.cpc_amt', 'users.wallet', 'categories.cpm', 'categories.cpc', 'campaigns.pricing_model')->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                ->where('campaigns.campaign_id', $campid)->where('users.status', 0)
                ->first();
            if (!empty($campaign))
            {
                $spent_amt = getDailyBudget($campaign->advertiser_code, $campid);
                $campaign->imprsamt = $spent_amt;
                $return['code'] = 200;
                $return['data'] = $campaign;
                $return['message'] = 'Campaign successfully!';
            }
            else
            {
                $return['code'] = 101;
                $return['message'] = 'Something went wrong!';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function addclickimpnew(Request $request)
    {
        $validator = Validator::make($request->all() , ['campaign_id' => 'required', 'impressions' => 'required|numeric', 'click' => 'required|numeric', 'date' => 'required', 'click_amt' => 'required|numeric', 'impression_amt' => 'required|numeric', ]);

        if ($validator->fails())
        {
            $return['code'] = 101;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }

        $createCampDate = Campaign::select('id', 'created_at', 'daily_budget')->where('campaign_id', $request->campaign_id)
            ->where('status', '!=', 0)
            ->where('status', '!=', 1)
            ->where('status', '!=', 3)
            ->where('status', '!=', 5)
            ->where('status', '!=', 6)
            ->first();

        if (strtotime(date('Y-m-d', strtotime($createCampDate->created_at))) > strtotime($request->date))
        {
            $return['code'] = 101;
            $return['message'] = 'Unable to add clicks & impressions - Selected date is prior to campaign creation date!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        else if (strtotime(date('Y-m-d')) < strtotime($request->date))
        {
            $return['code'] = 101;
            $return['message'] = 'Cannot add clicks & impressions to a future date!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }

        $campid = $request->input('campaign_id');
        $impression = $request->input('impressions');
        $impressionamt = $request->input('impression_amt');
        $click = $request->input('click');
        $clickamt = $request->input('click_amt');
        $date = $request->input('date');

        $data = array(
            'campaign_id' => $campid,
            'impressions' => $impression,
            'impression_amt' => $impressionamt,
            'click' => $click,
            'click_amt' => $clickamt,
            'date' => $date
        );

        $campdata = Campaign::where('campaign_id', $campid)->first();

        if (empty($campdata))
        {
            $return['code'] = 101;
            $return['message'] = 'Campaign Not Found !';
        }
        else
        {
            $uid = $campdata['advertiser_code'];
            $userwallet = User::select('first_name', 'last_name', 'email', 'wallet')->where('uid', $uid)->first();

            if ($userwallet['wallet'] <= 0)
            {
                $email = $userwallet['email'];
                $fullname = $userwallet['first_name'] . ' ' . $userwallet['last_name'];
                $useridas = $uid;

                /* Send Notification to user Wallet is low */
                $notification = new Notification();
                $notification->notif_id = gennotificationuniq();
                $notification->title = 'Wallet Balance is low !';
                $notification->noti_desc = 'Dear Advertiser Your wallet balance is low. Please add fund into your wallet.';
                $notification->noti_type = 1;
                $notification->noti_for = 1;
                // $notification->display_url = 'N/A';
                $notification->all_users = 0;
                $notification->status = 1;
                if ($notification->save())
                {
                    $noti = new UserNotification();
                    $noti->notifuser_id = gennotificationuseruniq();
                    $noti->noti_id = $notification->id;
                    $noti->user_id = $useridas;
                    $noti->user_type = 1;
                    $noti->view = 0;
                    $noti->created_at = Carbon::now();
                    $noti->updated_at = now();
                    $noti->save();
                    /* Send mail to User Wallet is low */
                    $data['details'] = array(
                        'subject' => 'Dear Advertiser Wallet balance is low please add fund into your wallet  - 7Search PPC ',
                        'fullname' => $fullname,
                        'usersid' => $useridas
                    );
                    $subject = 'Dear Advertiser Wallet balance is low please add fund into your wallet - 7Search PPC';
                    $body = View('emailtemp.userwalletlow', $data);
                    /* User Mail Section */
                    sendmailUser($subject, $body, $email);

                    $return['code'] = 101;
                    $return['message'] = 'Please add fund into your wallet!';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                }
            }

            $totaluserwallet = $userwallet['wallet'];
            $camdailybudget = $campdata['daily_budget'];

            $getdevicetype = explode(',', $campdata['device_type']);
            $getdeviceos = explode(',', $campdata['device_os']);
            $ad_type = $campdata['ad_type'];
            $countryids = $campdata['country_ids'];
            $countresid = explode(',', $countryids);

            if ($click > $impression)
            {
                $return['code'] = 101;
                $return['message'] = 'Impression should be greater than clicks!';
                return json_encode($return, JSON_NUMERIC_CHECK);
            }

            $totalbudgetcimp = ($impression * $impressionamt) + ($click * $clickamt);

            // $impsum = DB::table('ad_impressions')->where('campaign_id', $campid)->where('advertiser_code', $uid)->whereDate('created_at', $date)->sum('amount');
            // $clicksum = DB::table('user_camp_click_logs')->where('campaign_id', $campid)->where('advertiser_code', $uid)->whereDate('created_at', $date)->sum('amount');
            // $totimpclickd = $impsum + $clicksum;
            $totimpclickd = DB::table('camp_budget_utilize')->where('camp_id', $campid)->where('advertiser_code', $uid)->whereDate('udate', $date)->sum('amount');

            $mainbalce = $camdailybudget - $totimpclickd;

            $camdailybudget = ($mainbalce > $userwallet['wallet']) ? $userwallet['wallet'] : $mainbalce;
            $email = $userwallet["email"];
            $fullname = $userwallet["first_name"] . " " . $userwallet["last_name"];
            if (round($mainbalce, 2) <= 0.5) {
                self::sendMailEndBudget($email, $fullname, $uid, $campid);
            }
            if ($totalbudgetcimp <= $camdailybudget)
            {

                $deductamt = $totalbudgetcimp;
                $impclickinsrt = array(
                    'Impression' => $impression,
                    'Click' => $click
                );

                $countrie = $campdata['countries'];
                if ($countrie == 'All')
                {
                    $getisodata = DB::table('countries')->select('iso')
                        ->get()
                        ->toArray();
                }
                else
                {
                    $getisodata = DB::table('countries')->select('iso')
                        ->whereIn('id', $countresid)->get()
                        ->toArray();
                }
                $countrycode = array_column($getisodata, 'iso');

                $mainsum = $totimpclickd + $totalbudgetcimp;

                $netdeductamtsf = $totaluserwallet - $deductamt;
                $netdeductamt = ($netdeductamtsf > 0) ? round($netdeductamtsf, 2) : 0;

                $ipcountrow = CountriesIps::select('ip_addr', 'country_name')->whereIn('country_code', $countrycode)->orderByRaw('RAND()')
                    ->limit($impression)->get();
                $countrow = $ipcountrow->count();

                /* Insert data Impression */
                foreach ($ipcountrow as $vlaueimp)
                {
                    shuffle($getdevicetype);
                    shuffle($getdeviceos);
                    $time = rand(0, time());
                    $sss = date("H:i:s", $time);
                    $newdateinst = $date . ' ' . $sss;
                    $ucountry = strtoupper($vlaueimp['country_name']);
                    $adimp = new AdImpression();
                    $adimp->impression_id = 'IMP' . strtoupper(uniqid());
                    $adimp->campaign_id = $campid;
                    $adimp->advertiser_code = $uid;
                    $adimp->device_type = $getdevicetype[0];
                    $adimp->uni_imp_id = md5($uid . $campid . $getdeviceos[0] . $getdevicetype[0] . $ucountry . date('Ymd', strtotime($date)));
                    $adimp->uni_bd_id = md5($uid . $campid . date('Ymd', strtotime($date)));
                    $adimp->device_os = $getdeviceos[0];
                    $adimp->ip_addr = $vlaueimp['ip_addr'];
                    $adimp->country = strtoupper($vlaueimp['country_name']);
                    $adimp->ad_type = $ad_type;
                    $adimp->created_at = $newdateinst;
                    $adimp->updated_at = $newdateinst;
                    $adimp->amount = $impressionamt;
                    $adimp->save();
                }

                $clicklogi = CountriesIps::select('ip_addr', 'country_name')->whereIn('country_code', $countrycode)->orderByRaw('RAND()')
                    ->limit($click)->get();

                foreach ($clicklogi as $clickvlaue)
                {
                    shuffle($getdevicetype);
                    shuffle($getdeviceos);
                    $time = rand(0, time());
                    $sss = date("H:i:s", $time);
                    $newdateinst = $date . ' ' . $sss;
                    $ucountry = strtoupper($clickvlaue['country_name']);
                    $campclick = new UserCampClickLog();
                    $campclick->campaign_id = $campid;;
                    $campclick->advertiser_code = $uid;
                    $campclick->uni_imp_id = md5($uid . $campid . $getdeviceos[0] . $getdevicetype[0] . $ucountry . date('Ymd', strtotime($date)));
                    $campclick->uni_bd_id = md5($uid . $campid . date('Ymd', strtotime($date)));
                    $campclick->device_type = $getdevicetype[0];
                    $campclick->device_os = $getdeviceos[0];
                    $campclick->ad_type = $ad_type;
                    $campclick->country = strtoupper($clickvlaue['country_name']);
                    $campclick->ip_address = $clickvlaue['ip_addr'];
                    $campclick->amount = $clickamt;
                    $campclick->created_at = $newdateinst;
                    $campclick->updated_at = $newdateinst;
                    $campclick->save();
                }
                $userdata = DB::table('users')->where('uid', $uid)->update(array(
                    'wallet' => $netdeductamt
                ));

                if ($netdeductamt <= 15)
                {
                    $email = $userwallet['email'];
                    $fullname = $userwallet['first_name'] . ' ' . $userwallet['last_name'];
                    $useridas = $uid;
                    /* Send Notification to user Wallet is low */
                    $notification = new Notification();
                    $notification->notif_id = gennotificationuniq();
                    $notification->title = 'Wallet Balance is low !';
                    $notification->noti_desc = 'Dear Advertiser Your wallet balance is low. Please add fund into your wallet.';
                    $notification->noti_type = 1;
                    $notification->noti_for = 1;
                    // $notification->display_url = 'N/A';
                    $notification->all_users = 0;
                    $notification->status = 1;
                    if ($notification->save())
                    {
                        $noti = new UserNotification();
                        $noti->notifuser_id = gennotificationuseruniq();
                        $noti->noti_id = $notification->id;
                        $noti->user_id = $useridas;
                        $noti->user_type = 1;
                        $noti->view = 0;
                        $noti->created_at = Carbon::now();
                        $noti->updated_at = now();
                        $noti->save();
                        /* Send mail to User Wallet is low */
                        $data['details'] = array(
                            'subject' => 'Dear Advertiser Wallet balance is low please add fund into your wallet  - 7Search PPC ',
                            'fullname' => $fullname,
                            'usersid' => $useridas
                        );
                        $subject = 'Dear Advertiser Wallet balance is low please add fund into your wallet - 7Search PPC';
                        $data["email"] = $email;
                        $data["title"] = $subject;
                        $body = View('emailtemp.userwalletlow', $data);
                        /* User Mail Section */
                        sendmailUser($subject, $body, $email);
                    }
                }
                $return['data'] = $userdata;
                $return['code'] = 200;
                $return['message'] = 'Click & Impression Added Successfully';
            }
            else
            {
                listNotificationMassages($campdata->campaign_id, $campdata->advertiser_code, $campdata->daily_budget);
                $return['code'] = 101;
                $return['message'] = 'Daily budget limit exceeded or Wallet Balance is less than your entry!';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function addclickimp(Request $request)
    {
        $redisCon = Redis::connection('default');
        $validator = Validator::make($request->all() , ['campaign_id' => 'required', 'impressions' => 'required|numeric', 'click' => 'required|numeric', 'date' => 'required', 'click_amt' => 'required|numeric', 'impression_amt' => 'required|numeric', ]);

        if ($validator->fails())
        {
            $return['code'] = 101;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        
        
        $createCampDate = Campaign::where('campaign_id', $request->campaign_id)
            ->where('status', '!=', 0)
            ->where('status', '!=', 1)
            ->where('status', '!=', 3)
            ->where('status', '!=', 5)
            ->where('status', '!=', 6)
            ->first();
        if (strtotime(date('Y-m-d', strtotime($createCampDate->created_at))) > strtotime($request->date))
        {
            $return['code'] = 101;
            $return['message'] = 'Unable to add clicks & impressions - Selected date is prior to campaign creation date!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        else if (strtotime(date('Y-m-d')) < strtotime($request->date))
        {
            $return['code'] = 101;
            $return['message'] = 'Cannot add clicks & impressions to a future date!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
        $campid = $request->input('campaign_id');
        $impression = $request->input('impressions');
        $impressionamt = $request->input('impression_amt');
        $click = $request->input('click');
        $clickamt = $request->input('click_amt');
        $date = $request->input('date');
        $data = array(
            'campaign_id' => $campid,
            'impressions' => $impression,
            'impression_amt' => $impressionamt,
            'click' => $click,
            'click_amt' => $clickamt,
            'date' => $date
        );
        $campdata = Campaign::where('campaign_id', $campid)->first();
        if (empty($campdata))
        {
            $return['code'] = 101;
            $return['message'] = 'Campaign Not Found !';
        }
        else
        {
            $uid = $campdata['advertiser_code'];
            $userwallet = User::select('first_name', 'last_name', 'email', 'wallet')->where('uid', $uid)->first();
            $uwallt = $redisCon->rawCommand('hget', 'adv_wallet', $uid);
            // if ($userwallet['wallet'] <= 0)
            if ($uwallt <= 0)
            {
                $email = $userwallet['email'];
                $fullname = $userwallet['first_name'] . ' ' . $userwallet['last_name'];
                $useridas = $uid;
                /* Send Notification to user Wallet is low */
                $notification = new Notification();
                $notification->notif_id = gennotificationuniq();
                $notification->title = 'Wallet Balance is low !';
                $notification->noti_desc = 'Dear Advertiser Your wallet balance is low. Please add fund into your wallet.';
                $notification->noti_type = 1;
                $notification->noti_for = 1;
                // $notification->display_url = 'N/A';
                $notification->all_users = 0;
                $notification->status = 1;
                if ($notification->save())
                {
                    $noti = new UserNotification();
                    $noti->notifuser_id = gennotificationuseruniq();
                    $noti->noti_id = $notification->id;
                    $noti->user_id = $useridas;
                    $noti->user_type = 1;
                    $noti->view = 0;
                    $noti->created_at = Carbon::now();
                    $noti->updated_at = now();
                    $noti->save();
                    /* Send mail to User Wallet is low */
                    $data['details'] = array(
                        'subject' => 'Dear Advertiser Wallet balance is low please add fund into your wallet  - 7Search PPC ',
                        'fullname' => $fullname,
                        'usersid' => $useridas
                    );
                    $subject = 'Dear Advertiser Wallet balance is low please add fund into your wallet - 7Search PPC';
                    $body = View('emailtemp.userwalletlow', $data);
                    /* User Mail Section */
                    sendmailUser($subject, $body, $email);

                    $return['code'] = 101;
                    $return['message'] = 'Please add fund into your wallet!';
                    return json_encode($return, JSON_NUMERIC_CHECK);
                }
            }

            $totaluserwallet = $userwallet['wallet'];
            $camdailybudget = $campdata['daily_budget'];

            $getdevicetype = explode(',', $campdata['device_type']);
            $getdeviceos = explode(',', $campdata['device_os']);
            $ad_type = $campdata['ad_type'];
            $countryids = $campdata['country_ids'];
            $countresid = explode(',', $countryids);

            if ($click > $impression)
            {
                $return['code'] = 101;
                $return['message'] = 'Impression should be greater than clicks!';
                return json_encode($return, JSON_NUMERIC_CHECK);
            }

            $totalbudgetcimp = ($impression * $impressionamt) + ($click * $clickamt);

            // $impsum = DB::table('ad_impressions')->where('campaign_id', $campid)->where('advertiser_code', $uid)->whereDate('created_at', $date)->sum('amount');
            // $clicksum = DB::table('user_camp_click_logs')->where('campaign_id', $campid)->where('advertiser_code', $uid)->whereDate('created_at', $date)->sum('amount');
            // $totimpclickd = $impsum + $clicksum;
            // $totimpclickd = DB::table('camp_budget_utilize')->where('camp_id', $campid)->where('advertiser_code', $uid)->whereDate('udate', $date)->sum('amount');
            $totimpclickd = getDailyBudgetAdmin($uid, $campid, $date);
            $mainbalce = $camdailybudget - $totimpclickd;
            $camdailybudget = ($mainbalce > $uwallt) ? $uwallt : $mainbalce;
            $email = $userwallet["email"];
            $fullname = $userwallet["first_name"] . " " . $userwallet["last_name"];
            if (round($mainbalce, 2) <= 0.5) {
                self::sendMailEndBudget($email, $fullname, $uid, $campid);
            }

            if ($totalbudgetcimp <= $camdailybudget)
            {
                $deductamt = $totalbudgetcimp;
                $impclickinsrt = array(
                    'Impression' => $impression,
                    'Click' => $click
                );
                $countrie = $campdata['countries'];
                if ($countrie == 'All')
                {
                    $getisodata = DB::table('countries')->select('iso')
                        ->get()
                        ->toArray();
                }
                else
                {
                    $getisodata = DB::table('countries')->select('iso')
                        ->whereIn('id', $countresid)->get()
                        ->toArray();
                }
                $countrycode = array_column($getisodata, 'iso');

                $mainsum = $totimpclickd + $totalbudgetcimp;

                $netdeductamtsf = $totaluserwallet - $deductamt;
                $netdeductamt = ($netdeductamtsf > 0) ? round($netdeductamtsf, 2) : 0;

                $result = getImpClickData($impression, $click, explode(",", $createCampDate->device_os) , explode(",", $createCampDate->device_type));
                foreach ($result as $key => $value)
                {
                    foreach ($value as $val)
                    {
                        $ipcountrow = CountriesIps::select('ip_addr', 'country_name')->whereIn('country_code', $countrycode)->orderByRaw('RAND()')
                            ->limit($val['imp'])->get()
                            ->toArray();
                        $cip = array_column($ipcountrow, 'ip_addr');
                        $ccountry = array_column($ipcountrow, 'country_name');
                        for ($i = 0;$i <= ($val['imp'] - 1);$i++)
                        {
                            self::addMultipleDataImpAndClkInsert($date, $cip[$i], $ccountry[$i], $campid, $uid, $ad_type, $impressionamt, $val['device'], $val['os'], $campdata->daily_budget);
                            /* This will add or update impressions into Adv stats set into Redis */
                            // advStatsUpdate($impressionamt, 0, md5($uid . $campid . $val['os'] . $val['device'] . strtoupper($ccountry[$i]) . date('Ymd', strtotime($date))), $uid, $campid, $val['os'], $val['device'], $ccountry[$i], $date, 0);
                            /* This will add or update impressions into budget utilize set into Redis */
                            self::utilizeSetUpdate($uid, $campid, $impressionamt,  $date, 0);
                        }
                    }
                }
                foreach ($result as $key => $value)
                {
                    foreach ($value as $val)
                    {
                        $ipcountrow = CountriesIps::select('ip_addr', 'country_name')->whereIn('country_code', $countrycode)->orderByRaw('RAND()')
                            ->limit($val['clk'])->get()
                            ->toArray();
                        $cip = array_column($ipcountrow, 'ip_addr');
                        $ccountry = array_column($ipcountrow, 'country_name');
                        for ($i = 0;$i <= ($val['clk'] - 1);$i++)
                        {
                            self::addMultipleDataImpAndClkInsertClick($date, $cip[$i], $ccountry[$i], $campid, $uid, $ad_type, $clickamt, $val['device'], $val['os']);
                            /* This will add or update clicks into Adv stats set into Redis */
                            // advStatsUpdate(0, $clickamt, md5($uid . $campid . $val['os'] . $val['device'] . strtoupper($ccountry[$i]) . date('Ymd', strtotime($date))), $uid, $campid, $val['os'], $val['device'], $ccountry[$i], $date, 1);
                            /* This will add or update clicks into budget utilize set into Redis */
                            self::utilizeSetUpdate($uid, $campid, $clickamt,  $date, 1);
                        }
                    }
                }
                $userdata = DB::table('users')->where('uid', $uid)->update(array(
                    'wallet' => $netdeductamt
                ));
                $redisCon->rawCommand('hincrbyfloat', 'adv_wallet',  $uid, '-' . $deductamt);
                if ($netdeductamt <= 15)
                {
                    $email = $userwallet['email'];
                    $fullname = $userwallet['first_name'] . ' ' . $userwallet['last_name'];
                    $useridas = $uid;
                    /* Send Notification to user Wallet is low */
                    $notification = new Notification();
                    $notification->notif_id = gennotificationuniq();
                    $notification->title = 'Wallet Balance is low !';
                    $notification->noti_desc = 'Dear Advertiser Your wallet balance is low. Please add fund into your wallet.';
                    $notification->noti_type = 1;
                    $notification->noti_for = 1;
                    // $notification->display_url = 'N/A';
                    $notification->all_users = 0;
                    $notification->status = 1;
                    if ($notification->save())
                    {
                        $noti = new UserNotification();
                        $noti->notifuser_id = gennotificationuseruniq();
                        $noti->noti_id = $notification->id;
                        $noti->user_id = $useridas;
                        $noti->user_type = 1;
                        $noti->view = 0;
                        $noti->created_at = Carbon::now();
                        $noti->updated_at = now();
                        $noti->save();
                        /* Send mail to User Wallet is low */
                        $data['details'] = array(
                            'subject' => 'Dear Advertiser Wallet balance is low please add fund into your wallet  - 7Search PPC ',
                            'fullname' => $fullname,
                            'usersid' => $useridas
                        );
                        $subject = 'Dear Advertiser Wallet balance is low please add fund into your wallet - 7Search PPC';
                        $data["email"] = $email;
                        $data["title"] = $subject;
                        $body = View('emailtemp.userwalletlow', $data);
                        /* User Mail Section */
                        sendmailUser($subject, $body, $email);
                    }
                }
                self::impressionAndClickLog($request->all());
                $return['data'] = $userdata;
                $return['code'] = 200;
                $return['message'] = 'Click & Impression Added Successfully';
            }
            else
            {
                listNotificationMassages($campdata->campaign_id, $campdata->advertiser_code, $campdata->daily_budget);
                $return['code'] = 101;
                $return['message'] = 'Daily budget limit exceeded or Wallet Balance is less than your entry!';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /* This will add or update clicks/impressions utilize data into Redis */
    static function utilizeSetUpdate($uid, $campid, $adv_cpm, $date, $click){
        $redisCon = Redis::connection('default');
        $uni_bd_id = md5($uid . $campid . date('Ymd', strtotime($date)));
        $utilize_data = json_decode($redisCon->rawCommand('hget', 'budget_utilize', $uid), true);
        $utilize_data = !empty($utilize_data) ? $utilize_data : [];
          $utilize_data2 = array_reduce($utilize_data, function ($carry, $item) {
            $carry[$item['camp_id']][] = $item;
            return $carry;
          }, []);
            if (array_key_exists($campid, $utilize_data2)) {
                foreach ($utilize_data2[$campid] as $val) {
                  if ($val['udate'] == $date) {
                    $camp = $val;
                  }
                }
            } else {
               $camp = [];
            }
              
          if (!empty($camp)) {
              if($click > 0){
                $camp['clicks'] = $camp['clicks'] + 1;
                $camp['click_amount'] = $camp['click_amount'] + $adv_cpm;
              }else{
                $camp['impressions'] = $camp['impressions'] + 1;
                $camp['imp_amount'] = $camp['imp_amount'] + $adv_cpm;
              }
            $camp['amount'] = $camp['amount'] + $adv_cpm;
        
            foreach ($utilize_data as $row) {
              if ($row['uni_bd_id'] != $uni_bd_id) {
                $data4[] = $row;
              } else {
                $data4[] = $camp;
              }
            }
    
            $data_utilize = $data4;
          } else {
            $data3 = [
              "uni_bd_id" => $uni_bd_id,
              "advertiser_code" => $uid,
              "camp_id" => $campid,
              "impressions" => 1,
              "clicks" => 0,
              "imp_amount" => $adv_cpm,
              "click_amount" => 0,
              "amount" => $adv_cpm,
              "udate" => $date,
            ];
        
            $data_utilize[] = $data3;
          }
        $redisCon->rawCommand('hset', 'budget_utilize', $uid, json_encode($data_utilize));
    }

    static function addMultipleDataImpAndClkInsert($date, $cip, $ccountry, $campid, $uid, $ad_type, $impressionamt, $device_type, $device_os, $daily_budget)
    {
        listNotificationMassages($campid, $uid, $daily_budget);
        $time = rand(0, time());
        $sss = date("H:i:s", $time);
        $newdateinst = $date . ' ' . $sss;
        $ucountry = strtoupper($ccountry);
        $adimp = new AdImpression();
        $adimp->impression_id = 'IMP' . strtoupper(uniqid());
        $adimp->campaign_id = $campid;
        $adimp->advertiser_code = $uid;
        $adimp->device_type = $device_type;
        $adimp->uni_imp_id = md5($uid . $campid . $device_os . $device_type . $ucountry . date('Ymd', strtotime($date)));
        $adimp->uni_bd_id = md5($uid . $campid . date('Ymd', strtotime($date)));
        $adimp->device_os = $device_os;
        $adimp->ip_addr = $cip;
        $adimp->country = $ucountry;
        $adimp->ad_type = $ad_type;
        $adimp->created_at = $newdateinst;
        $adimp->updated_at = $newdateinst;
        $adimp->amount = $impressionamt;
        $adimp->save();
    }
    static function addMultipleDataImpAndClkInsertClick($date, $cip, $ccountry, $campid, $uid, $ad_type, $clickamt, $device_type, $device_os)
    {
        $time = rand(0, time());
        $sss = date("H:i:s", $time);
        $newdateinst = $date . ' ' . $sss;
        $ucountry = strtoupper($ccountry);
        $campclick = new UserCampClickLog();
        $campclick->campaign_id = $campid;;
        $campclick->advertiser_code = $uid;
        $campclick->uni_imp_id = md5($uid . $campid . $device_os . $device_type . $ucountry . date('Ymd', strtotime($date)));
        $campclick->uni_bd_id = md5($uid . $campid . date('Ymd', strtotime($date)));
        $campclick->device_type = $device_type;
        $campclick->device_os = $device_os;
        $campclick->ad_type = $ad_type;
        $campclick->country = $ucountry;
        $campclick->ip_address = $cip;
        $campclick->amount = $clickamt;
        $campclick->created_at = $newdateinst;
        $campclick->updated_at = $newdateinst;
        $campclick->save();
    }

    public function campaignDetail(Request $request)
    {
        $cid = $request->campaign_id;
        // $advertiser_code = $request->uid;
        $campaign = DB::table('campaigns')->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, SUM(ss_adv_stats.impressions) as imprs, SUM(ss_adv_stats.clicks) as click") , 'campaigns.id', 'campaigns.campaign_name', 'campaigns.campaign_id', 'campaigns.device_type', 'campaigns.device_os', 'campaigns.ad_title', 'campaigns.ad_description', 'campaigns.advertiser_code', 'campaigns.website_category', 'campaigns.status', 'campaigns.ad_type', 'campaigns.daily_budget', 'campaigns.country_ids', 'campaigns.pricing_model', 'campaigns.cpc_amt', 'campaigns.country_name', 'campaigns.countries', 'campaigns.social_ad_type', 'campaigns.created_at', 'campaigns.target_url', 'campaigns.conversion_url', 'categories.cat_name', 'campaigns.campaign_type')
            ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
            ->leftJoin('adv_stats', 'campaigns.campaign_id', '=', 'adv_stats.camp_id')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaigns.trash', 0)
            ->where('campaigns.campaign_id', $cid)->first();

        if ($campaign)
        {
            if ($campaign->ad_type == 'text')
            {

                $return['data'] = $campaign;
            }
            elseif ($campaign->ad_type == 'banner')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $img['image_type']] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'social')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $i] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'native')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $i] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'popup')
            {
                $return['data'] = $campaign;
            }
            $return['code'] = 200;
            $return['message'] = 'Campaign data retrieved!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function adminUpdateText(Request $request)
    {
        $cid = $request->cid;
        $campaign = Campaign::where('campaign_id', $cid)->first();
        $campaign->campaign_name = $request->campaign_name;
        $campaign->ad_title = $request->ad_title;
        $campaign->ad_description = $request->ad_description;
        $campaign->conversion_url = $request->conversion_url;
        $campaign->target_url = $request->target_url;
        if ($campaign->update())
        {
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Edit Campaign';
            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['message'] = 'Campaign updated successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function showAdAdmin(Request $request)
    {
        $cid = $request->cid;
        // $advertiser_code = $request->uid;
        $campaign = DB::table('campaigns')->select(DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as name, SUM(ss_adv_stats.impressions) as imprs, SUM(ss_adv_stats.clicks) as camp_ck") , 'campaigns.id', 'campaigns.campaign_name', 'campaigns.campaign_id', 'campaigns.device_type', 'campaigns.device_os', 'campaigns.ad_title', 'campaigns.ad_description', 'campaigns.advertiser_code', 'campaigns.website_category', 'campaigns.status', 'campaigns.ad_type', 'campaigns.daily_budget', 'campaigns.country_ids', 'campaigns.pricing_model', 'campaigns.cpc_amt', 'campaigns.country_name', 'campaigns.countries', 'campaigns.social_ad_type', 'campaigns.created_at', 'campaigns.target_url', 'campaigns.conversion_url', 'categories.cat_name')
            ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
            ->join('adv_stats', 'campaigns.campaign_id', '=', 'adv_stats.camp_id')
            ->join('categories', 'campaigns.website_category', '=', 'categories.id')
            ->where('campaigns.trash', 0)
            ->where('campaigns.campaign_id', $cid)
        //->where('campaigns.advertiser_code', $advertiser_code)
        ->first();
        if ($campaign)
        {
            if ($campaign->ad_type == 'text')
            {

                $return['data'] = $campaign;
            }
            elseif ($campaign->ad_type == 'banner')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $i] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'social')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $i] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'native')
            {
                $images = AdBannerImage::select('image_type', 'image_path')->where('campaign_id', $campaign->campaign_id)
                    ->get();

                $return['data'] = $campaign;
                $i = 0;
                foreach ($images as $img)
                {
                    $i++;
                    $return['images']['ad' . $i] = $img['image_path'];
                }
            }
            elseif ($campaign->ad_type == 'popup')
            {
                $return['data'] = $campaign;
            }

            $return['code'] = 200;
            $return['message'] = 'Campaign data retrieved!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updateBanner(Request $request)
    {
        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'countries' => 'required', 'pricing_model' => 'required',

        ]);

        if ($validator->fails())
        {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $cid = $request->cid;
        $campaign = Campaign::where('campaign_id', $cid)->first();

        $campaign->countries = $request->countries;
        if ($request->countries != 'All')
        {
            $targetCountries = json_decode($request->countries);
            $countryId = implode(',', array_column($targetCountries, 'value'));
            $countryName = implode(',', array_column($targetCountries, 'label'));
            $campaign->country_name = $countryName;
            $campaign->country_ids = $countryId;
        }

        $campaign->campaign_name = $request->campaign_name;
        $campaign->device_type = $request->device_type;
        $campaign->device_os = $request->device_os;
        $campaign->ad_title = $request->ad_title;
        $campaign->ad_description = $request->ad_description;
        $campaign->target_url = $request->target_url;
        $campaign->conversion_url = $request->conversion_url;
        $campaign->website_category = $request->website_category;
        $campaign->daily_budget = $request->daily_budget;
        $campaign->pricing_model = $request->pricing_model;
        if ($request->pricing_model == 'CPM')
        {
            $cat = Category::where('id', $request->website_category)
                ->first();
            $cpm = $cat->cpm;
            $campaign->cpc_amt = $cpm;
        }
        else
        {
            $campaign->cpc_amt = $request->cpc_amt;
        }
        $campaign->status = 1;
        $images = $request->images;
        if ($campaign->update())
        {
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Edit Campaign';
            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            if ($images)
            {
                foreach ($images as $image)
                {
                    $img = AdBannerImage::where([['campaign_id', '=', $campaign->campaign_id], ['advertiser_code', '=', $campaign->advertiser_code], ['image_type', '=', $image['type']], ])->update(['image_path' => $image['img']]);
                }
            }
            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['message'] = 'Campaign detail updated successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function onchangecpc(Request $request)
    {
        $typename = $request->type;
        $catname = $request->cat_name;
        if ($typename == 'CPC')
        {
            $catid = Category::where('cat_name', $catname)->where('status', 1)
                ->first();
            if (empty($catid))
            {
                $return['code'] = 101;
                $return['message'] = 'Not Found Category Name!';
                return json_encode($return);
            }
            $cpcamt = $catid->cpc;
            $cpcid = $catid->id;
            $query = DB::table('campaigns')->select('cpc_amt')
                ->whereRaw('cpc_amt in (select max(cpc_amt) from ss_campaigns group by (website_category))')
                ->where('website_category', $cpcid)->where('status', 2)
                ->where('pricing_model', $typename)->first();
            if (empty($query))
            {
                $return['code'] = 200;
                $return['base_amt'] = $cpcamt;
                $return['high_amt'] = $cpcamt;
                $return['message'] = 'Successfully found !';
                return json_encode($return);
            }
            else
            {
                $campcpcamt = $query->cpc_amt;
                $return['code'] = 200;
                $return['base_amt'] = $cpcamt;
                $return['high_amt'] = $campcpcamt;
                $return['message'] = 'Successfully found !';
            }
            return json_encode($return);
        }
        elseif ($typename == 'CPM')
        {
            $catid = Category::where('cat_name', $catname)->where('status', 1)
                ->first();
            if (empty($catid))
            {
                $return['code'] = 101;
                $return['message'] = 'Not Found Category Name!';
                return json_encode($return);
            }
            $cpmamt = $catid->cpm;
            $cpcid = $catid->id;
            $query = DB::table('campaigns')->select('cpc_amt')
                ->whereRaw('cpc_amt in (select max(cpc_amt) from ss_campaigns group by (website_category))')
                ->where('website_category', $cpcid)->where('status', 2)
                ->where('pricing_model', $typename)->first();
            if (empty($query))
            {
                $return['code'] = 200;
                $return['base_amt'] = $cpmamt;
                $return['high_amt'] = $cpmamt;
                $return['message'] = 'Successfully found !';
                return json_encode($return);
            }
            else
            {
                $campcpcamt = $query->cpc_amt;
                $return['code'] = 200;
                $return['base_amt'] = $cpmamt;
                $return['high_amt'] = $cpmamt;
                $return['message'] = 'Successfully found !';
            }
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Invalid Format';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function updateSocial(Request $request)
    {
        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'website_category' => 'required', 'device_type' => 'required', 'social_ad_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'countries' => 'required', 'pricing_model' => 'required',

        ]);

        if ($validator->fails())
        {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $cid = $request->cid;
        $campaign = Campaign::where('campaign_id', $cid)->first();

        $campaign->countries = $request->countries;
        if ($request->countries != 'All')
        {
            $targetCountries = json_decode($request->countries);
            $countryId = implode(',', array_column($targetCountries, 'value'));
            $countryName = implode(',', array_column($targetCountries, 'label'));
            $campaign->country_name = $countryName;
            $campaign->country_ids = $countryId;
        }

        $campaign->campaign_name = $request->campaign_name;
        $campaign->device_type = $request->device_type;
        $campaign->device_os = $request->device_os;
        $campaign->ad_title = $request->ad_title;
        $campaign->ad_description = $request->ad_description;
        $campaign->target_url = $request->target_url;
        $campaign->conversion_url = $request->conversion_url;
        $campaign->website_category = $request->website_category;
        $campaign->daily_budget = $request->daily_budget;
        $campaign->social_ad_type = $request->social_ad_type;
        $campaign->pricing_model = $request->pricing_model;
        if ($request->pricing_model == 'CPM')
        {
            $cat = Category::where('id', $request->website_category)
                ->first();
            $cpm = $cat->cpm;
            $campaign->cpc_amt = $cpm;
        }
        else
        {
            $campaign->cpc_amt = $request->cpc_amt;
        }
        $campaign->status = 1;

        $images = $request->images;

        if ($campaign->update())
        {
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Edit Campaign';
            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            if ($images)
            {
                foreach ($images as $image)
                {
                    $img = AdBannerImage::where([['campaign_id', '=', $campaign->campaign_id], ['advertiser_code', '=', $campaign->advertiser_code], ['image_type', '=', $image['type']], ])->update(['image_path' => $image['img']]);
                }
            }
            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['message'] = 'Campaign detail updated successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updateNative(Request $request)
    {
        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'website_category' => 'required', 'device_type' => 'required', 'device_os' => 'required', 'target_url' => 'required', 'countries' => 'required', 'pricing_model' => 'required',

        ]);

        if ($validator->fails())
        {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $cid = $request->cid;
        $campaign = Campaign::where('campaign_id', $cid)->first();

        $campaign->countries = $request->countries;
        if ($request->countries != 'All')
        {
            $targetCountries = json_decode($request->countries);
            $countryId = implode(',', array_column($targetCountries, 'value'));
            $countryName = implode(',', array_column($targetCountries, 'label'));
            $campaign->country_name = $countryName;
            $campaign->country_ids = $countryId;
        }

        $campaign->campaign_name = $request->campaign_name;
        $campaign->device_type = $request->device_type;
        $campaign->device_os = $request->device_os;
        $campaign->ad_title = $request->ad_title;
        // $campaign->ad_description   = $request->ad_description;
        $campaign->target_url = $request->target_url;
        $campaign->conversion_url = $request->conversion_url;
        $campaign->website_category = $request->website_category;
        $campaign->daily_budget = $request->daily_budget;
        $campaign->pricing_model = $request->pricing_model;
        if ($request->pricing_model == 'CPM')
        {
            $cat = Category::where('id', $request->website_category)
                ->first();
            $cpm = $cat->cpm;
            $campaign->cpc_amt = $cpm;
        }
        else
        {
            $campaign->cpc_amt = $request->cpc_amt;
        }
        $campaign->status = 1;

        $images = $request->images;

        if ($campaign->update())
        {
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Edit Campaign';
            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            if ($images)
            {
                foreach ($images as $image)
                {
                    $img = AdBannerImage::where([['campaign_id', '=', $campaign->campaign_id], ['advertiser_code', '=', $campaign->advertiser_code], ['image_type', '=', $image['type']], ])->update(['image_path' => $image['img']]);
                }
            }

            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['message'] = 'Campaign detail updated successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function updatePopUnder(Request $request)
    {
        $validator = Validator::make($request->all() , ['campaign_name' => 'required', 'device_type' => 'required', 'website_category' => 'required', 'daily_budget' => 'required', 'pricing_model' => 'required', ]);

        if ($validator->fails())
        {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation  Error!';
            return json_encode($return);
        }
        $cid = $request->cid;
        $campaign = Campaign::where('campaign_id', $cid)->first();
        $campaign->countries = $request->countries;
        if ($request->countries != 'All')
        {
            $targetCountries = json_decode($request->countries);
            $countryId = implode(',', array_column($targetCountries, 'value'));
            $countryName = implode(',', array_column($targetCountries, 'label'));
            $campaign->country_name = $countryName;
            $campaign->country_ids = $countryId;
        }

        $campaign->campaign_name = $request->campaign_name;
        $campaign->device_type = $request->device_type;
        $campaign->device_os = $request->device_os;
        $campaign->website_category = $request->website_category;
        $campaign->daily_budget = $request->daily_budget;
        $campaign->pricing_model = $request->pricing_model;
        if ($request->pricing_model == 'CPM')
        {
            $cat = Category::where('id', $request->website_category)
                ->first();
            $cpm = $cat->cpm;
            $campaign->cpc_amt = $cpm;
        }
        else
        {
            $campaign->cpc_amt = $request->cpc_amt;
        }
        $campaign->status = 1;

        if ($campaign->update())
        {
            $activitylog = new Activitylog();
            $activitylog->uid = 'Admin';
            $activitylog->type = 'Edit Campaign';
            $activitylog->description = '' . $campaign->campaign_id . ' is edit Successfully';
            $activitylog->status = '1';
            $activitylog->save();
            $return['code'] = 200;
            $return['data'] = $campaign;
            $return['message'] = 'Campaign updated successfully!';
        }
        else
        {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function cpmAmountUpdateCampaign(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bidding_price'     => 'required'
            ]
        );
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation  Error!';
            return json_encode($return);
        }
        // $updatedData = DB::table('campaigns')
        // ->where('campaign_id', $request->campaign_id)
        // ->where('advertiser_code', $request->advertiser_code)
        // ->where('status', 2)
        // ->update(['cpc_amt' => $request->bidding_price]);
        if($request->pricing_model == 'CPM'){
            $cpc_amts = ['cpm_amt' => $request->bidding_price];
        }else{
            $cpc_amts = ['cpc_amt' => $request->bidding_price];
        }
        $updatedData = Campaign::where('campaign_id', $request->campaign_id)
            ->where('advertiser_code', $request->advertiser_code)
            ->where('status', 2)
            ->first();
        $campLogData = [];
        $oldData = $updatedData->only([
            'cpc_amt'
        ]);
        // $newData = $request->only([
        //     'bidding_price'
        // ]);
        $newData = $cpc_amts;
        foreach ($oldData as $property => $value) {
            if ($value != $newData[$request->pricing_model == 'CPM' ? 'cpm_amt' : 'cpc_amt']) {
                $campLogData[$request->pricing_model == 'CPM' ? 'cpm_amt' : 'cpc_amt']['previous'] = $value;
                $campLogData[$request->pricing_model == 'CPM' ? 'cpm_amt' : 'cpc_amt']['updated'] = $newData[$request->pricing_model == 'CPM' ? 'cpm_amt' : 'cpc_amt'];
            }
        }
        $campLogData['message'] = 'Admin has changed the Bidding Price!';
        $camp_data = json_encode($campLogData);

        $camp_log = new CampaignLogs();
        $camp_log->uid = $request->advertiser_code;
        $camp_log->campaign_id = $request->campaign_id;
        $camp_log->campaign_type = $updatedData->ad_type;
        $camp_log->campaign_data = $camp_data;
        $camp_log->action = 2;
        $camp_log->user_type = 2;
        $camp_log->save();
        $updatedData->cpc_amt = $request->bidding_price;
        $updatedData->update();

        if ($updatedData) {
            $return['code']    =  200;
            $return['message'] = 'Campaign Amount Rate updated successfully!';
        } else {
            $return['code']    =  101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function adminCampaignLogs(Request $request)
    {
        $page = $request->page;
        $limit = $request->lim;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $campaign_data = [];
        $logs = CampaignLogs::select('uid', 'campaign_type', 'campaign_id', 'campaign_data', 'user_type', 'created_at')
            ->where('campaign_id', $request->cid);
        $row  = $logs->count();
        $data = $logs->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
        foreach ($data as $log) {
            $campaign_data = [json_decode($log->campaign_data)];
            $log->campaign_data = $campaign_data;
            $date = new DateTime($log->created_at);
            $format = $date->format('d-m-Y');
            $format2 = $date->format('h:i A');
            $log->date = $format;
            $log->time = $format2;
        }
        // dd($data);
        if (count($data) > 0) {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row']  = $row;
            $return['message'] = 'Logs found successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Logs Not found!';
        }
        // return json_encode($return, JSON_NUMERIC_CHECK);
        return $return;
    }
    
    static function impressionAndClickLog($request){
        $date = $request['date'];
        $dateTime = Carbon::parse($date);
        $dateTime->setTime(now()->hour, now()->minute, now()->second);
        $currentDateTime = Carbon::now();
        $currentDateTime->format('Y-m-d H:i:s');
        DB::table('manual_stats_logs')->insert([
        'campaign_id' => $request['campaign_id'],
        'impressions' => $request['impressions'],
        'impressions_rate' => $request['impression_amt'],
        'clicks' => $request['click'],
        'clicks_rate' => $request['click_amt'],
        'stats_date' => $dateTime,
        'created_at' => $currentDateTime,
      ]);
    }
    
    public function manualStatusLogList(Request $request){
        $page = $request->page;
        $limit = $request->lim;
        $cmpid = $request->src;
        $nfromdate = date('Y-m-d', strtotime($request->start_date));
        $endDate =  date('Y-m-d',strtotime($request->end_date));
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $logs = DB::table('manual_stats_logs')->select('manual_stats_logs.*','campaigns.ad_type')
            ->leftJoin('campaigns', 'manual_stats_logs.campaign_id', '=', 'campaigns.campaign_id');
        if($cmpid){
            $logs->where('manual_stats_logs.campaign_id','like','%' . $cmpid . '%');
        }
        if($request->start_date){
            $logs->whereDate('manual_stats_logs.created_at', '>=', $nfromdate)->whereDate('manual_stats_logs.created_at', '<=', $endDate);
        }
        $row  = $logs->count();
        $data = $logs->offset($start)->limit($limit)->orderBy('manual_stats_logs.id', 'desc')->get();
        if (count($data) > 0) {
            $return['code'] = 200;
            $return['data'] = $data;
            $return['row']  = $row;
            $return['message'] = 'Logs found successfully!';
        } else {
            $return['code'] = 101;
            $return['data'] = [];
            $return['row']  = $row;
            $return['message'] = 'Logs Not found!';
        }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
    static function sendMailEndBudget($uemail, $fname, $uid, $campid)
    {
        $email = $uemail;
        $fullname = $fname;
        $data["details"] = [
            "full_name" => $fullname,
            "email" => $email,
        ];
        $subject =
            "Your campaign's daily budget is about to be exhausted - 7Search PPC";
        $data["email"] = $email;
        $data["title"] = $subject;
        $body = View("emailtemp.dailybudgetexhausted", $data);
        $endBudgetMailirst = DB::table("end_budget_mail")
            ->where("uid", $uid)
            ->where("camp_id", $campid)
            ->where("send_mail_first", 1)
            ->whereDate("created_at", date("Y-m-d"))
            ->first();
        if (empty($endBudgetMailirst)) {
            sendmailUser($subject, $body, $email);
            DB::table("end_budget_mail")->insert([
                "uid" => $uid,
                "camp_id" => $campid,
                "send_mail_count" => 0,
                "send_mail_first" => 1,
            ]);
        }
        /* User Mail Section */
        $endBudgetMail = DB::table("end_budget_mail")
            ->where("uid", $uid)
            ->where("camp_id", $campid)
            ->where("send_mail_count", 1)
            ->whereDate("created_at", date("Y-m-d"))
            ->first();
        if (!empty($endBudgetMail)) {
            sendmailUser($subject, $body, $email);
            DB::table("end_budget_mail")
                ->where([
                    "uid" => $uid,
                    "camp_id" => $campid,
                ])
                ->whereDate("created_at", date("Y-m-d"))
                ->update(["send_mail_count" => 0]);
        }
    }
}
