<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use App\Services\MemberService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    private MemberService $memberService;
    public function __construct(MemberService $memberService){
        $config = [
            'get_members_of_room' => [
                'access'     => ['room_id'],
                'validate'   => ['room_id' =>'required|numeric'],
                'permission' => ['checkRoomPermission'],
            ],
            'set_unread'   => [
                'access'     => ['room_id', 'position'],
                'validate'   => [ 'room_id'  =>'required|numeric', 'position' =>'numeric'],
                'permission' => ['checkRoomPermission'],
            ],
        ];

        $this->memberService     = $memberService;

        $this->memberService->setConfig($config);
    }

    public function getMembers($roomId){
        // set action
        $this->memberService->setAction('get_members_of_room');

        // check permission request
        if(!$this->memberService->checkPermission(['room_id'=> $roomId])){
            throw new \Exception('User not permission !');
        }

        return $this->responseSync($this->memberService->getMembers($roomId));
    }

    public function setUnread(Request $request){
        // set action
        $this->memberService->setAction('set_unread');

        // get and process request
        $data = $this->memberService->processRequest($request->input());

        // check permission request
        if(!$this->memberService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->memberService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            $data['user_id'] = $data['auth_id'];
            if($key = $this->memberService->updateUnread($data)){
                return $this->responseSync($this->memberService->getRepo()->getObject('MEMBER', $key));
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }
}
