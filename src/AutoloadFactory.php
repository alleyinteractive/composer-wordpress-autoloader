<?php

namespace ComposerWordPressAutoloader;

use Alley_Interactive\Autoloader\Autoloader;

class AutoloadFactory
{
  /**
   * Generate an autoloader from a set of rules.
   *
   * @param array<string, array<string>> $rules Array of rules.
   * @return array<Autoloader>
   */
    public static function generateFromRules(array $rules, string $baseDir)
    {
        $loaders = [];

        foreach ($rules as $namespace => $paths) {
            $loaders = array_merge(
                $loaders,
                array_map(
                    fn ($path) => Autoloader::generate($namespace, $baseDir . '/' . $path),
                    $paths,
                ),
            );
        }

        return $loaders;
    }

  /**
   * Register autoloaders from rules.
   *
   * @param array<string, string> $rules Array of rules.
   * @return void
   */
    public static function registerFromRules(array $rules, string $baseDir)
    {
        foreach (static::generateFromRules($rules, $baseDir) as $autoloader) {
            $autoloader->register();
        }
    }
}
