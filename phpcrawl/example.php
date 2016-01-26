<?php

// Control speed site is crawled
set_time_limit(10000);

// Include the scraper library
include('../simplehtmldom/simple_html_dom.php');

// Include the phpcrawl-mainclass
include("libs/PHPCrawler.class.php");

session_start();


// Extend the class and override the handleDocumentInfo()-method 
class MyCrawler extends PHPCrawler 
{
  function handleDocumentInfo($DocInfo) 
  {


    // Create connection
    $conn = new mysqli('localhost', 'root', '', 'property');


    // Just detect linebreak for output ("\n" in CLI-mode, otherwise "<br>").
    if (PHP_SAPI == "cli") $lb = "\n";
    else $lb = "<br />";

    // Print the URL and the HTTP-status-Code
    echo "Page requested: ".$DocInfo->url." (".$DocInfo->http_status_code.")".$lb;
    
    // Print the refering URL
    echo "Referer-page: ".$DocInfo->referer_url.$lb;
    
    // Print if the content of the document was be recieved or not
    if ($DocInfo->received == true)
      echo "Content received: ".$DocInfo->bytes_received." bytes".$lb;
    else
      echo "Content not received".$lb; 
    
    echo $lb; 


    // Get details of each page that is crawled
    $pageurl= $DocInfo->url;
    $status = $DocInfo->http_status_code;
    $source = $DocInfo->source;

    if($status==200 && $source!=""){

      // create dom from page crawled
      $html = str_get_html($source);

      if(is_object($html)){

        // Look for elements that contain the address, no.bedrooms & price
        $collection['address'] = $html->find('h2[class=detailAddress]');
        $collection['bedrooms'] = $html->find('ul[id=detailFeatures] span',0)->innertext;
        $collection['price'] = $html->find('h3[id=listingViewDisplayPrice]');

        // If the page doesn't have all 3 skip page, otherwise run code
        if(  !empty($collection['address'])
          && !empty($collection['bedrooms'])
          && !empty($collection['price']) ) {
          
          // Process the address element
          foreach($html->find('h2[class=detailAddress]') as $address ) {

          // Take the text that's inside the element
          $address = $address->innertext;

          // Split the string in half & store the area in a seperate variable 
          $address_input = preg_split("#.*,#", $address);
          $area_input = preg_split("#,\s.*#", $address);

          // Store the values in the session if theres both an address & area
          if($address_input != '' && $area_input != ''){

            // Store the address input
            foreach($address_input as $ad){
              $dataCollection['address'][] = $ad;
              $dataCollection['address'] = array_filter( $dataCollection['address'], 'strlen' );
            }

            // Store the area input
            foreach($area_input as $ar){
              $dataCollection['area'][] = $ar;
              $dataCollection['area'] = array_filter( $dataCollection['area'], 'strlen' );
            }

          } 

       } // end of the addresses loop


      // Price 
      foreach($html->find('h3[id=listingViewDisplayPrice]') as $price) {

        // Just get the price text
        $price = $price->innertext;

        // Take out the dollar sign & the commas
        $filterCharacters = array("$", ",");
        $price = str_replace($filterCharacters, "", $price);

        // Make the value a number or null
        $price = (int)$price;

        // If the value of price is a number insert the price into the session
        // otherwise specify that there was no price given for that listing
        if(empty($price)){
          $dataCollection['pricess'][] = 'NO_PRICE_TAG';
        } else {
          $dataCollection['pricess'][] = $price;
        }

        // Is it a property
        if(is_numeric($dataCollection['pricess'][0]) && $dataCollection['pricess'][0] < 5000 ) {
          $dataCollection['type'][] = 'RENTAL';
        } else {
          $dataCollection['type'][] = 'SALE';
        }

        // Values for the database 
        $db_address_input = $dataCollection['address'][0];
        $db_area_input = $dataCollection['area'][0];
        $db_type_input = $dataCollection['type'][0];
        $db_bedroom_input = $collection['bedrooms'];
        $db_price_input = $dataCollection['pricess'][0];
        $db_url_input = $DocInfo->url;

        // Insert the collected data into the database 
        $sql = "INSERT INTO property 
                       (ID,ADDRESS,AREA,TYPE,BEDROOMS,PRICE,URL) 
                VALUES ('NULL',
                        '$db_address_input',
                        '$db_area_input',
                        '$db_type_input',
                        '$db_bedroom_input',
                        '$db_price_input',
                        '$db_url_input' )";

        // Insert the data in the database
        $conn->query($sql);

        } // end if

        } else {
          echo 'skip these pages <br>';
        }
 
      }

    $html->clear(); 
    unset($html);

    } 
    
    flush();
  } 
}

// Bring the crawler out
$crawler = new MyCrawler();

// URL to crawl
$crawler->setURL("http://harcourts.co.nz/");

// Crawl only URL's with the word property in them
$crawler->addURLFollowRule("#property# i");

// Only receive content of files with content-type "text/html"
$crawler->addContentTypeReceiveRule("#text/html#");

// Ignore links to pictures, dont even request pictures
$crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");


// Store and send cookie-data like a browser does
$crawler->enableCookieHandling(true);

// Set the traffic-limit to 10mb
$crawler->setTrafficLimit(1000 * 104857600);

// Start crawler
$crawler->go();

// At the end, after the process is finished print report
$report = $crawler->getProcessReport();

if (PHP_SAPI == "cli") $lb = "\n";
else $lb = "<br />";
    
echo "Summary:".$lb;
echo "Links followed: ".$report->links_followed.$lb;
echo "Documents received: ".$report->files_received.$lb;
echo "Bytes received: ".$report->bytes_received." bytes".$lb;
echo "Process runtime: ".$report->process_runtime." sec".$lb; 

?>