<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppCountryControllers extends Controller
{
    public function index()
    {
        $country = Country::select('id as value', 'name as label')->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function list()
    {
        $country = Country::select('id', 'name', 'iso', 'iso3', 'nicename', 'numcode', 'phonecode')->where('name','!=','Abhinav')->where('status', 1)->orderBy('name', 'asc')->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['msg'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

?>
