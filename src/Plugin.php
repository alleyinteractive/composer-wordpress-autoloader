<?php

declare(strict_types=1);

namespace ComposerWordPressAutoloader;

use Composer\Composer;
use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use RuntimeException;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected Composer $composer;
    protected IOInterface $io;
    protected Filesystem $filesystem;
    protected AutoloadGenerator $generator;

    /**
     * Apply plugin modifications to Composer
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = new Filesystem();
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        if ($this->filesystem->remove($this->getAutoloaderFilePath())) {
            $this->io->write('<info>Removed WordPress autoloader.</info>');
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => 'generateAutoloaderFile',
        ];
    }

    /**
     * Generate the autoloader file.
     */
    public function generateAutoloaderFile(Event $event): void
    {
        $this->filesystem->ensureDirectoryExists($this->getVendorDir());

        $this->generator = new AutoloadGenerator(
            $this->composer,
            $this->io,
        );

        $this->generator->setApcuMode(
            (bool) $this->composer->getConfig()->get('apcu-autoloader')
        );

        $extra = $this->composer->getPackage()->getExtra()['wordpress-autoloader'] ?? [];

        /**
         * Determine if we should inject our autoloader into the
         * vendor/autoload.php from Composer.
         *
         * Injecting is not generally necessary any more since the file will
         * automatically be loaded. However, it is still possible to inject it
         * for circumstances where it is needed such as when dealing with symlinks.
         */
        $injecting = is_array($extra) && !empty($extra['inject']);

        $autoloaderFile = $this->generator->generate($injecting, $event->isDevMode());

        $partyEmoji = [
            '🪩',
            '🎉',
            '🎊',
            '🍾',
        ];

        $partyEmoji = $partyEmoji[array_rand($partyEmoji)];

        if (
            $this->filesystem->filePutContentsIfModified(
                $this->getAutoloaderFilePath(),
                $autoloaderFile,
            )
        ) {
            if (!$injecting) {
                $this->io->write("<info>{$partyEmoji} WordPress autoloader generated</info>");
            }
        }

        // Inject the autoloader into the existing autoloader.
        if ($injecting) {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->getVendorDir() . '/autoload.php',
                    $this->getInjectedAutoloaderFileContents($this->getAutoloaderFilePath()),
                )
            ) {
                $this->io->write(
                    "<info>{$partyEmoji} WordPress autoloader genearted and injected into vendor/autoload.php.</info>"
                );
            } else {
                $this->io->write('<error>⚠️ Error injecting Wordpress Autoloader.</error>');
            }
        }
    }

    /**
     * Retrieve the file path for the autoloader file.
     */
    protected function getAutoloaderFilePath(): string
    {
        return $this->getVendorDir() . '/wordpress-autoload.php';
    }

    /**
     * Retrieve the Composer vendor directory.
     */
    protected function getVendorDir(): string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        if (!is_string($vendorDir)) {
            throw new RuntimeException('Unable to determine the Composer vendor directory.');
        }

        return $vendorDir;
    }

    /**
     * Generate the injected autoloader file.
     *
     * @param string $autoloaderFileName The path to the WordPress autoloader file.
     */
    protected function getInjectedAutoloaderFileContents(string $autoloaderFileName): string
    {
        $filename = basename($autoloaderFileName);
        $autoloader = file_get_contents($this->getVendorDir() . '/autoload.php');

        if ($autoloader === false) {
            throw new RuntimeException('Unable to read vendor/autoload.php.');
        }

        $contents = preg_replace_callback(
            '/^return (.*);$/m',
            function ($matches) use ($filename) {
                $autoloader = <<<AUTOLOADER
\$loader = {$matches[1]};

/*
  Composer WordPress Autoloader injected by alleyinteractive/composer-wordpress-autoloader

  To disable, set the "inject" key in the 'extra -> wordpress-autoloader'
  section of your composer.json file to false. Injecting the autoloader is
  not generally necessary as the autoloader is automatically loaded for you.
*/
require_once __DIR__ . '/{$filename}';

return \$loader;
AUTOLOADER;

                return "$autoloader\n";
            },
            $autoloader,
            1,
            $count,
        );

        if ($contents === null || !$count) {
            throw new RuntimeException('Error finding proper place to inject autoloader.');
        }

        return $contents;
    }
}
