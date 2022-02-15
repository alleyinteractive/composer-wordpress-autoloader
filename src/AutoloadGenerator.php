<?php

namespace ComposerWordPressAutoloader;

use Composer\Autoload\AutoloadGenerator as ComposerAutoloadGenerator;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Pcre\Preg;

/**
 * Composer Autoload Generator
 */
class AutoloadGenerator extends ComposerAutoloadGenerator
{
    protected $devMode = null;

    /**
     * @param bool $devMode
     * @return void
     */
    public function setDevMode($devMode = true)
    {
        parent::setDevMode($devMode);
        $this->devMode = (bool) $devMode;
    }

    /**
     * Compiles an ordered list of namespace => path mappings
     *
     * @param array $packageMap
     * @param PackageInterface $rootPackage
     * @param boolean $filteredDevPackages
     * @return array
     */
    public function parseAutoloads(array $packageMap, PackageInterface $rootPackage, $filteredDevPackages = false)
    {
        // foreach ($packageMap as $package)
        // {
        //     var_dump('package', get_class($package[0]), $package[0]->getName());
        // }
        // var_dump('packages============================');

        if ($filteredDevPackages) {
            $packageMap = $this->filterPackageMap($packageMap, $rootPackage);
        }

        // var_dump('parseAutoloads', $this->parseAutoloadsType($packageMap, 'wordpress', $rootPackage),);exit;

        return [
          'wordpress' => $this->parseAutoloadsType($packageMap, 'wordpress', $rootPackage),
        ];
    }

    /**
     * Compiles an ordered list of namespace => path mappings of autoloads defined in the 'extra' part of a package.
     *
     * @param array $packageMap
     * @param PackageInterface $rootPackage
     * @param boolean $filteredDevPackages
     * @return array
     */
    public function parseExtraAutoloads(array $packageMap, PackageInterface $rootPackage, $filteredDevPackages = false)
    {
        if ($filteredDevPackages) {
            $packageMap = $this->filterPackageMap($packageMap, $rootPackage);
        }

        return [
          'wordpress' => $this->parseExtraAutoloadsType($packageMap, 'wordpress', $rootPackage),
        ];
    }

    /**
     * A modified port of the {@see AutoloadGenerator::parseAutoloadsType()} method from Composer.
     *
     * Imports autoload rules from a package's extra path.
     *
     * @param array<int, array{0: PackageInterface, 1: string}> $packageMap
     * @param string $type one of: 'wordpress'
     * @return array<int, string>|array<string, array<string>>|array<string, string>
     */
    protected function parseExtraAutoloadsType(array $packageMap, $type, RootPackageInterface $rootPackage)
    {
        return [];
        $autoloads = [];

        foreach ($packageMap as $item) {
            [$package, $installPath] = $item;
            $autoload = [
              'wordpress' => $package->getExtra()['wordpress-autoloader']['autoload'] ?? [],
            ];

            // if ($this->devMode && $package === $rootPackage) {
            //     $autoload = array_merge_recursive(
            //         $autoload,
            //         [
            //             'wordpress' => $package->getExtra()['wordpress-autoloader']['autoload-dev'] ?? [],
            //         ],
            //     );
            // }

            // skip misconfigured packages
            if (!isset($autoload[$type]) || !is_array($autoload[$type])) {
                continue;
            }

            if (null !== $package->getTargetDir() && $package !== $rootPackage) {
                $installPath = substr($installPath, 0, -strlen('/' . $package->getTargetDir()));
            }

            if ($package !== $rootPackage) {
                $installPath = str_replace($rootPackage->getTargetDir(), '', $installPath);
            }

            if (!empty($autoload[$type])) {
                var_dump('paths', $autoload[$type], $installPath, $rootPackage->getTargetDir());
            }

            foreach ($autoload[$type] as $namespace => $paths) {
                foreach ((array) $paths as $path) {
                    $relativePath = empty($installPath) ? (empty($path) ? '.' : $path) : $installPath . '/' . $path;
                    $autoloads[$namespace][] = $relativePath;
                    // $autoloads[$namespace][] = $path;
                }
            }
        }

        return $autoloads;
    }
}
