<?php

spl_autoload_register(function($class) {
    // project-specific namespace prefix
    $prefix = 'LsPanel\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__;

    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        /**
         * class does not use the namespace prefix, move to the next registered
         * autoloader.
         */
        return;
    }

    $relative_class = substr($class, $len);

    /**
     * replace the namespace prefix with the base directory, replace namespace
     * separators with directory separators in the relative class name, append
     * with .php
     */
    $file = "{$base_dir}/" . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
