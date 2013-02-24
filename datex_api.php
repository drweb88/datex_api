<?php

/*
 * Copyright (C) 2012 Koosha Hosseiny
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Author Koosha Hosseiny, Drupalion Group
 * @copyright (c) 2012, Koosha Hosseiny
 * more info: @see http://www.drupalion.com
 * contact: info@koosha.cc
 */

<?php

/**
 * @file
* API and helper functions used by other datex modules.
*/

/**
 * API Config Constants.
*/

/**
 * whether to use PHP-Intl on date conversion or not. It's a default value and
* can be set in module's settings form.
*/
define('DATEX_USE_INTL', FALSE);

/**
 * Define state of a given date.
*/
define('DATEX_GREGORIAN', TRUE);
define('DATEX_JALALI', FALSE);

/**
 * Determines wheter default date given to datetime constructor is Jalali or
 * Gregorian.
*/
define('DEFAULT_DATEXOBJECT_POLICY', DATEX_GREGORIAN);

/**
 * Date tools for Jalali Dates.
*/
class DatexFormatter {
  /**
   * Similar to php date_format.
   *
   * @param mixed $date
   *   Date to format.
   * @param string $format
   *   Needed date format.
   * @param DateTimeZone $tz
   *   DateTimeZone.
   * @param bool $use_intl
   *   Wheter to use php-intl or not, recomended.
   *
   * @return string
   *   formatted date.
   */
  public static function format($date, $format, $tz = NULL, $use_intl = DATEX_USE_INTL, $formatter_args = NULL, &$error_code = NULL, &$error_message = NULL) {
    $tz = self::getTzObject($tz);

    if (self::hasINTL() && $use_intl) {
      return self::formatINTL($date, $format, $tz, $formatter_args, $error_code, $error_message);
    }
    else {
      return self::formatPHP($date, $format, $tz);
    }
  }

  /**
   * Returns array containing names of days and monthes in persian.
   */
  public static function persianDateNames() {
    static $names = NULL;
    if (!$names) {
      $names = array(
          'months' => array(
              1 => 'فروردین',
              2 => 'اردیبهشت',
              3 => 'خرداد',
              4 => 'تیر',
              5 => 'مرداد',
              6 => 'شهریور',
              7 => 'مهر',
              8 => 'آبان',
              9 => 'آذر',
              10 => 'دی',
              11 => 'بهمن',
              12 => 'اسفند',
          ),
          'ampm' => array(
              'am' => 'ق.ظ',
              'pm' => 'ب.ظ',
          ),
          'day_abbr' => array(
              6 => 'ش.',
              7 => 'ی.',
              1 => 'د.',
              2 => 'س.',
              3 => 'چ.',
              4 => 'پ.',
              5 => 'ج.',
          ),
          'day' => array(
              6 => 'شنبه',
              7 => 'یک‌شنبه',
              1 => 'دوشنبه',
              2 => 'سه‌شنبه',
              3 => 'چهارشنبه',
              4 => 'پنج‌شنبه',
              5 => 'جمعه',
          ),
          'tz' => 'تهران',
      );
    }

    return $names;
  }

  /**
   * Converts a Gregorian date to Jalali.
   */
  public static function toJalali($gregorian_year = NULL, $gregorian_month = NULL, $gregorian_day = NULL) {
    $now = getdate();
    if (!$gregorian_month) {
      $gregorian_month = $now['mon'];
    }
    if (!$gregorian_year) {
      $gregorian_year = $now['year'];
    }
    if (!$gregorian_day) {
      $gregorian_day = $now['mday'];
    }

    $num_days_in_gregorian_month = array(31, 28, 31, 30, 31, 30,
        31, 31, 30, 31, 30, 31);
    $num_days_in_jalali_month = array(31, 31, 31, 31, 31, 31,
        30, 30, 30, 30, 30, 29);

    $g_year = $gregorian_year - 1600;
    $g_month = $gregorian_month - 1;
    $g_day = $gregorian_day - 1;

    $gregorian_day_no = 365 * $g_year + intval(($g_year + 3) / 4) - intval(($g_year + 99) / 100) + intval(($g_year + 399) / 400);
    for ($i = 0; $i < $g_month; ++$i) {
      $gregorian_day_no += $num_days_in_gregorian_month[$i];
    }
    if (
        ($g_month > 1 && (($g_year % 4 == 0 && $g_year % 100 != 0))
            || ($g_year % 400 == 0))
    ) {
      // Leap and after Feb.
      $gregorian_day_no++;
    }
    $gregorian_day_no += $g_day;

    $jalali_day_no = $gregorian_day_no - 79;
    $j_np = intval($jalali_day_no / 12053);
    $jalali_day_no = $jalali_day_no % 12053;

    $j_year = 979 + 33 * $j_np + 4 * intval($jalali_day_no / 1461);
    $jalali_day_no %= 1461;

    if ($jalali_day_no >= 366) {
      $j_year += intval(($jalali_day_no - 1) / 365);
      $jalali_day_no = ($jalali_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $jalali_day_no >= $num_days_in_jalali_month[$i]; ++$i) {
      $jalali_day_no -= $num_days_in_jalali_month[$i];
    }

    $j_month = $i + 1;
    $j_day = $jalali_day_no + 1;
    return array('day' => $j_day, 'month' => $j_month, 'year' => $j_year);
  }

  /**
   * Converts a Gregorian date to Jalali.
   */
  public static function toGregorian($jalali_year = NULL, $jalali_month = NULL, $jalali_day = NULL) {
    $now = self::toJalali();
    if (!$jalali_day) {
      $jalali_day = $now['day'];
    }
    if (!$jalali_month) {
      $jalali_month = $now['month'];
    }
    if (!$jalali_year) {
      $jalali_year = $now['year'];
    }

    $gregorian_days_in_month = array(31, 28, 31, 30, 31, 30, 31,
        31, 30, 31, 30, 31,);
    $jalali_days_in_month = array(31, 31, 31, 31, 31, 31, 30,
        30, 30, 30, 30, 29,);

    $j_year = $jalali_year - 979;
    $j_month = $jalali_month - 1;
    $j_day = $jalali_day - 1;

    $jalali_day_no = 365 * $j_year + intval($j_year / 33) * 8 + intval((($j_year % 33) + 3) / 4);
    for ($i = 0; $i < $j_month; ++$i) {
      $jalali_day_no += $jalali_days_in_month[$i];
    }
    $jalali_day_no += $j_day;
    $gregorian_day_no = $jalali_day_no + 79;

    $g_year = 1600 + 400 * intval($gregorian_day_no / 146097);
    $gregorian_day_no = $gregorian_day_no % 146097;

    $leap = TRUE;
    if ($gregorian_day_no >= 36525) {
      $gregorian_day_no--;
      $g_year += 100 * intval($gregorian_day_no / 36524);
      $gregorian_day_no = $gregorian_day_no % 36524;

      if ($gregorian_day_no >= 365) {
        $gregorian_day_no++;
      }
      else {
        $leap = FALSE;
      }
    }

    $g_year += 4 * intval($gregorian_day_no / 1461);
    $gregorian_day_no %= 1461;

    if ($gregorian_day_no >= 366) {
      $leap = FALSE;

      $gregorian_day_no--;
      $g_year += intval($gregorian_day_no / 365);
      $gregorian_day_no = $gregorian_day_no % 365;
    }

    for ($i = 0; $gregorian_day_no >= $gregorian_days_in_month[$i] + ($i == 1 && $leap); $i++) {
      $gregorian_day_no -= $gregorian_days_in_month[$i] + ($i == 1 && $leap);
    }
    $g_month = $i + 1;
    $g_day = $gregorian_day_no + 1;

    $ret = array('year' => $g_year, 'month' => $g_month, 'day' => $g_day);
    return $ret;
  }

  /**
   * Converts php date format string (like 'Y-m-d') to it's php-intl equivilant.
   *
   * @param string $format
   *   Format accepted by php date_format.
   *
   * @return string
   *   Format accepted by php-intl date formatter (ICU).
   */
  public static function phpToIntl($format) {
    static $format_map = NULL;
    if ($format_map == NULL) {
      $format_map = array(
          'd' => 'dd',
          'D' => 'EEE',
          'j' => 'd',
          'l' => 'EEEE',
          'N' => 'e',
          'S' => 'LLLL',
          'w' => '',
          'z' => 'D',
          'W' => 'w',
          'm' => 'MM',
          'M' => 'MMM',
          'F' => 'MMMM',
          'n' => 'M',
          't' => '',
          'L' => '',
          'o' => 'yyyy',
          'y' => 'yy',
          'Y' => 'YYYY',
          'a' => 'a',
          'A' => 'a',
          'B' => '',
          'g' => 'h',
          'G' => 'H',
          'h' => 'hh',
          'H' => 'HH',
          'i' => 'mm',
          's' => 'ss',
          'u' => 'SSSSSS',
          'e' => 'z',
          'I' => '',
          'O' => 'Z',
          'P' => 'ZZZZ',
          'T' => 'v',
          'Z' => '',
          'c' => '',
          'r' => '',
          'U' => '',
          ' ' => ' ',
          '-' => '-',
          '.' => '.',
          '-' => '-',
          ':' => ':',
      );
    }

    $replace_pattern = '/[^ \:\-\/\.\\\\dDjlNSwzWmMFntLoyYaABgGhHisueIOPTZcrU]/';
    return strtr(preg_replace($replace_pattern, '', $format), $format_map);
  }

  /**
   * Formats a date according to format given.
   *
   * This function uses internal
   * methods for converting, See DatexFormatter::formatINTL is suggested
   * instead.
   */
  public static function formatPHP($date, $format, $tz) {
    $persian_date_names = self::persianDateNames();
    $number_of_days = array(0, 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    $date = self::ObjectFromDate($date, $tz);
    $date = self::ObjectFromDate(self::ObjectFromDate($date, $tz)->format('U') + $tz->getOffset($date), $tz);

    $gregorian_date = array(
        'd' => intval($date->format('j')),
        'm' => intval($date->format('n')),
        'Y' => intval($date->format('Y')),
    );
    $jalali_date = self::toJalali($gregorian_date['Y'], $gregorian_date['m'], $gregorian_date['d']);

    $z = $jalali_date['month'] <= 6 ?
    ($jalali_date['month'] * 31 + $jalali_date['day']) :
    186 + (($jalali_date['month'] - 6) * 30 + $jalali_date['day']);
    $W = ceil($z / 7);
    $t = $number_of_days[$jalali_date['month']];
    $r = $persian_date_names['day_abbr'][$date->format('N')] .
    ', ' . $jalali_date['day'] . ' ' .
    $persian_date_names['months'][$jalali_date['month']] . ' ' .
    $jalali_date['year'] . $date->format('H:i:s P');

    $L = self::isLeap($jalali_date['year']) ? 1 : 0;

    if ($L && $jalali_date['month'] == 12) {
      $t = 30;
    }

    $format = preg_replace('/[\\\\][a-z]/', '', $format);

    $replacements = array(
        'd' => sprintf('%02d', $jalali_date['day']),
        'D' => $persian_date_names['day_abbr'][$date->format('N')],
        'j' => $jalali_date['day'],
        'l' => $persian_date_names['day'][$date->format('N')],
        'S' => $persian_date_names['day_abbr'][$date->format('N')],
        'F' => $persian_date_names['months'][$jalali_date['month']],
        'm' => sprintf('%02d', $jalali_date['month']),
        'n' => $jalali_date['month'],
        'L' => str_replace('L', $L, $format),
        'Y' => $jalali_date['year'],
        'y' => $jalali_date['year'],
        'o' => $jalali_date['year'],
        'a' => $persian_date_names['ampm'][$date->format('a')],
        'A' => $persian_date_names['ampm'][$date->format('a')],
        'B' => $persian_date_names['ampm'][$date->format('a')],
        'c' => $jalali_date['year'] . '-' . $jalali_date['month'] . '-' . $jalali_date['day'] . 'T' . $date->format('H:i:sP'),
        'g' => $date->format('g'),
        'G' => $date->format('G'),
        'h' => $date->format('h'),
        'H' => $date->format('H'),
        'i' => $date->format('i'),
        's' => $date->format('s'),
        'u' => $date->format('u'),
        'I' => $date->format('I'),
        'O' => $date->format('O'),
        'P' => $date->format('P'),
        'T' => $date->format('T'),
        'Z' => $date->format('Z'),
        'U' => $date->format('U'),
        'w' => $date->format('w'),
        'N' => $date->format('N'),
        'e' => $date->format('e'),
        'z' => $z,
        'W' => $W,
        'r' => $r,
        't' => $t,
    );

    return strtr($format, $replacements);
  }

  /**
   * Formats a date according to format given.
   *
   * This function uses php-intl methods for converting. PECL package php-intl
   * must be enabled.
   */
  public static function formatINTL($date, $format, $tz, $formatter_args = NULL, &$error_code = NULL, &$error_message = NULL) {
    static $intl_formatter = NULL;
    if ($intl_formatter == NULL || isset($formatter_args)) {
      if (isset($formatter_args)) {
        $intl_formatter = new IntlDateFormatter(
            $formatter_args[0],
            $formatter_args[1],
            $formatter_args[2],
            $formatter_args[3],
            $formatter_args[4]
        );
      }
      else {
        $intl_formatter = new IntlDateFormatter(
            "fa_IR@calendar=persian",
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL
        );
      }
    }
    $intl_formatter->setPattern(self::phpToIntl($format));
    $date = $intl_formatter->format(self::getTimestamp($date));

    if ($error_code) {
      $error_code = $intl_formatter->getErrorCode();
    }
    if ($error_message) {
      $error_message = $intl_formatter->getErrorMessage();
    }

    return $date;
  }

  /**
   * Created a DateTime object according to a jalali date given.
   *
   * Accepted date formats are an integer (as timestamp), a DatexObject object,
   * a DateTime or DateObject object, an array containing Jalali date parts.
   * If none of above is given, DateTime form current date is created.
   */
  public static function ObjectFromJalali($date = NULL, $tz = NULL) {
    $tz = self::getTzObject($tz);

    if (is_int($date) || is_object($date)) {
      return self::ObjectFromDate($date, $tz);
    }
    elseif (is_array($date)) {
      // Dont touch array indexed 'hour' 'minute' 'second'.
      $greg_date = self::toGregorian(@$date['year'], @$date['month'], @$date['day']);
      list($date['year'], $date['month'], $date['day'])
      = array($greg_date['year'], $greg_date['month'], $greg_date['day']);
      return self::ObjectFromDate($date);
    }
  }

  /**
   * Created a DateTime object containing date from $date.
   *
   * Accepted date formats are an integer (as timestamp), A DatexObject object,
   * A DateTime or DateObject object, an array containing Gregorian date parts.
   * If none of above is given, DateTime form current date is created.
   */
  public static function ObjectFromDate($date = NULL, $tz = NULL) {
    $tz = self::getTzObject($tz);

    if (is_int($date)) {
      return new DateTime('@' . $date, $tz);
    }
    elseif (is_string($date)) {
      try {
        $date = new DateTime($date, $tz);
      }
      catch (Exception $e) {
        return NULL;
      }
    }
    elseif (is_object($date)) {
      $c = get_class($date);
      if ($c == 'DatexObject') {
        return $date->getDateobjClone();
      }
      elseif ($c == 'DateObject' || $c == 'DateTime') {
        return new DateTime('@' . $date->format('U'), $tz);
      }
    }
    elseif (is_array($date)) {
      list($year, $month, $day, $hour, $minute, $second) = array(
          isset($date['year']) ? intval($date['year']) : intval(date('Y')),
          isset($date['month']) ? intval($date['month']) : intval(date('n')),
          isset($date['day']) ? intval($date['day']) : intval(date('j')),
          isset($date['hour']) ? intval($date['hour']) : intval(date('G')),
          isset($date['minute']) ? intval($date['minute']) : intval(date('i')),
          isset($date['second']) ? intval($date['second']) : intval(date('s')),
      );
      return new DateTime('@' . mktime($hour, $minute, $second, $month, $day, $year), $tz);
    }
    elseif ($date == NULL) {
      return new DateTime(NULL, $tz);
    }
    return NULL;
  }

  /**
   * Generates timestamp from given date.
   */
  public static function getTimestamp($date) {
    $date = self::ObjectFromDate($date);
    return intval($date->format('U'));
  }

  /**
   * Determines wether PECL package php-intl is available or not.
   */
  public static function hasINTL() {
    return class_exists('IntlDateFormatter');
  }

  /**
   * Returns current Jalali Year.
   */
  public static function getYear() {
    $date = self::toJalali();
    return $date['year'];
  }

  /**
   * Returns current Jalali month.
   */
  public static function getMonth() {
    $date = self::toJalali();
    return $date['month'];
  }

  /**
   * Returns current Jalali day.
   */
  public static function getDay() {
    $date = self::toJalali();
    return $date['day'];
  }

  /**
   * See php date().
   */
  public static function date($format, $timestamp = NULL) {
    return self::format($timestamp === NULL ? time() : $timestamp, $format);
  }

  /**
   * See php mktime().
   */
  public static function mktime($hour = NULL, $minute = NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL) {
    $date = self::toGregorian($year, $month, $day);
    list($year, $month, $date) = array(
        $year === NULL ? NULL : $date['year'],
        $month === NULL ? NULL : $date['month'],
        $day === NULL ? NULL : $date['day']);
    return mktime($hour, $minute, $second, $month, $day, $year);
  }

  /**
   * See php getdate().
   */
  public static function getdate($timestamp = NULL) {
    $ret = array();

    $timestamp = $timestamp === NULL ? time() : $timestamp;
    $date = new DateTime($timestamp);
    $ret['seconds'] = intval($date->format('s'));
    $ret['minutes'] = intval($date->format('i'));
    $ret['hours'] = intval($date->format('G'));

    $jalali = self::toJalali();
    $ret['mday'] = intval($jalali['day']);
    $ret['mon'] = intval($jalali['month']);
    $ret['year'] = intval($jalali['year']);

    $ret['wday'] = self::formatPHP($date, 'w');
    $ret['yday'] = self::formatPHP($date, 'z');
    $ret['weekday'] = self::formatPHP($date, 'l');
    $ret['month'] = self::formatPHP($date, 'F');
    $ret[0] = $timestamp;
    return $ret;
  }

  /**
   * Returns non zero if given year is a leap year.
   *
   * Algorithm:
   * @author Amin <amin.w3dev@gmail.com>
   */
  public static function isLeap($year_value) {
    return array_search((($year_value + 2346) % 2820) % 128, array(
        5, 9, 13, 17, 21, 25, 29,
        34, 38, 42, 46, 50, 54, 58, 62,
        67, 71, 75, 79, 83, 87, 91, 95,
        100, 104, 108, 112, 116, 120, 124, 0,
    ));
  }

  /**
   * Returns a valid timezone object from given timezone
   */
  public static function getTzObject($tz = NULL) {
    if (is_string($tz) && in_array($tz, DateTimeZone::listIdentifiers())) {
      $timezone = new DateTimeZone($tz);
    }
    elseif($tz instanceof DateTimeZone) {
      $timezone = $tz;
    }
    else {
      $default_tz = date_default_timezone_get();
      $default_tz = $default_tz  ? $default_tz : 'Asia/Tehran';
      $timezone = new DateTimeZone($default_tz);
    }

    return $timezone;
  }

  /**
   * Returns a valid timezone string from given timezone
   */
  public static function getTzString($tz = NULL) {
    if (is_string($tz) && in_array($tz, DateTimeZone::listIdentifiers())) {
      $timezone = new DateTimeZone($tz);
    }
    elseif($tz instanceof DateTimeZone) {
      $timezone = $tz;
    }
    else {
      $default_tz = date_default_timezone_get();
      $default_tz = $default_tz  ? $default_tz : 'Asia/Tehran';
      $timezone = new DateTimeZone($default_tz);
    }

    return $timezone->getName();
  }
}

/**
 * This class is Jalali equivilant of php DateTime. It also has some
 * functionallity from object defiend in Drupal's date module DateObject.
 */
class DatexObject {

  protected $dateobj;
  public $error;
  public $errorMessage;
  public $hasError;
  public $tz;
  protected $format_string;

  /**
   * Constructor for DatexObject.
   *
   * @param mixed $datetime
   *   Given date/time
   * @param bool $date_is_gregorian
   *   Indicates wheter given date to constructor is Gregorian or not, default
   *   is set by a constant in module file.
   * @param DateTimezone $tz
   *   DateTimeZone to use.
   * @param string $format
   *   format used for formatting date.
   */
  public function __construct($datetime = NULL, $date_is_gregorian = DEFAULT_DATEXOBJECT_POLICY, $tz = NULL, $format = NULL) {
    $this->hasError = FALSE;
    $this->error = '';
    $this->errorMessage = '';

    $this->setDatetime($datetime, $date_is_gregorian, $tz);

    $format = $format ? $format : 'Y-m-d';
    $this->setFormat($format);

    $tz = DatexFormatter::getTzObject($tz);
    $this->dateobj->setTimezone($tz);

    return $this;
  }

  /**
   * Magic Function toString.
   *
   * Returns date stored in this function, Formatted according to internal
   * format string, As an string.
   */
  public function __toString() {
    return $this->format();
  }

  /**
   * Reset Date/Time to now.
   */
  public function reset() {
    $this->dateobj = new DateTime(NULL, $this->dateobj->getTimezone());
    return $this;
  }

  /**
   * Similar to DateTime::format().
   *
   * @param string $format
   *   Format string.
   * @param bool $use_intl
   *   Whether to use php-intl or not.
   */
  public function format($format = NULL, $use_intl = DATEX_USE_INTL) {
    $format = $format ? $format : $this->format_string;
    $tz = $this->dateobj->getTimezone();
    return DatexFormatter::format($this->dateobj, $format, $tz, $use_intl, $this->error, $this->errorMessage);
  }

  /**
   * Returns a clone of internal DateTime object.
   *
   * Cloned object always contains Gregorian date converted from jalali date
   * given to DatexObject.
   */
  public function getDateobjClone() {
    return clone($this->dateobj);
  }

  /**
   * Set's date from given date.
   *
   * For accepted list of accepted date formats,
   * See DatexFormatter::ObjectFromDate.
   * See DatexFormatter::ObjectFromJalali().
   */
  public function setDatetime($datetime = NULL, $date_is_gregorian = DEFAULT_DATEXOBJECT_POLICY, $tz = NULL) {
    $tz = DatexFormatter::getTzObject($tz);
    if ($date_is_gregorian) {
      $this->dateobj = DatexFormatter::ObjectFromDate($datetime, $tz);
    }
    else {
      $this->dateobj = DatexFormatter::ObjectFromJalali($datetime, $tz);
    }

    return $this;
  }

  /**
   * Sets date and time zone.
   */
  public function setDate($year = NULL, $month = NULL, $day = NULL, $tz = NULL) {
    $tz = DatexFormatter::getTzObject($tz);

    $year = $year === NULL ? $this->format('Y') : $year;
    $month = $month === NULL ? $this->format('n') : $month;
    $day = $day === NULL ? $this->format('j') : $day;

    $date = DatexFormatter::toGregorian($year, $month, $day);

    $this->xsetDate($date['year'], $date['month'], $date['day']);
    $this->setTimezone($tz);

    return $this;
  }

  /**
   * Set format string used for formating date.
   */
  public function setFormat($format) {
    $this->format_string = $format;
    return $this;
  }

  /**
   * Returns format string set by setFormat.
   */
  public function getFormat() {
    return $this->format_string;
  }

  /**
   * Same as DateTime::getTimezone().
   */
  public function getTimezone() {
    return $this->dateobj->getTimezone();
  }

  /**
   * Sets Time Zone of internal date object.
   *
   * Accepts a DateTimeZone Object or an string representing a timezone.
   */
  public function setTimezone($timezone) {
    $timezone = DatexFormatter::getTzObject($timezone);
    $this->dateobj->setTimezone($timezone);

    return $this;
  }

  /**
   * Same as DateTime::setTimestamp().
   */
  public function setTimestamp($timestamp) {
    $this->dateobj->setTimestamp($timestamp);
    return $this;
  }

  /**
   * Same as DateTime::setTime().
   */
  public function setTime($hour, $minute, $second = 0) {
    $this->dateobj->setTime($hour, $minute, $second);
    return $this;
  }

  /**
   * Same as DateTime::getOffset().
   */
  public function getOffset() {
    return $this->dateobj->getOffset();
  }

  /**
   * Same as DateTime::diff().
   */
  public function xdiff(DateTime $datetime2, $absolute = FALSE) {
    return $this->dateobj->diff($datetime2, $absolute);
  }

  /**
   * Same as DateTime::format().
   */
  public function xformat($format) {
    return $this->dateobj->format($format);
  }

  /**
   * Same as DateTime::getLastErrors().
   */
  public function xgetLastErrors() {
    return $this->getLastErrors();
  }

  /**
   * Same as DateTime::setDate().
   */
  public function xsetDate($year, $month, $day) {
    return $this->dateobj->setDate($year, $month, $day);
  }

  /**
   * Same as DateTime::getTimestamp().
   */
  public function getTimestamp() {
    return $this->dateobj->format('U');
  }

  /**
   * Same as DateTime::modify().
   */
  public function modify($modify) {
    $this->dateobj->modify($modify);
    return $this;
  }

  /**
   * Returns an object containing first day of Jalali month.
   */
  public function monthFirstDay() {
    return DatexObjectUtils::monthFirstDay($this->dateobj);
  }

  /**
   * Returns an object containing last day of Jalali month.
   */
  public function monthLastDay() {
    return DatexObjectUtils::monthLastDay($this->dateobj);
  }

  /**
   * Returns date granularities put in an array.
   *
   * @return array
   *   Date granularities put in an array.
   */
  public function toArray() {
    return DatexObjectUtils::toArray($this->dateobj);
  }

  /**
   * Returns amount of time difference to another date object.
   *
   * @throws Exception if given measure is week, it will throw an exception, it
   * is not implemented yet.
   */
  public function difference(DatexObject $date2_in, $measure = 'seconds', $absolute = TRUE) {
    // Create cloned objects or original dates will be impacted by the
    // date_modify() operations done in this code.
    $date1 = $this->getDateobjClone();
    $date2 = $date2_in->getDateobjClone();

    $diff = date_format($date2, 'U') - date_format($date1, 'U');
    if ($diff == 0) {
      return 0;
    }
    elseif ($diff < 0 && $absolute) {
      // Make sure $date1 is the smaller date.
      $temp = $date2;
      $date2 = $date1;
      $date1 = $temp;
      $diff = date_format($date2, 'U') - date_format($date1, 'U');
    }

    $year_diff = intval(date_format($date2, 'Y') - date_format($date1, 'Y'));

    switch ($measure) {
      case 'seconds':
        return $diff;

      case 'minutes':
        return $diff / 60;

      case 'hours':
        return $diff / 3600;

      case 'years':
        return $year_diff;

      case 'months':
        $format = 'n';
        $item1 = $this->format_php($format, $date1);
        $item2 = $this->format_php($format, $date2);
        if ($year_diff == 0) {
          return intval($item2 - $item1);
        }
        else {
          $item_diff = 12 - $item1;
          $item_diff += intval(($year_diff - 1) * 12);
          return $item_diff + $item2;
        }
        break;

      case 'days':
        $format = 'z';
        $item1 = $this->format_php($format, $date1);
        $item2 = $this->format_php($format, $date2);
        if ($year_diff == 0) {
          return intval($item2 - $item1);
        }
        else {
          $item_diff = date_days_in_year($date1) - $item1;
          for ($i = 1; $i < $year_diff; $i++) {
            date_modify($date1, '+1 year');
            $item_diff += date_days_in_year($date1);
          }
          return $item_diff + $item2;
        }
        break;

      case 'weeks':
        //throw new Exception('week not implemented!');

      default:
        break;
    }

    return NULL;
  }

  /**
   * Same as DatexObject toArray but in Gregorian format.
   *
   * @return array
   *   An array of date granuls.
   */
  public function xtoArray() {
    return array(
        'year' => $this->dateobj->format('Y'),
        'month' => $this->dateobj->format('n'),
        'day' => $this->dateobj->format('j'),
        'hour' => intval($this->dateobj->format('H')),
        'minute' => intval($this->dateobj->format('i')),
        'second' => intval($this->dateobj->format('s')),
        'timezone' => $this->dateobj->format('e'),
    );
  }

}

/**
 * Utitilities to work with a DatexObject
 */
class DatexObjectUtils {

  /**
   * Returns first day of a month.
   */
  public static function monthFirstDay($date = NULL) {
    $date = new DatexObject($date, FALSE);
    $date->setDate(NULL, NULL, 1);
    return $date;
  }

  /**
   * Returns last day of a month.
   */
  public static function monthLastDay($date = NULL) {
    $date = new DatexObject($date, FALSE);
    $date->setDate(NULL, NULL, DatexFormatter::format($date, 't'));
    return $date;
  }

  /**
   * Returns granularity parts of a goven date in an array.
   */
  public static function toArray($date = NULL) {
    $date = new DatexObject($date, FALSE);
    return array(
        'year' => $date->format('Y'),
        'month' => $date->format('n'),
        'day' => $date->format('j'),
        'hour' => intval($date->format('H')),
        'minute' => intval($date->format('i')),
        'second' => intval($date->format('s')),
        'timezone' => $date->format('e'),
    );
  }

  /**
   * Returns a Jalali Object from a given date.
   */
  public static function getJalaliObject($date = NULL) {
    return new DatexObject($date, FALSE);
  }
}
