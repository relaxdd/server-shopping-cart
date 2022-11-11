<?php

namespace Relaxdd\Cart\Libs;

// Class that abstracts both the $_COOKIE and setcookie()
use Exception;

class Cookie {
  /** @var false|mixed|string|null */
  protected $data = array();
  protected int $expire;
  /** @var string|null */
  protected ?string $domain;

  /**
   * @param array $cookie
   * @param int $expire
   * @param string|null $domain
   */
  public function __construct(array $cookie, int $expire = 2419200, ?string $domain = null) {
    // Set up the data of this cookie
    $this->data = $cookie;
    $this->expire = $expire;
    $this->domain = $domain
      ?? ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']));
  }

  /**
   * @param string $name
   * @return mixed|string|null
   */
  public function get(string $name) {
    return (isset($this->data[$name])) ? $this->data[$name] : null;
  }

  /**
   * @param string $name
   * @param mixed|null $value
   * @return void
   * @throws Exception
   */
  public function set(string $name, $value = null) {
    // Check whether the headers are already sent or not
    if (headers_sent())
      throw new Exception("Can't change cookie " . $name . " after sending headers.");

    // Delete the cookie
    if (!$value) {
      setcookie($name, null, time() - 10, '/', '.' . $this->domain, false, true);
      unset($this->data[$name]);
      unset($_COOKIE[$name]);
    } else {
      // Set the actual cookie
      setcookie($name, $value, time() + $this->expire, '/', $this->domain, false, true);
      $this->data[$name] = $value;
      $_COOKIE[$name] = $value;
    }
  }
}
