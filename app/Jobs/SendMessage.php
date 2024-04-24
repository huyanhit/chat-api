<?php

namespace App\Jobs;

use App\Services\SocketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MemberService;
use App\Services\MessageService;
use App\Services\RoomService;
use App\Services\FileService;


class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private array $data;
    private MessageService $messageService;
    private RoomService $roomService;
    private MemberService $memberService;
    private FileService $fileService;
    private SocketService $socketService;

    public function __construct($data){
        $this->data = $data;
    }

    public function handle(
        SocketService $socketService,
        MessageService $messageService,
        RoomService $roomService,
        FileService $fileService,
        MemberService $memberService){

        $this->socketService     = $socketService;
        $this->messageService    = $messageService;
        $this->roomService       = $roomService;
        $this->memberService     = $memberService;
        $this->fileService       = $fileService;

        if($messageId = $this->messageService->addMessage($this->data)){
            $lastMessage = $this->messageService->getRepo()->getLenOfList('ROOM_MESSAGE', $this->data['room_id']);
            $members     = $this->roomService->getRepo()->getObject('ROOM_MEMBER', $this->data['room_id']);
            $hasToAll    = $this->memberService->checkContentHasMentionToAll($this->data['content']);
            foreach ($members['ROOM_MEMBER'][$this->data['room_id']] as $member){
                $hasTo   = $this->memberService->checkContentHasMentionTo($this->data['room_id'], $member, $this->data['content']);
                if($hasToAll || $hasTo){
                    $keyMember  = $this->data['room_id'] .'_'. $member;
                    $object     = $this->memberService->getRepo()->getObject('MEMBER', $keyMember);
                    $roomMember = $object['MEMBER'][$keyMember];
                    $keyMember  = $this->memberService->updateMembers($keyMember, [
                        'mention' => $roomMember['mention'] + 1,
                        'updated' => now()->timestamp,
                    ]);
                    if ($hasTo){
                        $this->socketService->emit([
                            'channel' => 'USER_' . $member,
                            'event'   => 'user_update_member',
                            'data'    => $this->memberService->getRepo()->getObject('MEMBER', $keyMember)]
                        );
                    }
                }
            }

            if($hasToAll){
                $this->socketService->emit([
                    'channel' => 'ROOM_' . $this->data['room_id'],
                    'event'   => 'room_to_all',
                    'data'    => $this->data['room_id']
                ]);
            }

            $fileInsert  = $this->messageService->processFileInContentMessage($this->data, $messageId);
            $files       = $this->messageService->getObjectsByList('FILE', $fileInsert);
            $roomFiles   = $this->fileService->getLastListRoomFile($this->data);
            $messageFile = $this->fileService->getLastListMessageFile(array_merge($this->data, ['message_id' => $messageId]));

            $result = array_merge(
                $files, $roomFiles, $messageFile,
                $this->messageService->getRepo()->getObject('MESSAGE', $messageId),
                $this->messageService->getLastList('ROOM_MESSAGE', $this->data['room_id']),
                $this->memberService->getRepo()->getObject('MEMBER', $this->memberService->updateMembers(
                    $this->data['room_id'] .'_'.$this->data['auth_id'], [
                    'position'=> $lastMessage + 1,
                    'mention' => 0,
                    'updated' => now()->timestamp,
                ])),
                $this->messageService->getRepo()->getObject('ROOM', $this->roomService->updateRoom([
                    'room_id' => $this->data['room_id'],
                    'total' => $lastMessage,
                ])),
            );

            $this->socketService->emit([
                'channel' => 'ROOM_' . $this->data['room_id'],
                'event'   => 'room_push_message',
                'data'    => $result
            ]);

            return $result;
        }

        return null;
    }
}
