<?php

namespace ComposerWordPressAutoloader\Tests;

use Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
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
     * @dataProvider autoloaders
     */
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
    public function autoloaders()
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

    /**
     * Parse the generated PHP text file using PHP-Parser and check the file contains the expected variables
     * and function calls in the expected order.
     *
     * @see https://github.com/nikic/PHP-Parser
     *
     * The generated file should have:
     * * autoload assign
     * * vendor dir assign
     * * basedir assign
     * * classmap array
     * * spl_autoload_register call
     */
    public function testAutoloaderFile()
    {
        $code = file_get_contents(__DIR__ . '/fixtures/root/vendor/wordpress-autoload.php');

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        // We'll check each parsed expression and use the index to make sure things are in order.
        $ast_index = 0;

        /** @var array<array<string,array<string>>> $expected The expected value and its key paths. */
        $expected = array(
            array( 'autoload' => array( 'expr', 'var', 'name' )),
            array( 'vendorDir'  => array( 'expr', 'var', 'name' )),
            array( 'baseDir' => array( 'expr', 'var', 'name' ) ),
            array( 'wordpress_classmap'  => array( 'expr', 'var', 'name' )),
            array( 'spl_autoload_register' => array( 'expr', 'name', 'parts', 0 ) ),
        );

        /** @var bool[] $found An empty array of `false` indicating was the $expected entry found. */
        $found = array_map(function () {
            return false;
        }, $expected);

        $ast_entries_count = count($ast);

        foreach ($expected as $found_index => $sequential_item) {
            $expected_value = array_key_first($sequential_item);
            $key_path = $sequential_item[$expected_value];

            for ($inner_ast_index = $ast_index; $inner_ast_index < $ast_entries_count; $inner_ast_index++) {
                $ast_index++;

                $ast_entry = $ast[$inner_ast_index];
                $value = $ast_entry;

                foreach ($key_path as $path) {
                    if (is_object($value) && isset($value->$path)) {
                        $value = $value->$path;
                    } elseif (is_array($value) && isset($value[$path])) {
                        $value = $value[$path];
                    } else {
                        // This entry in the AST is not what we're looking for.
                        // It may just be a trivial assignment we're not concerned with.
                        continue 2;
                    }
                }

                if ($expected_value === $value) {
                    $found[$found_index] = true;
                    continue 2;
                }
            }

            if ($ast_index >= $ast_entries_count) {
                break;
            }
        }

        $all_found = array_reduce($found, function ($carry, $actual) {
            return $carry && $actual;
        }, true);

        $this->assertTrue($all_found);
    }

    public function testApcuLoader()
    {
        $this->assertStringContainsString(
            'setApcuPrefix(',
            file_get_contents(__DIR__ . '/fixtures/apcu/vendor/wordpress-autoload.php'),
        );
    }
}
