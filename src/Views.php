<?php

namespace Lightscale\Simtem;

class Views {

    private static $view_dir = '';

    private static $state_stack = [];
    private static $state = NULL;

    public static function set_view_dir($path) {
        self::$view_dir = $path;
    }

    public static function aget($arr, $path, $def = NULL) {
        if(is_string($path)) return self::aget($arr, [$path], $def);
        if(!is_array($path)) return $def;

        $k = array_shift($path);
        $v = isset($arr[$k]) ? $arr[$k] : $def;
        if(isset($path[0])) {
            return self::aget($v, $path, $def);
        }
        return $v;
    }

    private static function pop_state() {
        $old = self::$state;
        if(empty(self::$state_stack)) return self::$state = NULL;
        return (self::$state = array_shift(self::$state_stack));
    }

    private static function push_state($new) {
        if(self::$state !== NULL) self::$state_stack[] = self::$state;
        self::$state = $new;
    }

    public static function extend($new_state) {
        return array_merge(self::$state, $new_state);
    }

    public static function get($p, $d = NULL) {
        return self::aget(self::$state, $p, $d);
    }

    public static function include($view, $data = []) {
        extract($data);
        self::push_state($data);
        require(self::$view_dir . $view . '.php');
        self::pop_state($data);
    }

    public static function render($view, $data = []) {
        ob_start();
        self::include($view, $data);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}
