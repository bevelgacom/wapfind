<?php
$url = "";
$filetype = "";
$raw_image = NULL;

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

$context = stream_context_create(['http' => array('method' => 'HEAD')]);
$headers = get_headers($url, true, $context);

if (array_key_exists('Content-Type', $headers)) {
    $headers['content-type'] = $headers['Content-Type'];
}

if (!array_key_exists('content-type', $headers)) {
    echo "Failed to get the image, its server did not return expected details :(";
    exit();
}

$allowed_types = ["image/jpeg", "image/png"];

if (!in_array($headers['content-type'], $allowed_types)) {
    echo("Unsupported file type :( " . $headers['content-type']);
    exit();
}

// get the image
$raw_image = file_get_contents($url);

$im1 = new Imagick();
$im1->readImageBlob($raw_image);

// Resize the image to 100 pixels in width (height auto-adjusts)
$im1->resizeImage(100, 0, Imagick::FILTER_LANCZOS, 1);

// get image width and height
$width = $im1->getImageWidth();

// Apply Floyd-Steinberg dithering and remap to a grayscale pattern
$palette = new Imagick();
$palette->newPseudoImage(100, $width, "pattern:gray50");
$im1->remapImage($palette, Imagick::DITHERMETHOD_FLOYDSTEINBERG);

// Output the image as a BMP format and store it in a variable
$im1->setImageFormat('wbmp');
$bmpData = $im1->getImageBlob();

// Optionally, display or save the output
header('Content-Type: image/vnd.wap.wbmp');
echo $bmpData;

// Cleanup
$im1->clear();
$im1->destroy();
$palette->clear();
$palette->destroy();

?>
