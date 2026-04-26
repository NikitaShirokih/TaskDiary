<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'                   => true,
        'array_syntax'               => ['syntax' => 'short'],
        'ordered_imports'            => true,
        'no_unused_imports'          => true,
        'trailing_comma_in_multiline'=> true,
    ])
    ->setFinder($finder);
