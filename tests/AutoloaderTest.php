<?php

declare(strict_types=1);

namespace ComposerWordPressAutoloader\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    /**
     * @param array<class-string> $classes
     */
    #[DataProvider('autoloaders')]
    public function testAutoloaders(string $file, array $classes): void
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
     *
     * @return array<array{string, array<class-string>}>
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

    public function testAutoloaderFile(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Skipping test on Windows');
        }

        $expected = '9893efd7d77972c681f2693e9d20a975';
        $actual = md5(file_get_contents(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php'));

        $this->assertEquals($expected, $actual);
    }

    #[RunInSeparateProcess]
    public function testAutoloadFileLoadsProjectAutoloader(): void
    {
        require __DIR__ . '/fixtures/root/vendor/alleyinteractive/composer-wordpress-autoloader/src/autoload.php';

        $this->assertTrue(class_exists(\ComposerWordPressAutoloaderTests\Example_Class::class));
    }

    #[RunInSeparateProcess]
    public function testAutoloadFileSkipsProjectAutoloaderInsideComposer(): void
    {
        $this->assertTrue(class_exists(\Composer\Console\Application::class));

        require __DIR__ . '/fixtures/root/vendor/alleyinteractive/composer-wordpress-autoloader/src/autoload.php';

        $this->assertFalse(class_exists(\ComposerWordPressAutoloaderTests\Example_Class::class));
    }

    public function testApcuLoader(): void
    {
        $this->assertStringContainsString(
            'setApcuPrefix(',
            file_get_contents(__DIR__ . '/fixtures/apcu/vendor/wordpress-autoload.php'),
        );
    }
}
