<?php

use QuickDRY\Utilities\SafeClass;

/**
 * Class PrincipalInterest
 */
class PrincipalInterest extends SafeClass
{
    public $month;
    public $principal;
    public $interest;
    public $principal_payment;
    public $interest_payment;
    public $rollover;

    public $table;
}