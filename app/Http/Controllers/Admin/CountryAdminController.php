<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryAdminController extends Controller
{
    public function list(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $src =  $request->src;

        if ($src) {
            $country = Country::select('id', 'name', 'iso', 'iso3', 'nicename', 'numcode', 'phonecode','currency_code', 'status')->where('trash', 1)->whereRaw('concat(ss_countries.name) like ?', "%{$src}%");
            $row = $country->count();
            $data = $country->offset($start)->limit($limit)->get()->toArray();
        } else {
            $country = Country::select('id', 'name', 'iso', 'iso3', 'nicename', 'numcode', 'phonecode','currency_code', 'status')->where('trash', 1);
            $row = $country->count();
            $data = $country->offset($start)->limit($limit)->get()->toArray();
        }
        if ($row !== null) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function drodownList()
    {
        $country = Country::select('id as value', 'name as label')->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function userCountryDrodownList()
    {
        $country = Country::select('id as value', 'name as label', 'phonecode')->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($country !== null) {
            $return['code']    = 200;
            $return['data']    = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        //dd($request);
        $validator = Validator::make(
            $request->all(),
            [
                'name'          => 'required|unique:countries',
                'nicename'      => 'required|unique:countries',
                'iso'           => 'required|unique:countries',
                'iso3'          => 'required|unique:countries',
                'numcode'       => 'required|numeric|unique:countries',
                'phonecode'     => 'required|numeric|unique:countries',
                'currency_code' => 'required|unique:countries',

            ]
        );
        if ($validator->fails()) {
            $return['code']    = 101;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        $country                = new Country();
        $country->name          = $request->name;
        $country->iso           = $request->iso;
        $country->nicename      = $request->nicename;
        $country->iso3          = $request->iso3;
        $country->numcode       = $request->numcode;
        $country->phonecode     = $request->phonecode;
        $country->currency_code = $request->currency_code;
        if ($country->save()) {
            $return['code']    = 200;
            $return['data']    = $country;
            $return['message'] = 'Country added successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {
        $cid = $request->cid;
        $validator = Validator::make(
            $request->all(),
            [
                'iso'       => 'required',
                'name'      => 'required',
                'nicename'  => 'required',
                'iso3'      => 'required',
                'numcode'   => 'required|numeric',
                'phonecode' => 'required|numeric',
                'currency_code' => 'required',
            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';

            return json_encode($return);
        }

        $country                = Country::where('id', $request->cid)->first();
        $country->iso           = $request->iso;
        $country->name          = $request->name;
        $country->nicename      = $request->nicename;
        $country->iso3          = $request->iso3;
        $country->numcode       = $request->numcode;
        $country->phonecode     = $request->phonecode;
        $country->currency_code = $request->currency_code;

        if ($country->update()) {
        /* This will update country data into Redis */
        updateCountries($cid, 1);
            $return['code']    = 200;
            $return['data']    = $country;
            $return['message'] = 'Updated Successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Somthing went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function destroy(Request $request)
    {
        //dd($request);
        $country = Country::where('id', $request->id)->first();
        $country->trash = 0;
        if ($country->update()) {
            $return['code']    = 200;
            $return['message'] = 'Country deleted successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function countryUpdateStatus(Request $request)
    {
        $status = $request->status;
        $id = $request->uid;
        $country  = Country::where('id', $request->uid)->first();
        $country->status = $request->status;
        if ($country->update()) {
            // $camp = Campaign::select('id')->where('')
        /* This will update country status into Redis */
            updateCountries($id, $status);
            $return['code']    = 200;
            //$return ['data']    = $country;
            $return['message'] = 'Country Status updated!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
