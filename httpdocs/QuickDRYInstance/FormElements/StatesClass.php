<?php
namespace QuickDRYInstance\FormElements;

/**
 * Class StatesClass
 */
class StatesClass
{
  private static array $states = [
    'Alabama' => 'AL',
    'Alaska' => 'AK',
    'Arizona' => 'AZ',
    'Arkansas' => 'AR',
    'California' => 'CA',
    'Colorado' => 'CO',
    'Connecticut' => 'CT',
    'Delaware' => 'DE',
    'Florida' => 'FL',
    'Georgia' => 'GA',
    'Hawaii' => 'HI',
    'Idaho' => 'ID',
    'Illinois' => 'IL',
    'Indiana' => 'IN',
    'Iowa' => 'IA',
    'Kansas' => 'KS',
    'Kentucky' => 'KY',
    'Louisiana' => 'LA',
    'Maine' => 'ME',
    'Maryland' => 'MD',
    'Massachusetts' => 'MA',
    'Michigan' => 'MI',
    'Minnesota' => 'MN',
    'Mississippi' => 'MS',
    'Missouri' => 'MO',
    'Montana' => 'MT',
    'Nebraska' => 'NE',
    'Nevada' => 'NV',
    'New Hampshire' => 'NH',
    'New Jersey' => 'NJ',
    'New Mexico' => 'NM',
    'New York' => 'NY',
    'North Carolina' => 'NC',
    'North Dakota' => 'ND',
    'Ohio' => 'OH',
    'Oklahoma' => 'OK',
    'Oregon' => 'OR',
    'Pennsylvania' => 'PA',
    'Rhode Island' => 'RI',
    'South Carolina' => 'SC',
    'South Dakota' => 'SD',
    'Tennessee' => 'TN',
    'Texas' => 'TX',
    'Utah' => 'UT',
    'Vermont' => 'VT',
    'Virginia' => 'VA',
    'Washington' => 'WA',
    'West Virginia' => 'WV',
    'Wisconsin' => 'WI',
    'Wyoming' => 'WY'
  ];

  /**
   * @return array|null
   */
  public static function GetStates(): ?array
  {
    return array_flip(self::$states);
  }

  /**
   * @param $name
   * @param string $get
   * @return null
   */
  public static function StateABBR($name, string $get = 'abbr')
  {
    $name = preg_replace('/[^a-z]/si', '', trim($name));

    if (strlen($name) > 2) {
      $get = 'abbr';
    }
//make sure the state name has correct capitalization:
    if ($get != 'name') {
      $name = strtolower($name);
      $name = ucwords($name);
    } else {
      $name = strtoupper($name);
    }

    if ($get === 'name') {
      // in this case $name is actually the abbreviation of the state name and you want the full name
      $states = array_flip(self::$states);
    }

    return $states[$name] ?? null;
  }
}