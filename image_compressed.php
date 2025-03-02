<?php
$url = "";
$filetype = "";
$raw_image = NULL;

$supports_png = false;
if (strpos($_SERVER['HTTP_ACCEPT'], 'image/png') !== false) {
    $supports_png = true;
}

//get the image url
if (isset( $_GET['i'] ) ) {
    $url = $_GET[ 'i' ];
} else {
    echo("no image URL :(");
    exit();
}

//an image will start with http, anything else is sus
if (substr( $url, 0, 4 ) != "http") {
    echo("image URL invalid :(");
    exit();
}

// get the image
$raw_image = file_get_contents($url);

$im1 = new Imagick();
try {
    $im1->readImageBlob($raw_image);
} catch (ImagickException $e) {
    echo("Failed to read image :(");
    exit();
}

// Resize the image to 100 pixels in width (height auto-adjusts)
$im1->resizeImage(100, 0, Imagick::FILTER_LANCZOS, 1);

if (!$supports_png) {
    // get image width and height
    $width = $im1->getImageWidth();

    // Apply Floyd-Steinberg dithering and remap to a grayscale pattern
    $palette = new Imagick();
    $palette->newPseudoImage(100, $width, "pattern:gray50");
    $im1->remapImage($palette, Imagick::DITHERMETHOD_FLOYDSTEINBERG);

    // Output the image as a BMP format and store it in a variable
    $im1->setImageFormat('wbmp');
    header('Content-Type: image/vnd.wap.wbmp');
} else {
    $im1->setImageFormat('png');
    header('Content-Type: image/png');
}
$bmpData = $im1->getImageBlob();

echo $bmpData;

// Cleanup
$im1->clear();
$im1->destroy();
$palette->clear();
$palette->destroy();

?>
