<?php
require_once 'lib_barcode.php';

$code = $_GET['code'] ?? '';
$text = $_GET['text'] ?? '0'; // Whether to show text below

if ($code && class_exists('SimpleBarcode')) {
    $width = 200;
    $height = 60;
    
    $img = imagecreatetruecolor($width, $height);
    
    // Transparent background (or white)
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    
    imagefill($img, 0, 0, $white);
    
    // Draw Barcode
    // x, y, code, color, height, scale
    SimpleBarcode::draw($img, 10, 10, $code, $black, 30, 1);
    
    if ($text) {
        imagestring($img, 3, 10, 45, $code, $black);
    }
    
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
}
?>
