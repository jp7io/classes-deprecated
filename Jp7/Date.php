<?php

use Carbon\Carbon;

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Helper for date utils.
 */
class Jp7_Date extends Carbon
{
    /**
     * A test Date instance to be returned when now instances are created.
     *
     * @see copied from Carbon: http://carbon.nesbot.com/docs/#api-testing
     * @var Jp7_Date
     */
    protected static $testNow;

    const DURATION_LOWERISO = 0;
    const DURATION_ISO = 1;
    const DURATION_HUMAN = 2;
    const EMPTY_DATE = '0000-00-00 00:00:00';

    public function __construct($time = 'now', $timezone = null)
    {
        if ($time === 'now' && static::hasTestNow()) {
            $testInstance = clone static::getTestNow();
            $time = (string) $testInstance;
        }
        return parent::__construct($time, $timezone);
    }

    public static function setTestNow($testNow = null)
    {
        static::$testNow = $testNow;
    }

    public static function getTestNow()
    {
        return static::$testNow;
    }

    public static function hasTestNow()
    {
        return static::getTestNow() !== null;
    }

    /**
     * Returns new Jp7_Date object formatted according to the specified format.
     *
     * @param string       $format
     * @param string       $time
     * @param DateTimeZone $timezone
     *
     * @return Jp7_Date
     */
    public static function createFromFormat($format, $time, $timezone = null)
    {
        if ($timezone) {
            $date = parent::createFromFormat($format, $time, $timezone);
        } else {
            $date = parent::createFromFormat($format, $time);
        }
        if ($date) {
            return new static($date->format('c'), $date->getTimezone());
        } else {
            return $date;
        }
    }

    public static function createFromString($time, $formats = ['d/m/Y', 'Y-m-d'], $timezone = null)
    {
        foreach ($formats as $format) {
            if ($date = static::createFromFormat($format, $time, $timezone)) {
                return $date;
            }
        }
    }

    /**
     * @param $string
     * @deprecated use copy() or clone
     */
    public function cloneAndModify($string)
    {
        $copy = clone $this;

        return $copy->modify($string);
    }

    /**
     * Adds an amount of days, months, years, hours, minutes and seconds to a Date object.
     *
     * @param DateInterval|string $interval
     * @return Date|bool
     */
    public function add($interval, $value = 1, $overflow = null)
    {
        if (is_string($interval)) {
            // Check for ISO 8601
            if (strtoupper(substr($interval, 0, 1)) == 'P') {
                $interval = new DateInterval($interval);
            } else {
                $interval = DateInterval::createFromDateString($interval);
            }
        }
        return parent::add($interval) ? $this : false;
    }
    /**
     * Subtracts an amount of days, months, years, hours, minutes and seconds from a DateTime object.
     *
     * @param DateInterval|string $interval
     * @return Date|bool
     */
    public function sub($interval, $value = 1, $overflow = null)
    {
        if (is_string($interval)) {
            // Check for ISO 8601
            if (strtoupper(substr($interval, 0, 1)) == 'P') {
                $interval = new DateInterval($interval);
            } else {
                $interval = DateInterval::createFromDateString($interval);
            }
        }
        return parent::sub($interval) ? $this : false;
    }

    /**
     * Retorna string da diferença de tempo, ex: '3 dias atrás'.
     * O valor é arredondado: 2 anos e 4 meses retorna '2 anos atrás'.
     * Diferenças menores de 1 minuto retornam 'agora'.
     *
     * @return string
     */
    public function humanDiff()
    {
        global $lang;
        switch ($lang->lang) {
            case 'en':
                $units_names = ['years' => 'year', 'months' => 'month', 'weeks' => 'week', 'days' => 'day', 'hours' => 'hour', 'minutes' => 'minute'];
                $now = 'now';
                $yesterday = 'yesterday';
                $ago = 'ago';
                break;
            default:
                $units_names = ['anos' => 'ano', 'meses' => 'mês', 'semanas' => 'semana', 'dias' => 'dia', 'horas' => 'hora', 'minutos' => 'minuto'];
                $now = 'agora';
                $yesterday = 'ontem';
                $ago = 'atrás';
        }
        $timeStamp = $this->getTimestamp();
        $currentTime = time();
        $units = array_combine($units_names, [31556926, 2629743, 604800, 86400, 3600, 60]);
        $seconds = $currentTime - $timeStamp;
        if ($seconds <= 60) {
            return $now;
        } elseif ($seconds < 86400 * 2 && date('d', $currentTime) - 1 == date('d', $timeStamp)) {
            return $yesterday; // ontem
        } elseif ($seconds > 86400 && $seconds < 604800) {
            $seconds = round($seconds / 86400) * 86400; // dias
        }
        foreach ($units as $unit => $seconds_in_period) {
            if ($seconds >= $seconds_in_period) {
                $count = floor($seconds / $seconds_in_period);

                return $count.' '.(($count > 1) ? array_search($unit, $units_names) : $unit).' '.$ago;
            }
        }
    }

    /**
     * Returns the age based on the birthdate and the current date.
     *
     * @param string|int $to   [optional]
     *
     * @return int Age in years.
     *
     */
    public function yearsDiff($to = false)
    {
        $from = $this->getTimestamp();
        if ($to === false) {
            $to = time();
        } else {
            $to = self::_toTime($to);
        }
        // Function itself
        list($y, $m, $d) = explode('-', date('Y-m-d', $from));
        $years = date('Y', $to) - $y;
        if (date('md', $to) < $m.$d) {
            $years--;
        }

        return $years;
    }

    /**
     * Difference of days between 2 timestamps.
     *
     * @param int $from [only with static calls]
     * @param int $to   [optional]
     *
     * @return int
     */
    public function daysDiff($to = false, $min = false)
    {
        $from = $this->getTimestamp();
        if ($to === false) {
            $to = time();
        } else {
            $to = self::_toTime($to);
        }
        // Function itself
        $diff = $to - $from;

        $days = $min == false ? round($diff / 86400) : $diff / 86400;

        return $days;
    }

    public function hoursDiff($to = false, $min = false)
    {
        $daydiff = $this->daysDiff($to, true);

        return $min == false ? floor($daydiff * 24) : $daydiff * 24;
    }

    public function minutesDiff($to = false, $sec = false)
    {
        $hoursDiff = $this->hoursDiff($to, true);

        return $sec == false ? floor($hoursDiff * 60) : $hoursDiff * 60;
    }

    public function secondsDiff($to = false)
    {
        $minutesDiff = $this->minutesDiff($to, true);

        return floor($minutesDiff * 60);
    }

    /**
     * Converts string to time if needed.
     *
     * @param string $datetime
     *
     * @return int
     */
    protected static function _toTime($datetime)
    {
        if (!is_int($datetime)) {
            $datetime = strtotime($datetime);
        }

        return $datetime;
    }

    /**
     * Returns date formatted according to given format.
     *
     * @param string $format Format accepted by date().
     *
     * @return
     */
    public function format($format)
    {
        // InterAdmin trabalha com data zerada
        if (!$this->isValid()) {
            // If usado para performance
            if ($format === 'Y-m-d H:i:s') {
                return self::EMPTY_DATE;
            } else {
                $format = preg_replace('/(?<!\\\\)Y/', '0000', $format);
                $format = preg_replace('/(?<!\\\\)(d|m|y)/', '00', $format);
                $format = preg_replace('/(?<!\\\\)c/', '0000-00-00\T00:00:00', $format);
            }
        }
        // Tratamento de nomes para múltiplas línguas
        if (strpos($format, 'D') !== false) {
            $format = preg_replace('/(?<!\\\\)D/', addcslashes(jp7_date_week(intval($this->format('w')), true), 'A..z'), $format);
        }
        if (strpos($format, 'l') !== false) {
            $format = preg_replace('/(?<!\\\\)l/', addcslashes(jp7_date_week(intval($this->format('w'))), 'A..z'), $format);
        }
        if (strpos($format, 'M') !== false) {
            $format = preg_replace('/(?<!\\\\)M/', addcslashes(jp7_date_month($this->format('m'), true), 'A..z'), $format);
        }
        if (strpos($format, 'F') !== false) {
            $format = preg_replace('/(?<!\\\\)F/', addcslashes(jp7_date_month($this->format('m')), 'A..z'), $format);
        }
        // Format padrão
        return parent::format($format);
    }

    public function short()
    {
        global $lang;
        if ($lang->lang === 'en') {
            return $this->format('m/d/Y');
        } else {
            return $this->format('d/m/Y');
        }
    }

    public function long()
    {
        global $lang;
        if ($lang->lang === 'en') {
            return $this->format('F d, Y');
        } else {
            return $this->format('d \d\e F \d\e Y');
        }
    }

    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function minute($value = null)
    {
        if ($value !== null) {
            return pareng::minute($value);
        }
        return $this->format('i');
    }
    public function hour($value = null)
    {
        if ($value !== null) {
            return pareng::hour($value);
        }
        return $this->format('H');
    }
    public function day($value = null)
    {
        if ($value !== null) {
            return pareng::day($value);
        }
        return $this->format('d');
    }
    public function month($value = null)
    {
        if ($value !== null) {
            return pareng::month($value);
        }
        return $this->format('m');
    }
    public function quarter()
    {
        return ceil($this->format('m') / 3);
    }
    public function year($value = null)
    {
        if ($value !== null) {
            return pareng::year($value);
        }
        return $this->format('Y');
    }

    /**
     * Checks if its not an invalid date such as '0000-00-00 00:00:00'.
     *
     * @return bool
     */
    public function isValid()
    {
        return parent::format('Y') !== '-0001';
    }

    /**
     * Returns the duration between two Jp7_Date objects.
     *
     * @param Jp7_Date $datetime
     * @param $iso Retorna no formato iso ou num formato mais comum como 4h30m.
     *
     * @return string
     */
    public function duration(Jp7_Date $datetime, $iso = self::DURATION_ISO)
    {
        $diff = $this->diff($datetime);

        if ($iso == self::DURATION_ISO) {
            $duration = 'P';
        } else {
            $duration = '';
        }

        if ($diff->y) {
            if ($iso == self::DURATION_HUMAN) {
                $duration .= $diff->y.(($diff->y == 1) ? ' ano ' : ' anos ');
            } else {
                $duration .= $diff->y.'Y';
            }
        }
        if ($diff->m) {
            if ($iso == self::DURATION_HUMAN) {
                $duration .= $diff->m.(($diff->m == 1) ? ' mês ' : ' meses ');
            } else {
                $duration .= $diff->m.'M';
            }
        }
        if ($diff->d) {
            if ($iso == self::DURATION_HUMAN) {
                $duration .= $diff->d.(($diff->d == 1) ? ' dia ' : ' dias ');
            } else {
                $duration .= $diff->d.'D';
            }
        }
        if ($diff->h || $diff->i || $diff->s) {
            if ($iso == self::DURATION_ISO) {
                $duration .= 'T';
            }

            if ($diff->h) {
                if ($iso == self::DURATION_HUMAN) {
                    $duration .= $diff->h.(($diff->h == 1) ? ' hora ' : ' horas ');
                } else {
                    $duration .= $diff->h.'H';
                }
            }
            if ($diff->i) {
                if ($iso == self::DURATION_HUMAN) {
                    $duration .= $diff->i.(($diff->i == 1) ? ' minuto ' : ' minutos ');
                } else {
                    $duration .= $diff->i.'M';
                }
            }
            if ($diff->s) {
                if ($iso == self::DURATION_HUMAN) {
                    $duration .= $diff->s.(($diff->s == 1) ? ' segundo ' : ' segundos ');
                } else {
                    $duration .= $diff->s.'S';
                }
            }
        }

        if ($iso == self::DURATION_LOWERISO) {
            $duration = mb_strtolower($duration);
        }

        return $duration;
    }
}
