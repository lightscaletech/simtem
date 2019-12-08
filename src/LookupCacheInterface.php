<?php

namespace Lightscale\Simtem;

interface LookupCacheInterface {
    public function get();
    public function set($data);
}
