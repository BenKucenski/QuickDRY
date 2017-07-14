<?php
class MathClass
{
    public static function ReportPercent($raw, $decimals = 1) {
        return number_format($raw * 100, $decimals);
    }
	public static function accrued_interest($rate, $principal, $periods)
    {
        return $principal * pow(1 + $rate, $periods) - $principal;
    }

	public static function months_to_repay($rate, $principle, $payment)
	{
		$res = new principle_interest();
		$r = $rate / 12.0 / 100.0;
		if (round($r * $principle, 2) >= $payment)
			return null;
		while ($principle > 0)
		{
			$interest_paid = round(r * principle, 2);
			$p = $payment - $interest_paid;
			$res->interest += $interest_paid;
			if ($principle < $p)
				$p = $principle;
			$principle -= $p;
			$res->month++;
		}
		return $res;
	}

	public static function monthly_payment($rate, $principle, $term)
	{
		return self::monthly_payment_for_period($rate, $principle, $term, 12);
	}

	public static function find_interest($principle, $payment, $periods)
	{
		$low = 0.0;
		$high = 100.0;
		$calc_p = 0;
		$cur = 0.0;
		$tries = 0;
		while (abs($calc_p - $payment) > 0.01 && $tries++ < 64)
		{
			$cur = ($high + $low) / 2.0;
			$calc_p = math::monthly_payment($cur, $principle, $periods / 12.0);
			if ($calc_p > $payment) $high = $cur; else $low = $cur;
		}
		return $cur;
	}

	public static function monthly_payment_for_period($rate, $principle, $term, $periods)
	{
		if ($rate == 0)
			return $principle / ($term * $periods);

		$L = $principle;
		$I = $rate;
		$i = $rate / 100.0 / $periods;
		$T = $term;
		$Y = $I * $T;
		$X = 0.5 * $Y;
		$n = $periods * $T;
		$P = 0.0;
		$P = ($L * $i) / (1 - pow(M_E, -$n * log(1 + $i)));
		return round($P, 2);
	}

	public static function total_interest($rate, $principle, $term)
	{
		return self::total_interest_for_period($rate, $principle, $term, 12);
	}

	public static function total_interest_for_period($rate, $principle, $term, $periods)
	{
		$payment = self::monthly_payment_for_period($rate, $principle, $term, $periods);
		$res = self::months_to_repay($rate, $principle, $payment);
		if(!is_null($res))
			return $res->interest;
		return 0;
	}

	public static function amortization($rate, $principle, $term)
	{
		return self::amortization_for_period($rate, $principle, $term, 12);
	}

	public static function amortization_for_period($rate, $principle, $term, $periods)
	{
		$points = array();


		$point = new principle_interest();
		$point->principle = $principle;
		$point->interest = 0;
		$points[] = $point;

		$payment = monthly_payment_for_period($rate, $principle, $term, $periods);
		$period_rate = $rate / 100.0 / $periods;
		for ($j = 0; $j < $term * $periods; $j++)
		{
			$interest = round($point->principle * $period_rate, 2);

			$point->interest_payment = $interest;
			$point->principle_payment = $payment - $interest;
			$point->principle -= $point->principle_payment;
			$point->interest += $interest;

	
			$p = new principle_interest();
			$p->interest = $point->interest;
			$p->interest_payment = $point->interest_payment;
			$p->principle = $point->principle;
			$p->principle_payment = $point->principle_payment;
			$points[] = $p;
		}

		return $points;
	}

	public static function payoff($debt)
	{
		$history = array();
		$month = 0;

		$total = new principle_interest();
		$total->principle = $debt->principle;

		while ($total->principle > 0 && $month < 360)
		{
			$month++;
			$point = new principle_interest();
			$period_interest = $debt->interest_rate / 100.0 / 12.0 * $total->principle;

			$total->interest += $period_interest;

			if($debt->payment < $total->principle + $period_interest)
			{
				$point->principle_payment = $debt->payment - $period_interest;
				$total->principle -= $point->principle_payment;
			}
			else
			{
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
		$history = array();
		$month = 0;

		$total = new principle_interest();
		$total->principle = $account->principle;

		while ($month < $months)
		{
			$month++;
			$point = new principle_interest();
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
		return history;
	}

	public static function snowball($debts)
	{
		$points = array();
		$point = new principle_interest();
		$history = array();
		$cur_month = 0;
		$in_debt = true;

		$h = array();

		for ($j = 0; $j < sizeof(debts); $j++)
		{
			$d = new debt();
			$d->interest_rate = $debts[$j]->interest_rate;
			$d->payment = 0;
			$d->principle = $debts[$j]->principle;
			$d->name = $debts[$j]->name;
			$h[] = $d;
		}
		$history[] = $h;

		while ($in_debt && $cur_month < 1200)
		{
			$point->principle = 0;
			$point->interest_payment = 0;
			$point->principle_payment = 0;

			$h = array();

			$rollover = 0.0;
			for ($j = 0; $j < sizeof($debts); $j++)
			{
				$interest = round($debts[$j]->principle * $debts[$j]->interest_rate / 100.0 / 12.0, 2);

				$point->interest += $interest;
				$point->principle += $debts[$j]->principle;

				$point->interest_payment += $interest;

				$debts[$j]->principle += $interest;
				$payment = $debts[$j].payment;

				if ($payment > $debts[$j]->principle)
				{
					$rollover += $payment - $debts[$j]->principle;
					$point->principle_payment += $debts[$j]->principle - $interest;
					$payment = $debts[$j]->principle;
					$debts[$j]->principle = 0;
				}
				else
				{
					$debts[$j]->principle -= $payment;
					$point->principle_payment += $payment - $interest;
				}

				$d = new debt();
				$d->interest_rate = $debts[$j]->interest_rate;
				$d->payment = $payment;
				$d->principle = $debts[$j]->principle;
				$d->name = $debts[$j]->name;
				$h[] = $d;
			}
	
			$has_debts = true;
			while ($rollover > 0 && $has_debts)
			{
				$remaining_debt = 0;
				for ($k = 0; $k < sizeof($debts); $k++)
				{
					if ($debts[$k]->principle > 0)
					{
						if ($debts[$k]->principle < $rollover)
						{
							$rollover -= $debts[$k]->principle;
							$point->principle_payment += $debts[$k]->principle;
							$h[$k]->payment += $debts[$k]->principle;
							$debts[$k]->principle = 0;
						}
						else
						{
							$debts[$k]->principle -= $rollover;
							$point->principle_payment += $rollover;
							$h[$k]->payment += $rollover;
							$rollover = 0;
							$remaining_debt++;
						}
					}
				}
				if ($remaining_debt == 0)
					$has_debts = false;
			}

			if ($point->principle <= 0)
				$in_debt = false;
			else
			{
				$cur_month++;

				$history[] = $h;
				$p = new principle_interest();
				$p->month = $cur_month;
				$p->interest = $point->interest;
				$p->interest_payment = $point->interest_payment;
				$p->principle = $point->principle;
				$p->principle_payment = $point->principle_payment;
				$points[] = $p;
			}
		}
		return array('points'=>$points,'history'=>$history);
	}
}
