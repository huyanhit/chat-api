<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void{
        Redis::flushAll();
        parent::tearDown();
    }
}
