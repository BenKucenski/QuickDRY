<?php
namespace QuickDRY\Utilities;


class DateCalcWeekDTO extends SafeClass
{
  public ?string $week_date, $month_year, $week_year;

  public function __construct(?string $week_date, ?string $month_year, ?string $week_year)
  {
    $this->week_date = $week_date;
    $this->week_year = $week_year;
    $this->month_year = $month_year;
  }
}