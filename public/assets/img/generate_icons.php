<?php

declare(strict_types=1);

/**
 * Renders the Cortex & Kinetic icon mark (same geometry as logo-icon.svg)
 * using GD, at several sizes, for use as favicon.ico / apple-touch-icon /
 * png fallbacks where SVG favicons aren't supported.
 *
 * This is a build-time asset-generation script (run once, output
 * committed), not part of the application runtime.
 *
 * Run: php public/assets/img/generate_icons.php
 */

function drawIcon(int $size): \GdImage
{
    $im = imagecreatetruecolor($size, $size);
    imagesavealpha($im, true);
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);
    imageantialias($im, true);

    $scale = $size / 120.0;
    $s = static fn (float $v): int => (int) round($v * $scale);

    $dark = imagecolorallocate($im, 0x22, 0x22, 0x22);
    $gold = imagecolorallocate($im, 0xe8, 0xa3, 0x3d);
    $goldLight = imagecolorallocate($im, 0xff, 0xd2, 0x7a);
    $white = imagecolorallocate($im, 0xff, 0xff, 0xff);

    // base disc
    imagefilledellipse($im, $s(60), $s(60), $s(116), $s(116), $dark);
    imagesetthickness($im, max(1, $s(2.5)));
    imageellipse($im, $s(60), $s(60), $s(110), $s(110), $gold);

    // kinetic bars
    imagefilledrectangle($im, $s(24), $s(70), $s(33), $s(90), $gold);
    imagefilledrectangle($im, $s(36), $s(58), $s(45), $s(90), $gold);
    imagefilledrectangle($im, $s(48), $s(44), $s(57), $s(90), $gold);

    // bridge line
    imagesetthickness($im, max(1, $s(2.5)));
    imageline($im, $s(53), $s(44), $s(78), $s(30), $white);

    // neural net
    imagesetthickness($im, max(1, $s(1.6)));
    imageline($im, $s(78), $s(30), $s(92), $s(42), $gold);
    imageline($im, $s(92), $s(42), $s(84), $s(56), $gold);
    imageline($im, $s(84), $s(56), $s(96), $s(66), $gold);
    imageline($im, $s(78), $s(30), $s(84), $s(56), $gold);

    foreach ([[78, 30, 4.5], [92, 42, 3.4], [84, 56, 3.4], [96, 66, 2.8]] as [$x, $y, $r]) {
        $rx = max(1, $s($r));
        imagefilledellipse($im, $s($x), $s($y), $rx * 2, $rx * 2, $goldLight);
    }

    return $im;
}

$dir = __DIR__;
$sizes = [16 => 'favicon-16.png', 32 => 'favicon-32.png', 180 => 'apple-touch-icon.png', 512 => 'icon-512.png'];

foreach ($sizes as $size => $name) {
    $im = drawIcon($size);
    imagepng($im, $dir . '/' . $name);
    imagedestroy($im);
    echo "Wrote {$name} ({$size}x{$size})\n";
}

// Combine 16 + 32 into a real multi-size .ico
function pngToIcoEntry(string $pngPath): array
{
    return ['data' => file_get_contents($pngPath), 'size' => filesize($pngPath)];
}

$png16 = pngToIcoEntry($dir . '/favicon-16.png');
$png32 = pngToIcoEntry($dir . '/favicon-32.png');

$images = [[16, $png16], [32, $png32]];
$count = count($images);

$header = pack('vvv', 0, 1, $count);
$dirEntries = '';
$imageData = '';
$offset = 6 + 16 * $count;

foreach ($images as [$dim, $entry]) {
    $dirEntries .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, $entry['size'], $offset);
    $imageData .= $entry['data'];
    $offset += $entry['size'];
}

file_put_contents($dir . '/favicon.ico', $header . $dirEntries . $imageData);
echo "Wrote favicon.ico (multi-size: 16, 32)\n";
