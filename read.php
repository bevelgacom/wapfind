<?php
require_once('vendor/autoload.php');
require_once('urlcache.php');

$article_url = "";
$article_html = "";
$error_text = "";

$show_more_button = false;
$offset = 0;
$initial_offset = 0;

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

if (array_key_exists('Content-Type', $headers)) {
    $headers['content-type'] = $headers['Content-Type'];
}
if (array_key_exists('Content-Length', $headers)) {
    $headers['content-length'] = $headers['Content-Length'];
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
    $readable_article = preg_replace( '/<li.*".>/', '<br/> *', $readable_article ); //change <li> to '* '
    $readable_article = str_replace( '</li>', '', $readable_article ); //change </li> to ''
    $readable_article = str_replace( '<p>', '<br/>', $readable_article ); //change </p> to ''
    $readable_article = str_replace( '</p>', '', $readable_article ); //change </p> to ''
    $readable_article = str_replace( '<br/>', '<br/>', $readable_article ); //change <br/> to <br/>
    
    // remove all cite_note links from wikipedia
    $readable_article = preg_replace('/<a href="#cite_note-[^>]+>[^<]+<\/a>/', '', $readable_article);

    // strip title tags from <a> tags
    $readable_article = preg_replace('/title="[^"]+"/', '', $readable_article);
    
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

    $readable_article_parts = explode('<br/>', $readable_article);
    $readable_article = "";
    
    foreach ($readable_article_parts as $part) {
        global $offset;
        global $readable_article;
        $part = trim($part);
        if (strlen($readable_article.$part) < 700+$offset) {
            $readable_article .= "<br/>" . $part;
        } else {
            // add one more part to the end of the article
            $readable_article .= "<br/>" . $part;

            $show_more_button = true;
            $offset = strlen($readable_article);
            break;
        }
    }
    
    // limit readable_article to 700 characters without breaking html tags
    if (strlen($readable_article) > 700+$initial_offset) {
        $start = $initial_offset;
       
        $tag = false;
        $offset = 0;
        $c = 0;
        while (!$tag) {
            $tag = strpos($readable_article, '</', 700-$c+$initial_offset);
            if ($tag !== false) {
                $tag = strpos($readable_article, '>', $tag);
                if ($tag !== false) {
                    $offset = $tag+1;
                    break;
                }
            }
            if ($tag === false) {
                $tag = strpos($readable_article, '/>', 700-$c+$initial_offset);
                if ($tag !== false) {
                    $offset = $tag+2;
                    break;
                }
            }
            $c++;
        }

        $readable_article = substr($readable_article, $initial_offset, $offset);


        

        $show_more_button = true;
    } else if ($initial_offset > 0) {
        $readable_article = substr($readable_article, $initial_offset);
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