<?php
echo "=== Imagick Background Removal Test ===\n\n";

$url  = 'https://libertab2b.gr/media/catalog/product/0/3/035239-QhK7E-first-_1.jpg';
$temp = tempnam(sys_get_temp_dir(), 'imgtest_') . '.jpg';

echo "Downloading image...\n";
$data = file_get_contents($url);
if (!$data) { die("ERROR: Could not download image\n"); }
file_put_contents($temp, $data);
echo "Downloaded: " . filesize($temp) . " bytes → {$temp}\n\n";

echo "Imagick: " . (class_exists('Imagick') ? "YES" : "NO") . "\n";
echo "GD:      " . (function_exists('imagecreatefromjpeg') ? "YES" : "NO") . "\n";
echo "WebP GD: " . (function_exists('imagewebp') ? "YES" : "NO") . "\n\n";

if (!class_exists('Imagick')) { die("Imagick not available.\n"); }

$imagick = new Imagick($temp);
$w = $imagick->getImageWidth();
$h = $imagick->getImageHeight();
echo "Image size: {$w}x{$h}\n";

$quantum = $imagick->getQuantumRange()['quantumRangeLong'];
echo "Quantum range: {$quantum}\n";
$fuzz = 0.20 * $quantum;
echo "Fuzz (20%): {$fuzz}\n\n";

$corner = $imagick->getImagePixelColor(0, 0)->getColor();
echo "Corner pixel (0,0): R={$corner['r']} G={$corner['g']} B={$corner['b']}\n\n";

echo "Activating alpha channel...\n";
$imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

echo "Running floodfillPaintImage from (0,0)...\n";
$target = $imagick->getImagePixelColor(0, 0);
$fill   = new ImagickPixel('transparent');
$result = $imagick->floodfillPaintImage($fill, $fuzz, $target, 0, 0, false);
echo "Result: " . ($result ? "true" : "false") . "\n\n";

$webp = sys_get_temp_dir() . '/test_nobg.webp';
$imagick->setImageFormat('webp');
$imagick->writeImage($webp);
$imagick->destroy();
echo "WebP written: " . (file_exists($webp) ? filesize($webp) . " bytes" : "FAILED") . "\n";

if (file_exists($webp)) {
    $check = new Imagick($webp);
    $c = $check->getImagePixelColor(0, 0)->getColor(true);
    $mid = $check->getImagePixelColor((int)($w/2), (int)($h/2))->getColor(true);
    echo "Corner alpha after removal: " . round($c['a'], 3) . " (0=transparent, 1=opaque)\n";
    echo "Center alpha:               " . round($mid['a'], 3) . " (should be ~1.0)\n";
    $check->destroy();
    echo "\nWebP at: {$webp}\n";
}

@unlink($temp);
echo "\nDone.\n";
