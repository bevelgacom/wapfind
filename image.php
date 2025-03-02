<?php
    $url = "";
    
    //get the image url
    if (isset( $_GET['i'] ) ) {
        $url = $_GET["i"];  
    } else {
        exit();
    }

    header('Content-Type: text/vnd.wap.wml');
?>
<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">

<wml>
<card id="card1" title="W@PFind! Image Viewer">
<p align="center">
<img src="/c?i=<?php echo urlencode($url); ?>" alt="image"/>
</p>

<do type="prev" label="Back">
<prev/>
</do>
</card>
</wml>