<?php



namespace App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use App\Models\AdImpression;

use App\Models\PubAdunit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\IpStack;
use Illuminate\Support\Facades\Redis;
use App\Models\Publisher\Pubstats;

class AdScriptController extends Controller
{
    /**
    * @OA\Post(
    *     path="/api/v1/adscript",
    *     summary="Get list of active messengers",
    *     tags={"Ad Script V1"},
       *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"ad_type"},
    *             @OA\Property(property="ad_type", type="string", description="User ID"),
    *             @OA\Property(property="adunit_id", type="integer", description="Option"),
    *             @OA\Property(property="img_type", type="integer", description="User ID"),
    *             @OA\Property(property="lim", type="integer", description="Option"),
    *             @OA\Property(property="info", type="array", 
    *               @OA\Items(
    *                     @OA\Property(property="os", type="string"),
    *                     @OA\Property(property="device", type="string")
    *                 )),
    *             ),

    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [Advertiser]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="string"),
    *                     @OA\Property(property="value", type="string")
    *                 )
    *             ),
    *             @OA\Property(property="msg", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="msg", type="string")
    *         )
    *     )
    * )
    */
    public function adList(Request $request)
    { 
        if (empty($_SERVER['HTTP_X_API_KEY']) || $_SERVER['HTTP_X_API_KEY'] != 'cs4788livKoP9i4Erwt6') {
            return json_encode(['code' => 101, 'message' => 'Api key went wrong!'], JSON_NUMERIC_CHECK);
        }
        $website_server = request()->headers->get('referer');
        $lim = ($request->lim > 1) ? $request->lim : 1;
        $adunit_id = $request->adunit_id;
        $adunit = getAdInfo($adunit_id);
        $webcn = strpos($website_server, $adunit->site_url);
        if (empty($webcn)) {
            $return['code'] = 101;
            $return['message'] = 'Domain not matched!';
            return json_encode($return);
        }
        if (empty($adunit)) {
            return json_encode(['code' => 101, 'message' => 'Adunit disabled!'], JSON_NUMERIC_CHECK);
        }    
        $publisher_code = $adunit->uid;
        $web_code = $adunit->web_code;
        $webcat = $adunit->website_category;
        $grid = ($adunit->grid_type > 4)  ? $adunit->grid_type - 4 : $adunit->grid_type;
        $erotic_ads = $adunit->erotic_ads;
        $alert_ads = $adunit->alert_ads;        
        $user = User::where('uid', $publisher_code)->where('user_type', '!=', 1)->where('status', '=', 0)->first();
        if (empty($user)) {
            return json_encode(['code' => 101, 'message' => 'Publisher Not Found!'], JSON_NUMERIC_CHECK);
        }
        $todaydate = date('Y-m-d');
        $ad_type = $request->ad_type;
        $ip = $request->ip();//'208.67.222.220';//$_SERVER['REMOTE_ADDR'];
        $loc = $this->getCountryNameAdScript($ip); 
        $info = $request->info;
        $device = ucfirst($info['device']);
        $os = $info['os'];
        if ($ad_type == '' || empty($loc['country_name']) || $device == '' || $os == '') {
            return json_encode(['code' => 101, 'message' => 'Ad type, country, device type and device os parameters are not correct!'], JSON_NUMERIC_CHECK);
        }
        if (in_array($ad_type, ['text', 'popup'])) {
            $adData = DB::table('campaigns')
                ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
              	->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                      $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                          ->whereDate('camp_budget_utilize.udate', $todaydate);
                  })
                ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url,
                ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                ->where('campaigns.ad_type', $ad_type)
                ->where('campaigns.advertiser_code', '!=', $publisher_code)
                ->where('campaigns.status', 2)
                ->where('campaigns.trash', 0)
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')");
            if ($webcat != 64) {
                $adData = $adData->where('campaigns.website_category', $webcat)
                    ->where('users.status', 0)
                    ->where('users.trash', 0)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit($lim)
                    ->first();
            } else {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } else if ($erotic_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } else if ($alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } else {
                    $adData = $adData->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                }
            }
            if (empty($adData)) {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                       	->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                              $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                  ->whereDate('camp_budget_utilize.udate', $todaydate);
                          })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                         ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } elseif ($erotic_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                      	->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                         ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } elseif ($alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                         ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                } else {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($lim)
                        ->first();
                }
            }
            if (empty($adData)) {
                $adData = DB::table('campaigns')
                    ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                    ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                   	->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                        $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                            ->whereDate('camp_budget_utilize.udate', $todaydate);
                    })
                    ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                    ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                    ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                    ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                    IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                    ->where('campaigns.ad_type', $ad_type)
                    ->where('campaigns.advertiser_code', '!=', $publisher_code)
                    ->where('campaigns.status', 2)
                    ->where('campaigns.trash', 0)
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                    ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                    ->where('campaigns.website_category', 113)
                    ->where('users.account_type', 1)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit($lim)
                    ->first();
            }
            if (empty($adData)) {
                return json_encode(['code' => 101, 'message' => 'Invalid ad!'], JSON_NUMERIC_CHECK);
            } else {
                $impData = array(
                    'campaign_id' => $adData->campaign_id,
                    'advertiser_code' => $adData->advertiser_code,
                    'publisher_code' => $publisher_code,
                    'adunit_id' => $adunit_id,
                    'website_id' => $web_code,
                    'device_os' => $os,
                    'device_type' => $device,
                    'device_os' => $os,
                    'ip_addr' => $ip,
                    'country' => $loc['country_name'],
                    'ad_type' => $ad_type,
                    'cpm' => $adData->cpc_amt,
                    'pricing_model' => $adData->pricing_model,
                    'total_amt' => $adData->amt,
                    'd_budget' => $adData->daily_budget,
                    'website_category' => $adData->website_category,
                );
                $sessid = $this->adImpression($impData);
                if ($ad_type === 'text') {
                    $return['code'] = 200;
                    $return['data'] = [
                        "campaign_id" => $adData->campaign_id,
                        "ad_title" => $adData->ad_title,
                        "ad_description" => $adData->ad_description,
                        "ad_type" => $adData->ad_type,
                        "target_url" => $adData->target_url,
                        "ad_session_id" => $sessid,
                        "display_brand" => $adData->display_brand,
                    ];
                    $return['message'] = 'Campaign Data Retrieved!';
                } else {
                    $return['code'] = 200;
                    $return['data'] = [
                        "ad_type" => $adData->ad_type,
                        "target_url" => $adData->target_url,
                        "ad_session_id" => $sessid,
                        "display_brand" => $adData->display_brand,
                    ];
                    $return['message'] = 'Campaign Data Retrieved!';
                }
            }
        } elseif ($ad_type === 'banner') {
            $adData = DB::table('campaigns')
                ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                    $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                        ->whereDate('camp_budget_utilize.udate', $todaydate);
                })
                ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                ->where('campaigns.ad_type', $ad_type)
                ->where('campaigns.status', 2)
                ->where('campaigns.advertiser_code', '!=', $publisher_code)
                ->where('campaigns.trash', 0)
                ->where('ad_banner_images.image_type', $adunit->ad_size)
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')");
            if ($webcat != 64) {
                $adData = $adData->where('campaigns.website_category', $webcat)
                    ->where('users.status', 0)
                    ->where('users.trash', 0)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit(1)
                    ->first();
            } else {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } elseif ($erotic_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } elseif ($alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } else {
                    $adData = $adData->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                }
            }

            if (empty($adData)) {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', $adunit->ad_size)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } elseif ($erotic_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', $adunit->ad_size)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } elseif ($alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt, IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', $adunit->ad_size)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                } else {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', $adunit->ad_size)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->first();
                }
            }
            if (empty($adData)) {
                $adData = DB::table('campaigns')
                    ->join('categories', 'campaigns.website_category', '=', 'categories.id')
                    ->join('users', 'campaigns.advertiser_code', '=', 'users.uid')
                    ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id')
                    ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                        $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                            ->whereDate('camp_budget_utilize.udate', $todaydate);
                    })
                    ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                    ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                    ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                    ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                    ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                    IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                    ->where('campaigns.ad_type', $ad_type)
                    ->where('campaigns.status', 2)
                    ->where('campaigns.advertiser_code', '!=', $publisher_code)
                    ->where('campaigns.trash', 0)
                    ->where('ad_banner_images.image_type', $adunit->ad_size)
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                    ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                    ->where('campaigns.website_category', 113)
                    ->where('users.account_type', 1)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit(1)
                    ->first();
            }
            if (empty($adData)) {
                $return['code'] = 101;
                $return['message'] = 'Invalid ad!';
                return json_encode($return, JSON_NUMERIC_CHECK);
            } else {
                $impData = array(
                    'campaign_id' => $adData->campaign_id,
                    'advertiser_code' => $adData->advertiser_code,
                    'publisher_code' => $publisher_code,
                    'adunit_id' => $adunit_id,
                    'website_id' => $web_code,
                    'device_os' => $os,
                    'device_type' => $device,
                    'device_os' => $os,
                    'ip_addr' => $ip,
                    'country' => $loc['country_name'],
                    'ad_type' => $ad_type,
                    'cpm' => $adData->cpc_amt,
                    'pricing_model' => $adData->pricing_model,
                    'total_amt' => $adData->amt,
                    'd_budget' => $adData->daily_budget,
                    'website_category' => $adData->website_category,
                );
                $sessid = $this->adImpression($impData);
                $return['code'] = 200;
                $return['data'] = [
                    "image_path" => $adData->image_path,
                    "image_type" => $adData->image_type,
                    "target_url" => $adData->target_url,
                    'campaign_id' => $adData->campaign_id,
                    "ad_session_id" => $sessid,
                    "display_brand" => $adData->display_brand,
                ];
                $return['message'] = 'Campaign Data Retrieved!';
            }
        } elseif ($ad_type === 'social') {
            $adData = DB::table('campaigns')
                ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                    $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                        ->whereDate('camp_budget_utilize.udate', $todaydate);
                })
                ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                ->where('campaigns.ad_type', $ad_type)
                ->where('campaigns.advertiser_code', '!=', $publisher_code)
                ->where('campaigns.status', 2)
                ->where('campaigns.trash', 0)
                ->where('ad_banner_images.image_type', 4)
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')");
            if ($webcat != 64) {
                $adData = $adData->where('campaigns.website_category', $webcat)
                    ->where('users.status', 0)
                    ->where('users.trash', 0)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit(1)
                    ->get()->toArray();
            } else {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } elseif ($erotic_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } elseif ($alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } else {
                    $adData = $adData->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                }
            }
            if (empty($adData)) {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                         ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                        ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                        ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                        ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                        ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 4)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } elseif ($erotic_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                        ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                        ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                        ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                        ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 4)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } elseif ($alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                        ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                        ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                        ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                        ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 4)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('campaigns.website_category', '!=', 17)
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                } else {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                        ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                        ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                        ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                        ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 4)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit(1)
                        ->get()->toArray();
                }
            }
            if (empty($adData)) {
                $adData = DB::table('campaigns')
                    ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                    ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                    ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                     ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                        $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                            ->whereDate('camp_budget_utilize.udate', $todaydate);
                    })
                    ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.social_ad_type, ss_campaigns.ad_title,
                    ss_campaigns.ad_description, ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url,
                    ss_campaigns.ad_type, ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name,
                    ss_campaigns.countries, ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm,
                    ss_ad_banner_images.image_path, ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                    IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                    ->where('campaigns.ad_type', $ad_type)
                    ->where('campaigns.advertiser_code', '!=', $publisher_code)
                    ->where('campaigns.status', 2)
                    ->where('campaigns.trash', 0)
                    ->where('ad_banner_images.image_type', 4)
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                    ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                    ->where('campaigns.website_category', 113)
                    ->where('users.account_type', 1)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit(1)
                    ->get()->toArray();
            }
            if (empty($adData)) {
                $return['code'] = 101;
                $return['message'] = 'Invalid ad!';
                return json_encode($return, JSON_NUMERIC_CHECK);
            } else {
                foreach ($adData as $data) {
                    $impData = array(
                        'campaign_id' => $data->campaign_id,
                        'advertiser_code' => $data->advertiser_code,
                        'publisher_code' => $publisher_code,
                        'adunit_id' => $adunit_id,
                        'website_id' => $web_code,
                        'device_os' => $os,
                        'device_type' => $device,
                        'device_os' => $os,
                        'ip_addr' => $ip,
                        'country' => $loc['country_name'],
                        'ad_type' => $ad_type,
                        'cpm' => $data->cpc_amt,
                        'pricing_model' => $data->pricing_model,
                        'total_amt' => $data->amt,
                        'd_budget' => $data->daily_budget,
                        'website_category' => $data->website_category,
                    );
                    $sessid = $this->adImpression($impData);
                    $resData[] = [
                        "image_path" => $data->image_path,
                        "campaign_id" => $data->campaign_id,
                        "target_url" => $data->target_url,
                        "ad_title" => $data->ad_title,
                        "ad_description" => $data->ad_description,
                        "ad_type" => $data->ad_type,
                        "display_brand" => $data->display_brand,
                        "social_ad_type" => $data->social_ad_type,
                        "ad_session_id" => $sessid,
                    ];
                }
                $return['code'] = 200;
                $return['data'] = $resData;
                $return['message'] = 'Campaign Data Retrieved!';
            }
        } elseif ($ad_type === 'native') {
            $adData = DB::table('campaigns')
                ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                    $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                        ->whereDate('camp_budget_utilize.udate', $todaydate);
                })
                ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt, IFNULL(ss_camp_budget_utilize.amount, 0) as amt
                ")
                ->where('campaigns.ad_type', $ad_type)
                ->where('campaigns.status', 2)
                ->where('campaigns.advertiser_code', '!=', $publisher_code)
                ->where('campaigns.trash', 0)
                ->where('ad_banner_images.image_type', 1)
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                ->where('users.status', 0)
                ->where('users.trash', 0);
            if ($webcat != 64) {
                $currentCategoryAcive = $adData->where('campaigns.website_category', $webcat)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit($grid)
                    ->get()->toArray();
                if(count($currentCategoryAcive) < $grid){
                    $limit = ($grid - count($currentCategoryAcive));
                    $allCategoryAcive = DB::table('campaigns')
                    ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                    ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                    ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                    ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                        $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                            ->whereDate('camp_budget_utilize.udate', $todaydate);
                    })
                    ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                    ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                    ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                    ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                    ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                    IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                    ->where('campaigns.ad_type', $ad_type)
                    ->where('campaigns.status', 2)
                    ->where('campaigns.advertiser_code', '!=', $publisher_code)
                    ->where('campaigns.trash', 0)
                    ->where('ad_banner_images.image_type', 1)
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                    ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                    ->where('users.status', 0)
                    ->where('users.trash', 0)
                    ->where('campaigns.website_category','!=', $webcat)
                    ->where('users.account_type', 1)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get()->toArray();
                    $adData =  array_merge($currentCategoryAcive,$allCategoryAcive);
                    }else{
                        $adData = $currentCategoryAcive;  
                    }
           
            } else {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                } elseif ($erotic_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 63)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                } elseif ($alert_ads == 1) {
                    $adData = $adData->where('campaigns.website_category', '!=', 17)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                } else {
                    $adData = $adData->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                }
            }
            if (empty($adData)) {
                if ($erotic_ads == 1 && $alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt, IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 1)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->where('campaigns.website_category', '!=', 63)
                        ->where('campaigns.website_category', '!=', 17)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                } elseif ($alert_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 1)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->where('campaigns.website_category', '!=', 17)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                 } elseif ($erotic_ads == 1) {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt, IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 1)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->where('campaigns.website_category', '!=', 63)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                } else {
                    $adData = DB::table('campaigns')
                        ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                        ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                        ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                        ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                            $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                                ->whereDate('camp_budget_utilize.udate', $todaydate);
                        })
                        ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                        ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                        ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                        ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                        ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                        IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                        ->where('campaigns.ad_type', $ad_type)
                        ->where('campaigns.status', 2)
                        ->where('campaigns.advertiser_code', '!=', $publisher_code)
                        ->where('campaigns.trash', 0)
                        ->where('ad_banner_images.image_type', 1)
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                        ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                        ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                        ->where('users.status', 0)
                        ->where('users.trash', 0)
                        ->having('users.wallet', '>', '0')
                        ->havingRaw('ss_campaigns.daily_budget > amt')
                        ->inRandomOrder()
                        ->limit($grid)
                        ->get()->toArray();
                }
            }
            if (empty($adData)) {
                $adData = DB::table('campaigns')
                    ->join('categories', 'campaigns.website_category', '=', 'categories.id', 'left outer')
                    ->join('ad_banner_images', 'campaigns.campaign_id', '=', 'ad_banner_images.campaign_id', 'left outer')
                    ->join('users', 'campaigns.advertiser_code', '=', 'users.uid', 'left outer')
                    ->leftJoin('camp_budget_utilize', function ($join) use ($todaydate) {
                        $join->on('campaigns.campaign_id', '=', 'camp_budget_utilize.camp_id')
                            ->whereDate('camp_budget_utilize.udate', $todaydate);
                    })
                    ->selectRaw("ss_campaigns.campaign_id, ss_campaigns.campaign_name, ss_users.wallet, ss_campaigns.ad_title, ss_campaigns.ad_description,
                    ss_campaigns.daily_budget, ss_campaigns.target_url, ss_campaigns.website_category, ss_campaigns.conversion_url, ss_campaigns.ad_type,
                    ss_campaigns.device_type, ss_campaigns.device_os, ss_campaigns.advertiser_code, ss_campaigns.country_name, ss_campaigns.countries,
                    ss_campaigns.country_ids, ss_categories.display_brand, ss_categories.cat_name, ss_categories.cpm, ss_ad_banner_images.image_path,
                    ss_ad_banner_images.image_type,ss_campaigns.pricing_model,ss_campaigns.cpc_amt,
                    IFNULL(ss_camp_budget_utilize.amount, 0) as amt")
                    ->where('campaigns.ad_type', $ad_type)
                    ->where('campaigns.status', 2)
                    ->where('campaigns.advertiser_code', '!=', $publisher_code)
                    ->where('campaigns.trash', 0)
                    ->where('ad_banner_images.image_type', 1)
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_type)', [$device])
                    ->whereRaw('FIND_IN_SET(?, ss_campaigns.device_os)', [$os])
                    ->whereRaw("(FIND_IN_SET('" . strtoupper($loc['country_name']) . "', ss_campaigns.country_name) <> 0 OR ss_campaigns.countries = 'All')")
                    ->where('users.status', 0)
                    ->where('users.trash', 0)
                    ->where('campaigns.website_category', 113)
                    ->where('users.account_type', 1)
                    ->having('users.wallet', '>', '0')
                    ->havingRaw('ss_campaigns.daily_budget > amt')
                    ->inRandomOrder()
                    ->limit($grid)
                    ->get()->toArray();
            }
            if (empty($adData)) {
                return json_encode(['code' => 101, 'message' => 'Invalid ad!'], JSON_NUMERIC_CHECK);
            } else {
                foreach ($adData as $data) {
                    $impData = array(
                        'campaign_id' => $data->campaign_id,
                        'advertiser_code' => $data->advertiser_code,
                        'publisher_code' => $publisher_code,
                        'adunit_id' => $adunit_id,
                        'website_id' => $web_code,
                        'device_os' => $os,
                        'device_type' => $device,
                        'device_os' => $os,
                        'ip_addr' => $ip,
                        'country' => $loc['country_name'],
                        'ad_type' => $ad_type,
                        'cpm' => $data->cpc_amt,
                        'pricing_model' => $data->pricing_model,
                        'total_amt' => $data->amt,
                        'd_budget' => $data->daily_budget,
                        'website_category' => $data->website_category,
                    );
                    $sessid = $this->adImpression($impData);
                    $resData[] = [
                        "image_path" => $data->image_path,
                        "image_type" => $data->image_type,
                        "campaign_id" => $data->campaign_id,
                        "target_url" => $data->target_url,
                        "ad_title" => $data->ad_title,
                        "ad_description" => $data->ad_description,
                        "ad_type" => $data->ad_type,
                        "display_brand" => $data->display_brand,
                        "cat_name" => ($data->cat_name == 'All Categories') ? '' : $data->cat_name,
                        "ad_session_id" => $sessid,
                    ];
                }
                $return['code'] = 200;
                $return['data'] = $resData;
                $return['message'] = 'Campaign Data Retrieved!';
            }
        } else {
            return json_encode(['code' => 101, 'message' => 'Ad type not found!'], JSON_NUMERIC_CHECK);
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function adImpression($impData)
    {
        
        listNotificationMassagesFront($impData);
        $ad_rate = DB::table('pub_rate_masters')->select('*')->where('status', 0)->where('category_id', $impData['website_category'])->where('country_name', $impData['country'])->first();
         if (empty($ad_rate)) {
            $ad_rate = DB::table('categories')->where('status', 1)->where('trash', 0)->select('*')->where('id', $impData['website_category'])->first();
        }
        if($impData['pricing_model']== 'CPM'){
            $adv_cpm = $impData['cpm'];
        }else{
            $adv_cpm = $ad_rate->cpm;
        }
        $cpm = ($ad_rate->cpm * $ad_rate->pub_cpm) / 100;
        DB::table('users')->where('uid', $impData['advertiser_code'])->decrement('wallet', $adv_cpm);
        DB::table('users')->where('uid', $impData['publisher_code'])->increment('pub_wallet', $cpm);
        
        // DB::table('pub_adunits')->where('web_code', $impData['website_id'])->where('uid', $impData['publisher_code'])->where('ad_code', $impData['adunit_id'])->increment('impressions', 1);
        $sessid = 'SESID' . strtoupper(uniqid());
        $ucountry = strtoupper($impData['country']);
        $adimp = new AdImpression();
        $device_os = strtolower($impData['device_os']);
        $adimp->impression_id = 'IMP' . strtoupper(uniqid());
        $adimp->ad_session_id = $sessid;
        $adimp->campaign_id = $impData['campaign_id'];
        $adimp->advertiser_code = $impData['advertiser_code'];
        $adimp->publisher_code = $impData['publisher_code'];
        $adimp->adunit_id = $impData['adunit_id'];
        $adimp->website_id = $impData['website_id'];
        $adimp->device_type = $impData['device_type'];
        $adimp->device_os = $device_os;
        $adimp->ip_addr = $impData['ip_addr'];
        $adimp->country = $ucountry;
        $adimp->ad_type = $impData['ad_type'];
        $adimp->amount = $adv_cpm;
        $adimp->website_category = $impData['website_category'];
        $adimp->pub_imp_credit = $cpm;
        $adimp->uni_imp_id = md5($impData['advertiser_code'] . $impData['campaign_id'] . $impData['device_os'] . $impData['device_type'] . $ucountry . date('Ymd'));
        $adimp->uni_bd_id = md5($impData['advertiser_code'] . $impData['campaign_id'] . date('Ymd'));
        $adimp->save();
        // Publisher Dashboard Stats //
        $pub_uni_id = md5($impData['publisher_code'] . $impData['adunit_id'] . $impData['device_type'] . $impData['device_os'] . $ucountry . date('Ymd'));
        
        /*$user = Pubstats::updateOrCreate(
            ['uni_pub_imp_id' => $pub_uni_id],
            [
                "publisher_code" => $impData['publisher_code'],
                "adunit_id" => $impData['adunit_id'],
                "website_id" => $impData['website_id'],
                "device_os" => $device_os,
                "device_type" => $impData['device_type'],
                "impressions" => \DB::raw('impressions + 1'),
                "amount" => \DB::raw('amount +'.$cpm),
                "country" => $ucountry,
                "udate" => date('Y-m-d'),
            ]
        );*/
        
        $row = DB::table('pub_stats')->where('uni_pub_imp_id', $pub_uni_id)->first();
        if ($row) {
            DB::table('pub_stats')->where('uni_pub_imp_id', $pub_uni_id)->update(['impressions' => $row->impressions + 1, 'amount' => $row->amount + $cpm]);
        } else {
            DB::table('pub_stats')->insert([
                "uni_pub_imp_id" => $pub_uni_id,
                "publisher_code" => $impData['publisher_code'],
                "adunit_id" => $impData['adunit_id'],
                "website_id" => $impData['website_id'],
                "device_os" => $device_os,
                "device_type" => $impData['device_type'],
                "impressions" => 1,
                "amount" => $cpm,
                "country" => $ucountry,
                "udate" => date('Y-m-d'),
            ]);
        }
        return $sessid;
    }

    function getCountryNameAdScript($ip) {
        //$data  = file_get_contents('https://locationip.7searchppc.com/?ip=' . $ip);  https://ips.7searchppc.in/
        $data  = file_get_contents('https://ips.7searchppc.in/?ip=' . $ip);
        return json_decode($data, true);
        /*$data = $this->getCountryIpLocal($ip);
        $alldata = is_null($data) ? [] :json_decode($data, true);
        if (!is_null($alldata) && count($alldata) > 0) {
            return ($alldata);
        } else {
            $data  = file_get_contents('http://api.ipstack.com/' . $ip . '?access_key=73edfcf302ecac3b68b27d0aee4ba152');
            $this->insertCountryIpLocal(json_decode($data), $ip);
            return json_decode($data, true);
        }*/
    }

    function getCountryIpLocal($ip)
    {
        $redisKey = date('YmdH');
        $redis_data = json_decode(Redis::get($redisKey), true);
        if(empty($redis_data[$ip])){
            return json_encode($data = IpStack::where('ip_addrs',$ip)->first(), true);
        } else {
            return json_encode($redis_data[$ip], true);
        }
    }

    function insertCountryIpLocal($data, $ip)
    {
        $redisKey = date('YmdH');
        $redis_data = json_decode(Redis::get($redisKey), true);
        $redis_data[$ip]  = $data;
        Redis::set($redisKey, json_encode($redis_data));
    }
    
    public function update_ipstack(){
        $redisKey = date('YmdH');
        $redis_data = json_decode(Redis::get($redisKey), true);
        if(!is_null($redis_data)){
            $data = [];
            foreach($redis_data as $key => $value){
                $data[] = ['ip_addrs' =>   $value['ip'], 'continent_code' => $value['continent_code'], 'continent_name' => $value['continent_name'], 'country_code' => $value['country_code'], 'country_name' =>  $value['country_name'], 'region_code' => $value['region_code'],  'region_name' => $value['region_name'], 'city' => $value['city'],  'zip' => $value['zip'], 'time_zone' => $value['time_zone']['id'], 'created_at' =>  date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            } 
            $dataArr =  array_chunk($data,500);
            foreach($dataArr as $value) {
                IpStack::insert($value);
            }  
            Redis::del($redisKey);
        }
    }
}
