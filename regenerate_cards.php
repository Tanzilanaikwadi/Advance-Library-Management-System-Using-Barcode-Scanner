<?php
require_once 'config/db.php';
include_once 'lib_barcode.php';

// Function to generate ID Cards (Cleaned up version)
function generateFullIDCard($member) {
    $barcode = $member['barcode_id'];
    $name = $member['name'];
    $dept = $member['department'];
    $year = $member['admission_year'];
    $prn = $member['prn_number'];
    $photo_path = $member['photo_path'];
    $phone = $member['phone'];

    $width = 600;
    $height = 380;
    
    // --- FRONT SIDE ---
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $blue = imagecolorallocate($img, 37, 99, 235);
    $gray = imagecolorallocate($img, 71, 85, 105);
    $light_bg = imagecolorallocate($img, 248, 250, 252);
    $dark_blue = imagecolorallocate($img, 30, 64, 175);

    imagefilledrectangle($img, 0, 0, $width, $height, $light_bg);
    imagefilledrectangle($img, 0, 0, $width, 90, $blue);
    imagerectangle($img, 10, 10, $width-10, $height-10, $blue);
    
    imagestring($img, 5, 150, 25, "UNIVERSITY LIBRARY", $white);
    imagestring($img, 4, 180, 50, "STUDENT IDENTITY CARD", $white);
    
    // Photo
    if ($photo_path && file_exists($photo_path)) {
        $ext = strtolower(pathinfo($photo_path, PATHINFO_EXTENSION));
        $src = ($ext == 'png') ? imagecreatefrompng($photo_path) : imagecreatefromjpeg($photo_path);
        if ($src) {
            imagecopyresampled($img, $src, 25, 110, 0, 0, 110, 130, imagesx($src), imagesy($src));
            imagedestroy($src);
        }
    } else {
        imagerectangle($img, 25, 110, 135, 240, $black);
        imagestring($img, 3, 50, 160, "No Photo", $black);
    }
    
    // Details
    $text_x = 160; $y = 110; $lh = 28;
    imagestring($img, 5, $text_x, $y, strtoupper($name), $black);
    imagestring($img, 4, $text_x, $y + $lh*1.5, "ID No   : " . $barcode, $black);
    imagestring($img, 4, $text_x, $y + $lh*2.5, "Dept    : " . $dept, $black);
    imagestring($img, 4, $text_x, $y + $lh*3.5, "Year    : " . $year, $black);
    imagestring($img, 4, $text_x, $y + $lh*4.5, "PRN     : " . $prn, $black);
    
    // Barcode
    if (class_exists('SimpleBarcode')) {
       SimpleBarcode::draw($img, 220, $height - 60, $barcode, $black, 40, 2);
    }
    
    $save_dir = 'uploads/id_cards/';
    if (!is_dir($save_dir)) mkdir($save_dir, 0777, true);
    
    $file_front = $save_dir . 'ID_FRONT_' . $barcode . '.png';
    imagepng($img, $file_front);
    imagedestroy($img);
    
    // --- BACK SIDE ---
    $img_back = imagecreatetruecolor($width, $height);
    imagefilledrectangle($img_back, 0, 0, $width, $height, $white);
    imagerectangle($img_back, 10, 10, $width-10, $height-10, $blue);
    imagefilledrectangle($img_back, 0, 0, $width, 50, $blue);
    imagestring($img_back, 4, 30, 15, "TERMS AND CONDITIONS", $white);
    
    $rules = [
        "1. Non-transferable card.",
        "2. Must present while borrowing.",
        "3. Report loss immediately.",
        "4. Valid only for course duration.",
        "5. Return if found."
    ];
    
    $y_rule = 80;
    foreach ($rules as $rule) {
        imagestring($img_back, 4, 30, $y_rule, $rule, $black);
        $y_rule += 30;
    }
    
    $y_rule += 20;
    imagestring($img_back, 4, 30, $y_rule, "Address:", $dark_blue);
    imagestring($img_back, 3, 30, $y_rule + 20, "University Library Main Campus,", $gray);
    imagestring($img_back, 3, 30, $y_rule + 35, "City - 400001", $gray);
    imagestring($img_back, 3, 30, $y_rule + 50, "Ph: " . ($phone ? $phone : "0123-456789"), $gray);
    
    $file_back = $save_dir . 'ID_BACK_' . $barcode . '.png';
    imagepng($img_back, $file_back);
    imagedestroy($img_back);

    return $file_front;
}

try {
    $stmt = $pdo->query("SELECT * FROM members");
    $members = $stmt->fetchAll();

    echo "Regenerating cards for " . count($members) . " members...\n";

    foreach ($members as $m) {
        if ($m['barcode_id']) {
            $front_path = generateFullIDCard($m);
            
            // Update DB if path missing
            if (empty($m['id_card_path'])) {
                $upd = $pdo->prepare("UPDATE members SET id_card_path = ? WHERE id = ?");
                $upd->execute([$front_path, $m['id']]);
            }
            echo "Done: " . $m['name'] . "\n";
        }
    }
    echo "Regeneration Complete.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
