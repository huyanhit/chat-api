<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class FileController extends Controller{
    private FileService $fileService;
    public function __construct(FileService $fileService){
        $config = [
            'get_my_file' => [
                'access'     => [],
                'validate'   => [],
                'permission' => ['checkUserGetFile'],
            ],
            'get_room_file' => [
                'access'     => ['room_id'],
                'validate'   => ['room_id' => 'required|numeric'],
                'permission' => ['checkUserGetFile', 'checkRoomPermission'],
            ],
            'get_file_thumbnail' => [
                'access'     => ['file_id', 'token'],
                'validate'   => ['file_id' => 'required|numeric', 'token' => 'required'],
                'permission' => ['checkUserGetFile'],
            ],
            'get_file_raw' => [
                'access'     => ['file_id', 'token'],
                'validate'   => ['file_id' => 'required|numeric', 'token' => 'required'],
                'permission' => ['checkUserGetFile'],
            ],
            'upload_file' => [
                'access'     => ['file'],
                'permission' => ['checkUserGetFile'],
            ],
            'add_file' => [
                'access'     => ['room_id', 'message_id', 'file'],
                'validate'   => ['user_id' => 'required|numeric'],
                'permission' => ['checkUserInCompany'],
            ],
        ];

        $this->fileService = $fileService;
        $this->fileService->setConfig($config);
    }

    public function getMyFile(){
        $this->fileService->setAction('get_my_file');
        $data = $this->fileService->processRequest([]);
        if(!$this->fileService->checkPermission($data)){
            throw new \Exception('File not permission !');
        }

        return $this->responseSync($this->fileService->getMyFile($data));
    }

    public function getRoomFile($roomId){
        $this->fileService->setAction('get_room_file');
        $data = $this->fileService->processRequest(['room_id'=> $roomId]);
        if(!$this->fileService->checkPermission($data)){
            throw new \Exception('File not permission !');
        }
        return $this->responseSync($this->fileService->getRoomFile($data));
    }

    public function uploadFiles(Request $request){
        // set action
        $this->fileService->setAction('upload_file');

        // get and process request
        $data = $this->fileService->processRequest($request->all());

        // check permission request
        if(!$this->fileService->checkPermission($data)){
            throw new \Exception('File not permission !');
        }

        $fileKeys    = $this->fileService->uploadFiles($data);
        $objectFiles = array_merge(
            $this->fileService->getLastListMyFile($data),
            $this->fileService->getObjectsByList('FILE', $fileKeys)
        );

        return $this->responseSync($objectFiles);
    }

    public function getFileThumbnail($fileId, $token){
        // process token
        $token = PersonalAccessToken::findToken($token);
        $user = $token->tokenable;

        // set action
        $this->fileService->setAction('get_file_thumbnail');

        // check permission request
        if(!$this->fileService->checkPermission($fileId)){
            throw new \Exception('User not permission !');
        }

        // validate request
        return $this->fileService->getFileThumbnail($fileId);
    }

    public function getFileRaw($fileId, $token){
        // process token
        $token = PersonalAccessToken::findToken($token);
        $user = $token->tokenable;

        // set action
        $this->fileService->setAction('get_file_raw');

        // get and process request
        $data = ['file_id' => $fileId, 'auth_id' => $user->id];
        $data = $this->fileService->processRequest($data);

        //check permission request
        if(!$this->fileService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        return $this->fileService->getFileRaw($fileId);
    }

    public function addFile(Request $request){
        // set action
        $this->fileService->setAction('add_file');

        // get and process request
        $data = $this->fileService->processRequest($request->input());

        // check permission request
        if(!$this->fileService->checkPermission($data)){
            throw new \Exception('File not permission !');
        }

        // validate request
        $validator = $this->fileService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($result = $this->fileService->addFile($data)){
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function deleteFile($fileId){
        // set action
        $this->fileService->setAction('delete_file');

        // check permission request
        if(!$this->fileService->checkPermission(['file_id'=> $fileId])){
            throw new \Exception('File not permission !');
        }

        // validate request
        $validator = $this->fileService->validateData(['file_id'=> $fileId]);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($result = $this->fileService->deleteFile($fileId)){
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }
}
