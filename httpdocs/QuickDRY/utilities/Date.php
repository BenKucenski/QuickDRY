<?php
/**
 * @param     $timeStamp
 * @param int $totalMonths
 *
 * @return int
 */
function addMonthToDate($timeStamp, $totalMonths=1)
{
    if(!is_numeric($timeStamp))
        $timeStamp = strtotime($timeStamp);

    // You can add as many months as you want. mktime will accumulate to the next year.
    $thePHPDate = getdate($timeStamp); // Covert to Array
    $thePHPDate['mon'] = $thePHPDate['mon']+$totalMonths; // Add to Month
    $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']); // Convert back to timestamp
    return $timeStamp;
}

/**
 * @param     $timeStamp
 * @param int $totalDays
 *
 * @return int
 */
function addDayToDate($timeStamp, $totalDays=1)
{
    if(!is_numeric($timeStamp))
        $timeStamp = strtotime($timeStamp);

    // You can add as many days as you want. mktime will accumulate to the next month / year.
    $thePHPDate = getdate($timeStamp);
    $thePHPDate['mday'] = $thePHPDate['mday']+$totalDays;
    $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
    return $timeStamp;
}

/**
 * @param     $timeStamp
 * @param int $totalYears
 *
 * @return int
 */
function addYearToDate($timeStamp, $totalYears=1)
{
    if(!is_numeric($timeStamp))
        $timeStamp = strtotime($timeStamp);

    $thePHPDate = getdate($timeStamp);
    $thePHPDate['year'] = $thePHPDate['year']+$totalYears;
    $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
    return $timeStamp;
}

// http://webdesign.anmari.com/1956/calculate-date-from-day-of-year-in-php/
/**
 * @param $year
 * @param $DayInYear
 *
 * @return bool|string
 */
function dayofyear2date( $year, $DayInYear )
{
    $d = new DateTime($year.'-01-01');
    date_modify($d, '+'.($DayInYear-1).' days');
    return Datestamp($d->getTimestamp());
}

/**
 * @param $date
 *
 * @return array
 */
function x_week_range($date) {
    $ts = strtotime($date);
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    return [date('Y-m-d', $start),
        date('Y-m-d', strtotime('next saturday', $start))];
}

/**
 * @param      $date
 * @param bool $debug
 *
 * @return array
 */
function CalcWeek($date, $debug = false)
{
    if(strtotime($date) == 0)
    {
        [null,null,null];
    }

    list($start_date, $end_date) = x_week_range($date);

    $t = strtotime($date);
    $y = date('Y', $t);
    $m = date('m', $t);



    if($debug) {
        print_r(['date'=>$date, 'y'=>$y,'m'=>$m]);
    }
    $week_date = $start_date;
    $month_year = $m . $y;

    $w = date('W', strtotime($week_date));

    $week_year = $w . $y;
    return [$week_date, $month_year, $week_year];
}

/**
 * @param $datetime
 *
 * @return int
 */
function iCalDate2TimeStamp($datetime)
{
    $output = mktime( $datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year'] );
    return $output;
}

/**
 * @param $date
 *
 * @return bool|string
 */
function FancyDateTime($date)
{
    if(!is_numeric($date))
    {
        if(($date = strtotime($date)) === false)
            return '<i>Invalid Date</i>';
    }
    if($date == 0)
        return '<i>Not Set</i>';
    return date('F jS, Y g:iA', $date);
}

/**
 * @param $date
 *
 * @return bool|string
 */
function FancyDate($date)
{
    if(is_null($date))
        return '<i>Not Set</i>';

    if(!is_numeric($date)) {
        $date = Timestamp($date);
        $date = strtotime($date);
    }

    if($date == 0)
        return '<i>Not Set</i>';

    return date('F jS, Y', $date);
}

/**
 * @param $date
 *
 * @return bool|string
 */
function ShortDate($date)
{
    if(is_null($date))
        return '<i>Not Set</i>';

    if(!is_numeric($date))
        $date = strtotime($date);

    if($date == 0)
        return '<i>Not Set</i>';

    return date('n/j/y', $date);
}

/**
 * @param $start_at
 * @param $end_at
 *
 * @return string
 */
function HourMinDiff($start_at, $end_at)
{
    if(!is_numeric($start_at)) $start_at = strtotime($start_at);
    if(!is_numeric($end_at)) $end_at = strtotime($end_at);

    $hours = floor(($end_at - $start_at) / 3600);
    $mins = ceil((($end_at - $start_at) / 3600 - $hours) * 60);
    return $hours . ':' . ($mins < 10 ? '0' : '') . $mins;
}

/**
 * @param int $time
 *
 * @return int
 */
function AdjustedTime($time = 0)
{
    if(!$time) $time = time();

    if(!is_numeric($time)) $time = strtotime($time);

    return $time;
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function StandardDate($time = 0, $null = null, $last_month = null, $last_year = null)
{
    if(!$time && !is_null($null)) {
        return $null;
    }

    if(!is_numeric($time)) {
        $time = Timestamp($time);
    }

    if(!$time) {
        if (is_null($null)) {
            $time = AdjustedTime();
        } else {
            return $null;
        }
    }

    $time = strtotime($time);

    if(!is_null($last_month) && !is_null($last_year)) {
        $m = date('m', $time);
        $y = date('Y', $time);

        if ($m == $last_month && $y == $last_year) {
            return date('j', $time);
        }

        if ($y == $last_year) {
            return date('n/j', $time);
        }
    }
    return date('n/j/Y', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function DayMonthDate($time = 0, $null = null)
{
    if(!is_numeric($time)) $time = strtotime($time);
    if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
    return date('n-j', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function StandardDateTime($time = 0, $null = null, $debug  = false)
{
    if(!is_numeric($time)) {
        $time = strtotime(Timestamp($time, $null, $debug));
    }
    if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
    return date('n/j/Y h:i A', $time);
}

function StandardTime($time = 0, $null = null)
{
    if(!is_numeric($time)) $time = strtotime($time);
    if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
    return date('h:iA', $time);
}

function FromUserTimeToGMT($time) {
    global $User;

    if(!is_numeric($time)) {
        $time = strtotime($time);
    }

    return Timestamp($time - $User->hours_diff * 3600);
}

/**
 * @param int $time
 *
 * @return bool|string
 */
function Timestamp($time = 0, $null = null, $debug = false)
{
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
        if($debug) {
            CleanHalt(date('Y-m-d', $time));
        }
        if(!$time) {
            return ''; // don't return null
        }
    }

    if($time && !is_numeric($time)) {
        $time = strtotime($time);
    }

    if($time == 0) {
        if(!is_null($null)) {
            return $null;
        }
        $time = AdjustedTime();
    }
    return date('Y-m-d H:i:s', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function TimeOnlystamp($time = 0, $null = null)
{
    if(!is_numeric($time)) $time = strtotime($time);
    if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
    return date('H:i', $time);
}

function time_elapsed_string($ptime)
{
    if(!is_numeric($ptime)) {
        $ptime = strtotime(Timestamp($ptime));
    }
    $etime = time() - $ptime;

    if ($etime < 1)
    {
        return 'just now';
    }

    $a = array( 365 * 24 * 60 * 60  =>  'year',
        30 * 24 * 60 * 60  =>  'month',
        24 * 60 * 60  =>  'day',
        60 * 60  =>  'hour',
        60  =>  'minute',
        1  =>  'second'
    );
    $a_plural = array( 'year'   => 'years',
        'month'  => 'months',
        'day'    => 'days',
        'hour'   => 'hours',
        'minute' => 'minutes',
        'second' => 'seconds'
    );

    foreach ($a as $secs => $str)
    {
        $d = $etime / $secs;
        if ($d >= 1)
        {
            if($secs > 30 * 24 * 60 * 60) {
                $r = number_format($d, 1);
            } else {
                $r = round($d);
            }
            return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
        }
    }
    return '';
}

function EchoTime($msg)
{
    echo time() . ': ' . Timestamp() . ': ' . $msg . PHP_EOL;
}

function getStartAndEndDate($week, $year)
{
    $time = strtotime("1 January $year", time());
    $day = date('w', $time);
    $time += ((7*$week)+1-$day)*24*3600;
    $return[0] = date('Y-n-j', $time);
    $time += 6*24*3600;
    $return[1] = date('Y-n-j', $time);
    return $return;
}

function Datestamp($time = 0, $null = null, $format = 'Y-m-d')
{
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
        if(!$time) {
            return $null; // don't return null
        }

    }
    if(!is_numeric($time)) $time = strtotime($time);

    if(!$time) {
        if(is_null($null)) {
            $time = time();
        } else {
            return $null;
        }
    }

    if(is_array($time))
        Halt($time);

    return date($format, $time);
}

/**
 * @param $time
 *
 * @return bool|string
 */
function SolrTime($time) {
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
    }

    if(!is_numeric($time))
        $time = strtotime($time);
    return date("Y-m-d\TH:i:s\Z", $time);
}

/**
 * @param $date
 *
 * @return int
 */
function getTimeStampFromDBDate($date)
{
    $dateArr = explode(" ", $date);
    $datePartArr = explode("-", $dateArr[0]);
    $timePartArr = explode(":", $dateArr[1]);

    return mktime($timePartArr[0], $timePartArr[1], $timePartArr[2], $datePartArr[1], $datePartArr[2], $datePartArr[0]);
}

function Age($time) {
    if(!is_numeric($time)) {
        $time = strtotime($time);
    }
    $diff = time() - $time;

    if($diff < 15 * 60) {
        return floor(($diff + 30) / 60) . ' minutes';
    }

    if($diff / 3600 < 24)
        return ceil($diff / 3600) . ' hours';

    return ceil($diff / 3600 / 24 - 0.5) . ' days';
}

function gmtime() {
    return strtotime(gmdate('m/d/Y H:i:s'));
}
