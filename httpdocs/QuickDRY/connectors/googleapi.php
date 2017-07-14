<?php

/**
 * Class googleRequest
 */
class googleRequest {

	var $gKey;
	var $code;
	var $Accuracy;
	var $latitude;
	var $longitude;
	var $address;
	var $city;
	var $zip;
	var $country;
	var $error;

    /**
     * @param        $address
     * @param        $city
     * @param        $zip
     * @param string $country
     *
     * @return array
     */
    function GetForAddress($address, $city, $zip, $country = '')
	{
		$this->gKey = GOOGLE_GEOCODEAPIKEY;
		$this->address = $address;
		$this->city = $city;
		$this->zip = $zip;
		$this->country = $country;
		return $this->GetRequest();
	}

    /**
     * @return array
     */
    function GetRequest()
	{

		if (strlen($this->gKey) > 1) {
			$q = str_replace(' ', '_', str_replace(' ','+',urlencode($this->address)) . ',+'.str_replace(' ','+',$this->city).',+'.str_replace(' ','+',$this->country).',+'.$this->zip);
			if ($d = fopen("http://maps.googleapis.com/maps/api/geocode/xml?address=$q&sensor=false", "r")) {
				$gcsv = '';
				while($r = fread($d, 2048))
					$gcsv .= $r;
				fclose($d);

				$xml = new SimpleXMLElement($gcsv);

				$output= [];
				if(isset($xml->result[0]) && is_object($xml->result[0]))
				{
					$output[0]=$this->latitude  = strip_tags($xml->result[0]->geometry->location->lat->asXML());
					$output[1]=$this->longitude = strip_tags($xml->result[0]->geometry->location->lng->asXML());
				}
				else
				{
					$output[0] = 0;
					$output[1] = 0;
				}
				return $output;

			} else {
				$error = "NO_CONNECTION" ;
			}
		} else {
			$error = "No Google Maps Api Key" ;
		}
		exit($error);
	}

}
