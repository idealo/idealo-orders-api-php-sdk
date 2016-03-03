<?php

spl_autoload_register(function ($sClass) {
    // project-specific namespace prefix
    $sPrefix = 'idealo\\Direktkauf\\';

    // base directory for the namespace prefix
    $sBaseDir = __DIR__.'/idealo/Direktkauf/';

    // does the class use the namespace prefix?
    $iLength = strlen($sPrefix);
    if (strncmp($sPrefix, $sClass, $iLength) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $sRelativeClass = substr($sClass, $iLength);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $sFile = $sBaseDir . str_replace('\\', '/', $sRelativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($sFile)) {
        require $sFile;
    }
});