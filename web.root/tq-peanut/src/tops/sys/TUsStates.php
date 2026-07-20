<?php

namespace Tops\sys;

use Tops\sys\TLookupItem;
use Tops\sys\TNameValuePair;

class TUsStates
{
    public const ALL = 0;
    public const STATES = 1;
    public const TERRITORIES = 2;
    public const ASSOCIATED= 3;

    private static $stateList = [
        // Full name → abbreviation

        // States
        'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
        'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT',
        'Delaware' => 'DE', 'Florida' => 'FL', 'Georgia' => 'GA',
        'Hawaii' => 'HI', 'Idaho' => 'ID', 'Illinois' => 'IL', 'Indiana' => 'IN',
        'Iowa' => 'IA', 'Kansas' => 'KS', 'Kentucky' => 'KY', 'Louisiana' => 'LA',
        'Maine' => 'ME', 'Maryland' => 'MD', 'Massachusetts' => 'MA',
        'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS',
        'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV',
        'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM',
        'New York' => 'NY', 'North Carolina' => 'NC', 'North Dakota' => 'ND',
        'Ohio' => 'OH', 'Oklahoma' => 'OK', 'Oregon' => 'OR', 'Pennsylvania' => 'PA',
        'Rhode Island' => 'RI', 'South Carolina' => 'SC', 'South Dakota' => 'SD',
        'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT', 'Vermont' => 'VT',
        'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV',
        'Wisconsin' => 'WI', 'Wyoming' => 'WY',

        // District
        'District of Columbia' => 'DC',

    ];

    private static array $all;
    private static array $statesAndTerritories;

    private static $territories = [
        // Territories & Possessions
        'Puerto Rico' => 'PR',
        'Guam' => 'GU',
        'U.S. Virgin Islands' => 'VI',
        'American Samoa' => 'AS',
        'Northern Mariana Islands' => 'MP',

    ];

    private static $associatedStates = [
        // Freely Associated States (USPS-recognized)
        'Federated States of Micronesia' => 'FM',
        'Marshall Islands' => 'MH',
        'Palau' => 'PW',
    ];

    public static function getStateList($include = self::ALL): array
    {
        if ($include === self::STATES) {
            return self::$stateList;
        }
        if ($include === self::TERRITORIES) {
            if (!isset(self::$statesAndTerritories)) {
                self::$statesAndTerritories = array_merge(self::$stateList, self::$territories);;
            }
            return self::$statesAndTerritories;
        }
        if (!isset(self::$all)) {
            self::$all = array_merge(self::$stateList, self::$territories, self::$associatedStates);
        }
        return self::$all;
    }

    public static function getStateLookup($include = self::STATES)
    {
        $list = self::getStateList($include);
        return TNameValuePair::CreateArray($list);
    }

    /**
     * @param $state
     * @return string
     *
     * If US State return the two letter postal abbreviation,
     * otherwise return original value trimmed
     *
     */
    public static function convertToAbbrevation($state): string
    {
        if ($state === null) {
            return '';
        }
        $state = trim($state);
        // remove white space and punctuation,  Eg. " N.C " = "NC"
        $stateKey = strtoupper(preg_replace('/[\p{P}\p{S}\p{Z}\s]+/u', '', $state));
        if (strlen($stateKey) == 2) {
            $list = self::getStateList();
            $name = array_search($stateKey, self::getStateList(), true);
            if ($name !== false) {
                return $stateKey;
            }
        }
        // Normalize input for lookup
        $normalized = ucwords(strtolower($state));
        $normalized = str_replace([' Of ', ' And '], [' of ', ' and '], $normalized);
        $allStates = self::getStateList();
        return $allStates[$normalized] ?? $normalized;
    }

    /**
     * @param $name
     * @param $default
     * @return string
     *
     * Convert blank input or various forms of united states to 'USA'
     * otherwise return original value.
     *
     * Conversions for provinces and states of other countries not supported...yet.
     */
    public static function getFullCountryName($name, $default='United States of America'): string {
        if ($name === null) {
            return $default;
        }
        $name = trim($name);
        $cmp = strtolower(str_replace('.','',$name));
        if (empty($name) || $cmp == 'us' || $cmp == 'usa' || $cmp == 'united states') {
            return $default;
        }
        return $name;
    }

    private static $reverseList;
    private static $reverseIncludes = self::STATES;
    public static function getReverseLookupList($include = self::TERRITORIES): array
    {
        if (!isset(self::$reverseList) || self::$reverseIncludes != $include) {
            self::$reverseList = array_flip(self::getStateList($include));
            self::$reverseIncludes = $include;
        }
        return self::$reverseList;
    }

    public static function findStateName($abbreviation,$include = self::TERRITORIES): string
    {
        $list = self::getReverseLookupList($include);
        return $list[$abbreviation] ?? $abbreviation;
    }

    public static function getFullStateName($abbreviation,$country=''): string
    {
        $abbreviation = ($abbreviation === null) ? '' : trim($abbreviation);
        if (strlen($abbreviation) == 2) {
            $country = ($country === null) ? '' : strtolower(trim($country));
            $country = str_replace('.','',$country);
            if ($country == 'us' || $country == 'usa' || $country == 'united states' || $country == 'united states of america'
                || $country == 'estados unidos' || $country === '') {
                $key = strtoupper(trim($abbreviation));
                if (strlen($key) == 2) {
                    $name = self::findStateName($key);
                }
            }
        }
        return $abbreviation;
    }

    /**
     * @param $state
     * @param $countryName
     * @param $default
     * @return string
     *
     * Return $default if $state is blank or matches a state abbreviation, except...
     * If state abbreviation for an associated state is given return corresponding full name.
     * Otherwise return $countryName trimmed
     */
    public static function getCountryNameForState($state, $countryName = '', $default='United States'): string {
        $countryName = ($countryName === null) ? '' : trim($countryName);
        $state = ($state === null) ? '' : trim($state);
        $stateKey = self::convertToAbbrevation($state);
        $countryKey = self::normalizeCountryName($countryName, $default);
        if ($countryKey === $default) {
            // state should be found in US lookups
            $key = array_search($stateKey, self::$stateList, true);
            if ($key !== false) {
                return $default;
            }
            $key = array_search($stateKey, self::$territories, true);
            if ($key !== false) {
                return $default;
            }

            // Associated states are independent countries receiving support from the US under the "Compact of Free Association"
            //  In these cases, return the full name not "USA".
            //  E.g. if the state abbreviation is "PW" return "Pelau" not "USA"
            $key = array_search($stateKey, self::$associatedStates, true);
            if ($key !== false) {
                return $key;
            }
        }
        return $countryName;
    }

    /**
     * @param $name
     * @param $default
     * @return string
     *
     * Return $default ('United States') if $name is any form representing United States.
     * otherwise return original value trimmed
     */
    public static function normalizeCountryName($name, $default='United States'): string {
        $name = trim($name ?? '');
        if ($name === '') {
            return $default;
        }
        // strip all white space and punctuation
        $abr = strtolower(preg_replace('/[\p{P}\p{S}\p{Z}\s]+/u', '', $name));
        $cmp = strtolower($name);
        if ($abr == 'us' || $abr == 'usa'
            || $cmp == 'united states' || $cmp == 'united states of america'
            || $cmp == 'estados unidos') {
            return $default;
        }
        return $name;
    }
}