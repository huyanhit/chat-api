<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\RedisRepository;

class RedisRepositoryTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        $this->assertTrue(true);
    }

    public function testGetObjectList(){
        $redisRepo = new RedisRepository();
        $objMyroom = $redisRepo->getObject('MY_ROOM', 29);

        $this->assertCount(1, $objMyroom);
    }

    public function testGetObjectData(){
        $redisRepo = new RedisRepository();
        $objRoom   = $redisRepo->getObject('ROOM', 1);

        $this->assertCount(1, $objRoom);
    }
}
