<?php

namespace ComposerWordPressAutoloader\Tests;

use ComposerWordPressAutoloaderTests\Example_Class;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AutoloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(__DIR__ . '/includes/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/includes');
        }
    }

    public function testAutoloadedClass()
    {
        // Ensure it is undefined until we load it.
        $this->assertFalse(class_exists(Example_Class::class));

        require_once __DIR__ . '/includes/vendor/wordpress-autoload.php';
        $this->assertTrue(class_exists(Example_Class::class));
    }
}
