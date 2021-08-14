<?php

use QuickDRY\Utilities\SafeClass;

/**
 * Class Debt
 */
class Debt extends SafeClass
{
    public $id;
    public $interest_rate;
    public $payment;
    public $principal;
    public $name;
}