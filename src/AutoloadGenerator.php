<?php

namespace ComposerWordPressAutoloader;

use Composer\Autoload\AutoloadGenerator as ComposerAutoloadGenerator;
use Composer\Package\PackageInterface;

/**
 * Composer Autoload Generator
 */
class AutoloadGenerator extends ComposerAutoloadGenerator
{
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
        if ($filteredDevPackages) {
            $packageMap = $this->filterPackageMap($packageMap, $rootPackage);
        }

        return [
          'wordpress' => $this->parseAutoloadsType($packageMap, 'wordpress', $rootPackage),
        ];
    }
}
