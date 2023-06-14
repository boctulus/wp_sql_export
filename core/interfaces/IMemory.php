<?php

namespace boctulus\SW\traits;

/*
    Taken (and modified) from 
    https://spiral.dev/docs/framework-memory
*/
interface IMemory {
    /**
     * Put data to long memory cache. No inner references or closures are allowed. Current
     * convention allows to store serializable (var_export-able) data.
     *
     * @param string       $section Non case sensitive.
     * @param string|array $data
     * @param int $seconds
     */
    public function saveData(string $section, $data, int $seconds);

    /**
     * Read data from long memory cache. Must return exacts same value as saved or null. Current
     * convention allows to store serializable (var_export-able) data.
     *
     * @param string $section Non case sensitive.
     * @return string|array|null
     */
    public function loadData(string $section);


}