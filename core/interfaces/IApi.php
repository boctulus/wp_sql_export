<?php

namespace boctulus\SWcore\interfaces;

interface IApi {
    public function get($id = null);
    public function head(int $id = null);
    public function options();
    public function post();
    public function put($id = null);
    public function patch($id = null);
    public function delete($id = NULL);
}