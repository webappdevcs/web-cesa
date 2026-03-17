<?php

$loadDirectory = function (string $directory) use (&$loadDirectory): array {
    $translations = [];
    $entries = array_values(array_diff(scandir($directory) ?: [], ['.', '..']));

    sort($entries);

    foreach ($entries as $entry) {
        $path = $directory.DIRECTORY_SEPARATOR.$entry;
        $key = is_dir($path) ? $entry : pathinfo($entry, PATHINFO_FILENAME);

        if (is_dir($path)) {
            $value = $loadDirectory($path);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $value = require $path;
        }

        if (isset($translations[$key]) && is_array($translations[$key]) && is_array($value)) {
            $translations[$key] = array_replace_recursive($translations[$key], $value);
        } else {
            $translations[$key] = $value;
        }
    }

    return $translations;
};

return $loadDirectory(__DIR__.DIRECTORY_SEPARATOR.'filament');
