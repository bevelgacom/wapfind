<?php
require_once('urlcache.php');

$url = "";
$filetype = "";
$raw_image = NULL;

$supports_jpeg = false;
if (strpos($_SERVER['HTTP_ACCEPT'], 'image/jpeg') !== false) {
    $supports_jpeg = true;
}

//get the image url
if (isset( $_GET['i'] ) ) {
    $url = get_url_for_key($_GET["i"]);
    if (!$url) {
        $url = $_GET["i"];  
    }
} else {
    echo("no image URL :(");
    exit();
}

//an image will start with http, anything else is sus
if (substr( $url, 0, 4 ) != "http") {
    echo("image URL invalid :(");
    exit();
}

// get the image with curl
$c = curl_init($url);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($c, CURLOPT_MAXREDIRS, 10);
curl_setopt($c, CURLOPT_TIMEOUT, 10);
curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0");
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: same-origin',
        'Sec-Fetch-User: ?1',
        'Priority: u=0, i',
        'Pragma: no-cache',
        'Cache-Control: no-cache',
        'TE: trailers'
    ));
$raw_image = curl_exec($c);
curl_close($c);

$im1 = new Imagick();
try {
    $im1->readImageBlob($raw_image);
} catch (ImagickException $e) {
    echo("Failed to read image :(");
    echo($url);
    echo($raw_image);
    exit();
}

// Resize the image to 100 pixels in width (height auto-adjusts)
$im1->resizeImage(100, 0, Imagick::FILTER_LANCZOS, 1);

if (!$supports_jpeg) {
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
    $im1->setImageCompression(Imagick::COMPRESSION_JPEG);
    $im1->setImageCompressionQuality(20);
    $im1->setImageFormat('jpeg');
    header('Content-Type: image/jpeg');
}
$bmpData = $im1->getImageBlob();

echo $bmpData;

// Cleanup
$im1->clear();
$im1->destroy();
if (!$supports_jpeg) {
    $palette->clear();
    $palette->destroy();
}

?>
