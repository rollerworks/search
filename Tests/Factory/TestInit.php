<?php

if (!file_exists(__DIR__ . '/_generated')) {
    if (!mkdir(__DIR__ . '/_generated')) {
        throw new Exception('Could not create ' . __DIR__ . '/_generated Folder.');
    }
}

if (!file_exists(__DIR__ . '/../_TwigCache')) {
    if (!mkdir(__DIR__ . '/../_TwigCache')) {
        throw new Exception('Could not create ' . __DIR__ . '/../_TwigCache Folder.');
    }
}