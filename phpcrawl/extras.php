        







1. if these elements are on the page 
      display the urls for those pages in a list 
      
      2. get each element value and add it to an array with a key

      3. loop over every key and insert values into database 


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


        This will insert into the database real quick so be careful

        if(is_numeric($price)){
          $sql = "INSERT INTO property (ID,ADDRESS,AREA,BEDROOMS,PRICE,URL) 
                  VALUES ('NULL', '$address_input', '$area_input','', '$price', '')";
        } else {
          $sql = "INSERT INTO property (PRICE) 
          VALUES ('NULL', '$address_input', '$area_input','', 'NULL', '')";
        }

        $conn->query($sql);
      }