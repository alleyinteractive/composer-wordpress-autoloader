<?php

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

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
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
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
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
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'generateAutoloaderFile',
        ];
    }

    /**
     * Generate the autoloader file.
     *
     * @param Event $event
     * @return void
     */
    public function generateAutoloaderFile(Event $event): void
    {
        $this->filesystem->ensureDirectoryExists($this->composer->getConfig()->get('vendor-dir'));

        // Determine if we should inject our autoloader into the vendor/autoload.php from Composer.
        $injecting = !empty($this->composer->getPackage()->getExtra()['wordpress-autoloader']['inject']);

        $autoloaderFile = $this->getAutoloaderFileContents(
            array_merge_recursive(
                $this->collectAutoloaderRules($event),
                $this->collectExtraAutoloaderRules($event),
            ),
            $injecting,
        );

        var_dump('autoloaderFile', $autoloaderFile);exit;

        // todo: array unique.

        // Inject the autoloader into the existing autoloader.
        if ($injecting) {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->composer->getConfig()->get('vendor-dir') . '/autoload.php',
                    $this->getInjectedAutoloaderFileContents($autoloaderFile),
                )
            ) {
                $this->io->write('<info>WordPress Autoloader generated and injected.</info>');
            } else {
                $this->io->write('<error>Error injecting Wordpress Autoloader.</error>');
            }
        } else {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->getAutoloaderFilePath(),
                    $autoloaderFile,
                )
            ) {
                $this->io->write('<info>WordPress Autoloader generated.</info>');
            }
        }
    }

    /**
     * Retrieve the file path for the autoloader file.
     *
     * @return string
     */
    protected function getAutoloaderFilePath(): string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        return "{$vendorDir}/wordpress-autoload.php";
    }

    /**
     * Collect the autoloader rules to generator for.
     *
     * @param Event $event
     * @return array<string, string>
     */
    protected function collectAutoloaderRules(Event $event): array
    {
        $generator = new AutoloadGenerator(
            $this->composer->getEventDispatcher(),
            $this->io,
        );

        $generator->setDevMode($event->isDevMode());

        $autoloaders = $generator->parseAutoloads(
            $generator->buildPackageMap(
                $this->composer->getInstallationManager(),
                $this->composer->getPackage(),
                $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages(),
            ),
            $this->composer->getPackage(),
            !$event->isDevMode()
        )['wordpress'] ?? [];

        return $autoloaders;

        return $this->normalizeAutoloadingPaths($autoloaders);
    }

    /**
     * Collect the autoloader rules registered via 'extra' to generate for.
     *
     * @param Event $event
     * @return array<string, string>
     */
    protected function collectExtraAutoloaderRules(Event $event): array
    {
        $generator = new AutoloadGenerator(
            $this->composer->getEventDispatcher(),
            $this->io,
        );

        $generator->setDevMode($event->isDevMode());

        $autoloaders = $generator->parseExtraAutoloads(
            $generator->buildPackageMap(
                $this->composer->getInstallationManager(),
                $this->composer->getPackage(),
                $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages(),
            ),
            $this->composer->getPackage(),
            !$event->isDevMode()
        )['wordpress'] ?? [];

        return $autoloaders;

        return $this->normalizeAutoloadingPaths($autoloaders);
    }

    protected function normalizeAutoloadingPaths(array $autoloaders): array
    {
        $baseDir = dirname($this->composer->getConfig()->get('vendor-dir'));
        $filesystem = new Filesystem();

        $autoloaders = array_map(function ($paths) use ($baseDir, $filesystem) {
            return array_map(function ($path) use ($baseDir, $filesystem) {
                if (!$filesystem->isAbsolutePath($path)) {
                    return $path;
                }

                // Normalize absolute paths to be relative to the base path of the project.
                return str_replace($baseDir, '', $path);
            }, $paths);
        }, $autoloaders);

        // var_dump($autoloaders);exit;
        return $autoloaders;
    }

    /**
     * Generate the autoloader file given a set of rules.
     *
     * @param array<string, string> $rules Autoloader rules.
     * @param string $beingInjected Flag if the autoloader is being injected.
     * @return string
     */
    protected function getAutoloaderFileContents(array $rules, bool $beingInjected): string
    {
        $contents = '';

        if ($beingInjected) {
            $contents = "/* Composer WordPress Autoloader Injected */\n\n";
            $contents .= "\n\n";
        } else {
            $contents = <<<AUTOLOAD
<?php

/* Composer WordPress Autoloader */
require_once __DIR__ . '/autoload.php';

\$baseDir = dirname(__DIR__);

AUTOLOAD;
        }

        $contents .= "\n";
        $contents .= sprintf(
            '\ComposerWordPressAutoloader\AutoloadFactory::registerFromRules(%s, $baseDir);',
            var_export($rules, true),
        );

        $contents .= "\n\n";

        return $contents;
    }

    /**
     * Generate the injected autoloader file.
     *
     * @param string $contents File contents to inject.
     * @return string
     */
    protected function getInjectedAutoloaderFileContents(string $contents): string
    {
        $autoloader = file_get_contents($this->composer->getConfig()->get('vendor-dir') . '/autoload.php');

        $contents = preg_replace_callback(
            '/^return ([A-Za-z0-9]*)::getLoader\(\);$/m',
            function ($matches) use ($contents) {
                $autoloader = '$loader' . $matches[1] . ' = ' . $matches[1] . '::getLoader();';
                $autoloader .= "\n\n";
                $autoloader .= $contents;
                $autoloader .= "\n\n";
                $autoloader .= 'return $loader' . $matches[1] . ';';

                return $autoloader;
            },
            $autoloader,
            1,
            $count,
        );

        if (!$count) {
            throw new RuntimeException('Error finding proper place to inject autoloader.');
        }

        return $contents;
    }
}
