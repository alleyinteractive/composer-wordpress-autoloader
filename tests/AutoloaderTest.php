<?php

namespace ComposerWordPressAutoloader\Tests;

use ComposerWordPressAutoloaderTests\Example_Class;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
    public function testAutoloadedClass()
    {
        // Ensure it is undefined until we load it.
        $this->assertFalse(class_exists(Example_Class::class));

        require_once __DIR__ . '/includes/vendor/wordpress-autoload.php';
        $this->assertTrue(class_exists(Example_Class::class));
    }
}
