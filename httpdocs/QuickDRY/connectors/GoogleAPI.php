<?php

/**
 * Class googleRequest
 */
class GoogleAPI {

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
    var $result;

    /**
     * @param        $address
     * @param        $city
     * @param        $zip
     * @param string $country
     *
     * @return GoogleAPI
     */
    public static function GetForAddress($address, $city, $zip, $country = '')
	{
	    $t = new GoogleAPI();

        $t->gKey = GOOGLE_GEOCODEAPIKEY;
        $t->address = $address;
        $t->city = $city;
        $t->zip = $zip;
        $t->country = $country;
		$t->GetRequest();
		return $t;
	}

    /**
     *
     */
    function GetRequest()
	{
		if (strlen($this->gKey) > 1) {
			$q = str_replace(' ', '_', str_replace(' ','+',urlencode($this->address)) . ',+'.str_replace(' ','+',$this->city).',+'.str_replace(' ','+',$this->country).',+'.$this->zip);
			if ($d = fopen("http://maps.googleapis.com/maps/api/geocode/xml?address=$q&sensor=false", "r")) {
				$gcsv = '';
				while($r = fread($d, 2048)) {
                    $gcsv .= $r;
                }
				fclose($d);
                $this->result = $gcsv;
                $res = self::ParseResult($gcsv);
                $this->latitude = $res['latitude'];
                $this->longitude = $res['longitude'];

                return;
			} else {
				$error = "NO_CONNECTION" ;
			}
		} else {
			$error = "No Google Maps Api Key" ;
		}
		Halt($error);
	}

    /**
     * @param $result
     * @return array
     */
	public static function ParseResult($result)
    {
        $res = [];
        $res['error'] = '';
        $res['latitude'] = 0;
        $res['longitude'] = 0;

        if(!$result) {
            return $res;
        }

        try {
            $xml = new SimpleXMLElement($result);
        } catch(exception $ex) {
            return $res;
        }


        if(isset($xml->error_message)) {
            $res['error'] = $xml->error_message;
        }
        if(isset($xml->result[0]) && is_object($xml->result[0]))
        {
            $res['latitude']  = strip_tags($xml->result[0]->geometry->location->lat->asXML());
            $res['longitude'] = strip_tags($xml->result[0]->geometry->location->lng->asXML());
        }
        return $res;
    }

}
