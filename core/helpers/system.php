<?php

function is_cli(){
	return (php_sapi_name() == 'cli');
}

function is_unix(){
	return (DIRECTORY_SEPARATOR === '/');
}

function long_exec(){
	ini_set("memory_limit", $config["memory_limit"] ?? "728M");
	ini_set("max_execution_time", $config["max_execution_time"] ?? -1);
}