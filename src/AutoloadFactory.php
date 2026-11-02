<?php

declare(strict_types=1);

namespace ComposerWordPressAutoloader;

use Alley_Interactive\Autoloader\Autoloader;

class AutoloadFactory
{
    protected static ?string $apcuPrefix = null;

    /**
     * Set the APCu prefix to use.
     */
    public static function setApcuPrefix(?string $apcuPrefix): void
    {
        static::$apcuPrefix = $apcuPrefix;
    }

    /**
     * Generate an autoloader from a set of rules.
     *
     * @param array<string, array<string>> $rules Array of rules.
     * @return array<Autoloader>
     */
    public static function generateFromRules(array $rules): array
    {
        $loaders = [];

        foreach ($rules as $namespace => $paths) {
            foreach ($paths as $path) {
                $loader = new Autoloader($namespace, $path);

                if (static::$apcuPrefix) {
                    $loader->set_apcu_prefix(static::$apcuPrefix);
                }

                $loaders[] = $loader;
            }
        }

        return $loaders;
    }

    /**
     * Register autoloaders from rules.
     *
     * @param array<string, array<string>> $rules Array of rules.
     */
    public static function registerFromRules(array $rules): void
    {
        foreach (static::generateFromRules($rules) as $autoloader) {
            $autoloader->register();
        }
    }
}
