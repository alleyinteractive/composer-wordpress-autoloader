<?php

namespace ComposerWordPressAutoloader\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AutoloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/fixtures/root');
        }

        if (!file_exists(__DIR__ . '/fixtures/inject/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/fixtures/inject');
        }

        if (!file_exists(__DIR__ . '/fixtures/apcu/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/fixtures/apcu');
        }

        if (!file_exists(__DIR__ . '/fixtures/vendor-dir/client-mu-plugins/vendor/wordpress-autoload.php')) {
            throw new RuntimeException('"composer install" needs to be run in tests/fixtures/vendor-dir');
        }
    }

    #[DataProvider('autoloaders')]
    public function testAutoloaders(string $file, array $classes)
    {
        foreach ($classes as $class) {
            $this->assertFalse(class_exists($class), "Class {$class} should not be found");
        }

        require_once $file;

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Class {$class} should be found");
        }
    }

    /**
     * Data provider for all the various autoloader fixtures.
     */
    public static function autoloaders(): array
    {
        return [
            [
                __DIR__ . '/fixtures/root/vendor/wordpress-autoload.php',
                [
                    \ComposerWordPressAutoloaderTests\Example_Class::class,
                    \ComposerWordPressAutoloaderTests\Tests\Example_Test_File::class,
                    \ComposerWordPressAutoloaderTests\Extra\Example_Class::class,
                    \ComposerWordPressAutoloaderTests\Extra\Tests\Example_Test_File::class,
                    \ComposerWordPressAutoloaderTests_Dependency\Example_Class::class,
                    \ComposerWordPressAutoloaderTests_Dependency\Extra\Example_Class::class,
                ],
            ],
            [
                __DIR__ . '/fixtures/inject/vendor/wordpress-autoload.php',
                [
                    \ComposerWordPressAutoloaderTests_Inject\Example_Class::class,
                ],
            ],
            [
                __DIR__ . '/fixtures/apcu/vendor/wordpress-autoload.php',
                [
                    \ComposerWordPressAutoloaderTests_APCu\Example_Class::class,
                ],
            ],
            [
                __DIR__ . '/fixtures/vendor-dir/client-mu-plugins/vendor/wordpress-autoload.php',
                [
                    \ComposerWordPressAutoloaderTests_VendorDir\Example_Class::class,
                ],
            ],
        ];
    }

    public function testAutoloaderFile()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Skipping test on Windows');
        }

        $expected = '9893efd7d77972c681f2693e9d20a975';
        $actual = md5(file_get_contents(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php'));

        $this->assertEquals($expected, $actual);
    }

    public function testApcuLoader()
    {
        $this->assertStringContainsString(
            'setApcuPrefix(',
            file_get_contents(__DIR__ . '/fixtures/apcu/vendor/wordpress-autoload.php'),
        );
    }
}
