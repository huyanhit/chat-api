<?php

namespace App\Services;

trait PermissionService{
    protected function checkRoomPermission($request){
        return true;
    }

    protected function checkUserOfMe($request){
        return true;
    }

    protected function checkRoleUpdateUser($request){
        return true;
    }

    protected function checkRoleDeleteUser($request){
        return true;
    }

    protected function checkUserInCompany($request){
        return true;
    }

    protected function checkMemberInContact($request){
        return true;
    }

    protected function checkUserGetFile($request){
        return true;
    }
}
