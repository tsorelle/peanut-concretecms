<?php

namespace PeanutTest\unit;

use PHPUnit\Framework\TestCase;

class Test2 extends TestCase
{
    public function testMe() {
        $x = null;
        $this->assertNotNull($x);
    }
}
