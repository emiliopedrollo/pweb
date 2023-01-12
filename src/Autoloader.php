<?php

namespace App;

/**
 * Autoloader - A (very) simple alternative to composer autoloader
 *
 * @author Emilio Brandt Pedrollo <emiliopedrollo at gmail dot com>
 */
class Autoloader
{
    protected static ?Autoloader $loader = null;

    public function __construct(
       protected readonly array $config
    ){ }

    public function loadClass($class) {
        foreach ($this->config['psr-4'] as $namespace => $path) {
            if (str_starts_with($class, $namespace)) {
                $classPath = str_replace($namespace, $path, $class);
                $file = str_replace('\\', DIRECTORY_SEPARATOR ,$this->config['basedir'] . $classPath. '.php');
                if (file_exists( $file ) ) {
                    include_once $file;
                    return true;
                }
            }
        }
        return false;
    }

    public static function register(array $config): Autoloader {

        if (null !== self::$loader) {
            return self::$loader;
        }

        self::$loader = $loader = new Autoloader(array_merge($config,[ 'basedir' =>
            rtrim(__DIR__,'/') . '/'
        ]));

        spl_autoload_register(array($loader, 'loadClass'), true, true);

        foreach($config['files'] ?? [] as $file) {
            include_once __DIR__ . DIRECTORY_SEPARATOR . $file;
        }

        return $loader;
    }

}
