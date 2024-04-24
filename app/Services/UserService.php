<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\RedisInterface;
use Illuminate\Support\Facades\Auth;

class UserService extends Service
{
    public function getAllUser($data){
        $users  = [];
        $listUser = $myUser = $this->repo->getObject('MY_USER', $data['auth_id']);
        $listUser = array_map(function ($userId){
            return [$userId => User::find($userId)->toArray()];
        }, $listUser['MY_USER'][$data['auth_id']]);

        foreach ($listUser as $key => $user){
            $users = array_replace($users, $user);
        }

        return array_merge($myUser, ['USER' => $users]);
    }

    public function getUser($data){
        $user = User::find($data['user_id']);
        if(!empty($user)){
            return ['USER' => [$data['auth_id'] => $user]];
        }

        return null;
    }

    public function addUser($data){
        $user = User::find($data['user_id'])->toArray();
        if(!empty($user)) {
            $this->repo->pushList('MY_USER', $data['auth_id'], null, $data['user_id']);
            $userData = ['USER' => [$data['user_id'] => $user]];

            return array_merge($this->repo->getObject('MY_USER', $data['auth_id']), $userData);
        }

        return false;
    }

    public function updateUser($data){
        $user = User::find($data['user_id']);
        if(!empty($user)){
            $user->update($data);
            $userData = ['USER' => [$data['auth_id'] => User::find($data['user_id'])]];
            return array_merge($this->repo->getObject('MY_USER', $data['auth_id']), $userData);
        }

        return false;
    }

    public function deleteUser($data){
        $user = User::find($data['user_id']);
        if(!empty($user)){
            $this->repo->removeList('MY_USER', Auth::id(), $user->id);
            $userData =   $userData = ['USER' => [$data['auth_id'] => null]];
            return array_merge($this->repo->getObject('MY_USER', $data['auth_id']), $userData);
        }

        return false;
    }
}
