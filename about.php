<?php
    // set content type
    header('Content-Type: text/vnd.wap.wml');
?>
<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">

<wml>
<card id="card1" title="About W@PFind!">
<p align="center">
<img src="/logo.wbmp" alt="W@PFind!"/>
</p>
<p>
What in the world is W@PFind?
</p>

<p><small>A quick FAQ on a(n) (un)conventional search engine</small></p>

<p>
    <b>Who made W@PFind!?</b> <br/>
    Hi I am Maartje, and toghether with a team of retro phone fans under a retro-isp <a href="http://wap.bevelgacom.be">Bevelgacom</a> we made this search engine.<br/>
    The inspiration of this vane from Sean from <a href="https://youtube.com/ActionRetro">Action Retro</a> on YouTube who built a search engine for vintage computers.
</p>
<p>
    <b>WHow does W@PFind! work?</b><br/>
    The search functionality of W@PFind! is basically a custom wrapper for DuckDuckGo search, converting the results to extremely basic HTML that old browsers can read. When clicking through to pages from search results, those pages are processed through a <a href="https://github.com/fivefilters/readability.php">PHP port of Mozilla's Readability</a>, which is what powers Firefox's reader mode. I then further strip down the results to be as basic WML as possible. 
</p>
<p>
    <b>What machines do you test W@PFind! on?</b><br/>
    The engine is specifically designed to fit the constraints of the first Nokia WAP capable phone the Nokia 7110.<br/>
    However, it should work on any WAP 1.1 compatible device, this will include the 2025 S30+ based HMD Global phones.
</p>


<do type="prev" label="Back">
<prev/>
</do>
</card>
</wml>