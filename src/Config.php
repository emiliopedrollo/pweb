<?php

namespace App;

class Config
{
    private array $configs = [];

    public function __construct(
        private readonly Application $app
    ) {}

    public function parse(): void {
        $config_path = $this->app->getBasePath('config');
        $directoryIterator = new \RecursiveDirectoryIterator($config_path);
        $iteratorIterator = new \RecursiveIteratorIterator($directoryIterator);
        $fileList = new \RegexIterator($iteratorIterator, '/^.*\.(php)$/i');
        foreach ($fileList as $file) {
            $config_namespace = preg_replace('/\.php$/i','',$file->getBasename());
            $this->configs[$config_namespace] = include($file->getPathname());
        }
    }

    public function get(?string $config = null, mixed $default = null) {

        if (is_null($config)) return $this->configs;

        $parts = explode('.',$config);
        $data = $this->configs;
        if (!empty($parts)) {
            foreach($parts as $part) {
                $data = !is_null($data) ? $data[$part] ?? null : null;
            }
        }

        return $data ?? $default;
    }
}
