<?php

/**
 * Class SnowballMath
 */
class SnowballMath extends SafeClass
{
	private $debts = [];
	private $_last_id = 0;

    /**
     * @param my_BkucenskiBankCreditCardsClass $cc
     */
	public function AddCreditCard(my_BkucenskiBankCreditCardsClass $cc)
	{
		$t = new Debt();
		$t->id = $this->_last_id++;
		$t->name = $cc->card_name;
		$t->principal = $cc->card_balance;
		$t->payment = $cc->card_payment;
		$t->interest_rate = $cc->card_interest;
		$this->debts[] = $t;
	}

    /**
     * @param my_BkucenskiBankLoansClass $cc
     */
	public function AddLoan(my_BkucenskiBankLoansClass $cc)
	{
		$t = new Debt();
		$t->id = $this->_last_id++;
		$t->name = $cc->loan_name;
		$t->principal = $cc->loan_balance;
		$t->payment = $cc->loan_payment;
		$t->interest_rate = $cc->loan_interest;
		$this->debts[] = $t;
	}

    /**
     * @param bool $desc
     */
	public function SortByInterest($desc = true)
	{
		$list = [];
		foreach($this->debts as $debt)
			$list[$debt->interest_rate][] = $debt;

		if($desc)
			krsort($list);
		else
			ksort($list);

		$this->debts = [];
		foreach($list as $items)
			foreach($items as $debt)
				$this->debts[] = $debt;
	}

    /**
     * @param bool $apply_rollover
     * @return array
     */
	public function DoSnowball($apply_rollover = true)
	{
		$debts = $this->debts;
		$points = [];
		$point = new PrincipalInterest();
		$history = [];
		$cur_month = 0;
		$in_debt = true;
		$base_rollover = 0.0;

		$h = [];

		for ($j = 0; $j < sizeof($debts); $j++)
		{
			if($debts[$j]->principal == 0) {
				$base_rollover += $debts[$j]->payment;
                unset($debts[$j]);
			} else {
				$d = new Debt();
				$d->interest_rate = $debts[$j]->interest_rate;
				$d->payment = 0;
				$d->principal = $debts[$j]->principal;
				$d->name = $debts[$j]->name;
				$h[] = $d;
			}
		}

		$history[] = $h;

		while ($in_debt && $cur_month < 1200)
		{
			$point->principal = 0;
			$point->interest_payment = 0;
			$point->principal_payment = 0;

			$h = [];
            $rollover = $base_rollover;

			foreach ($debts as $j => $debt)
			{
				$interest = round($debts[$j]->principal * $debts[$j]->interest_rate / 100.0 / 12.0, 2);

				$point->interest += $interest;
				$point->principal += $debts[$j]->principal;

				$point->interest_payment += $interest;

				$debts[$j]->principal += $interest;
				$payment = $debts[$j]->payment;

				if ($payment > $debts[$j]->principal)
				{
					$rollover += $payment - $debts[$j]->principal;
					$point->principal_payment += $debts[$j]->principal - $interest;
					$payment = $debts[$j]->principal;
					$debts[$j]->principal = 0;
				}
				else
				{
					$debts[$j]->principal -= $payment;
					$point->principal_payment += $payment - $interest;
				}

				$d = new Debt();
				$d->interest_rate = $debts[$j]->interest_rate;
				$d->payment = $payment;
				$d->principal = $debts[$j]->principal;
				$d->name = $debts[$j]->name;
				$h[$j] = $d;
			}

			$total_rollover = $rollover;

			$has_debts = true;
			while ($apply_rollover && $rollover > 0 && $has_debts)
			{
				$remaining_debt = 0;
				for ($k = 0; $k < sizeof($debts) && $rollover > 0; $k++)
				{
					if ($debts[$k]->principal > 0)
					{
						if ($debts[$k]->principal < $rollover)
						{
							$rollover -= $debts[$k]->principal;
							$point->principal_payment += $debts[$k]->principal;
							$h[$k]->payment += $debts[$k]->principal;
							$h[$k]->principal = 0;
							$debts[$k]->principal = 0;
						}
						else
						{
							$debts[$k]->principal -= $rollover;
							$h[$k]->principal -= $rollover;
							$point->principal_payment += $rollover;
							$h[$k]->payment = $debts[$k]->payment + $rollover;
							$rollover = 0;
							$remaining_debt++;
						}
					}
				}
				if ($remaining_debt == 0)
					$has_debts = false;

				$total_rollover = $rollover;
			}

			if ($point->principal <= 0)
				$in_debt = false;
			else
			{
				$cur_month++;

				$history[] = $h;
				$p = new PrincipalInterest();
				$p->rollover = $total_rollover;
				$p->month = $cur_month;
				$p->interest = $point->interest;
				$p->interest_payment = $point->interest_payment;
				$p->principal = $point->principal;
				$p->principal_payment = $point->principal_payment;
				$points[] = $p;
			}
		}
		return array('points'=>$points,'history'=>$history);
	}
}