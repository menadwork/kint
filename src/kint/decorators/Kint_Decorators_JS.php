<?php

namespace kint\decorators;

use kint\inc\KintParser;
use kint\inc\KintVariableData;

/**
 * Class Kint_Decorators_JS
 */
class Kint_Decorators_JS
{
  /**
   * @var bool
   */
  public static $firstRun = true;

  /**
   * @param KintVariableData $kintVar
   *
   * @return mixed
   */
  private static function _unparse(KintVariableData $kintVar)
  {
    if (
        $kintVar->value !== null
        &&
        (
            $kintVar->size === null
            ||
            $kintVar->extendedValue === null
        )
    ) {
      if ($kintVar->type === "string") {
        return substr($kintVar->value, 1, -1);
      } else {
        return $kintVar->value;
      }
    }

    $ret = array();

    if ($kintVar->extendedValue !== null) {
      foreach ($kintVar->extendedValue as $key => $var) {
        if ($var->name !== null) {
          $key = $var->name;
          if ($key[0] === "'" && substr($key, -1) === "'") {
            $key = substr($key, 1, -1);
          }
          if (ctype_digit($key)) {
            $key = (int)$key;
          }
        }
        $ret[$key] = self::_unparse($var);
      }
    }

    if (class_exists($kintVar->type)) {
      $ret = (object)$ret;
    }

    return $ret;
  }

  /**
   * @param KintVariableData $kintVar
   * @param int              $level
   *
   * @return string
   */
  public static function decorate(KintVariableData $kintVar, /** @noinspection PhpUnusedParameterInspection */ $level = 0)
  {
    return "kintDump.push(" . json_encode(self::_unparse($kintVar)) . ");"
           . "console.log(kintDump[kintDump.length-1]);";
  }

  /**
   * @param $traceData
   *
   * @return string
   */
  public static function decorateTrace($traceData)
  {
    foreach ($traceData as &$frame) {
      if (isset($frame['args'])) {
        KintParser::reset();
        $frame['args'] = self::_unparse(KintParser::factory($frame['args']));
      }

      if (isset($frame['object'])) {
        KintParser::reset();
        $frame['object'] = self::_unparse(KintParser::factory($frame['object']));
      }
    }

    return "kintDump.push(" . json_encode($traceData) . ");"
           . "console.log(kintDump[kintDump.length-1]);";
  }

  /**
   * called for each dump, opens the html tag
   */
  public static function wrapStart()
  {
    return "<script>";
  }

  /**
   * closes wrapStart() started html tags
   */
  public static function wrapEnd()
  {
    return "</script>";
  }

  /**
   * @return string
   */
  public static function init()
  {
    return "<script>if(typeof kintDump==='undefined')var kintDump = [];</script>";
  }
}
