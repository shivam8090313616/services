<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use App\Models\User;



class UserAdvertiser

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
        $key = 'cR9i43OnLk7r9Ty44QespV2h';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        $email = $request->email;
        $uid = ($request->user_id) ? $request->user_id : $request->uid;
        $authtoken = $request->header('Authorization');
        $getuid = User::where('uid', $uid)->where('password',base64_decode($authtoken))->first();
        $authtoken = $request->header('Authorization');
        $getuid = User::where('password',base64_decode($authtoken))->where('uid', $uid)->first();
        if(empty($serkey)){
         return response()->json('Api Key Empty');
        }if($serkey == $key){
            if(strlen($email)) {
                return $next($request);
            }else if(empty($getuid)){
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not exist!']);
            }else if($getuid->trash == 1){
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not exist!']);
            }else if($getuid->ac_verified == 0){
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not verified!']);
            }elseif ($getuid->status == '3') {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is suspended!']);
            } elseif ($getuid->status == '4') {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is on hold!']);
            }else{
                return $next($request);
            }
        }else {
            return response()->json([
                    'code' => 403,
                    'msg' => 'Invalid Api key!']);
        }
    }

    public function handleOld(Request $request, Closure $next)

    {
        $key = 'cR9i43OnLk7r9Ty44QespV2h';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        $key = 'cR9i43OnLk7r9Ty44QespV2h';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        $email = $request->email;
        $uid = ($request->user_id) ? $request->user_id : $request->uid;
        // $uid = null;
        $getuid = User::where('uid', $uid)->first();
    //     $dstr = date('Ynj-G:').ltrim(date('i'),0);
    //     $key = hash('sha256','cR9i43OnLk7r9Ty44QespV2h|'.$dstr);
    //   	$serkey = $_SERVER['HTTP_X_API_KEY'];
    //     $email = $request->email;
    //     $uid = ($request->user_id) ? $request->user_id : $request->uid;
    //     $getuid = User::where('uid', $uid)->first();
        if(empty($serkey))
        {
         return response()->json('Api Key Empty');
        }
        if($serkey == $key)
        {
            // dd($getuid);
            if(strlen($email)) {
                return $next($request);
            }  
            else if(empty($getuid))
            {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not exist!']);
            }
            else if($getuid->trash == 1)
            {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not exist!']);
            }
            else if($getuid->ac_verified == 0)
            {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is not verified!']);
            }
            elseif ($getuid->status == '3') {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is suspended!']);
            } elseif ($getuid->status == '4') {
                return response()->json([
                    'code' => 403,
                    'msg' => 'Your Account is on hold!']);
            }
            else
            {
                return $next($request);
            }
        }
        else {
            return response()->json([
                    'code' => 403,
                    'msg' => 'Invalid Api key!']);
        }
    }
}

