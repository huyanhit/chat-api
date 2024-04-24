<?php

namespace Tests\Unit\Service;

use Tests\TestCase;
use App\Services\RoomService;
use App\Repositories\RedisInterface;
use App\Repositories\RedisRepository;

class RoomServiceTest extends TestCase
{
    public function testAddRoom(){
        app()->bind(RedisInterface::class, RedisRepository::class);
        app()->bind('RoomService', RoomService::class);
        $result = app()->make('RoomService')->addRoom(['auth_id'=>29,'name'=>'test','description'=>'test decs']);
        $this->assertEquals(1, $result);
    }
    public function testGetAllRoom(){
        app()->bind(RedisInterface::class, RedisRepository::class);
        app()->bind('RoomService', RoomService::class);
        $result = app()->make('RoomService')->getAllRoom(['auth_id'=>29]);
        $this->assertCount(3, $result);
    }
}
