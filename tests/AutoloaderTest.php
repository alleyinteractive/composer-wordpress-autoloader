<?php

namespace ComposerWordPressAutoloader\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class AutoloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/includes');
        }
    }

    public function testAutoloadedClass()
    {
        // Ensure it is undefined until we load it.
        $this->assertFalse(class_exists(\ComposerWordPressAutoloaderTests\Example_Class::class));
        $this->assertFalse(class_exists(\ComposerWordPressAutoloaderTests\Tests\Example_Test_File::class));

        require_once __DIR__ . '/fixtures/root/vendor/wordpress-autoload.php';

        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests\Example_Class::class));
        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests\Tests\Example_Test_File::class));
    }

    public function testExtraAutoloadedClass()
    {
        require_once __DIR__ . '/fixtures/root/vendor/wordpress-autoload.php';

        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests\Extra\Example_Class::class));
        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests\Extra\Tests\Example_Test_File::class));
    }

    public function testAutoloadedClassDependency()
    {
        require_once __DIR__ . '/fixtures/root/vendor/wordpress-autoload.php';

        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests_Dependency\Example_Class::class));
        // $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests_Dependency\Tests\Example_Test_File::class));
    }

    public function testAutoloadedClassDependencyExtra()
    {
        $this->assertTrue(
            class_exists(\ComposerWordPressAutoloaderTests_Dependency\Extra\Example_Class::class),
            \ComposerWordPressAutoloaderTests_Dependency\Extra\Example_Class::class . ' class does not exist',
        );

        $this->assertTrue(
            class_exists(\ComposerWordPressAutoloaderTests_Dependency\Extra\Tests\Example_Test_File::class),
            \ComposerWordPressAutoloaderTests_Dependency\Extra\Tests\Example_Test_File::class . ' class does not',
        );
    }

    // public function testAutoloaderFile()
    // {
    //     $expected = 'bb6fffa420b29861d981a264238916e4';
    //     $actual = md5(file_get_contents(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php'));

    //     $this->assertEquals($expected, $actual);
    // }
}
