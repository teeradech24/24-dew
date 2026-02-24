<?php
/**
 * Generate placeholder product images using PHP GD
 * Run this once: php generate_placeholders.php
 */

$dir = __DIR__ . '/assets/images/products/';

$placeholders = [
    'gskill_z5.png'       => ['G.Skill Z5 RGB', '#6366f1', '🧩'],
    'samsung_990pro.png'   => ['Samsung 990 Pro', '#f97316', '💾'],
    'wd_sn850x.png'       => ['WD Black SN850X', '#1e293b', '💾'],
    'lg_27gp850.png'      => ['LG 27GP850-B', '#dc2626', '🖥️'],
    'asus_pg279qm.png'    => ['ASUS ROG Swift', '#1e293b', '🖥️'],
    'logitech_superlight.png' => ['Logitech G Pro X', '#3b82f6', '🎮'],
    'razer_deathadder.png' => ['Razer DeathAdder', '#22c55e', '🎮'],
];

foreach ($placeholders as $filename => $info) {
    $filepath = $dir . $filename;
    if (file_exists($filepath)) continue;
    
    // Create 400x400 image
    $img = imagecreatetruecolor(400, 400);
    
    // Parse hex color
    $hex = $info[1];
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    
    // Background color
    $bg = imagecolorallocate($img, $r, $g, $b);
    imagefilledrectangle($img, 0, 0, 399, 399, $bg);
    
    // Text
    $white = imagecolorallocate($img, 255, 255, 255);
    $text = $info[0];
    
    // Center text
    $fontSize = 5; // Built-in font
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textX = (400 - $textWidth) / 2;
    imagestring($img, $fontSize, $textX, 190, $text, $white);
    
    imagepng($img, $filepath);
    imagedestroy($img);
    
    echo "Created: $filename\n";
}

echo "Done!\n";
