<?php

namespace boctulus\SW\core\interfaces;

interface IMigration {

    /**
     * Run migration
     *
     * @return void
     */
    function up();

}