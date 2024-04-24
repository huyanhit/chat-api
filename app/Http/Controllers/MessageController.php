<?php

namespace App\Http\Controllers;

use App\Services\MemberService;
use App\Services\MessageService;
use App\Services\RoomService;
use App\Jobs\SendMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private MessageService $messageService;
    private RoomService $roomService;
    private MemberService $memberService;
    public function __construct(
        MessageService $messageService,
        RoomService $roomService,
        MemberService $memberService){

        $config = [
            'get_messages' => [
                'access'     => ['room_id', 'message_id', 'position', 'type'],
                'validate'   => ['room_id' => 'required|numeric', 'position'  => 'numeric', 'type' => 'numeric'],
                'permission' => ['checkRoomPermission'],
            ],
            'add_message' => [
                'access'     => ['room_id', 'content', 'name', 'description', 'icon'],
                'validate'   => ['room_id' => 'required|numeric', 'content' => 'required'],
                'permission' => ['checkRoomPermission'],
            ],
            'update_message' => [
                'access'     => ['room_id', 'message_id', 'content', 'name', 'description', 'icon'],
                'validate'   => ['room_id' => 'required|numeric', 'message_id' => 'required|numeric', 'content' => 'required'],
                'permission' => ['checkRoomPermission'],
            ],
            'delete_message' => [
                'access'     => ['room_id', 'message_id'],
                'validate'   => ['room_id' => 'required|numeric', 'message_id' => 'required|numeric'],
                'permission' => ['checkRoomPermission'],
            ],
        ];

        $this->messageService    = $messageService;
        $this->roomService       = $roomService;
        $this->memberService     = $memberService;

        $this->messageService->setConfig($config);
    }

    public function getMessages($roomId, $position = 0, $type = 0){
        $data = ['room_id' => $roomId, 'position' => $position, 'type' => $type];

        $this->messageService->setAction('get_messages');

        $data = $this->messageService->processRequest($data);

        if(!$this->messageService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }
        $validator = $this->messageService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            return $this->responseSync($this->messageService->getMessages($data));
        }
    }

    public function addMessage(Request $request){

        // set action
        $this->messageService->setAction('add_message');

        // get and process request
        $data = $this->messageService->processRequest($request->input());

        // check permission request
        if(!$this->messageService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->messageService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            $this->dispatchSync((new SendMessage($data))->onQueue('message'));
            return $this->responseSync(true);
        }
    }

    public function updateMessage(Request $request){

        // set action
        $this->messageService->setAction('update_message');

        // get and process request
        $data = $this->messageService->processRequest($request->input());

        // check permission request
        if(!$this->messageService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->messageService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{

            // save and response data
            if($key = $this->messageService->updateMessage($data)){
                return $this->responseSync($this->messageService->getRepo()->getObject('MESSAGE', $key));
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function deleteMessage($roomId, $messageId){

        // set action
        $this->messageService->setAction('delete_message');

        // get and process request
        $data = ['room_id' => $roomId,'message_id' => $messageId];
        $data = $this->messageService->processRequest($data);

        // check permission request
        if(!$this->messageService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->messageService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{

            // save and response data
            if($key = $this->messageService->deleteMessage($roomId, $messageId)){
                return $this->responseSync($this->messageService->getRepo()->getObject('MESSAGE', $key));
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }
}
