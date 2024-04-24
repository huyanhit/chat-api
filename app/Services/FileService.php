<?php
namespace App\Services;

use App\Models\File;
use App\Repositories\RedisInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Image;

class FileService extends Service
{
    const COMPANY_LAMPART_ID = 1;
    const LIST_ITEM_FILE     = 15;
    const STORE_FILE         = 'local';
    const THUMBNAIL_SEPARATE = 'thumb_';

    public function getMyFile($data){
        $myFile = $this->getLastListMyFile($data);
        return array_merge($myFile, $this->getObjectsByList('FILE', $myFile['MY_FILE'][$data['auth_id']]));
    }

    public function getRoomFile($data){
        $roomFile = $this->getLastListRoomFile($data);
        return array_merge($roomFile, $this->getObjectsByList('FILE', $roomFile['ROOM_FILE'][$data['room_id']]));
    }

    public function getLastListMyFile($data){
        return ['MY_FILE' => [$data['auth_id'] =>
            $this->repo->getList('MY_FILE',  $data['auth_id'], null, -self::LIST_ITEM_FILE, -1)]];
    }

    public function getLastListRoomFile($data){
        return ['ROOM_FILE' => [$data['room_id'] =>
            $this->repo->getList('ROOM_FILE',  $data['room_id'], null, -self::LIST_ITEM_FILE, -1)]];
    }

    public function getLastListMessageFile($data){
        return ['MESSAGE_FILE' => [$data['message_id'] =>
            $this->repo->getList('MESSAGE_FILE',  $data['room_id'].'_'.$data['message_id'], null, -self::LIST_ITEM_FILE, -1)]];
    }

    public function getFileThumbnail($fileId){
        $objectFile = $this->repo->getObject('FILE', $fileId);
        if($objectFile['FILE'][$fileId]['type'] === 'image'){
            $path = self::THUMBNAIL_SEPARATE.$objectFile['FILE'][$fileId]['path'];
            $file = Storage::disk($objectFile['FILE'][$fileId]['store'])->get($path);
            $type = Storage::mimeType($path);

            return response($file)->header('Content-Type', $type);
        }

        return null;
    }

    public function getFileRaw($fileId){
        $objectFile = $this->repo->getObject('FILE', $fileId);
        $file = Storage::disk($objectFile['FILE'][$fileId]['store'])->get($objectFile['FILE'][$fileId]['path']);
        $type = Storage::mimeType($objectFile['FILE'][$fileId]['path']);

        return response($file)->header('Content-Type', $type);
    }

    public function addFile($data){
        $file = File::find($data['file_id'])->toArray();
        if(!empty($file)) {
            $fileData = ['FILE' => [$data['file_id'] => $file]];
            return array_merge($this->repo->getObject('MY_FILE', $data['auth_id']), $fileData);
        }

        return false;
    }

    public function updateFile($data){
        $file = File::find($data['file_id']);
        if(!empty($file)){
            $file->update($data);
            $fileData = ['FILE' => [$data['auth_id'] => File::find($data['file_id'])]];
            return array_merge($this->repo->getObject('MY_FILE', $data['auth_id']), $fileData);
        }

        return false;
    }

    public function deleteFile($data){
        $file = File::find($data['file_id']);
        if(!empty($file)){
            $this->repo->removeList('MY_FILE', Auth::id(), $file->id);
            $fileData =   $fileData = ['FILE' => [$data['auth_id'] => null]];
            return array_merge($this->repo->getObject('MY_FILE', $data['auth_id']), $fileData);
        }

        return false;
    }

    public function uploadFiles($data){
        $files  = $data['file'];
        $result = [];

        foreach ($files as $file) {
            $upload = $this->uploadFile($file);
            $fileId = $this->repo->pushList('FILES',  self::COMPANY_LAMPART_ID);
            $this->repo->pushList('MY_FILE', $data['auth_id'],null, $fileId);
            $result[] = $this->repo->addObject('FILE', $fileId, array_merge($upload, ['created' => now()->timestamp]));
        }

        return $result;
    }

    public function uploadFile($file, $type = null, $path = 'public/v2/'){
        $name       = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext        = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $store      = self::STORE_FILE;
        $path       = $path.$name;
        $pathStore  = $path.'.'.$ext;
        $run        = 1;
        $type       = 'file';

        $imageType  = array('jpg','jpeg','png','gif','webp','apng','avif','pjpeg','jfif','pjp','svg');
        $videoType  = array('mp4','avi','wmv','ogg','ogv','webm','flv','swf','ram','rm','mov','mpeg','mpg');
        if(in_array(strtolower($ext), $imageType)) {
            $type = 'image';
        }
        if(in_array(strtolower($ext), $videoType)) {
            $type = 'video';
        }

        while($run){
            if(Storage::disk($store)->exists($pathStore)){
                $pathStore = $path.'-'.$run.'.'.$ext;
            }else{
                Storage::disk($store)->put($pathStore, file_get_contents($file));
                if($type === 'image'){
                    Storage::disk($store)->put(self::THUMBNAIL_SEPARATE.$pathStore,
                    Image::make($file)->orientate()->resize(null, 100, function ($constraint) {
                        $constraint->aspectRatio();
                    })->stream());
                }
                break;
            }
            $run ++;
        }

        return [
            'name'  => $name,
            'ext'   => $ext,
            'type'  => $type,
            'store' => $store,
            'path'  => $pathStore,
        ];
    }
}
