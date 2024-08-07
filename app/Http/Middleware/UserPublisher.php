<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class UserPublisher
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
     
     public function handle(Request $request, Closure $next)
    {
        $key = '580eca75d1ffbacca33edc3278c092e9';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        $authtoken = $request->header('Authorization');
        $getuid = User::where('password',base64_decode($authtoken))->first();
        if(empty($getuid)){
            return response()->json([
                'code' => 403,
                'msg' => 'Your Account is not exist!']);
        }
        if(empty($serkey))
        {
         return response()->json('Api Key Empty');
        }
        if($serkey == $key && $getuid)
        {
            
            if($getuid->status == 3){
                return response()->json([
                'code' => 403,
                'msg' => 'Your Account is suspended!'
            ]);
            } else if($getuid->status == 4){
                return response()->json([
                'code' => 403,
                'msg' => 'Your Account is on hold!'
            ]);
            } else if($getuid->status == 2){
                return response()->json([
                'code' => 403,
                'msg' => 'Your Account is pending!'
            ]);
            }else if($getuid->trash == 1){
                return response()->json([
                'code' => 403,
                'msg' => 'Your Account is Removed!'
            ]);
            }  else{
                return $next($request);
            }
          
        } else {
            return response()->json('Invalid Api key');
        }
        
    }
    
    public function handleOld(Request $request, Closure $next)
    {
        $key = '580eca75d1ffbacca33edc3278c092e9';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        if(empty($serkey))
        {
         return response()->json('Api Key Empty');
        }
        if($serkey == $key)
        {
            return $next($request);
          
        } else {
            return response()->json('Invalid Api key');
        }
        
    }
}
