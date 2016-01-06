 <?php

 $v = array();
    $i=0;
    // start loop
                foreach ($this->json_data->locations as $key => $value) {
                    if ($value->country_name == $data['city']->country_name)
    // return $value with data
                         $i++;
                         $v[$i] = $value ; 
                }
    //print $v
                print_r($v);

?>




