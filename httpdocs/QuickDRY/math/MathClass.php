<?php

class MathClass
{
    public static function ReportPercent($raw, $decimals = 1)
    {
        return number_format($raw * 100, $decimals);
    }

    public static function AccruedInterest($rate, $principal, $periods)
    {
        return $principal * pow(1 + $rate, $periods) - $principal;
    }

    public static function MonthsToRepay($rate, $principle, $payment)
    {
        $res = new PrincipleInterest();
        $r = $rate / 12.0 / 100.0;
        if (round($r * $principle, 2) >= $payment)
            return null;
        while ($principle > 0) {
            $interest_paid = round($r * $principle, 2);
            $p = $payment - $interest_paid;
            $res->interest += $interest_paid;
            if ($principle < $p)
                $p = $principle;
            $principle -= $p;
            $res->month++;
        }
        return $res;
    }

    public static function MonthlyPayment($rate, $principle, $term)
    {
        return self::MonthlyPaymentForPeriod($rate, $principle, $term, 12);
    }

    public static function FindInterest($principle, $payment, $periods)
    {
        $low = 0.0;
        $high = 100.0;
        $calc_p = 0;
        $cur = 0.0;
        $tries = 0;
        while (abs($calc_p - $payment) > 0.01 && $tries++ < 64) {
            $cur = ($high + $low) / 2.0;
            $calc_p = self::MonthlyPayment($cur, $principle, $periods / 12.0);
            if ($calc_p > $payment) $high = $cur; else $low = $cur;
        }
        return $cur;
    }

    public static function MonthlyPaymentForPeriod($rate, $principle, $term, $periods)
    {
        if ($rate == 0)
            return $principle / ($term * $periods);

        $L = $principle;
        //$I = $rate;
        $i = $rate / 100.0 / $periods;
        $T = $term;
        //$Y = $I * $T;
        //$X = 0.5 * $Y;
        $n = $periods * $T;
        $P = ($L * $i) / (1 - pow(M_E, -$n * log(1 + $i)));
        return round($P, 2);
    }

    public static function total_interest($rate, $principle, $term)
    {
        return self::TotalInterestForPeriod($rate, $principle, $term, 12);
    }

    public static function TotalInterestForPeriod($rate, $principle, $term, $periods)
    {
        $payment = self::MonthlyPaymentForPeriod($rate, $principle, $term, $periods);
        $res = self::MonthsToRepay($rate, $principle, $payment);
        if (!is_null($res)) {
            return $res->interest;
        }
        return 0;
    }

    public static function Amortization($rate, $principle, $term)
    {
        return self::AmortizationForPeriod($rate, $principle, $term, 12);
    }

    public static function AmortizationForPeriod($rate, $principle, $term, $periods)
    {
        $points = [];


        $point = new PrincipleInterest();
        $point->principle = $principle;
        $point->interest = 0;
        $points[] = $point;

        $payment = self::MonthlyPaymentForPeriod($rate, $principle, $term, $periods);
        $period_rate = $rate / 100.0 / $periods;
        for ($j = 0; $j < $term * $periods; $j++) {
            $interest = round($point->principle * $period_rate, 2);

            $point->interest_payment = $interest;
            $point->principle_payment = $payment - $interest;
            $point->principle -= $point->principle_payment;
            $point->interest += $interest;


            $p = new PrincipleInterest();
            $p->interest = $point->interest;
            $p->interest_payment = $point->interest_payment;
            $p->principle = $point->principle;
            $p->principle_payment = $point->principle_payment;
            $points[] = $p;
        }

        return $points;
    }

    public static function PayOff($debt)
    {
        $history = [];
        $month = 0;

        $total = new PrincipleInterest();
        $total->principle = $debt->principle;

        while ($total->principle > 0 && $month < 360) {
            $month++;
            $point = new PrincipleInterest();
            $period_interest = $debt->interest_rate / 100.0 / 12.0 * $total->principle;

            $total->interest += $period_interest;

            if ($debt->payment < $total->principle + $period_interest) {
                $point->principle_payment = $debt->payment - $period_interest;
                $total->principle -= $point->principle_payment;
            } else {
                $point->principle_payment = $total->principle;
                $total->principle = 0;
            }

            $point->interest = $total->interest;
            $point->principle = $total->principle;
            $point->month = $month;
            $point->interest_payment = $period_interest;

            $history[] = $point;
        }
        return $history;
    }

    public static function future_value($account, $months)
    {
        $history = [];
        $month = 0;

        $total = new PrincipleInterest();
        $total->principle = $account->principle;

        while ($month < $months) {
            $month++;
            $point = new PrincipleInterest();
            $period_interest = $account->interest_rate / 100.0 / 12.0 * $total->principle;

            $total->interest += $period_interest;

            $point->principle_payment = $account->payment;
            $total->principle += $point->principle_payment + $period_interest;

            $point->interest = $total->interest;
            $point->principle = $total->principle;
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
    public static function snowball($debts)
    {
        $points = [];
        $point = new PrincipleInterest();
        $history = [];
        $cur_month = 0;
        $in_debt = true;

        $h = [];

        for ($j = 0; $j < sizeof($debts); $j++) {
            $d = new Debt();
            $d->interest_rate = $debts[$j]->interest_rate;
            $d->payment = 0;
            $d->principle = $debts[$j]->principle;
            $d->name = $debts[$j]->name;
            $h[] = $d;
        }
        $history[] = $h;

        while ($in_debt && $cur_month < 1200) {
            $point->principle = 0;
            $point->interest_payment = 0;
            $point->principle_payment = 0;

            $h = [];

            $rollover = 0.0;
            for ($j = 0; $j < sizeof($debts); $j++) {
                $interest = round($debts[$j]->principle * $debts[$j]->interest_rate / 100.0 / 12.0, 2);

                $point->interest += $interest;
                $point->principle += $debts[$j]->principle;

                $point->interest_payment += $interest;

                $debts[$j]->principle += $interest;
                $payment = $debts[$j]->payment;

                if ($payment > $debts[$j]->principle) {
                    $rollover += $payment - $debts[$j]->principle;
                    $point->principle_payment += $debts[$j]->principle - $interest;
                    $payment = $debts[$j]->principle;
                    $debts[$j]->principle = 0;
                } else {
                    $debts[$j]->principle -= $payment;
                    $point->principle_payment += $payment - $interest;
                }

                $d = new Debt();
                $d->interest_rate = $debts[$j]->interest_rate;
                $d->payment = $payment;
                $d->principle = $debts[$j]->principle;
                $d->name = $debts[$j]->name;
                $h[] = $d;
            }

            $has_debts = true;
            while ($rollover > 0 && $has_debts) {
                $remaining_debt = 0;
                for ($k = 0; $k < sizeof($debts); $k++) {
                    if ($debts[$k]->principle > 0) {
                        if ($debts[$k]->principle < $rollover) {
                            $rollover -= $debts[$k]->principle;
                            $point->principle_payment += $debts[$k]->principle;
                            $h[$k]->payment += $debts[$k]->principle;
                            $debts[$k]->principle = 0;
                        } else {
                            $debts[$k]->principle -= $rollover;
                            $point->principle_payment += $rollover;
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

            if ($point->principle <= 0) {
                $in_debt = false;
            } else {
                $cur_month++;

                $history[] = $h;
                $p = new PrincipleInterest();
                $p->month = $cur_month;
                $p->interest = $point->interest;
                $p->interest_payment = $point->interest_payment;
                $p->principle = $point->principle;
                $p->principle_payment = $point->principle_payment;
                $points[] = $p;
            }
        }
        return ['points' => $points, 'history' => $history];
    }
}
