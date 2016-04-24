<?php

namespace kint\parsers\custom;

use kint\inc\KintParser;

/**
 * Class Kint_Parsers_Microtime
 */
class Kint_Parsers_Microtime extends KintParser
{
  private static $_times = array();
  private static $_laps  = array();

  /**
   * @param mixed $variable
   *
   * @return bool
   */
  protected function _parse(&$variable)
  {
    if (
        is_object($variable)
        ||
        is_array($variable)
        ||
        (string)$variable !== $variable
        ||
        !preg_match('[0\.[0-9]{8} [0-9]{10}]', $variable)
    ) {
      return false;
    }

    list($usec, $sec) = explode(" ", $variable);

    $time = (float)$usec + (float)$sec;
    $size = memory_get_usage(true);

    # '@' is used to prevent the dreaded timezone not set error
    /** @noinspection PhpUsageOfSilenceOperatorInspection */
    $this->value = @date('Y-m-d H:i:s', $sec) . '.' . substr($usec, 2, 4);

    $numberOfCalls = count(self::$_times);
    if ($numberOfCalls > 0) { # meh, faster than count($times) > 1
      $lap = $time - end(self::$_times);
      self::$_laps[] = $lap;

      $this->value .= "\n<b>SINCE LAST CALL:</b> <b class=\"kint-microtime\">" . round($lap, 4) . '</b>s.';
      if ($numberOfCalls > 1) {
        $this->value .= "\n<b>SINCE START:</b> " . round($time - self::$_times[0], 4) . 's.';
        $this->value .= "\n<b>AVERAGE DURATION:</b> "
                        . round(array_sum(self::$_laps) / $numberOfCalls, 4) . 's.';
      }
    }

    $unit = array('B', 'KB', 'MB', 'GB', 'TB');
    $memTmp = round($size / pow(1024, $i = (int)floor(log($size, 1024))), 3);
    $this->value .= "\n<b>MEMORY USAGE:</b> " . $size . " bytes (" . $memTmp . ' ' . $unit[$i] . ")";

    self::$_times[] = $time;
    $this->type = 'Stats';

    return true;
  }
}