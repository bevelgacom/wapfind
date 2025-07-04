<?php
require_once('vendor/autoload.php');
require_once('urlcache.php');

$article_url = "";
$article_html = "";
$error_text = "";

$show_more_button = false;
$offset = 0;
$initial_offset = 0;

$size_limit = 700; // Maximum size of the article to display at once

function tokenizeArticle($article) {
    // Use a regex pattern to match entire HTML tags along with their contents or plain text separately
    preg_match_all('/<[^>]+>[^<]*<\/[^>]+>|<[^>]+>|[^<>]+/', $article, $matches);
    
    // Return the matched tokens as an array
    return $matches[0];
}

if (isset($_GET['o']) && $_GET['o']  > 0) {
    $offset = $_GET['o'];
    $initial_offset = $_GET['o'];
}

// List of content-types that we know we can (try to) parse. 
// Anything else will get piped through directly, if possible.
$compatible_content_types = [
    "text/html",
    "text/plain"
];

// The maximum allowed filesize for proxy download passthroughs. 
// Any file larger than this will instead show an error message, with
// a direct link to the file.
$proxy_download_max_filesize = 8000000; // ~ 8Mb

if( isset( $_GET['a'] ) ) {
    $article_url = get_url_for_key($_GET['a']);
    if (!$article_url) {
        $article_url = "http".$_GET["a"];  
    }
} else {
    echo "What do you think you're doing... >:(";
    exit();
}

if (substr( $article_url, 0, 4 ) != "http") {
    echo("That's not a web page :(");
    die();
}

$url = parse_url($article_url);
$host = $url['host'];

// Attempt to figure out what the requested URL content-type may be
$context = stream_context_create(['http' => array('method' => 'HEAD')]);
$headers = get_headers($article_url, true, $context);

$redirs_followed = 0;
if (array_key_exists('Location', $headers)) {
    $headers['location'] = $headers['Location'];
}
while (array_key_exists('location', $headers)) {
    // If the server returned a redirect, follow it
    // Handle case where Location header could be an array
    if (is_array($headers['location'])) {
        $article_url = end($headers['location']); // Get the last redirect URL
    } else {
        $article_url = $headers['location'];
    }

    // handle relative URLs
    if (strpos($article_url, 'http') !== 0) {
        // If the URL is relative, prepend the host
        $article_url = 'http://' . $host . '/' . ltrim($article_url, '/');
    }

    $context = stream_context_create(['http' => array('method' => 'HEAD')]);
    $headers = get_headers($article_url, true, $context);
    $url = parse_url($article_url);
    if (array_key_exists('Location', $headers)) {
        $headers['location'] = $headers['Location'];
    }
    $host = $url['host'];
    $redirs_followed++;
    if ($redirs_followed > 10) {
        // If we followed too many redirects, give up
        $error_text .= "Too many redirects, giving up. :( <br/>";
        break;
    }
}

if (array_key_exists('Content-Type', $headers)) {
    $headers['content-type'] = $headers['Content-Type'];
}
if (array_key_exists('Content-Length', $headers)) {
    $headers['content-length'] = $headers['Content-Length'];
}

// if the user-agent contains `Nokia7110` lower the size limit
if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && strpos($_SERVER['HTTP_USER_AGENT'], 'Nokia7110') !== false) {
    $size_limit = 500; // Maximum size of the article to display at once for Nokia7110
}

if (!array_key_exists('content-type', $headers) || !array_key_exists('content-length', $headers)) {
    $error_text .=  "Failed to get the article, its server did not return expected details :( <br/>";
}
else {
    // Attempt to handle downloads or other mime-types by passing proxying them through.
    foreach ($compatible_content_types as $content_type) {
        if (str_contains($headers['content-type'], $content_type)) {
            $passthrough = false;
            break;
        }
    }
    if ($passthrough) {
        $filesize = $headers['content-length'];

        // Check if the linked file isn't too large for us to proxy.
        if ($filesize > $proxy_download_max_filesize) {
            echo 'Failed to proxy file download, it\'s too large. :( <br/>';
            echo 'You can try downloading the file directly: ' . $article_url;
            die();
        }
        else {
            $contentType = $headers['content-type'];
            // Only use the last-provided content type if an array was returned (ie. when there were redirects involved)
            if (is_array($contentType)) {
                $contentType = $contentType[count($contentType)-1];
            }

            $filename = basename($url['path']);

            // If no filename can be deduced from the URL, set a placeholder filename
            if (!$filename) {
                $filename = "download";
            }
            
            // Set the content headers based on the file we're proxying through.
            header('content-type: ' . $contentType);
            header('content-length: ' . $filesize);
            // Set the content-disposition to encourage the browser to download the file.
            header('Content-Disposition: attachment; filename="'. $filename . '"');

            // Use readfile 
            readfile($article_url);
            die();
        }
    }
}

use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

$configuration = new Configuration();
$configuration
    ->setArticleByLine(false)
    ->setFixRelativeURLs(true)
    ->setOriginalURL('http://' . $host);

$readability = new Readability($configuration);
if(!$article_html = file_get_contents($article_url)) {
    $error_text .=  "Failed to get the article :( <br/>";
}

function replace_links($html) {
    return preg_replace_callback(
        '/<a\s+href=["\']([^"\']+)["\']/i',
        function ($matches) {
            $urlKey = save_url($matches[1]);
            return '<a href="/r?a=' . $urlKey . '"';
        },
        $html
    );
}

try {
    $readability->parse($article_html);

   

    $readable_article = strip_tags($readability->getContent(), '<a><li><br/><p><small><b><strong><i><em>');
    $readable_article = str_replace( 'strong>', 'b>', $readable_article ); //change <strong> to <b>
    $readable_article = str_replace( 'em>', 'i>', $readable_article ); //change <em> to <i>
    $readable_article = preg_replace( '/<li>/', '<br/> *', $readable_article ); //change <li> to '* '
    $readable_article = preg_replace( '/<li[^>]*>/', '<br/> *', $readable_article ); //change <li> to '* '
    $readable_article = str_replace( '</li>', '', $readable_article ); //change </li> to ''

    // remove all cite_note links from wikipedia
    $readable_article = preg_replace('/<a href="#cite_note-[^>]+>[^<]+<\/a>/', '', $readable_article);

    // strip title tags from <a> tags
    $readable_article = preg_replace('/title="[^"]+"/', '', $readable_article);
    $readable_article = preg_replace('/rel="[^"]+"/', '', $readable_article);

    // strip all data- tags 
    $readable_article = preg_replace('/data-[^=]+="[^"]+"/', '', $readable_article);

    $readable_article = preg_replace( '/<p[^>]*>/', '<br/>', $readable_article ); //change <p> to '<br/>'
    $readable_article = str_replace( '</p>', '', $readable_article ); //change </p> to ''
    $readable_article = str_replace( '<br/>', '<br/>', $readable_article ); //change <br/> to <br/>

    $readable_article = clean_str($readable_article);
    //$readable_article = str_replace( 'href="http', 'href="/r?a=', $readable_article ); //route links through proxy

    //route links through proxy and urlencode the full url
    $readable_article = replace_links($readable_article);

    // remove empty a tags
    $readable_article = preg_replace('/<a[^>]*><\/a>/', '', $readable_article);

    // strip tags in link text (e.g. <a href="http://example.com">hello <b>example</b></a> -> <a href="http://example.com">hello example</a>)
    $readable_article = preg_replace_callback('/<a [^>]*>(.*?)<\/a>/', function ($matches) {
        return '<a' . substr($matches[0], 2, strpos($matches[0], '>') - 2) . '>' . strip_tags($matches[1]) . '</a>';
    }, $readable_article);


    $readable_article .= "<br/>";

    $readable_article = substr($readable_article, $initial_offset);

    $readable_article_original = $readable_article;

    $tokens = tokenizeArticle($readable_article);

    $readable_article = "";
    
    foreach ($tokens as $token) {
        if (strlen($readable_article.$token) > $size_limit) {
            // if the token does not contain any html tags, we can add word by word
            if (strpos($token, '<') === false) {
                $words = explode(' ', $token);
                
                foreach ($words as $word) {
                    if (strlen($readable_article.$word) > $size_limit) {
                        break;
                    }
                    $readable_article .= " ". $word;
                }
            }
            break;
        }
        $readable_article .= $token;
    }

    if (strlen($readable_article) < strlen($readable_article_original)) {
        $show_more_button = true;
        $offset = strlen($readable_article) + $initial_offset;
    }
    
} catch (ParseException $e) {
    $error_text .= 'Sorry! ' . $e->getMessage() . '<br/>';
}

//replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace( "‘", "'", $str );    
    $str = str_replace( "’", "'", $str );  
    $str = str_replace( "“", '"', $str ); 
    $str = str_replace( "”", '"', $str );
    $str = str_replace( "–", '-', $str );

    return $str;
}

header('content-type: text/vnd.wap.wml');
?>
<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">

<wml>
<card id="card1" title="<?php echo $readability->getTitle();?>">
<p><b><?php echo clean_str($readability->getTitle());?></b></p>

    <p>
        <small>
        <?php
            $img_num = 0;
            $imgline_html = "View page images:";
            foreach ($readability->getImages() as $image_url):
                if ($img_num > 4) {
                    break;
                }
                //we can only do png and jpg
                if (strpos($image_url, ".jpg") || strpos($image_url, ".jpeg") || strpos($image_url, ".png") === true) {
                    $img_num++;
                    $imgline_html .= " <a href='/i?i=" . save_url($image_url) . "'>[$img_num]</a> ";
                }
            endforeach;
            if($img_num>0 && $initial_offset == 0) {
                echo  $imgline_html ;
            }
        ?>
    </small>
    </p>
    <?php if($error_text) { echo "<p>" . $error_text . "</p>"; } ?>
    <p><?php echo $readable_article;?></p>

<?php if($show_more_button) { ?>
    <p align="center"><a href="/r?a=<?php echo urlencode($_GET['a']); ?>&amp;o=<?php echo $offset ?>">&gt; More</a></p>
<?php } ?>
<do type="prev" label="Back">
<prev/>
</do>
</card>
</wml>