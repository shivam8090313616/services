<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\AgentClientLog;

use App\Models\AssignClient;

use App\Models\User;

use App\Models\Agent;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;



class AgentAdminController extends Controller

{
 
    // dropdown list of agent

      /**

    * @OA\Post(

    *     path="/api/admin/agent/dropdown-list",

    *     summary="Get Dropdown Agent List",

    *     tags={"Manage Admin Agents"},

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Data Fetched Successfully",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *             @OA\Property(property="label", type="string"),

    *             @OA\Property(property="value", type="integer")

    *         )

    *     ),

    *     @OA\Response(

    *         response=401,

    *         description="Data Not Found!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function dropdownAgentList()

    {

        $data = Agent::select('name', 'agent_id')

            ->selectRaw("concat(name, ' - ', agent_id, ' (',email, ') ') as label, agent_id as value")

            ->whereTrash(0)

            ->orderBy('name', 'asc')

            ->get();



        if (count($data) > 0) {

            $return['code'] = 200;

            $return['message'] = 'Agent Dropdown List Fetched Successfully.';

            $return['data'] = $data;

        } else {

            $return['code'] = 401;

            $return['message'] = 'Record Not Found!';

        }

        return json_encode($return);

    }



    // Store or update agent

    /**

    * @OA\Post(

    *     path="/api/admin/agent/create-update",

    *     summary="Create And Update Agent",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"name,email"},

    *              @OA\Property(property="name", type="string", description="Agent Name"),

    *              @OA\Property(property="email", type="string", description="Agent Email"),

    *              @OA\Property(property="contact_no", type="integer", description="Contact No."),

    *              @OA\Property(property="skype_id", type="string", description="Skype Id"),

    *              @OA\Property(property="telegram_id", type="string", description="Skype Id"),

    *              @OA\Property(property="profile_image", type="string", description="Profile Image"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Agents record saved or update Successfully.",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    *     )

    */

    public function agentStoreUpdate(Request $request)
    {
        $existagent = Agent::where('id', $request->id)->where('status', 1)->first();
        if ($existagent) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => "required|max:26",
                    'email' => 'required|email|unique:agents,email,' . $existagent->id . '|max:70',
                    'skype_id' => 'max:32',
                ]
            );
        } else {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => "required|max:26",
                    'email' => 'required|email|unique:agents|max:70',
                    'skype_id' => 'max:32',
                ]
            );
        }
        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error!';
            return json_encode($return);
        }

        if (!empty($request->profile_image)) {
            $base_str = explode(';base64,', $request->profile_image);
            $ext = str_replace('data:image/', '', $base_str[0]);
            $image = base64_decode($base_str[1]);
            $fileName = uniqid() . '.' . $ext;
            $file_path = 'agent/profile_image/' . $fileName;
            file_put_contents(public_path($file_path), $image);
            
            // $file = $request->file('profile_image');
            // $extension = $file->getClientOriginalExtension();
            // $fileName = uniqid() . '.' . $extension;
            // $file->move(public_path('agent/profile_image'), $fileName);

            // Delete previous profile image if exists
            if (!empty($existagent) && $existagent->profile_image) {
                $imagePath = public_path('agent/profile_image/' . $existagent->profile_image);
                if (File::exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $data = $fileName;
        } else {
            $data = $existagent ? $existagent->profile_image : null;
        }
        $agent_id = randomClientid('AGM');
        $existagent ? $agentid = $existagent->agent_id : $agentid = $agent_id;
        Agent::updateOrCreate(
            [
                'id'   => $request->id,
            ],
            [
                'agent_id'     => $agentid,
                'name'     => $request->name,
                'email'       => $request->email,
                'contact_no'    => $request->contact_no,
                'profile_image'   => $data,
                'skype_id'       => $request->skype_id,
                'telegram_id'   => $request->telegram_id,
            ]
        );
        if ($request->id) {
            $return['code']    = 200;
            $return['message'] = 'Agent Record Updated Successfully!';
        } else {
            $return['code']    = 200;
            $return['message'] = 'Agent Record Saved Successfully!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    // List of all active client

     /**

    * @OA\Post(

    *     path="/api/admin/agent/all-client-list",

    *     summary="Get All active client list in advertiser & publisher",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"lim,page"},

    *             @OA\Property(property="lim", type="integer", description="Limit"),

    *             @OA\Property(property="page", type="integer", description="Page"),

    *             @OA\Property(property="start", type="integer", description="Start"),

    *             @OA\Property(property="src", type="string", description="Search Name, ID, Email"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Data Found Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="data", type="array",

    *                 @OA\Items(

    *                     @OA\Property(property="uid", type="string"),

    *                     @OA\Property(property="email", type="string"),

    *                     @OA\Property(property="id", type="integer"),

    *                     @OA\Property(property="label", type="string")

    *                 )

    *             ),

    *             @OA\Property(property="message", type="string")

    *         )

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Data Not Found!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function allActiveClientList(Request $request)

    {

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $src = $request->src;



        $assignclient = AssignClient::select('cid')->get();

        $mchdata = [];

        foreach ($assignclient as $val) {

            if ($val->status == 0) {

                $mchdata[] = $val->cid;

            }

        }



        $getList = User::select('uid', 'email', 'user_type', 'id')

            ->selectRaw("CONCAT(first_name, ' ', last_name, ' - ', uid) AS label, uid AS value")

            ->where('status', 0)

            ->whereNotIn('uid', $mchdata)

            ->where('account_type', 0);

        if ($src) {

            $getList->whereRaw('concat(ss_users.first_name," ",ss_users.last_name," ",ss_users.uid,ss_users.email) like ?', "%{$src}%");

        }

        $row = $getList->count();

        $data = $getList->offset($start)->orderByDesc('id')->limit($limit)->get();

        if ($data) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['row']    = $row;

            $return['message'] = 'Data Found Successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }





    // List of all agents 

    /**

    * @OA\Post(

    *     path="/api/admin/agent/list",

    *     summary="Get All Active Agents List",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"lim,page"},

    *             @OA\Property(property="lim", type="integer", description="Limit"),

    *             @OA\Property(property="page", type="integer", description="Page"),

    *             @OA\Property(property="start", type="integer", description="Start"),

    *             @OA\Property(property="src", type="string", description="Search Name, ID, Email"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

      *     @OA\Response(

    *         response=200,

    *         description="Data Found Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Data Not Found!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function agentList(Request $request)

    {

        $src = $request->src;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $getList = Agent::select('id', 'agent_id', 'profile_image', 'name', 'email', 'contact_no', 'skype_id', 'telegram_id', 'created_at')

            ->selectRaw('(SELECT COUNT(*) FROM ss_assign_clients WHERE ss_assign_clients.aid = ss_agents.agent_id) as clientcount')

            ->where('status', 1)->where('trash', 0);

        if ($src) {

            $getList->whereRaw('concat(ss_agents.name,ss_agents.email,ss_agents.agent_id) like ?', "%{$src}%");

        }

        $getList->orderBy('id', 'desc');

        $row = $getList->count();

        $data = $getList->offset($start)->limit($limit)->get();

        if ($getList) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['row']    = $row;

            $return['message'] = 'Data Found Successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }


    // Delete agent

     /**

    * @OA\Post(

    *     path="/api/admin/agent/delete",

    *     summary="Remove agent",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Agent Removed Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function deleteAgent(Request $request)

    {

        $agent_id = $request->agent_id;

        $validator = Validator::make(

            $request->all(),

            [

                'agent_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }
        $fetchagent = Agent::select("agent_id")->where('agent_id', $agent_id)->first();
        $fetchassign = AssignClient::select("aid")->where('aid', $agent_id)->first();
        if (!$fetchagent) {

            $return['code']    = 101;

            $return['message'] = 'Invalid Agent ID!';

            return json_encode($return);

        }

        if ($fetchassign) {

            $return['code']    = 101;

            $return['message'] = 'Please first remove client this agent!';

        } else {

            $deleteAgent = DB::table('agents')->where('agent_id', $agent_id)->update(['trash' => 1]);

            if ($deleteAgent) {

                $return['code']    = 200;

                $return['message'] = 'Agent Removed Successfully!';

            } else {

                $return['code']    = 101;

                $return['message'] = 'This Agent Allready Removed!';

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // Delete client assign agent

     /**

    * @OA\Post(

    *     path="/api/admin/agent/assign-client-remove",

    *     summary="Remove Assign Agent - Client",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id,client_id"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             @OA\Property(property="client_id", type="string", description="Client ID"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Client Removed Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=100,

    *         description="Invalid client id!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function deleteassignclient(Request $request)

    {

        $clientIds = $request->client_id;

        $agentId = $request->agent_id;

        $message = "Removed Successfully.";

        $validator = Validator::make(

            $request->all(),

            [

                'agent_id' => "required",

                'client_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }

        $check = AssignClient::select('cid')->where("cid", $clientIds)->first();

        if (!empty($clientIds) && $check) {

            AssignClient::select("cid")->where('cid', $clientIds)->delete();

            self::agentlogs($agentId, [$clientIds], $message);

            $return['code']    = 200;

            $return['message'] = 'Client Removed Successfully!';

        } else if ($check == false) {

            $return['code']    = 100;

            $return['message'] = 'Invalid client id!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Something went wrong!';

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }





    // agent client list bulk action

   /**

    * @OA\Post(

    *     path="/api/admin/agent/bulk-action",

    *     summary="Remove Multiple Assign Agent - Client",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id,client_id,action"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             @OA\Property(property="client_id", type="string", description="Client ID In Array"),

    *             @OA\Property(property="action", type="string", description="Action send data- delete"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="All Client Removed Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function bulkagentaction(Request $request)

    {

        $actionType = $request->action;

        $clientIds = $request->client_id;

        $agentId = $request->agent_id;

        $data = [];



        $validator = Validator::make(

            $request->all(),

            [

                'action' => "required",

                'client_id' => "required|array",

                'agent_id' => 'required'

            ]

        );



        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }

        foreach ($clientIds as $clientId) {

            $data[] = [

                'cid' => $clientId,

            ];

        }

        if ($actionType == 'delete') {

            if (!empty($data)) {

                $message = "Removed Successfully.";

                AssignClient::select("cid")->whereIn("cid", $data)->delete();

                self::agentlogs($agentId, $clientIds, $message);

                $return['code']    = 200;

                $return['message'] = 'All Client Removed Successfully!';

            } else {

                $return['code']    = 101;

                $return['message'] = 'Something went wrong!';

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }

    // show agent assign client list

     /**

    * @OA\Post(

    *     path="/api/admin/agent/client-details",

    *     summary="Get Agent Assign Client List",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id,lim,page"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             @OA\Property(property="lim", type="integer", description="Limit"),

    *             @OA\Property(property="pg", type="integer", description="Page"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="All Client Removed Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function showAgentClientList(Request $request)

    {

        $src = $request->src;

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $agent_id = $request->agent_id;

        $getList = AssignClient::join('users', 'assign_clients.cid', '=', 'users.uid')

            ->select('users.email', 'users.country', 'users.user_type', 'assign_clients.cid', 'assign_clients.created_at', 'assign_clients.id', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))

            ->where("assign_clients.aid", $agent_id);



        if ($src) {

            $getList->whereRaw('concat(ss_users.first_name," ",ss_users.last_name," ",ss_users.email,ss_users.uid) like ?', "%{$src}%");

        }

        $row = $getList->count();

        $data = $getList->offset($start)->limit($limit)->orderByDesc('id')->get();

        if ($getList) {

            $return['code']    = 200;

            $return['data']    = $data;

            $return['row']    = $row;

            $return['message'] = 'Data Found Successfully!';

        } else {

            $return['code']    = 101;

            $return['message'] = 'Data Not Found!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // Assign Agent

     /**

    * @OA\Post(

    *     path="/api/admin/agent/assign",

    *     summary="Assign Agent to client",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"aid,cid"},

    *             @OA\Property(property="aid", type="string", description="Agent ID"),

    *             @OA\Property(property="cid", type="string", description="Client ID in array format"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Agent Assigned Successfully.",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function assignAgent(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'aid' => "required",

                'cid' => "required|array|unique:assign_clients,cid",

            ],

            [

                'aid.required' => 'The Agent Id field is required.',

                'cid.required' => 'The Client Id field is required.',

            ]

        );



        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }



        // Extract data from the request

        $agentId = $request->aid;

        $clientIds = $request->cid;



        $matchagentid = Agent::select('agent_id')->where('agent_id',$agentId)->first();

        if($matchagentid == true){

            // Prepare data for insertion

            $data = [];
    
            foreach ($clientIds as $clientId) {

                $data[] = [

                    'aid' => $agentId,

                    'cid' => $clientId,

                ];

            }

            // Perform the database insertion

            if (!empty($data)) {

                $message = "Assigned Successfully.";

                AssignClient::insert($data);

                self::agentlogs($agentId,$clientIds, $message);

                $return['code']    = 200;

                $return['message'] = 'Agent Assigned Successfully.';

            } else {

                $return['code']    = 101;

                $return['message'] = 'Something went wrong!';

            }

        }else{

                $return['code']    = 101;

                $return['message'] = 'Invalid Agent Id!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // transfer single client && transfer all client

     /**

    * @OA\Post(

    *     path="/api/admin/agent/transfer-client",

    *     summary="Transfer Client To Other Agent",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"transfer_agent_id,transfer_client_id,previous_agent_id,action"},

    *             @OA\Property(property="transfer_agent_id", type="string", description="Transfer Agent ID"),

    *             @OA\Property(property="transfer_client_id", type="string", description="Transfer Client ID in array format or single transfer client without array"),

    *             @OA\Property(property="previous_agent_id", type="string", description="Previous Agent ID"),

    *             @OA\Property(property="action", type="string", description="Action save data- transfer-all or 1 for single"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="All Client Transfered Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function transferClient(Request $request)

    {

        $agentId = $request->transfer_agent_id;

        $clientIds = $request->transfer_client_id;

        $previous_agent_id = $request->previous_agent_id;

        $action = $request->action;

        $data = [];

        $matchagentid = Agent::select('agent_id')->where('agent_id', $agentId)->first();

        $validator = Validator::make(

            $request->all(),

            [

                'transfer_agent_id' => "required",

                'transfer_client_id' => "required",

                'previous_agent_id' => 'required',

                'action' => 'required'

            ]

        );



        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }

        if ($agentId == $previous_agent_id) {

            $return['code']    = 101;

            $return['message'] = 'This client already assign for selected agent!';

        } else if(!$matchagentid){

            $return['code']    = 101;

            $return['message'] = 'Invalid Transfer Agent Id!';

        }

         else {

            if ($action == 1) {

                $matchagent = AssignClient::select('cid')->where('cid', $clientIds)->first();

                if ($matchagent) {

                    $transfer = AssignClient::where('cid', $clientIds)->update([

                        'aid' => $agentId

                    ]);

                    if ($transfer) {

                        $newagent_id = $agentId;

                        self::agentlogs($agentId = $previous_agent_id , [$clientIds], "Client Trasfer to-" ." ".$newagent_id." Successfully.");

                        self::agentlogs($agentId = $newagent_id, [$clientIds], "Assigned Successfully.");

                        $return['code']    = 200;

                        $return['message'] = 'Client Transfered Successfully!';

                    } else {

                        $return['code']    = 101;

                        $return['message'] = 'Something went wrong!';

                    }

                } else {

                    $return['code']    = 401;

                    $return['message'] = 'Invalid previous agent id!';

                }

            } else if ($action == 'transfer-all') {

                foreach ($clientIds as $clientId) {

                    $data[] = [

                        'cid' => $clientId,

                    ];

                }

                if (!empty($data)) {

                    $transfer = AssignClient::whereIn('cid', $data)->update([

                        'aid' => $agentId

                    ]);

                    if ($transfer) {

                        $newagent_id = $agentId;

                        self::agentlogs($agentId = $previous_agent_id , $clientIds, "Client Trasfer to-" ." ".$newagent_id." Successfully.");

                        self::agentlogs($agentId = $newagent_id, $clientIds, "Assigned Successfully.");

                        $return['code']    = 200;

                        $return['message'] = 'All Client Transfered Successfully!';

                    } else {

                        $return['code']    = 101;

                        $return['message'] = 'Something went wrong!';

                    }

                } else {

                    $return['code']    = 101;

                    $return['message'] = 'Transfer client id is empty!';

                }

            } else {

                $return['code']    = 101;

                $return['message'] = 'This action type not match!';

            }

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // agents log api

     /**

    * @OA\Post(

    *     path="/api/admin/agent/get-logs",

    *     summary="Get Agents Log",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id,lim,page"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             @OA\Property(property="lim", type="string", description="Limit"),

    *             @OA\Property(property="page", type="string", description="Page"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Agent Log Fetched Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Data Not Found!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function getagentslogs(Request $request)

    {

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $agent_id = $request->agent_id;

        $validator = Validator::make(

            $request->all(),

            [

                'page' => "required",

                'lim' => "required",

                'agent_id' => "required"

            ]

        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }

        $check = AgentClientLog::select('agent_id')->where('agent_id', $agent_id)->first();

        if ($check == true) {

            $getList = AgentClientLog::join('users', 'agent_client_logs.client_id', '=', 'users.uid')

            ->select('agent_client_logs.*', 'users.email as client_email', DB::raw("CONCAT(ss_users.first_name, ' ', ss_users.last_name) as full_name"))

            ->where('agent_id', $agent_id)

            ->orderByDesc('agent_client_logs.id');

            $row = $getList->count();

            $data = $getList->offset($start)->limit($limit)->get();

            if (!empty($data)) {

                $return['code']    = 200;

                $return['data']    = $data;

                $return['row']    = $row;

                $return['message'] = 'Agent Log Fetched Successfully!';

            } else {

                $return['code']    = 101;

                $return['message'] = 'Data Not Found!';

            }

        } else {

            $return['code']    = 101;

            $return['message'] = 'Invalid Agent Id!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // assign agent to client in manage advertiser & manage publisher func

     /**

    * @OA\Post(

    *     path="/api/admin/agent/assign-agent-client",

    *     summary="Assign Agent client on manage advertiser & publisher client list",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"agent_id,client_id"},

    *             @OA\Property(property="agent_id", type="string", description="Agent ID"),

    *             @OA\Property(property="client_id", type="string", description="Client ID"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Agent Assigned Successfully.",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Something went wrong!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function assignAgentToClient(Request $request)

    {

        $agentId = $request->agent_id;

        $clientIds = $request->client_id;

        $validator = Validator::make(

            $request->all(),

            [

                'agent_id' => 'required',

                'client_id' => 'required|unique:assign_clients,cid',

            ]

        );



        if ($validator->fails()) {

            $return['code'] = 100;

            $return['error'] = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }



        $matchclientid = User::select('uid')->where('uid', $clientIds)->first();

        $matchagenttid = Agent::select('agent_id')->where('agent_id', $agentId)->first();

        $matchaccounttp = User::select('uid', 'account_type')->where('uid', $clientIds)->where('account_type', 0)->first();



        if (!$matchclientid) {

            $return['code'] = '101';

            $return['message'] = 'Invalid Client Id';

        } else if (!$matchagenttid) {

            $return['code'] = '101';

            $return['message'] = 'Invalid Agent Id';

        } else if (!$matchaccounttp) {

            $return['code'] = '101';

            $return['message'] = 'This Client Id is Inhouse User!';

        } else {

            $assign = new AssignClient();

            $assign->aid = $agentId;

            $assign->cid = $clientIds;

            if ($assign->save()) {

                $message = 'Assigned Successfully.';

                self::agentlogs($agentId, [$clientIds], $message);

                $return['code']    = 200;

                $return['message'] = 'Agent Assigned Successfully.';

            } else {

                $return['code']    = 101;

                $return['message'] = 'Something went wrong!';

            }

        }



        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // client logs api

      /**

    * @OA\Post(

    *     path="/api/admin/agent/client-logs",

    *     summary="Get Clients Logs",

    *     tags={"Manage Admin Agents"},

       *     @OA\RequestBody(

    *         required=true,

    *         @OA\MediaType(

    *             mediaType="multipart/form-data",

    *             @OA\Schema(

    *                required={"client_id,lim,page"},

    *             @OA\Property(property="client_id", type="string", description="Client ID"),

    *             @OA\Property(property="lim", type="string", description="Limit"),

    *             @OA\Property(property="page", type="string", description="Page"),

    *             ),

    *         ),

    *     ),

    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

     *     @OA\Parameter(name="Authorization", in="header", required=true, description="Authorization [admin]",

    *         @OA\Schema(

    *             type="string"

    *         )

    *     ),

    *     @OA\Response(

    *         response=200,

    *         description="Client Log Fetched Successfully!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string"),

    *     ),

    *     ),

    *     @OA\Response(

    *         response=101,

    *         description="Data Not Found!",

    *         @OA\JsonContent(

    *             @OA\Property(property="code", type="integer"),

    *             @OA\Property(property="message", type="string")

    *         )

    *     )

    * )

    */

    public function getclientlogs(Request $request)

    {

        $limit = $request->lim;

        $page = $request->page;

        $pg = $page - 1;

        $start = ($pg > 0) ? $limit * $pg : 0;

        $client_id = $request->client_id;

        $validator = Validator::make(

            $request->all(),

            [

                'page' => "required",

                'lim' => "required",

                'client_id' => "required"

            ]

        );

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['message'] = 'Validation Error!';

            return json_encode($return);

        }

        $check = AgentClientLog::select('client_id')->where('client_id', $client_id)->first();

        if ($check == true) {

            $getList =  AgentClientLog::join('agents', 'agent_client_logs.agent_id', '=', 'agents.agent_id')

            ->select('agent_client_logs.*','agents.name as agent_name','agents.email as agent_email')

            ->where('client_id', $client_id)

            ->orderByDesc('agent_client_logs.id');

            $row = $getList->count();

            $data = $getList->offset($start)->limit($limit)->get();

            if (!empty($data)) {

                $return['code']    = 200;

                $return['data']    = $data;

                $return['row']    = $row;

                $return['message'] = 'Client Log Fetched Successfully!';

            } else {

                $return['code']    = 101;

                $return['message'] = 'Data Not Found!';

            }

        } else {

            $return['code']    = 101;

            $return['message'] = 'Invalid Client Id!';

        }

        return json_encode($return, JSON_NUMERIC_CHECK);

    }



    // manage log function

    static function agentlogs($agentId, $clientIds, $message)

    {

        $logs = [];

        foreach($clientIds as $client_id){

            $logs[] = [

                'agent_id' => $agentId,

                'client_id' => $client_id,

                'message' => $message

            ];

        }

        if(!empty($logs)){

            AgentClientLog::insert($logs);

            return 'Log Created Successfully!';
        }
    }
}
