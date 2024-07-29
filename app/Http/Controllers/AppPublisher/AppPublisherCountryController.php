<?php

namespace App\Http\Controllers\AppPublisher;
use App\Http\Controllers\Controller;
use App\Models\Country;

class AppPublisherCountryController extends Controller
{
    public function index()
    {
        $country = Country::select('id as value', 'name as label')->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return);
    }

    public function list()
    {
        $country = Country::select('id', 'name', 'iso', 'iso3', 'nicename', 'numcode', 'phonecode')->where('status', 1)->orderBy('name', 'asc')->where('id','!=',247)->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return);
    }
}

?>
