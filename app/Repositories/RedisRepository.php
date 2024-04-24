<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisRepository implements RepoInterface {
    const SEPARATE_KEY = '_';
    private array $config;
    public function __construct(){
        $this->config = config('redis');
    }

    public function getObject($object, $key){
        if(isset($this->config[$object])){
            foreach ($this->config[$object] as $prefix => $properties){
                if($prefix === '[KEY]' && isset($properties['type'])){
                    return [$object => [$key => Redis::lrange($object. self::SEPARATE_KEY .$key, 0 , -1)]];
                }else{
                    return $this->getProperties($object, $key, $properties);
                }
            }
        }

        return null;
    }

    public function addObject($object, $key, $data = null){
        $maps = $this->mapKeyRedis($object, $key, $data, true);
        if(is_array($maps)){
            foreach ($maps as $k => $v){
                if(!empty($v)) Redis::set($k, $v);
            }
            return $key;
        }

        return null;
    }

    public function updateObject($object, $key, $data){
        $maps = $this->mapKeyRedis($object, $key, $data);
        if(is_array($maps)){
            foreach ($maps as $k => $v){
                Redis::set($k, $v);
            }
            return $key;
        }

        return null;
    }

    public function deleteObject($object, $key){
        $maps = $this->mapKeyRedis($object, $key, null, true);
        if(is_array($maps)){
            foreach ($maps as $k => $v){
                Redis::del($k);
            }
            return $key;
        }

        return null;
    }

    public function getList($object, $key, $property = null, $start = 0, $end = -1){
        return Redis::lrange($this->renderKey($object, $key, $property), $start, $end);
    }

    public function pushList($object, $key, $property = null, $value = null){
        $k = $this->renderKey($object, $key, $property);
        if(empty($value)){
            return $this->autoIncrementMaxList($k);
        }else{
            return $this->setListValue($k, $value);
        }
    }

    public function pushListAutoIncrement($object, $key, $property = null){
        return $this->autoIncrementList($this->renderKey($object, $key, $property));
    }

    public function removeList($object, $key, $property = null, $value = null){
        $k = $this->renderKey($object, $key, $property);
        return Redis::lrem($k, 0, $value);
    }

    public function getLenOfList($object, $key, $property = null){
        $k = $this->renderKey($object, $key, $property);
        if(Redis::exists($k)){
            return Redis::llen($k);
        }

        return 0;
    }

    private function getProperties($object, $key, $properties){
        $response = [];
        foreach ($properties as $index => $property){
            $k = $object. self::SEPARATE_KEY .$index. self::SEPARATE_KEY .$key;
            if(isset($property['type']) && ($property['type'] == 'list')){
                $value = Redis::lrange($k, 0 , -1);
            }else{
                $value = Redis::get($k);
                $value = empty($value)? $property['default']: $value;
            }
            if(isset($property['type']) ){
                switch ($property['type']){
                    case 'integer':
                        $value = (int)$value;
                        break;
                }
            }

            $response = array_merge($response, [$property['name'] => $value]);
        }

        return [$object => [$key => $response]];
    }

    private function mapKeyRedis($object, $key, $data = null, $all = false){
        if(isset($this->config[$object])){
            foreach ($this->config[$object] as $prefix => $properties){
                if($prefix === '[KEY]' && isset($properties['type'])){
                    return $object. self::SEPARATE_KEY .$key;
                }else{
                    return $this->mapProperties($properties, $data, $object, $key, $all);
                }
            }
        }

        return null;
    }

    private function mapProperties($properties, $data, $object, $key, $all){
        $keys = [];
        foreach ($properties as $index => $property){
            if(isset($property['name'])){
                if(isset($data[$property['name']])){
                    $keys[$object. self::SEPARATE_KEY .$index. self::SEPARATE_KEY .$key] = $data[$property['name']];
                }elseif($all){
                    if(isset($property['type']) && $property['type'] == 'list'){
                        $keys[$object. self::SEPARATE_KEY .$index. self::SEPARATE_KEY .$key] = [];
                    }else{
                        $keys[$object. self::SEPARATE_KEY .$index. self::SEPARATE_KEY .$key] = $property['default'];
                    }
                }
            }
        }

        return $keys;
    }

    private function renderKey($object, $key, $property){
        if(empty($property)){
            return $object. self::SEPARATE_KEY .$key;
        }

        return $object. self::SEPARATE_KEY .$property. self::SEPARATE_KEY .$key;
    }

    private function autoIncrementList($key){
        $increment = 0;
        if(Redis::exists($key)){
            $increment = Redis::llen($key);
        }
        Redis::rpush($key, ++$increment);

        return $increment;
    }

    private function autoIncrementMaxList($key){
        $increment = 1;
        if(Redis::exists('INCR_'.$key)){
            Redis::incr('INCR_'.$key);
            $increment = Redis::get('INCR_'.$key);
        }else{
            Redis::set('INCR_'.$key, '1');
        }
        Redis::rpush($key, $increment);

        return $increment;
    }

    private function setListValue($key, $value){
        if(Redis::exists($key)) {
            $list = Redis::lrange($key, 0, -1);
            if(!in_array($value, $list)){
                Redis::rpush($key, $value);
            }
        }else{
            Redis::rpush($key, $value);
        }

        return $value;
    }
}
