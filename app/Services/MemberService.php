<?php
namespace App\Services;

class MemberService extends Service
{

    public function getMembers($roomId){
        $listMember = $this->repo->getObject('ROOM_MEMBER', $roomId);
        return array_merge($listMember,$this->getObjectsByList('MEMBER', $listMember['ROOM_MEMBER'][$roomId], $roomId));
    }

    public function getMembersByUserIds($roomId, $data){
        return $this->getObjectsByList('MEMBER', $data, $roomId);
    }

    public function addMembersIntoRoom($roomId, $members){
        foreach ($members as $member){
            $this->repo->pushList('MY_ROOM', $member, null, $roomId);
            $this->repo->pushList('ROOM_MEMBER', $roomId, null, $member);
            $this->repo->addObject('MEMBER', $roomId.'_'.$member, ['type'=> 1, 'position' => 1]);
        }
    }

    public function updateUnread($data){
        $mention     = 0;
        $key         = $data['room_id'].'_'.$data['user_id'];
        $member      = $this->repo->getObject('MEMBER', $key);
        $roomMessage = range($member['MEMBER'][$key]['position'], $data['position']);
        $messages    = $this->getObjectsByList('MESSAGE', $roomMessage, $data['room_id']);
        foreach ($messages['MESSAGE'] as $message) {
            $hasMention = $this->checkContentHasMention($data['room_id'], $data['user_id'], $message['content']);
            $mention += $hasMention? 1: 0;
        }
        $data['mention']  = $member['MEMBER'][$key]['mention'];
        $data['mention'] += $data['position'] > $member['MEMBER'][$key]['position']? -$mention: $mention;

        return $this->repo->updateObject('MEMBER', $key, $data);
    }

    public function updateMembers($key, $data){
        return $this->repo->updateObject('MEMBER', $key, $data);
    }

    public function checkContentHasMention($roomId, $userId, $content){
        if($this->checkContentHasMentionToAll($content)){
            return true;
        }
        if($this->checkContentHasMentionTo($roomId, $userId, $content)){
            return true;
        }

        return false;
    }

    public function checkContentHasMentionToAll($content){
        if (preg_match('/\\[toall\\]/', $content)) {
            return true;
        }
        return false;
    }

    public function checkContentHasMentionTo($roomId, $userId, $content){
        if (preg_match('/\\[to:' .
            $userId . '\\]|\\[reply mid:[\\d]+ to:' .
            $userId . '( rid:' . $roomId . ')*\\]/', $content)) {
            return true;
        }

        return false;
    }
}
