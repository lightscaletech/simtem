<?php

namespace Lightscale\Simtem;

class Views {

    private static $view_dirs = [];

    private static $state_stack = [];
    private static $state = NULL;

    private static $path_lookup = [];
    private static $lookup_cache_handler = null;

    public static function set_view_dir($path) {
        self::set_view_dirs($path);
    }

    public static function set_view_dirs($dirs) {
        $path = is_string($dirs) ? [$dirs] : $dirs;
        self::$view_dirs = $path;
    }

    public static function set_lookup_cache_handler($handler) {
        if(!($handler instanceof LookupCacheInterface)) throw new \Exception(
            'Lookup cache handler does not implement LookupCacheInterface'
        );

        self::$lookup_cache_handler = $handler;
    }

    private static function aget($arr, $path, $def = NULL) {
        if(is_string($path)) return self::aget($arr, [$path], $def);
        if(!is_array($path)) return $def;

        $k = array_shift($path);
        $v = isset($arr[$k]) ? $arr[$k] : $def;
        if(isset($path[0])) return self::aget($v, $path, $def);
        return $v;
    }

    private static function push_state($new) {
        array_unshift(self::$state_stack, $new);
        return $new;
    }

    private static function pop_state() {
        return array_shift(self::$state_stack);
    }

    public static function state() {
        $r = self::$state;
        if(isset($r['render'])) unset($r['render']);
        return $r;
    }

    public static function extend($new_state) {
        return array_merge(self::state(), $new_state);
    }

    public static function get($p = NULL, $d = NULL) {
        if($p === NULL) return self::state();
        return self::aget(self::state(), $p, $d);
    }

    private static function get_path($view) {
        if(empty(self::$path_lookup) && self::$lookup_cache_handler)
            self::$path_lookup = self::$lookup_cache_handler->get();

        $path = self::aget(self::$path_lookup, $view, false);
        if($path) return $path;

        foreach(self::$view_dirs as $dir) {
            $path = $dir . $view . '.php';
            if(file_exists($path)) break;
        }

        if(!$path) throw new \Exception(
            "View file \"{$view}\" was not found in any template directory"
        );

        self::$path_lookup[$view] = $path;

        if(self::$lookup_cache_handler)
            self::$lookup_cache_handler->set(self::$path_lookup);

        return $path;
    }

    public static function include($view, $data = [], $render = NULL) {
        $render = is_callable($render) ? $render : function() {};
        $data['render'] = $render;

        self::$state = self::push_state(self::$state);
        self::$state = $data;
        extract(self::$state);
        require(self::get_path($view));
        self::$state = self::pop_state();
    }

    public static function render($view, $data = []) {
        ob_start();
        self::include($view, $data);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}
