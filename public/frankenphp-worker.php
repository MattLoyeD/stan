<?php

// FrankenPHP worker mode entry point for Laravel Octane
$_SERVER['FRANKENPHP_WORKER'] = 'stan';

require __DIR__ . '/../vendor/laravel/octane/src/Workers/frankenphp-worker.php';
