<?php
// Lightweight Code128 Barcode Generator for PHP GD
// Based on common implementations of Code128B

class SimpleBarcode {
    public static function draw($im, $x, $y, $code, $color, $height = 50, $thin_width = 2) {
        $patterns = array(
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', // 0-9
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', // 10-19
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', // 20-29
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', // 30-39
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', // 40-49
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', // 50-59
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', // 60-69
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', // 70-79
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', // 80-89
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', // 90-99
            '114131', '311141', '411131', // 100-102
            '211421', // 103 (Start A)
            '211214', // 104 (Start B)
            '211232', // 105 (Start C)
            '2331112' // 106 (Stop)
        );

        $start_b_index = 104;
        $stop_index = 106;

        $final_bars = "";
        
        // Start Code B
        $final_bars .= $patterns[$start_b_index];
        $check_sum = $start_b_index;

        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $char_val = ord($code[$i]) - 32;
            if ($char_val < 0 || $char_val > 106) {
                // Fallback for unsupported characters? simple skip or mapping
                // Assuming standard ASCII printable range support for Code 128B
                continue; 
            }
            $check_sum += $char_val * ($i + 1);
            $final_bars .= $patterns[$char_val];
        }

        $check_sum %= 103; // Checksum modulo 103
        $final_bars .= $patterns[$check_sum];
        
        // Stop Pattern
        $final_bars .= $patterns[$stop_index];
        
        // Draw
        $bar_x = $x;
        for ($i = 0; $i < strlen($final_bars); $i++) {
            $bar_val = (int)$final_bars[$i];
            $is_black = ($i % 2 == 0); 
            
            if ($is_black) {
                imagefilledrectangle($im, $bar_x, $y, $bar_x + ($bar_val * $thin_width) - 1, $y + $height, $color);
            }
            $bar_x += ($bar_val * $thin_width);
        }
        
        return $bar_x;
    }

    public static function generateAndSave($code, $filepath) {
        $width = 400; // Default width, might need adjustment based on code length
        $height = 80;
        
        // Calculate dynamic width based on code length to avoid cutoff
        // Approx 11 chars + 3 start/stop/chk * 11 bars * 2px width ~ 300px + margins
        $estimated_width = (strlen($code) + 5) * 11 * 2 + 40;
        if ($estimated_width > $width) $width = $estimated_width;

        $im = imagecreate($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        
        // Draw Barcode
        // Centering: roughly
        $content_width = (strlen($code) + 3) * 11 * 2; // rough estimate
        $start_x = ($width - $content_width) / 2;
        if ($start_x < 10) $start_x = 10;

        self::draw($im, $start_x, 10, $code, $black, 50, 2);

        // Add text below
        $font = 3; // Built-in font
        $text_width = imagefontwidth($font) * strlen($code);
        $text_x = ($width - $text_width) / 2;
        imagestring($im, $font, $text_x, 65, $code, $black);
        
        // Ensure directory exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        $result = imagepng($im, $filepath);
        imagedestroy($im);
        return $result;
    }
}
?>
