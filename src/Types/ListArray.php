<?php

namespace Relaxdd\Cart\Types;

use Error;
use TypeError;

class ListArray {
  private array $subject;

  private const methods = [
      "indexOf",
      "find",
      "every"
  ];

  public function __construct(...$args) {
    $this->subject = (array) $args;
  }

  public function get(int $index) {
    return $this->subject[$index] ?? null;
  }

  public function toArray(): array {
    return $this->subject;
  }

  public function toMapArray(callable $callback): array {
    $map = [];

    foreach ($this->subject as $key) {
      $map[$key] = $callback();
    }

    return $map;
  }

  /**
   * @param callable $callback
   * @return int
   */
  public function indexOf(callable $callback): int {
    return self::call("indexOf", $this->subject, $callback);
  }

  /**
   * @param callable $callback
   * @return mixed
   */
  public function find(callable $callback) {
    return self::call("find", $this->subject, $callback);
  }

  /**
   * @param callable $callback
   * @param mixed $thisArg
   * @return boolean
   */
  public function every(callable $callback, $thisArg = null): bool {
    return self::call("every", $this->subject, $callback, $thisArg);
  }

  /* Service methods */

  public static function call(string $method, ...$args) {
    if (!in_array($method, self::methods)) {
      throw new Error("Метода $method не существует");
    }

    return self::invoke($method, $args);
  }

  protected static function invoke(string $name, array $args = []) {
    $methods = [
        "indexOf" => function (array $array, callable $callback): int {
          foreach ($array as $index => $value) {
            if ($callback($value, $index) === true)
              return $index;
          }

          return -1;
        },
        "find" => function (array $array, callable $callback) {
          foreach ($array as $i => $value) {
            if ($callback($value, $i) === true)
              return $value;
          }

          return null;
        },
        "every" => function (array $array, callable $callback, $thisArg = null): bool {
          $index = 0;

          foreach ($array as $key => $value) {
            $condition = false;

            if (is_null($thisArg))
              $condition = call_user_func_array($callback, [$value, $key, $index, $array]);
            else if (is_object($thisArg) || (is_string($thisArg) && class_exists($thisArg)))
              $condition = call_user_func_array([$thisArg, $callback], [$value, $key, $index, $array]);
            else if (is_string($thisArg) && !class_exists($thisArg))
              throw new TypeError("Class '$thisArg' not found");

            if (!$condition)
              return false;
            $index++;
          }

          return true;
        }
    ];

    return $methods[$name](...$args);
  }
}