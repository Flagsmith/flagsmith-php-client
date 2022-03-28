<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(
        [
            'vendor',
        ]
    )
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(
        [
            '@PSR12' => true,
            'single_quote' => true,
            'no_trailing_comma_in_singleline_array' => true,
            'array_indentation' => true,
            'phpdoc_indent' => true,
        ]
    )
    ->setFinder($finder);
