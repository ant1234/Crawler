<?php

// Control speed site is crawled
set_time_limit(1000);

// Include the scraper library
include('../simplehtmldom/simple_html_dom.php');

// Include the phpcrawl-mainclass
include("libs/PHPCrawler.class.php");


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
    
    // Now you should do something with the content of the actual
    // received page or file ($DocInfo->source), we skip it in this example 
    
    echo $lb; 


    // Get details of each page that is crawled
    $pageurl= $DocInfo->url;
    $status = $DocInfo->http_status_code;
    $source = $DocInfo->source;

    if($status==200 && $source!=""){


    // create dom from page crawled
    $html = str_get_html($source );

    if(is_object($html)){


       foreach($html->find('h2[class=detailAddress]') as $address ) {

        // Just get the text inside the element
        $address = $address->innertext;

        // Split the string in half & store the area in a seperate variable 
        $address_input = preg_split("#.*,#", $address);
        $area_input = preg_split("#,\s.*#", $address);
          
        // Address and area input
        foreach($address_input as $ad){
          echo $ad.' <br>';
        }

        // Address and area input
        foreach($area_input as $ar){
          echo $ar.' <br>';
        }
        
       }



      // Bedrooms
      $ul = $html->find('ul[id=detailFeatures]');
      foreach($ul as $bedroom){
        $bedrooms = $bedroom->firstChild().'<br>';
        // $bedrooms = $bedrooms->innertext;

        echo $bedrooms.' <br>';
      }


    
      // Price 
      foreach($html->find('h3[id=listingViewDisplayPrice]') as $price) {

        // Just get the price text
        $price = $price->innertext;

        // There probably should be more code here to make sure the result is numeric 

        // Take out the dollar sign & the commas
        $filterCharacters = array("$", ",");
        $price = str_replace($filterCharacters, "", $price);


        // This will insert into the database real quick so be careful

        // if(is_numeric($price)){
        //   $sql = "INSERT INTO property (ID,ADDRESS,AREA,BEDROOMS,PRICE,URL) 
        //           VALUES ('NULL', '$address_input', '$area_input','', '$price', '')";
        // } else {
        //   $sql = "INSERT INTO property (PRICE) 
        //   VALUES ('NULL', '$address_input', '$area_input','', 'NULL', '')";
        // }

        // $conn->query($sql);
      }
        
    }

    // Page url
    echo $pageurl."<br/>";

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
$crawler->setTrafficLimit(1000 * 10485760);

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