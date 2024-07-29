<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin;

class Advertiser
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
       /* $key = '7SAPI321';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        $authtoken = $_SERVER['HTTP_X_AUTH_TOKEN'];
        $useradmin = Admin::where('remember_token',$authtoken)->count();
        if(empty($serkey))
        {
         return response()->json([
            'code' => 404,
            'msg' => 'Api Key Empty']);
        }
        if($serkey == $key)
        {
            if($useradmin == 1)
            {
                return $next($request);
            } else {
                return response()->json([
                    'code' => 105,
                    'msg' => 'Invalid Auth Token']);
            }
        } else {
            return response()->json([
                'code' => 106,
                'msg' => 'Invalid Api key']);
        } */

        $key = 'cR9i43OnLk7r9Ty44QespV2h';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        if(empty($serkey))
        {
         return response()->json([
            'code' => 404,
            'msg' => 'Api Key Empty']);
        }
        if($serkey == $key)
        {
            return $next($request);
        } 
        return response()->json([
            'code' => 404,
            'msg' => 'Api Key Empty']);
    }
}
