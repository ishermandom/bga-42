<?php

$finder = PhpCsFixer\Finder::create()
  ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
  '@PSR2' => true,
  'strict_param' => true,
  'array_syntax' => ['syntax' => 'short'],
  'array_indentation' => true,
  'braces' => [
    'allow_single_line_closure' => true,
    'position_after_functions_and_oop_constructs' => 'same'],
])
  ->setFinder($finder)
  ->setIndent("  ")
;
