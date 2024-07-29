<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\Admin;
use App\Models\User;

class AppPublisherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = '7SAPPI3209';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        // $authToken = Crypt::decryptString($_SERVER['HTTP_X_API_AUTH']);
        $authToken = $_SERVER['HTTP_X_API_AUTH'];
        dd($authToken);
        if(empty($serkey) && empty($authToken))
        {
         return response()->json([
            'code' => 404,
            'msg' => 'Api Key And Auth Token Is Empty']);
        }
        else
        {
            if($serkey == $key)
            {
                $getauth = User::where('login_token', $authToken)->first();
                if($getauth){
                    $gettokens =  $getauth->login_token;
                    if($authToken == $gettokens)
                     {
                         return $next($request);
                     }
                     else{
                        return response()->json([
                            'code' => 404,
                            'msg' => 'Invalid Auth Token.']);
                     }
                }else{
                    return response()->json([
                        'code' => 404,
                        'msg' => 'Invalid Auth Token.']);
                }
             }
            else
            {
                return response()->json([
                    'code' => 404,
                    'msg' => 'Invalid Auth Key.']);
            }

        }
    }
}

