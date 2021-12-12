<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('build')
    ->in(__DIR__);

$config = new Soosyze\PhpCsFixer\Config();
$config->setFinder($finder);

return $config;