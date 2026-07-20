<?php

namespace PeanutTest\unit\etc;

use PHPUnit\Framework\TestCase;

class TestTest3 extends TestCase
{
    public function testMe() {
        $actual=1;
        $this->assertNotNull($actual);
    }
}
