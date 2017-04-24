<?php
function deleteEngagements($vid, $type) {
    global $hubspot;
    
    $old_engagements = $hubspot->engagements()->get_engagements($vid, $type, array("limit" => 100));
                    
    foreach($old_engagements->data->results as $old_engagement) {
        $hubspot->engagements()->delete($old_engagement->engagement->id);
    }
}



function createEngagements($vid, $engagements) {
        global $hubspot, $isCompany, $owner;
        
        $cleanPattern = '/(Jan[\.]?|Feb[\.]?|March[\.]?|April[\.]?|May[\.]?|June[\.]?|July[\.]?|Aug[\.]?|Sept[\.]?|Oct[\.]?|Nov[\.]?|Dec[\.]?)(\/)([0-9]{4})/i';
        $replacement = '$1 $3';
        $engagements = preg_replace($cleanPattern, $replacement, $engagements);

        $regex = '/(Jan[\.]?|Feb[\.]?|March[\.]?|April[\.]?|May[\.]?|June[\.]?|July[\.]?|Aug[\.]?|Sept[\.]?|Oct[\.]?|Nov[\.]?|Dec[\.]?) ([0-9]{1,2}\/[0-9]{4})[\r\n]+([A-Za-z0-9 .&\,\n:?#\'"]*\n)/i';
        if(preg_match_all($regex, $engagements, $matches) > 0) {
                foreach($matches[0] as $matche) {
                    if(preg_match($regex, $matche, $match) > 0) {

                        if($isCompany)
                            $association = array("companyIds" => array($vid));
                        else
                            $association = array("contactIds" => array($vid));
                        
                        $metadata = array("body" => $match[3]);
                        list($day, $year) = explode("/", $match[2]);
                        $time = $day." ".$match[1].$year;
                        $time = strtotime(str_replace(".", " ", $time));
                        $time = $time * 1000;
                        
                        //@TODO CHANGE OWNER AFTER TESTING
                        $engagement =  array("active" => "true", "type" => "CALL", "timestamp" => $time, "ownerId" => $owner);
                        
                        
                        echo "<br>";
                        print_r($hubspot->engagements()->create($engagement, $association, $metadata));
                        echo "<br>";
                        

                    }
                }
                
            } else {
                $engagement =  array("active" => "true", "type" => "NOTE");
                        
                if($isCompany)
                    $association = array("companyIds" => array($vid));
                else
                    $association = array("contactIds" => array($vid));
                        
                $metadata = array("body" => $engagements);
                 
                //@TODO CHANGE OWNER AFTER TESTING
                $engagement =  array("active" => "true", "type" => "NOTE", "ownerId" => $owner);                        
                        
                //echo "<br>";
                print_r($hubspot->engagements()->create($engagement, $association, $metadata));
                //echo "<br>";
            }
}


function getAllCompanies($params) {
    global $hubspot, $companies;
    
    $companiesRes = $hubspot->companies()->all($params);
    
    foreach($companiesRes->data->companies as $company) {
        $companyObj = new stdClass();
        $companyObj->id = $company->companyId;
        $companyObj->name = $company->properties->name->value;
        $companies[] = $companyObj;
    }

    if($companiesRes->data->{"has-more"}) {
        getAllCompanies(array("limit" => 250, "offset" => $companiesRes->data->offset, "properties" => "name"));
    }
    
    
}


function getCompanyId($name) {
    global $companies;
    
    foreach($companies as $company) {
        if(strtolower($company->name) == strtolower($name)) {
            return $company->id;
        }
    }
    
    return -1;
}

function createOrUpdateCompany($vid, $company, $recordHS) {
    global $hubspot;
    
    $companyId= getCompanyId($company);
    if($companyId > -1) {
        $hubspot->companies()->addContact($vid, $companyId);
    } else {
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
        $companyId = $company->companyId;
                        
        $newCompany = new stdClass();
        $newCompany->id = $companyId;
        $newCompany->name = $company;
        $companies[] = $newCompany;
                        
        $hubspot->companies()->addContact($vid, $companyId);
    }
}