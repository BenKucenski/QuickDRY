<?php

/**
 * Class Dates
 */
class Dates extends SafeClass
{
    public static function ConvertToUserDate($datetime, $timezone)
    {
        $datetime = Dates::Timestamp($datetime);
        $tz = new DateTimeZone($timezone);
        $date = new DateTime($datetime . ' GMT');
        $date->setTimezone($tz);
        return $date->format('Y-m-d H:i:s');
    }

    public static function ConvertToServerDate($datetime, $timezone)
    {
        $datetime = Dates::Timestamp($datetime);
        $tz = new DateTimeZone('GMT');
        $date = new DateTime($datetime . ' ' . $timezone);
        $date->setTimezone($tz);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param $min_date
     * @param $max_date
     * @return int
     */
    public static function MonthsBetweenDates($min_date, $max_date)
    {
        $min_date = strtotime(Dates::Datestamp($min_date));
        $max_date = strtotime(Dates::Datestamp($max_date));

        if ($max_date < $min_date) {
            $a = $min_date;
            $min_date = $max_date;
            $max_date = $a;
        }

        $i = 0;
        while (($min_date = strtotime("+1 MONTH", $min_date)) <= $max_date) {
            $i++;
        }
        return $i;
    }

    /**
     * @param     $timeStamp
     * @param int $totalMonths
     *
     * @return int
     */
    public static function AddMonthToDate($timeStamp, $totalMonths = 1)
    {
        if (!is_numeric($timeStamp)) {
            $timeStamp = strtotime(self::Timestamp($timeStamp));
        }

        if(!is_numeric($totalMonths)) {
            $totalMonths = 0;
        }

        // You can add as many months as you want. mktime will accumulate to the next year.
        $thePHPDate = getdate($timeStamp); // Covert to Array
        $thePHPDate['mon'] = $thePHPDate['mon'] + $totalMonths; // Add to Month
        $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']); // Convert back to timestamp
        return $timeStamp;
    }

    /**
     * @param     $timeStamp
     * @param int $totalDays
     *
     * @return int
     */
    public static function AddDayToDate($timeStamp, $totalDays = 1)
    {
        $timeStamp = strtotime(self::Datestamp($timeStamp));

        // You can add as many days as you want. mktime will accumulate to the next month / year.
        $thePHPDate = getdate($timeStamp);
        $thePHPDate['mday'] = $thePHPDate['mday'] + $totalDays;
        $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
        return $timeStamp;
    }

    /**
     * @param     $timeStamp
     * @param int $totalYears
     *
     * @return int
     */
    public static function AddYearToDate($timeStamp, $totalYears = 1)
    {
        if (!is_numeric($timeStamp))
            $timeStamp = strtotime($timeStamp);

        $thePHPDate = getdate($timeStamp);
        $thePHPDate['year'] = $thePHPDate['year'] + $totalYears;
        $timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
        return $timeStamp;
    }


    /**
     * @param $year
     * @param $DayInYear
     *
     * @return bool|string
     */
    public static function DayOfYearToDate($year, $DayInYear)
    {
        // http://webdesign.anmari.com/1956/calculate-date-from-day-of-year-in-php/

        $DayInYear = floor($DayInYear);
        $d = new DateTime($year . '-01-01');
        date_modify($d, '+' . ($DayInYear - 1) . ' days');
        return self::Datestamp($d->getTimestamp());
    }

    /**
     * @param $date
     * @param string $last
     * @param string $next
     * @return array
     */
    public static function GetWeekRange($date, $last = 'sunday', $next = 'saturday')
    {
        if(!is_numeric($date)) {
            $ts = strtotime(Dates::Datestamp($date));
        } else {
            $ts = $date;
        }
        $start = (date('w', $ts) == 0) ? $ts : strtotime('last ' . $last, $ts);
        return [
            date('Y-m-d', $start),
            date('Y-m-d', strtotime('next ' . $next, $start))
        ];
    }

    /**
     * @param      $date
     * @param bool $debug
     *
     * @return array
     */
    public static function CalcWeek($date, $debug = false)
    {
        if (strtotime($date) == 0) {
            [null, null, null];
        }

        list($start_date, $end_date) = static::GetWeekRange($date);

        $t = strtotime($date);
        $y = date('Y', $t);
        $m = date('m', $t);


        if ($debug) {
            print_r(['date' => $date, 'y' => $y, 'm' => $m]);
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
    public static function iCalDate2TimeStamp($datetime)
    {
        $output = mktime($datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year']);
        return $output;
    }

    /**
     * @param $date
     * @param null $null
     * @return false|int|null
     */
    public static function DateToInt($date, $null = null)
    {
        if ($date instanceof DateTime) {
            $temp = $date->getTimestamp();
            $str = $date->format('Y-m-d H:i:s');

            if (!$temp && !$str) { // don't interpret 1970-01-01 as not set
                return $null;
            }
            return $temp;
        }

        if (!$date) {
            return $null;
        }

        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        return $date;
    }

    /**
     * @param $date
     *
     * @return bool|string
     */
    public static function FancyDateTime($date, $null = null)
    {
        return self::Datestamp($date, $null, 'F jS, Y g:iA');
    }

    /**
     * @param $date
     *
     * @return bool|string
     */
    public static function FancyDate($date, $null = null)
    {
        return self::Datestamp($date, $null, 'F jS, Y');
    }

    /**
     * @param $date
     *
     * @return bool|string
     */
    public static function FancyDateB($date, $null = null)
    {
        return self::Datestamp($date, $null, 'F j, Y');
    }

    /**
     * @param $date
     *
     * @return bool|string
     */
    public static function ShortDate($date, $null = null)
    {
        return self::Datestamp($date, $null, 'n/j/y');
    }


    /**
     * @param $date
     * @return false|string
     */
    public static function ShortDateYear($date, $null = null)
    {
        return self::Datestamp($date, $null, 'M Y');
    }

    /**
     * @param $date
     * @return false|string
     */
    public static function LongDateYear($date, $null = null)
    {
        return self::Datestamp($date, $null, 'F Y');
    }

    /**
     * @param $start_at
     * @param $end_at
     *
     * @return string
     */
    public static function HourMinDiff($start_at, $end_at)
    {
        if (!is_numeric($start_at)) $start_at = strtotime($start_at);
        if (!is_numeric($end_at)) $end_at = strtotime($end_at);

        $hours = floor(($end_at - $start_at) / 3600);
        $mins = ceil((($end_at - $start_at) / 3600 - $hours) * 60);
        return $hours . ':' . ($mins < 10 ? '0' : '') . $mins;
    }

    /**
     * @param int $time
     *
     * @return int
     */
    public static function AdjustedTime($time = 0, $offset = 0)
    {
        if (!$time) $time = time();

        if (!is_numeric($time)) $time = strtotime($time);
        if ($offset < 0) {
            $time = strtotime($offset . ' hour', $time);
        } else {
            $time = strtotime('+' . $offset . ' hour', $time);
        }

        return $time;
    }

    /**
     * @param int $date
     * @param null $null
     * @return false|null|string
     */
    public static function StandardDate($date = null, $null = null)
    {
        return self::Datestamp($date, $null, 'n/j/Y');
    }

    /**
     * @param int $time
     * @param null $null
     *
     * @return bool|null|string
     */
    public static function DayMonthDate($date = null, $null = null)
    {
        return self::Datestamp($date, $null, 'n-j');
    }

    /**
     * @param int $time
     * @param null $null
     *
     * @return bool|null|string
     */
    public static function StandardDateTime($date = null, $null = null, $offset = null)
    {
        return self::Datestamp($date, $null, 'n/j/Y h:i A', $offset);
    }

    /**
     * @param int $time
     * @param null $null
     * @return false|null|string
     */
    public static function StandardTime($date = null, $null = null)
    {
        return self::Datestamp($date, $null, 'h:iA');
    }

    /**
     * @param $time
     * @param UserClass $User
     * @return bool|string
     */
    public static function FromUserTimeToGMT($time, UserClass &$User)
    {
        $time = self::DateToInt($time);

        return self::Timestamp($time - $User->hours_diff * 3600);
    }

    /**
     * @param DateTime $dateTime
     * @return string
     */
    public static function SQLDateTimeToString($dateTime)
    {
        if (!is_object($dateTime)) {
            try {
                $dateTime = new DateTime($dateTime);
            } catch (Exception $ex) {
                Halt($ex);
            }
        }
        $t = $dateTime->format('Y-m-d H:i:s.u');
        return substr($t, 0, strlen($t) - 3);
    }

    /**
     * @param int $time
     *
     * @return bool|string
     */
    public static function Timestamp($date = null, $null = null, $format = 'Y-m-d H:i:s')
    {
        if (!$format) {
            $format = 'Y-m-d H:i:s';
        }
        return self::Datestamp($date, $null, $format);
    }

    /**
     * @param int $time
     * @param null $null
     *
     * @return bool|null|string
     */
    public static function TimeOnlystamp($date = null, $null = null)
    {
        return self::Datestamp($date, $null, 'H:i');
    }

    /**
     * @param $date
     * @return string
     */
    public static function TimeElapsedString($date)
    {
        $date = self::DateToInt($date);
        $etime = time() - $date;

        if ($etime < 1) {
            return 'just now';
        }

        $a = array(365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        $a_plural = array('year' => 'years',
            'month' => 'months',
            'day' => 'days',
            'hour' => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        );

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                if ($secs > 30 * 24 * 60 * 60) {
                    $r = number_format($d, 1);
                } else {
                    $r = round($d);
                }
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
        return '';
    }


    /**
     * @param $msg
     * @return string
     */
    public static function TimeString($msg)
    {
        return time() . ': ' . self::Timestamp() . ': ' . $msg . PHP_EOL;
    }

    /**
     * @param $week
     * @param $year
     * @return mixed
     */
    public static function GetStartAndEndDate($week, $year)
    {
        $time = strtotime("1 January $year", time());
        $day = date('w', $time);
        $time += ((7 * $week) + 1 - $day) * 24 * 3600;
        $return[0] = date('Y-n-j', $time);
        $time += 6 * 24 * 3600;
        $return[1] = date('Y-n-j', $time);
        return $return;
    }

    /**
     * @param int $time
     * @param null $null
     * @param string $format
     * @return false|null|string
     */
    public static function Datestamp($date = null, $null = null, $format = 'Y-m-d', $offset = null)
    {
        if (!$format) {
            $format = 'Y-m-d';
        }

        if (is_null($null) && is_null($date)) {
            $date = time();
        }
        $date = self::DateToInt($date, $null);
        if ($date === $null) {
            return $null;
        }

        if (!$date && $null) {
            return $null;
        }

        if ($offset) {
            $date += $offset * 3600;
        }
        return date($format, $date);
    }

    /**
     * @param $time
     *
     * @return bool|string
     */
    public static function SolrTime($date)
    {
        return self::Datestamp($date, null, "Y-m-d\TH:i:s\Z");
    }

    /**
     * @param $date
     *
     * @return int
     */
    function GetTimeStampFromDBDate($date)
    {
        $dateArr = explode(" ", $date);
        $datePartArr = explode("-", $dateArr[0]);
        $timePartArr = explode(":", $dateArr[1]);

        return mktime($timePartArr[0], $timePartArr[1], $timePartArr[2], $datePartArr[1], $datePartArr[2], $datePartArr[0]);
    }

    /**
     * @param $time
     * @return string
     */
    public static function Age($time)
    {
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }
        $diff = time() - $time;

        if ($diff < 15 * 60) {
            return floor(($diff + 30) / 60) . ' minutes';
        }

        if ($diff / 3600 < 24)
            return ceil($diff / 3600) . ' hours';

        return ceil($diff / 3600 / 24 - 0.5) . ' days';
    }

    /**
     * @return false|int
     */
    public static function GMTtime()
    {
        return strtotime(gmdate('m/d/Y H:i:s'));
    }

    /**
     * @param $t
     * @param $timezone
     * @return string
     */
    public static function FromGMT($t, $timezone)
    {
        $t = Dates::Timestamp($t);

        $dt = new DateTime($t);
        $dt->setTimezone(new DateTimeZone($timezone));

        $res = $dt->format('Y-m-d H:i:s');
        return $res;
    }

    /**
     * @param $t
     * @param $timezone
     * @return string
     */
    public static function ToGMT($t, $timezone)
    {
        $t = Dates::Timestamp($t);

        $dt = new DateTime($t, new DateTimeZone($timezone));
        $dt->setTimezone(new DateTimeZone('GMT'));

        $res = $dt->format('Y-m-d H:i:s');
        return $res;
    }

    /**
     * @param $date
     * @return bool
     */
    public static function IsWeekend($date)
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }
        return date('N', $date) >= 6;
    }

    /**
     * @param $start
     * @param $end
     * @return false|string|null
     */
    public static function DateRange($start, $end)
    {
        if(!$end) {
            return self::StandardDate($start, '');
        }

        $year_month_start = self::Datestamp($start,null,'Ym');
        $year_month_end = self::Datestamp($end,null,'Ym');

        if(self::Datestamp($start) == self::Datestamp($end)) {
            if(self::Timestamp($start) == self::Timestamp($end)) {
                return self::Datestamp($start);
            }

            return self::Datestamp($start, null, 'F jS g:ia') . ' - ' . self::Datestamp($end, null, 'g:ia');
        }

        if(strcasecmp($year_month_end, $year_month_start) == 0) {
            return Dates::Datestamp($start, null, 'F jS') . ' - ' . self::Datestamp($end, null,'jS, Y');
        }
        return Dates::StandardDate($start, '--') . ' - ' . Dates::StandardDate($end, '--');

    }
}

