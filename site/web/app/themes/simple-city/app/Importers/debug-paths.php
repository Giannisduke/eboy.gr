<?php
// Debug script to check paths
echo "=== PATH DEBUG ===\n";
echo "__DIR__ = " . __DIR__ . "\n";
echo "dirname(__DIR__) = " . dirname(__DIR__) . "\n";
echo "dirname(dirname(__DIR__)) = " . dirname(dirname(__DIR__)) . "\n";

$theme_root = dirname(dirname(__DIR__));
echo "\nTheme root = {$theme_root}\n";
echo "script_dir = {$theme_root}/scripts/product-ai-processor\n";
echo "xml_dir = {$theme_root}/xml_files/\n";

echo "\nExists checks:\n";
echo "script_dir exists? " . (file_exists($theme_root . '/scripts/product-ai-processor') ? 'YES' : 'NO') . "\n";
echo "xml_dir exists? " . (file_exists($theme_root . '/xml_files') ? 'YES' : 'NO') . "\n";
