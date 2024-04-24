<?php
namespace App\Services;

use App\Repositories\RedisInterface;
use Illuminate\Support\Facades\Auth;

class MessageService extends Service
{
    const MAX_LIST_MESSAGE       = 30;
    const NUMS_LOAD_MORE_MESSAGE = 10;
    const GET_MORE_MESSAGES_UP   = 1;
    const GET_MORE_MESSAGES_DOWN = 2;

    const FILE    = '/\[file:[\d]+\]|\[img:[\d]+\]/';
    const FILE_ID = '/[\d]+/';

    public function getMessages($data){
        if(empty($data['type'])){
            $positions   = $this->getPositionsById($data['room_id'], $data['position']);
            $roomMessage = $this->repo->getList('ROOM_MESSAGE', $data['room_id'], null, $positions['start'], $positions['end']);
        }else{
            $positions   = $this->getPositionsLoadMoreById($data['room_id'], $data['position'], $data['type']);
            $roomMessage = $this->repo->getList('ROOM_MESSAGE', $data['room_id'], null, $positions['start'], $positions['end']);

            if($data['type'] == self::GET_MORE_MESSAGES_UP){
                $end = $positions['start'] + self::MAX_LIST_MESSAGE;
                $roomMessage = $this->repo->getList('ROOM_MESSAGE', $data['room_id'], null, $positions['start'], $end);
            }
            if($data['type'] == self::GET_MORE_MESSAGES_DOWN){
                $start = $positions['end'] - self::MAX_LIST_MESSAGE;
                $start = ($start > 0) ? $start: 0;
                $roomMessage = $this->repo->getList('ROOM_MESSAGE', $data['room_id'], null, $start, $positions['end']);
            }
        }

        return array_merge(['ROOM_MESSAGE' => [$data['room_id'] => $roomMessage]], $this->getObjectsByList('MESSAGE', $roomMessage, $data['room_id']));
    }

    public function getMessage($data){
        $roomId    = $data['room_id'];
        $messageId = $data['message_id'];
        $key       = $roomId.'_'.$messageId;

        return $this->repo->getObject('MESSAGE', $key);
    }

    public function addMessage($data){
        $roomId    = $data['room_id'];
        $messageId = $this->repo->pushListAutoIncrement('ROOM_MESSAGE', $roomId);
        $key       = $roomId.'_'.$messageId;
        $data      = array_merge($data, ['status' => 1, 'auth' => $data['auth_id'], 'created' => now()->timestamp, 'updated' => now()->timestamp]);
        $messageId = $this->repo->addObject('MESSAGE', $key, $data);
        return $messageId;
    }

    public function updateMessage($data){
        $roomId    = $data['room_id'];
        $messageId = $data['message_id'];
        $key       = $roomId.'_'.$messageId;
        $data      = array_merge($data, ['status' => 2, 'updated' => now()->timestamp]);

        return $this->repo->updateObject('MESSAGE', $key, $data);
    }

    public function deleteMessage($roomId, $messageId){
        $key     = $roomId.'_'.$messageId;
        $data    = ['status' => 0, 'content'=> '', 'updated' => now()->timestamp];

        return $this->repo->updateObject('MESSAGE',$key, $data);
    }

    public function getLastList($object, $roomId, $nums = - self::MAX_LIST_MESSAGE){
        return [$object => [$roomId => $this->repo->getList($object, $roomId, null, $nums, -1)]];
    }

    public function processFileInContentMessage($data, $messageId){
        $files = [];
        $content = $data['content'];
        preg_match_all(self::FILE, $content, $matches) ;
        foreach($matches[0] as $match){
            preg_match(self::FILE_ID, $match, $idMatch);
            $files[] = $idMatch[0];
            $this->repo->pushList('ROOM_FILE', $data['room_id'],null, $idMatch[0]);
            $this->repo->pushList('MESSAGE_FILE', $data['room_id'].'_'.$messageId,null, $idMatch[0]);
        }

        return $files;
    }

    private function getPositionsById($roomId, $id){
        if(empty($id)){
            $keyMember = $roomId.'_'.Auth::id();
            $member = $this->repo->getObject('MEMBER', $keyMember);
            if(isset($member['MEMBER'][$keyMember])){
                $id = $member['MEMBER'][$keyMember]['position'];
            }
        }

        $lastId = $this->repo->getLenOfList('ROOM_MESSAGE', $roomId);
        $start  = $id - floor(self::MAX_LIST_MESSAGE/2);
        $end    = $id + floor(self::MAX_LIST_MESSAGE/2);

        if($end >= $lastId){
            $start = $lastId - self::MAX_LIST_MESSAGE;
        }
        if($start < 0){
            $start = 0;
        }

        return ['start' => $start, 'end' => $start + self::MAX_LIST_MESSAGE];
    }

    private function getPositionsLoadMoreById($roomId, $id, $type){
        $start = $end = 0;
        if($type == self::GET_MORE_MESSAGES_UP){
            $start = ($id - self::NUMS_LOAD_MORE_MESSAGE > 0) ? ($id - self::NUMS_LOAD_MORE_MESSAGE - 1) : 0;
        }else if($type == self::GET_MORE_MESSAGES_DOWN){
            $start = $id - 1;
        }

        return ['start' => $start, 'end' => $start + self::NUMS_LOAD_MORE_MESSAGE];
    }
}
