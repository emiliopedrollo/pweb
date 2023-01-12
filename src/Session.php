<?php

namespace App;

class Session
{
    protected static array $data = [];
    protected static array $flashes = [];

    protected function parse(): void {
        self::$data = $_SESSION['data'] ?? [];
        self::$flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']);
    }

    public function startup(): void {
        session_start();
        $this->parse();
    }

    public function cleanup(): void {
        $this->removeFlashes();
    }

    protected function removeFlashes(): void {
        foreach (self::$flashes as $flash) {
            unset($_SESSION['data'][$flash]);
        }
    }

    public static function add($key, $value, $flash = false): void {

        self::$data[$key] = $value;
        $_SESSION['data'][$key] = $value;

        if (in_array($key, self::$flashes)) {
            unset(self::$flashes[array_search($key, self::$flashes)]);
        }

        if ($flash) {
            $_SESSION['flashes'][] = $key;
        }

    }

    public static function get($key, $default = null): mixed {
        return self::has($key)
            ? self::$data[$key]
            : $default;
    }

    public static function remove($key): void {
        if (self::has($key)) {
            unset(self::$data[$key]);
            unset($_SESSION['data'][$key]);
        }
    }

    public static function has($key): bool {
        return array_key_exists($key, self::$data);
    }

    public static function flash($key, $value): void {
        self::add($key, $value, true);
    }

}
