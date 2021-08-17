<?php
namespace QuickDRY\Math;

use QuickDRY\Utilities\SafeClass;

/**
 * Class Debt
 */
class Debt extends SafeClass
{
    public ?int $id = null;
    public ?float $interest_rate = null;
    public ?float $payment = null;
    public ?float $principal = null;
    public ?string $name = null;
}