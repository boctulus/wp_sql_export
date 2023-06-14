<?php

namespace boctulus\SW\libs\HtmlBuilder;

class Tag
{
    protected $name;
    protected $props = [];
    protected $args;

    static protected $builder;

    function __construct(string $name) {
        if (static::$builder === null){
            dd("Please register the HTML builder first");
            exit(1);
        }

        $this->name = $name;
    }

    static function registerBuilder(string $builder){
        static::$builder = $builder;
    }

    public function __set(string $name, mixed $value): void{
        $this->props[$name] = $value;
    }

    public function __get(string $name): mixed{
        return $this->props[$name];
    }

    function __call($method, $args){
        $this->props[$method] = $args[0] ?? '';
        return $this;
    }

    function render(){
        return static::$builder::{$this->name}(...$this->props);
    }

    function __toString()
    {
        return $this->render();
    }

}

