<?php

namespace Relaxdd\Cart\Utils;

use Error;
use TypeError;

/**
 * @param string $string
 * @return mixed
 */
function TODO(string $string) {
  throw new Error("TODO - $string");
}

function indexOf(array $array, callable $callback): int {
  foreach ($array as $index => $value) {
    if ($callback($value, $index) === true)
      return $index;
  }

  return -1;
}

/**
 * @param array $array
 * @param callable $callback
 * @return mixed|null
 */
function arrayFind(array $array, callable $callback) {
  foreach ($array as $i => $value) {
    if ($callback($value, $i) === true)
      return $value;
  }

  return null;
}

function arrayEvery(array $array, callable $callback, $thisArg = null): bool {
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

/**
 * @return string
 */
function getMainDomain(): string {
  $parse = explode('.', $_SERVER['HTTP_HOST']);
  $qty_inners = count($parse);

  if ($qty_inners > 2)
    for ($i = 0; $i < $qty_inners - 2; $i++)
      array_shift($parse);

  return join(".", $parse);
}

/**
 * @param string|null $domain
 * @param string[] $additional_list
 * @return string|false
 */
function isAllowedDomain(?string $domain = null, array $additional_list = []) {
  $request_headers = apache_request_headers();
  $http_origin = $request_headers['origin']
    ?? ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']));
  $protocol = apache_getenv('HTTPS') ? 'https:' : 'http:';
  $domain = $domain ?: getMainDomain();
  $regexp = "/$protocol:\/\/(.*?)\.$domain/";

  if (in_array($http_origin, $additional_list))
    return $http_origin;

  if (preg_match($regexp, $http_origin, $matches))
    return $matches[0];

  return false;
}
