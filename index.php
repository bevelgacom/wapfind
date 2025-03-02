<?php
require_once('vendor/autoload.php');
header('Content-Type: text/vnd.wap.wml');

$show_results = FALSE;
$results_html = "";
$final_result_html = "<img src=\"/line.wbmp\" alt=\"------\"/>";
$query = "";
$show_more_button = false;

if(isset( $_GET['q'])) { // if there's a search query, show the results for it
    $query = urlencode($_GET["q"]);
    $show_results = TRUE;
    $search_url = "https://lite.duckduckgo.com/lite/";

    // get results using CURL
    // and set these headers

    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $search_url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

    // curl 'https://lite.duckduckgo.com/lite/' --compressed -X POST -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' -H 'Accept-Encoding: gzip, deflate, br, zstd' -H 'Referer: https://lite.duckduckgo.com/' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Origin: https://lite.duckduckgo.com' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1' -H 'Sec-Fetch-Dest: document' -H 'Sec-Fetch-Mode: navigate' -H 'Sec-Fetch-Site: same-origin' -H 'Sec-Fetch-User: ?1' -H 'Priority: u=0, i' -H 'Pragma: no-cache' -H 'Cache-Control: no-cache' -H 'TE: trailers' --data-raw 'q=bevel&kl=&df='
    curl_setopt($c, CURLOPT_POST, 1);
    curl_setopt($c, CURLOPT_POSTFIELDS, "q=$query&kl=&df=");
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0");
    curl_setopt($c, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Referer: https://lite.duckduckgo.com/',
        'Content-Type: application/x-www-form-urlencoded',
        'Origin: https://lite.duckduckgo.com',
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

    $results_html = curl_exec($c);
    curl_close($c);

    // drop all text before '<!-- Web results are present -->'
    $results_html = explode('<!-- Web results are present -->', $results_html)[1];

    $simple_results=$results_html;
    $simple_results = str_replace( 'strong>', 'b>', $simple_results ); //change <strong> to <b>
    $simple_results = str_replace( 'em>', 'i>', $simple_results ); //change <em> to <i>
    $simple_results = clean_str($simple_results);

    $result_blocks = explode('<td valign="top">', $simple_results);
    $total_results = count($result_blocks)-1;

    $offset = 0;
    if (isset($_GET['o']) && $_GET['o']  > 0) {
        $offset = $_GET['o'];
    }
    
    if ($total_results > 5+$offset) {
        $show_more_button = true;
    }

    // show 5 results at a time
    if ($total_results > 5) {
        $total_results = 5;
    }

    for ($x = $offset+1; $x <= $total_results+$offset; $x++) {
        // result link, redirected through our proxy
        $result_link = explode('<a rel="nofollow" href="', $result_blocks[$x])[1];
        $result_topline = explode("\" class='result-link'>", $result_link);
        $result_link = '/read.php?a=' . urlencode($result_topline[0]);
        // result title
        $result_title = str_replace("</a>","",explode("\n", $result_topline[1]));
        // result display url stripped of protocol and path
        $result_display_url = explode('://', $result_topline[0])[1];
        $result_display_url = explode('/', $result_display_url)[0];
        $result_display_url = clean_str($result_display_url);
        // result snippet
        $result_snippet = explode("class='result-snippet'>", $result_blocks[$x])[1];
        $result_snippet = explode('</td>', $result_snippet)[0];

        if (strlen($result_snippet) > 150) {
            $result_snippet = substr($result_snippet, 0, 150) . "...";
        }

        if (strlen($result_title[0]) > 35) {
            $result_title[0] = substr($result_title[0], 0, 35) . "...";
        }

        $final_result_html .= "<br/>\n<a href='" . $result_link . "'>" . $result_title[0] . "<br/>\n" 
                            . $result_display_url . "</a><br/>\n" . $result_snippet . "<br/>\n";
        
    }
}

//replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace( "‘", "'", $str );
    $str = str_replace( "’", "'", $str );  
    $str = str_replace( "“", '"', $str ); 
    $str = str_replace( "”", '"', $str );
    $str = str_replace( "–", '-', $str );
    $str = str_replace( "&#x27;", "'", $str );

    return $str;
}

?>
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">
<wml>
<card id="card1" title="W@PFind!">
<p>
<img src="/logo.wbmp" alt="W@PFind!"/>
</p>

<p>
<input name="q" title="Search:" maxlength="90" value="<?php echo strip_tags(urldecode($query)) ?>"/>
</p>

<?php if($show_results) { ?>
    <p align="center">Search Results for <b><?php echo strip_tags(urldecode($query)) ?></b></p>
    <p><?php echo $final_result_html ?></p>
<?php } ?>

<?php if($show_more_button) { ?>
    <p align="center"><a href="/index.php?q=<?php echo $query ?>&amp;o=<?php echo $offset+5 ?>">More Results</a></p>
<?php } ?>

<p><br/><br/><br/><br/></p>

<p align="center"><small>Powered by DuckDuckGo</small></p>
<p align="center"><a href="about.php">Why build such a thing?</a></p>

<do type="accept" label="&gt; Wappit!">
<go href="/?q=$(q)"/>
</do>
<do type="prev" label="Back">
<prev/>
</do>
</card>
</wml>