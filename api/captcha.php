<?php
session_start();

// Generate captcha image
function generateCaptcha() {
    error_log("Captcha generation started at " . date('Y-m-d H:i:s'));
    
    $width = 120;
    $height = 40;
    $font_size = 5;
    
    // Generate random code
    $chars = 'abcdefghijklmnopqrstuvwxyz123456789';
    $captcha_code = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha_code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    error_log("Captcha code generated: " . $captcha_code);
    
    // Store in session
    $_SESSION['captcha'] = $captcha_code;
    
    // Create image
    $image = imagecreate($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    $line_color = imagecolorallocate($image, 200, 200, 200);
    
    // Add some noise lines
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }
    
    // Add text
    $x = intval(($width - strlen($captcha_code) * imagefontwidth($font_size)) / 2);
    $y = intval(($height - imagefontheight($font_size)) / 2);
    imagestring($image, $font_size, $x, $y, $captcha_code, $text_color);
    
    // Output image
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    imagepng($image);
    imagedestroy($image);
    
    error_log("Captcha image generated successfully");
}

generateCaptcha();
?>