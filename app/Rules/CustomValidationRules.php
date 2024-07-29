<?php

namespace App\Rules;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CustomValidationRules implements Rule
{
    private $country;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        $getCountryName =  DB::table('countries')->where('name',$this->request->country)->where('phonecode',trim($this->request->phonecode,"+"))->where('status',1)->value('name','phonecode');
        if($getCountryName){
            return true;
        }
    }

    public function message()
    {  
        return 'The validation failed because of Countrie or phonecode wrong.';
    }
}
