<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller{
    private UserService $userService;
    public function __construct(UserService $userService){
        $config = [
            'get_user' => [
                'access'     => ['user_id'],
                'validate'   => [],
                'permission' => ['checkRoleDeleteUser'],
            ],
            'add_user' => [
                'access'     => ['name', 'icon', 'cover', 'company', 'phone', 'address'],
                'validate'   => ['user_id' => 'required|numeric'],
                'permission' => ['checkUserInCompany'],
            ],
            'update_user' => [
                'access'     => ['user_id', 'name', 'icon', 'cover', 'company', 'phone', 'address'],
                'validate'   => ['user_id' => 'required|numeric', 'name' => 'required'],
                'permission' => ['checkRoleUpdateUser'],
            ],
            'delete_user' => [
                'access'     => ['user_id'],
                'validate'   => ['user_id' => 'required|numeric'],
                'permission' => ['checkRoleDeleteUser'],
            ],
        ];

        $this->userService = $userService;

        $this->userService->setConfig($config);
    }

    public function getAllUser(){
        $this->userService->setAction('get_user');
        $data = $this->userService->processRequest([]);
        return $this->responseSync($this->userService->getAllUser($data));
    }

    public function getUserSetting(){
        $myNavigator = [Auth::id() => ['1', '2', '3', '4', '5', '6']];
        $navigator   = [
            '1'=>[
                'group'=> 1,
                'name'=>  'APP_HOME_PAGE',
                'icon'=>  'bi-house',
                'title'=> 'Goto Home Page',
                'notify'=> 0
            ],
            '2'=>[
                'group'=> 1,
                'name'=>  'APP_OPEN_CHAT',
                'icon'=>  'bi-chat-text',
                'title'=> 'Open MyChat',
                'active'=> 1,
                'notify'=> 2,
            ],
            '3'=>[
                'group'=> 1,
                'name'=>  'APP_OPEN_CONTACT',
                'icon'=>  'bi-person-lines-fill',
                'title'=> 'Open Contact',
                'notify'=> 2,
            ],
            '4'=>[
                'group'=> 2,
                'name'=> 'APP_OPEN_FILES',
                'icon'=> 'bi-file-earmark',
                'notify'=> 0,
            ],
            '5'=>[
                'group'=> 2,
                'name'=> 'APP_OPEN_SETTING',
                'icon'=> 'bi-gear',
                'notify'=> 0,
            ],
            '6'=>[
                'group'=> 2,
                'name'=> 'APP_OPEN_TUTORIAL',
                'icon'=> 'bi-info-square',
                'notify'=> 0,
            ]
        ];
        $data = [];
        foreach ($navigator as $key => $value){
            $data[Auth::id() . '_' . $key] = $value;
        }

        return $this->responseSync(array_merge(['MY_NAVIGATOR' => $myNavigator], ['NAVIGATOR' => $data]));
    }

    public function getUser(Request $request){
        // set action
        $this->userService->setAction('add_user');

        // get and process request
        $data = $this->userService->processRequest($request->input());

        // check permission request
        if(!$this->userService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        return $this->responseSync($this->userService->getUser($data));
    }

    public function addUser(Request $request){
        // set action
        $this->userService->setAction('add_user');

        // get and process request
        $data = $this->userService->processRequest($request->input());

        // check permission request
        if(!$this->userService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->userService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($result = $this->userService->addUser($data)){
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function updateUser(Request $request){
        // set action
        $this->userService->setAction('update_user');

        // get and process request
        $data = $this->userService->processRequest($request->input());

        // check permission request
        if(!$this->userService->checkPermission($data)){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->userService->validateData($data);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($result = $this->userService->updateUser($data)){
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }

    public function deleteUser($userId){
        // set action
        $this->userService->setAction('delete_user');

        // check permission request
        if(!$this->userService->checkPermission(['user_id'=> $userId])){
            throw new \Exception('User not permission !');
        }

        // validate request
        $validator = $this->userService->validateData(['user_id'=> $userId]);
        if(!$validator || $validator->fails()){
            return $this->responseError($validator->errors(), self::VALIDATE_ERROR_CODE);
        }else{
            // save and response data
            if($result = $this->userService->deleteUser($userId)){
                return $this->responseSync($result);
            }else{
                return $this->responseError([], self::PROCESS_ERROR_CODE);
            }
        }
    }
}
