<?php
class UTMClass
{
// http://home.hiwaay.net/~taylorc/toolbox/geography/geoutm.html
    public static $pi = 3.14159265358979;

    /* Ellipsoid model constants (actual values here are for WGS84) */
    public static $sm_a = 6378137.0;
    public static $sm_b = 6356752.314;
    public static $sm_EccSquared = 6.69437999013e-03;

    public static $UTMScaleFactor = 0.9996;


    /*
    * DegToRad
    *
    * Converts degrees to radians.
    *
    */
    public static function DegToRad($deg)
    {
        return ($deg * self::$pi / 180.0);
    }


    public static function GetDistanceFromLatLon($lat_a, $lon_a, $lat_b, $lon_b)
    {
        $lat_a_r = self::DegToRad($lat_a);
        $lon_a_r = self::DegToRad($lon_a);
        $lat_b_r = self::DegToRad($lat_b);
        $lon_b_r = self::DegToRad($lon_b);

        $phi = $lat_b_r - $lat_a_r;
        $theta = $lon_b_r - $lon_a_r;

        $a = sin($phi / 2.0) * sin($phi / 2.0) + cos($lat_a_r) * cos($lat_b_r) * sin($theta / 2.0) * sin($theta / 2.0);
        $c = 2.0 * atan2(sqrt($a), sqrt((1 - $a)));
        return self::$sm_a * $c / 1000.0;
    }

    public static function GetMilesFromLatLon($lat_a, $lon_a, $lat_b, $lon_b)
    {
        return self::GetDistanceFromLatLon($lat_a, $lon_a, $lat_b, $lon_b) / 1.6093;
    }

    /*
    * RadToDeg
    *
    * Converts radians to degrees.
    *
    */
    public static function RadToDeg($rad)
    {
        return ($rad * 180.0 / self::$pi);
    }


    /*
    * ArcLengthOfMeridian
    *
    * Computes the ellipsoidal distance from the equator to a point at a
    * given latitude.
    *
    * Reference: Hoffmann-Wellenhof, B., Lichtenegger, H., and Collins, J.,
    * GPS: Theory and Practice, 3rd ed.  New York: Springer-Verlag Wien, 1994.
    *
    * Inputs:
    *     phi - Latitude of the point, in radians.
    *
    * Globals:
    *     $sm_a - Ellipsoid model major axis.
    *     $sm_b - Ellipsoid model minor axis.
    *
    * Returns:
    *     The ellipsoidal distance of the point from the equator, in meters.
    *
    */
    public static function ArcLengthOfMeridian($phi)
    {
        //var alpha, beta, gamma, delta, epsilon, n;
        //var result;

        /* Precalculate n */
        $n = (self::$sm_a - self::$sm_b) / (self::$sm_a + self::$sm_b);

        /* Precalculate alpha */
        $alpha = ((self::$sm_a + self::$sm_b) / 2.0) * (1.0 + (pow($n, 2.0) / 4.0) + (pow($n, 4.0) / 64.0));

        /* Precalculate beta */
        $beta = (-3.0 * $n / 2.0) + (9.0 * pow($n, 3.0) / 16.0) + (-3.0 * pow($n, 5.0) / 32.0);

        /* Precalculate gamma */
        $gamma = (15.0 * pow($n, 2.0) / 16.0) + (-15.0 * pow($n, 4.0) / 32.0);

        /* Precalculate delta */
        $delta = (-35.0 * pow($n, 3.0) / 48.0) + (105.0 * pow($n, 5.0) / 256.0);

        /* Precalculate epsilon */
        $epsilon = (315.0 * pow($n, 4.0) / 512.0);

        /* Now calculate the sum of the series and return */
        $result = $alpha
            * ($phi + ($beta * sin(2.0 * $phi))
                + ($gamma * sin(4.0 * $phi))
                + ($delta * sin(6.0 * $phi))
                + ($epsilon * sin(8.0 * $phi)));

        return $result;
    }


    /*
    * UTMCentralMeridian
    *
    * Determines the central meridian for the given UTM zone.
    *
    * Inputs:
    *     zone - An integer value designating the UTM zone, range [1,60].
    *
    * Returns:
    *   The central meridian for the given UTM zone, in radians, or zero
    *   if the UTM zone parameter is outside the range [1,60].
    *   Range of the central meridian is the radian equivalent of [-177,+177].
    *
    */
    public static function UTMCentralMeridian($zone)
    {
        //var cmeridian;

        $cmeridian = self::DegToRad(-183.0 + ($zone * 6.0));

        return $cmeridian;
    }


    /*
    * FootpointLatitude
    *
    * Computes the footpoint latitude for use in converting transverse
    * Mercator coordinates to ellipsoidal coordinates.
    *
    * Reference: Hoffmann-Wellenhof, B., Lichtenegger, H., and Collins, J.,
    *   GPS: Theory and Practice, 3rd ed.  New York: Springer-Verlag Wien, 1994.
    *
    * Inputs:
    *   y - The UTM northing coordinate, in meters.
    *
    * Returns:
    *   The footpoint latitude, in radians.
    *
    */
    public static function FootpointLatitude($y)
    {
//        var y_, alpha_, beta_, gamma_, delta_, epsilon_, n;
//        var result;

        /* Precalculate n (Eq. 10.18) */
        $n = (self::$sm_a - self::$sm_b) / (self::$sm_a + self::$sm_b);

        /* Precalculate alpha_ (Eq. 10.22) */
        /* (Same as alpha in Eq. 10.17) */
        $alpha_ = ((self::$sm_a + self::$sm_b) / 2.0) * (1 + (pow($n, 2.0) / 4) + (pow($n, 4.0) / 64));

        /* Precalculate y_ (Eq. 10.23) */
        $y_ = $y / $alpha_;

        /* Precalculate beta_ (Eq. 10.22) */
        $beta_ = (3.0 * $n / 2.0) + (-27.0 * pow($n, 3.0) / 32.0) + (269.0 * pow($n, 5.0) / 512.0);

        /* Precalculate gamma_ (Eq. 10.22) */
        $gamma_ = (21.0 * pow($n, 2.0) / 16.0) + (-55.0 * pow($n, 4.0) / 32.0);

        /* Precalculate delta_ (Eq. 10.22) */
        $delta_ = (151.0 * pow($n, 3.0) / 96.0) + (-417.0 * pow($n, 5.0) / 128.0);

        /* Precalculate epsilon_ (Eq. 10.22) */
        $epsilon_ = (1097.0 * pow($n, 4.0) / 512.0);

        /* Now calculate the sum of the series (Eq. 10.21) */
        $result = $y_ + ($beta_ * sin(2.0 * $y_))
            + ($gamma_ * sin(4.0 * $y_))
            + ($delta_ * sin(6.0 * $y_))
            + ($epsilon_ * sin(8.0 * $y_));

        return $result;
    }


    /*
    * MapLatLonToXY
    *
    * Converts a latitude/longitude pair to x and y coordinates in the
    * Transverse Mercator projection.  Note that Transverse Mercator is not
    * the same as UTM; a scale factor is required to convert between them.
    *
    * Reference: Hoffmann-Wellenhof, B., Lichtenegger, H., and Collins, J.,
    * GPS: Theory and Practice, 3rd ed.  New York: Springer-Verlag Wien, 1994.
    *
    * Inputs:
    *    phi - Latitude of the point, in radians.
    *    lambda - Longitude of the point, in radians.
    *    lambda0 - Longitude of the central meridian to be used, in radians.
    *
    * Outputs:
    *    xy - A 2-element array containing the x and y coordinates
    *         of the computed point.
    *
    * Returns:
    *    The function does not return a value.
    *
    */
    public static function MapLatLonToXY($phi, $lambda, $lambda0)
    {
        //var N, nu2, ep2, t, t2, l;
        //var l3coef, l4coef, l5coef, l6coef, l7coef, l8coef;
        //var tmp;

        /* Precalculate ep2 */
        $ep2 = (pow(self::$sm_a, 2.0) - pow(self::$sm_b, 2.0)) / pow(self::$sm_b, 2.0);

        /* Precalculate nu2 */
        $nu2 = $ep2 * pow(cos($phi), 2.0);

        /* Precalculate N */
        $N = pow(self::$sm_a, 2.0) / (self::$sm_b * sqrt(1 + $nu2));

        /* Precalculate t */
        $t = tan($phi);
        $t2 = $t * $t;
        $tmp = ($t2 * $t2 * $t2) - pow($t, 6.0);

        /* Precalculate l */
        $l = $lambda - $lambda0;

        /* Precalculate coefficients for l**n in the equations below
           so a normal human being can read the expressions for easting
           and northing
           -- l**1 and l**2 have coefficients of 1.0 */

        $l3coef = 1.0 - $t2 + $nu2;
        $l4coef = 5.0 - $t2 + 9 * $nu2 + 4.0 * ($nu2 * $nu2);
        $l5coef = 5.0 - 18.0 * $t2 + ($t2 * $t2) + 14.0 * $nu2 - 58.0 * $t2 * $nu2;
        $l6coef = 61.0 - 58.0 * $t2 + ($t2 * $t2) + 270.0 * $nu2 - 330.0 * $t2 * $nu2;
        $l7coef = 61.0 - 479.0 * $t2 + 179.0 * ($t2 * $t2) - ($t2 * $t2 * $t2);
        $l8coef = 1385.0 - 3111.0 * $t2 + 543.0 * ($t2 * $t2) - ($t2 * $t2 * $t2);

        $xy = array();

        /* Calculate easting (x) */
        $xy[0] = $N * cos($phi) * $l
            + ($N / 6.0 * pow(cos($phi), 3.0) * $l3coef * pow($l, 3.0))
            + ($N / 120.0 * pow(cos($phi), 5.0) * $l5coef * pow($l, 5.0))
            + ($N / 5040.0 * pow(cos($phi), 7.0) * $l7coef * pow($l, 7.0));

        /* Calculate northing (y) */
        $xy[1] = self::ArcLengthOfMeridian($phi)
            + ($t / 2.0 * $N * pow(cos($phi), 2.0) * pow($l, 2.0))
            + ($t / 24.0 * $N * pow(cos($phi), 4.0) * $l4coef * pow($l, 4.0))
            + ($t / 720.0 * $N * pow(cos($phi), 6.0) * $l6coef * pow($l, 6.0))
            + ($t / 40320.0 * $N * pow(cos($phi), 8.0) * $l8coef * pow($l, 8.0));

        return $xy;
    }


    /*
    * LatLonToUTMXY
    *
    * Converts a latitude/longitude pair to x and y coordinates in the
    * Universal Transverse Mercator projection.
    *
    * Inputs:
    *   lat - Latitude of the point, in radians.
    *   lon - Longitude of the point, in radians.
    *   zone - UTM zone to be used for calculating values for x and y.
    *          If zone is less than 1 or greater than 60, the routine
    *          will determine the appropriate zone from the value of lon.
    *
    * Outputs:
    *   xy - A 2-element array where the UTM x and y values will be stored.
    *
    * Returns:
    *   The UTM zone used for calculating the values of x and y.
    *
    */
    public static function LatLonToUTMXY($lat, $lon, $zone)
    {
        $zone = self::UTMCentralMeridian($zone);
        $xy = self::MapLatLonToXY($lat, $lon, $zone);

        /* Adjust easting and northing for UTM system. */
        $xy[0] = $xy[0] * self::$UTMScaleFactor + 500000.0;
        $xy[1] = $xy[1] * self::$UTMScaleFactor;
        if ($xy[1] < 0.0)
            $xy[1] = $xy[1] + 10000000.0;

        $xy[2] = $zone;

        return $xy;
    }


    /**
     * @param $lat
     * @param $lon
     * @return array
     */
    public static function ToUTM($lat, $lon)
    {
        $zone = floor(($lon + 180.0) / 6) + 1;

        $xy = self::LatLonToUTMXY(self::DegToRad($lat), self::DegToRad($lon), $zone);
        return $xy;
    }

    public static function GetDistance($xy_a, $xy_b)
    {
        $a = $xy_a[0] - $xy_b[0];
        $b = $xy_a[1] - $xy_b[1];

        return sqrt($a * $a + $b * $b) / 1000;
    }

    public static function GetMiles($xy_a, $xy_b)
    {
        return self::GetDistance($xy_a, $xy_b) / 1.6093;
    }

    public static function AddKmToLatLon($lat, $lon, $distance, $bearing)
    {
        $earthRadius = 6371;
        $lat1 = deg2rad($lat);
        $lon1 = deg2rad($lon);
        $bearing = deg2rad($bearing);

        $lat2 = asin(sin($lat1) * cos($distance / $earthRadius) + cos($lat1) * sin($distance / $earthRadius) * cos($bearing));
        $lon2 = $lon1 + atan2(sin($bearing) * sin($distance / $earthRadius) * cos($lat1), cos($distance / $earthRadius) - sin($lat1) * sin($lat2));

        //Debug([$lat, $lon, $distance, 'lat'=>rad2deg($lat2),'lon'=>rad2deg($lon2)],null,true,false,false);
        return ['lat' => rad2deg($lat2), 'lon' => rad2deg($lon2)];
    }

    public static function GetBoundary($lat, $lon, $distance)
    {
        $res = [];

        $t = self::AddKmToLatLon($lat, $lon, $distance, 0);
        $res['max_lat'] = $t['lat'];

        $t = self::AddKmToLatLon($lat, $lon, $distance, 90);
        $res['max_lon'] = $t['lon'];

        $t = self::AddKmToLatLon($lat, $lon, $distance, 180);
        $res['min_lat'] = $t['lat'];

        $t = self::AddKmToLatLon($lat, $lon, $distance, 270);
        $res['min_lon'] = $t['lon'];

        return $res;
    }
}