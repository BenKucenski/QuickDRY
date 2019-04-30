<?php

/**
 * Class USPSAPI
 */
class USPSAPI extends SafeClass
{
    public static function Verify($address, $city, $state, $zip)
    {
        // http://production.shippingapis.com/ShippingAPITest.dll?  API=Verify&XML=%3CAddressValidateRequest%20USERID=%22548INSTI0029%22%3E%3CAddress%20ID=%220%22%3E%3CAddress1%3E%3C/Address1%3E%20%3CAddress2%3E6406%20Ivy%20Lane%3C/Address2%3E%3CCity%3EGreenbelt%3C/City%3E%3CState%3EMD%3C/State%3E%3CZip5%3E%3C/Zip5%3E%3CZip4%3E%3C/Zip4%3E%3C/Address%3E%3C/AddressValidateRequest%3E
        //                                                          API=Verify&XML=%3CAddressValidateRequest%20USERID=%22548INSTI0029%22%3E%3CAddress   ID=%220%22%3E%3CAddress1%3E%3C/Address1%3E   %3CAddress2%3E6406%20Ivy%20Lane%3C/Address2%3E%3CCity%3EGreenbelt%3C/City%3E%3CState%3EMD%3C/State%3E%3CZip5%3E%3C/Zip5%3E%3CZip4%3E%3C/Zip4%3E%3C/Address%3E%3C/AddressValidateRequest%3E
        $params = [];
        $params['API'] = 'Verify';
        $params['XML'] = '<AddressValidateRequest USERID="' . USPS_API_USERID .  '"><Address ID="0"><Address1></Address1><Address2>6406 Ivy Lane</Address2><City>Greenbelt</City><State>MD</State><Zip5></Zip5><Zip4></Zip4></Address></AddressValidateRequest>';
        $url = 'http://production.shippingapis.com/ShippingAPITest.dll';
        $res = Curl::Post($url, $params, true);
        CleanHalt($res);

    }
}