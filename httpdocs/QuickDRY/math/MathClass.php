<?php

/**
 * Class MathClass
 */
class MathClass
{
    public static function Median($arr) {
        $count = count($arr); //total numbers in array
        $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
        if($count % 2) { // odd number, middle is the median
            $median = $arr[$middleval];
        } else { // even number, calculate avg of 2 medians
            $low = $arr[$middleval];
            $high = $arr[$middleval+1];
            $median = (($low+$high)/2);
        }
        return $median;
    }

    /**
     * @param $raw
     * @param int $decimals
     * @return string
     */
    public static function ReportPercent($raw, $decimals = 1)
    {
        return number_format($raw * 100, $decimals);
    }

    /**
     * @param $rate
     * @param $principal
     * @param $periods
     * @return float|int
     */
    public static function AccruedInterest($rate, $principal, $periods)
    {
        return $principal * pow(1 + $rate, $periods) - $principal;
    }

    /**
     * @param $rate
     * @param $principal
     * @param $payment
     * @return null|PrincipalInterest
     */
    public static function MonthsToRepay($rate, $principal, $payment)
    {
        $res = new PrincipalInterest();
        $r = $rate / 12.0 / 100.0;
        if (round($r * $principal, 2) >= $payment)
            return null;
        while ($principal > 0) {
            $interest_paid = round($r * $principal, 2);
            $p = $payment - $interest_paid;
            $res->interest += $interest_paid;
            if ($principal < $p)
                $p = $principal;
            $principal -= $p;
            $res->month++;
        }
        return $res;
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @return float|int
     */
    public static function MonthlyPayment($rate, $principal, $term)
    {
        return self::MonthlyPaymentForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param $principal
     * @param $payment
     * @param $periods
     * @return float
     */
    public static function FindInterest($principal, $payment, $periods)
    {
        $low = 0.0;
        $high = 100.0;
        $calc_p = 0;
        $cur = 0.0;
        $tries = 0;
        while (abs($calc_p - $payment) > 0.01 && $tries++ < 64) {
            $cur = ($high + $low) / 2.0;
            $calc_p = self::MonthlyPayment($cur, $principal, $periods / 12.0);
            if ($calc_p > $payment) $high = $cur; else $low = $cur;
        }
        return $cur;
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @param $periods
     * @return float|int
     */
    public static function MonthlyPaymentForPeriod($rate, $principal, $term, $periods)
    {
        if ($rate == 0)
            return $principal / ($term * $periods);

        $L = $principal;
        //$I = $rate;
        $i = $rate / 100.0 / $periods;
        $T = $term;
        //$Y = $I * $T;
        //$X = 0.5 * $Y;
        $n = $periods * $T;
        $P = ($L * $i) / (1 - pow(M_E, -$n * log(1 + $i)));
        return round($P, 2);
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @return int
     */
    public static function TotalInterest($rate, $principal, $term)
    {
        return self::TotalInterestForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @param $periods
     * @return int
     */
    public static function TotalInterestForPeriod($rate, $principal, $term, $periods)
    {
        $payment = self::MonthlyPaymentForPeriod($rate, $principal, $term, $periods);
        $res = self::MonthsToRepay($rate, $principal, $payment);
        if (!is_null($res)) {
            return $res->interest;
        }
        return 0;
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @return array
     */
    public static function Amortization($rate, $principal, $term)
    {
        return self::AmortizationForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param $rate
     * @param $principal
     * @param $term
     * @param $periods
     * @return array
     */
    public static function AmortizationForPeriod($rate, $principal, $term, $periods)
    {
        $points = [];


        $point = new PrincipalInterest();
        $point->principal = $principal;
        $point->interest = 0;
        $points[] = $point;

        $payment = self::MonthlyPaymentForPeriod($rate, $principal, $term, $periods);
        $period_rate = $rate / 100.0 / $periods;
        for ($j = 0; $j < $term * $periods; $j++) {
            $interest = round($point->principal * $period_rate, 2);

            $point->interest_payment = $interest;
            $point->principal_payment = $payment - $interest;
            $point->principal -= $point->principal_payment;
            $point->interest += $interest;


            $p = new PrincipalInterest();
            $p->interest = $point->interest;
            $p->interest_payment = $point->interest_payment;
            $p->principal = $point->principal;
            $p->principal_payment = $point->principal_payment;
            $points[] = $p;
        }

        return $points;
    }

    /**
     * @param Debt $debt
     * @return array
     */
    public static function PayOff(Debt $debt)
    {
        $history = [];
        $month = 0;

        $total = new PrincipalInterest();
        $total->principal = $debt->principal;

        while ($total->principal > 0 && $month < 360) {
            $month++;
            $point = new PrincipalInterest();
            $period_interest = $debt->interest_rate / 100.0 / 12.0 * $total->principal;

            $total->interest += $period_interest;

            if ($debt->payment < $total->principal + $period_interest) {
                $point->principal_payment = $debt->payment - $period_interest;
                $total->principal -= $point->principal_payment;
            } else {
                $point->principal_payment = $total->principal;
                $total->principal = 0;
            }

            $point->interest = $total->interest;
            $point->principal = $total->principal;
            $point->month = $month;
            $point->interest_payment = $period_interest;

            $history[] = $point;
        }
        return $history;
    }

    /**
     * @param $account
     * @param $months
     * @return array
     */
    public static function FutureValue($account, $months)
    {
        $history = [];
        $month = 0;

        $total = new PrincipalInterest();
        $total->principal = $account->principal;

        while ($month < $months) {
            $month++;
            $point = new PrincipalInterest();
            $period_interest = $account->interest_rate / 100.0 / 12.0 * $total->principal;

            $total->interest += $period_interest;

            $point->principal_payment = $account->payment;
            $total->principal += $point->principal_payment + $period_interest;

            $point->interest = $total->interest;
            $point->principal = $total->principal;
            $point->month = $month;
            $point->interest_payment = $period_interest;
            $history[] = $point;
        }
        return $history;
    }

    /**
     * @param Debt[] $debts
     * @return array
     */
    public static function Snowball($debts)
    {
        $points = [];
        $point = new PrincipalInterest();
        $history = [];
        $cur_month = 0;
        $in_debt = true;

        $h = [];

        for ($j = 0; $j < sizeof($debts); $j++) {
            $d = new Debt();
            $d->interest_rate = $debts[$j]->interest_rate;
            $d->payment = 0;
            $d->principal = $debts[$j]->principal;
            $d->name = $debts[$j]->name;
            $h[] = $d;
        }
        $history[] = $h;

        while ($in_debt && $cur_month < 1200) {
            $point->principal = 0;
            $point->interest_payment = 0;
            $point->principal_payment = 0;

            $h = [];

            $rollover = 0.0;
            for ($j = 0; $j < sizeof($debts); $j++) {
                $interest = round($debts[$j]->principal * $debts[$j]->interest_rate / 100.0 / 12.0, 2);

                $point->interest += $interest;
                $point->principal += $debts[$j]->principal;

                $point->interest_payment += $interest;

                $debts[$j]->principal += $interest;
                $payment = $debts[$j]->payment;

                if ($payment > $debts[$j]->principal) {
                    $rollover += $payment - $debts[$j]->principal;
                    $point->principal_payment += $debts[$j]->principal - $interest;
                    $payment = $debts[$j]->principal;
                    $debts[$j]->principal = 0;
                } else {
                    $debts[$j]->principal -= $payment;
                    $point->principal_payment += $payment - $interest;
                }

                $d = new Debt();
                $d->interest_rate = $debts[$j]->interest_rate;
                $d->payment = $payment;
                $d->principal = $debts[$j]->principal;
                $d->name = $debts[$j]->name;
                $h[] = $d;
            }

            $has_debts = true;
            while ($rollover > 0 && $has_debts) {
                $remaining_debt = 0;
                for ($k = 0; $k < sizeof($debts); $k++) {
                    if ($debts[$k]->principal > 0) {
                        if ($debts[$k]->principal < $rollover) {
                            $rollover -= $debts[$k]->principal;
                            $point->principal_payment += $debts[$k]->principal;
                            $h[$k]->payment += $debts[$k]->principal;
                            $debts[$k]->principal = 0;
                        } else {
                            $debts[$k]->principal -= $rollover;
                            $point->principal_payment += $rollover;
                            $h[$k]->payment += $rollover;
                            $rollover = 0;
                            $remaining_debt++;
                        }
                    }
                }

                if ($remaining_debt == 0) {
                    $has_debts = false;
                }
            }

            if ($point->principal <= 0) {
                $in_debt = false;
            } else {
                $cur_month++;

                $history[] = $h;
                $p = new PrincipalInterest();
                $p->month = $cur_month;
                $p->interest = $point->interest;
                $p->interest_payment = $point->interest_payment;
                $p->principal = $point->principal;
                $p->principal_payment = $point->principal_payment;
                $points[] = $p;
            }
        }
        return ['points' => $points, 'history' => $history];
    }

    public static function APY($current_time, $current_price, $start_time, $start_price)
    {
        if(!is_numeric($current_time)) {
            $current_time = strtotime($current_time);
        }

        if(!is_numeric($start_time)) {
            $start_time = strtotime($start_time);
        }

        if($start_time == $current_time) {
            return 0;
        }

        return 100.0 * pow(
                ($current_price) /
                ($start_price), 1.0 / (($current_time - $start_time) * 1.0 / (365.0 * 24.0 * 3600.0))) - 100.0;
    }
}
