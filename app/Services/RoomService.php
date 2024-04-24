<?php
namespace App\Services;

use App\Repositories\RedisInterface;
use Illuminate\Support\Facades\Auth;

class RoomService extends Service
{

    public function getAllRoom($data){
        $memberKeys = [];
        $myRoom     = $this->repo->getObject('MY_ROOM', $data['auth_id']);
        $rooms      = $this->getObjectsByList('ROOM', $myRoom['MY_ROOM'][$data['auth_id']]);
        foreach ($myRoom['MY_ROOM'][$data['auth_id']] as $room){
            $memberKeys[] = $room.'_'. $data['auth_id'];
        }

        return array_merge($myRoom, $rooms, $this->getObjectsByList('MEMBER', $memberKeys));
    }

    public function getRoomInfo($data){
        $roomId = $data['room_id'];
        $room_file = $this->repo->getObject('ROOM_FILE', $roomId);

        return array_merge([], $room_file);
    }

    public function addRoom($data){
        $roomId = $this->repo->pushList('MY_ROOM', $data['auth_id']);
        $data   = array_merge($data, ['type' => 1, 'created' => now()->timestamp, 'updated' => now()->timestamp]);

        return $this->repo->addObject('ROOM', $roomId, $data);
    }

    public function updateRoom($data){
        $roomId = $data['room_id'];
        $data   = array_merge($data, ['updated' => now()->timestamp]);

        return $this->repo->updateObject('ROOM', $roomId, $data);
    }

    public function deleteRoom($data){
        $this->repo->removeList('MY_ROOM', $data['auth_id'], null, $data['room_id']);

        return $data['room_id'];
    }
}
