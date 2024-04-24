<?php

namespace App\Services;

use App\Repositories\RepoInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Service {
    use PermissionService;
    private array  $config = [];
    private string $action = '';

    protected RepoInterface $repo;
    public function __construct(RepoInterface $repo){
        $this->repo = $repo;
    }

    public function setConfig($config){
        $this->config = $config;
    }
    public function setAction($action){
        $this->action = $action;
    }
    public function getConfig(){
        return $this->config;
    }
    public function getAction(){
        return $this->action;
    }
    public function getRepo(){
        return $this->repo;
    }

    // filter param match config
    public function processRequest($data){
        if($this->checkConfig()){
            $response = ['auth_id' => Auth::id()];
            if(isset($this->config[$this->action]['access'])){
                $map = array_map(function ($item) use ($data){
                    if(isset($data[$item])){
                        return [$item => $data[$item]];
                    }
                }, $this->config[$this->action]['access']);
                foreach($map as $value){
                    if($value !== null){
                        $response = array_merge($response, $value);
                    }
                }
            }
            return $response;
        }
        return [];
    }
    // validate request param
    public function validateData($data){
        if($this->checkConfig()) {
            if(isset($this->config[$this->action]['validate'])) {
                return Validator::make($data, $this->config[$this->action]['validate'] );
            }else{
                return true;
            }
        }
        return false;
    }
    // check permissions action
    public function checkPermission($request){
        if($this->checkConfig()){
            if(isset($this->config[$this->action]['permission'])){
                foreach ($this->getConfig()[$this->getAction()]['permission'] as $permission){
                    return $this->$permission($request);
                }
            }
            return true;
        }
        return false;
    }

    public function getObjectsByList($object, $list, $prefix = null){
        $response = [];
        $objects = array_map(function ($id) use ($object, $prefix){
            $key = empty($prefix)? $id: $prefix.'_'.$id;
            return $this->repo->getObject($object, $key);
        }, $list);
        foreach ($objects as $key => $value){
            $response = array_replace($response, $value[$object]);
        }
        return [$object => $response];
    }

    private function checkConfig(){
        if(!empty($this->config) && !empty($this->action) && isset($this->config[$this->action])){
            return true;
        }
    }
}
