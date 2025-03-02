<?php

    $url = "";
    
    //get the image url
    if (isset( $_GET['i'] ) ) {
        $url = $_GET[ 'i' ];
    } else {
        exit();
    }

    //we can only do jpg and png here
    if (strpos($url, ".jpg") && strpos($url, ".jpeg") && strpos($url, ".png") != true ) {
        echo strpos($url, ".jpg");
        echo "Unsupported file type :(";
        exit();
    }

    //image needs to start with http
    if (substr( $url, 0, 4 ) != "http") {
        echo("Image failed :(");
        exit();
    }

    header('Content-Type: text/vnd.wap.wml');
?>
<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">

<wml>
<card id="card1" title="W@PFind! Image Viewer">
<p align="center">
<img src="/image_compressed.php?i=<?php echo $url; ?>" alt="image"/>
</p>

<do type="prev" label="Back">
<prev/>
</do>
</card>
</wml>