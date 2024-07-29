<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use App\Models\Admin;

use App\Models\User;



class AppMiddleware

{

    public function handle(Request $request, Closure $next)

    {
        $key = '7SAPPI3209';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        //$authToken = $_SERVER['HTTP_X_API_AUTH'];
        if(empty($serkey))
        {
         return response()->json([
            'code' => 404,
            'msg' => 'Api Key And Auth Token Is Empty']);
        }
        else
        {
            if($serkey == $key)
            {
                return $next($request);
                // $getauth = User::where('login_token', $authToken)->first();
                // if($getauth){
                //     $gettokens =  $getauth->login_token;
                //     if($authToken == $gettokens)
                //      {
                //          return $next($request);
                //      }
                //      else{
                //         return response()->json([
                //             'code' => 404,
                //             'msg' => 'Invalid Auth Token.']);
                //      }
                // }else{
                //     return response()->json([
                //         'code' => 404,
                //         'msg' => 'Invalid Auth Token.']);
                // }
            }
            else
            {
                return response()->json([
                    'code' => 404,
                    'msg' => 'Invalid Auth Key.']);
            }
        }

        // $key = 'cR9i43OnLk7r9Ty44QespV2h';
        // $serkey = $_SERVER['HTTP_X_API_KEY'];
        // $key = '7SAPPI3209';
        // $serkey = $_SERVER['HTTP_X_API_KEY'];
        // $email = $request->email;
        // $uid = ($request->user_id) ? $request->user_id : $request->uid;
        // $getuid = User::where('uid', $uid)->first();
        // if(empty($serkey))
        // {
        //  return response()->json('Api Key Empty');
        // }
        // if($serkey == $key)
        // {
        //     if(strlen($email)) {
        //         return $next($request);
        //     }  
        //     else if(empty($getuid))
        //     {
        //         return response()->json([
        //             'code' => 403,
        //             'msg' => 'Your Account is not exist!']);
        //     }
        //     else if($getuid->trash == 1)
        //     {
        //         return response()->json([
        //             'code' => 403,
        //             'msg' => 'Your Account is not exist!']);
        //     }
        //     else if($getuid->ac_verified == 0)
        //     {
        //         return response()->json([
        //             'code' => 403,
        //             'msg' => 'Your Account is not verified!']);
        //     }
        //     elseif ($getuid->status == '3') {
        //         return response()->json([
        //             'code' => 403,
        //             'msg' => 'Your Account is suspended!']);
        //     } elseif ($getuid->status == '4') {
        //         return response()->json([
        //             'code' => 403,
        //             'msg' => 'Your Account is on hold!']);
        //     }
        //     else
        //     {
        //         return $next($request);
        //     }
        // }
        // else {
        //     return response()->json([
        //             'code' => 403,
        //             'msg' => 'Invalid Api key!']);
        // }


    }
}

