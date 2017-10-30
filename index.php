<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__.'/functions.php';
$owner = "18806566";
$HSAPIKey = 'HUBSPOT API KEY HERE';
$csv = "contacts.csv";

$hubspot = SevenShores\Hubspot\Factory::create($HSAPIKey);

$companies = array();
getAllCompanies(array("limit" => 250, "properties" => "name"));




$fieldsMapping = array(0 => "", 1 => "firstname", 2 => "", 3 => "lastname", 4 => "", 5 => "company", 6 => "jobtitle", 7 => "address", 8 => "", 9 => "city", 10 => "state", 11 => "zip", 12 => "country", 13 => "phone");

$row = 1;
$engagements = array();
if (($handle = fopen($csv, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $num = count($data);
        $row++;
        $record = array();
        $recordHS = array();
        $engagements = "";
        $vid = 0;
        $isCompany = false;
        for ($c=0; $c < $num; $c++) {
            if($row > 2) {

                if($c == 14)
                    $engagements = $data[$c];
                else {
                    if($fieldsMapping[$c] !== "") {
                        if($fieldsMapping[$c] == "phone") {
                            $property = new stdClass();
                            $property->property = $fieldsMapping[$c];
                            $property->value = preg_replace('/\D+/', '', $data[$c]);
                            $property->value = substr($property->value, 0, 10);
                            $recordHS[] = $property;
                            
                            $record[$fieldsMapping[$c]] = $property->value;
                            
                        } else {
                            $property = new stdClass();
                            $property->property = $fieldsMapping[$c];
                            $property->value = $data[$c];
                            $recordHS[] = $property;
                            
                            
                            $record[$fieldsMapping[$c]] = $data[$c];
                        }
                    }
                    
                }
            }
        }
        
        //setting HS Owner as myself for testing @TODO REMOVE LATER
        $property = new stdClass();
        $property->property = "hubspot_owner_id";
        $property->value = $owner;
        $recordHS[] = $property;
        
        echo $record['phone'];
        if(!empty($record['phone'])) {
            if(empty($record['firstname'])) {
                if(getCompanyId($record['company']) == -1) {
                    echo " -- Create Company<br>";

                    $recordCompanyHS = array();
            
                    foreach($recordHS as $field) {
                        if($field->property == "company")
                            $field->property = "name";
                    
                        if($field->property != "firstname" && $field->property != "lastname" && $field->property != "jobtitle") {
                            $fieldCompany = new stdClass();
                            $fieldCompany->name = $field->property;
                            $fieldCompany->value = $field->value;
                        
                            $recordCompanyHS[] = $fieldCompany;
                        }
                 
                    }
                
                    $company = $hubspot->companies()->create($recordCompanyHS);
                    print_r($company);
                    $vid = $company->companyId;
                    $newCompany = new stdClass();
                    $newCompany->id = $vid;
                    $newCompany->name = $record['company'];
                    $companies[] = $newCompany;
                    $isCompany = true;
                }
            } else {
            
                $contacts = $hubspot->contacts()->search($record['phone']);
                if($contacts->data->total > 0) {
                    $vid = $contacts->data->contacts[0]->vid;
                    echo " -- Update Contact<br>";
                    
                    
                    $contact = $hubspot->contacts()->update($vid, $recordHS);
                    
                    
                    //delete engagements @TODO REMOVE AFTER TESTING
                    deleteEngagements($vid, "contact");
                    
                    createOrUpdateCompany($vid, $record['company'], $recordHS);
                    
                    print_r($contact);
                } else {
                    echo " -- Create Contact<br>";
                    $contact = $hubspot->contacts()->create($recordHS);
                    print_r($contact);
                    $vid = $contact->vid;
                    
                    createOrUpdateCompany($vid, $record['company'], $recordHS);
                    
                    
                }
                
            }
        }
            
            if($vid > 0)
                createEngagements($vid, $engagements);
            echo "<br><br>";
            
        }
        
      

    fclose($handle);
        
}
echo "<br><br>";



/*
$testEngagement = "Travel Time 2hrs. 14 mins.  Too far.
";
$cleanPattern = '/(Jan[\.]?|Feb[\.]?|March[\.]?|April[\.]?|May[\.]?|June[\.]?|July[\.]?|Aug[\.]?|Sept[\.]?|Oct[\.]?|Nov[\.]?|Dec[\.]?)(\/)([0-9]{4})/i';
$replacement = '$1 $3';
$testEngagement = preg_replace($cleanPattern, $replacement, $testEngagement);

$regex = '/(Jan[\.]?|Feb[\.]?|March[\.]?|April[\.]?|May[\.]?|June[\.]?|July[\.]?|Aug[\.]?|Sept[\.]?|Oct[\.]?|Nov[\.]?|Dec[\.]?) ([0-9]{1,2}\/[0-9]{4})[\r\n]+([A-Za-z0-9 .&\,\n:?#\'"]*\n)/i';
if(preg_match_all($regex, $testEngagement, $matches) > 0) {
    //print_r($matches);exit;
    foreach($matches[0] as $matche) {
        if(preg_match($regex, $matche, $match) > 0) {
            
            print ($match[3])."<br>";
        }
        
        
    }
} else {
    echo "Nothing matched";
}
*/
