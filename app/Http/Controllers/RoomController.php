<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use App\Services\MemberService;
use App\Services\SocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    private RoomService $roomService;
    private MemberService $memberService;
    private SocketService $socketService;
    public function __construct(
        SocketService $socketService,
        RoomService $roomService,
        MemberService $memberService){
        $config = [
            'get_all_room' => [],
            'get_room' => [
                'access'     => [],
                'validate'   => [],
                'permission' => ['checkRoomPermission'],
            ],
            'add_room' => [
                'access'     => ['name', 'description', 'icon', 'members'],
                'validate'   => ['name' => 'required'],
                'permission' => ['checkMemberInContact'],
            ],
            'update_room' => [
                'access'     => ['room_id', 'name', 'description', 'icon', 'members'],
                'validate'   => ['room_id' => 'required|numeric', 'name' => 'required'],
                'permission' => ['checkRoomPermission', 'checkMemberInContact'],
            ],
            'delete_room' => [
                'access'     => ['room_id'],
                'validate'   => ['room_id' => 'required|numeric'],
                'permission' => ['checkRoomPermission'],
            ],
        ];

        $this->socketService = $socketService;
        $this->roomService   = $roomService;
        $this->memberService = $memberService;

        $this->roomService->setConfig($config);
    }

    public function getAllRoom(){
        // set action
        $this->roomService->setAction('get_all_room');

        // get and process request
        $data = $this->roomService->processRequest([]);

        return $this->responseSync($this->roomService->getAllRoom($data));
    }

    public function getRoom(Request $request){
        // set action
        $this->roomService->setAction('get_room');

        // get and process request
        $data = $this->roomService->processRequest($request->input());

        // check permission request
        if(!$this->roomService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        return $this->responseSync($this->roomService->getRoom($data));
    }

    public function addRoom(Request $request){
        // set action
        $this->roomService->setAction('add_room');

        // get and process request
        $data = $this->roomService->processRequest($request->input());

        // check permission request
        if(!$this->roomService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->roomService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($key = $this->roomService->addRoom($data)){
                // add members in room
                $this->memberService->addMembersIntoRoom($key, $data['members']);
                $room       = $this->roomService->getRepo()->getObject('ROOM', $key);
                $roomMember = $this->roomService->getRepo()->getObject('ROOM_MEMBER', $key);
                $members    = $this->memberService->getMembersByUserIds($key, $data['members']);

                foreach ($data['members'] as $member){
                    $myRoom = $this->roomService->getRepo()->getObject('MY_ROOM', $member);
                    $result = array_merge($room, $roomMember, $members , $myRoom);
                    $this->socketService->emit([
                        'channel' => 'USER_' . $member,
                        'event'   => 'user_add_room',
                        'data'    => $result
                    ]);
                }

                return $this->responseSuccess(true, 'add room success');
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function updateRoom(Request $request){
        // set action
        $this->roomService->setAction('update_room');

        // get and process request
        $data = $this->roomService->processRequest($request->input());

        // check permission request
        if(!$this->roomService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->roomService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{

            // save and response data
            if($key = $this->roomService->updateRoom($data)){
                $result = array_merge(
                    $this->roomService->getRepo()->getObject('MY_ROOM', Auth::id()),
                    $this->roomService->getRepo()->getObject('ROOM', $key)
                );
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function deleteRoom($roomId){
        // set action
        $this->roomService->setAction('delete_room');

        // check permission request
        $data = $this->roomService->processRequest(['room_id'=> $roomId]);
        if(!$this->roomService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->roomService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{

            // save and response data
            if($key = $this->roomService->deleteRoom($data)){
                $result = array_merge(
                    $this->roomService->getRepo()->getObject('MY_ROOM', $data['auth_id']),
                    ['ROOM' => [$key => null]]
                );
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }
}
