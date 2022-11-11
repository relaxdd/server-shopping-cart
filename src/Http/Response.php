<?php

namespace Relaxdd\Cart\Http;

class Response {
  /**
   * Устанавливает статус ответа
   *
   * @param int $code
   * @return $this
   */
  public function status(int $code = 200): Response {
    http_response_code($code);
    return $this;
  }

  /**
   * Устанавливает http заголовок
   *
   * @param string $name
   * @param string $value
   * @return $this
   */
  public function header(string $name, string $value): Response {
    header("$name: $value");
    return $this;
  }

  /* Methods completing the request */

  /**
   * Отправить ответ клиенту с указанием статуса и дополнительных данных
   *
   * @param string|null $message
   * @param int $code
   * @param array $concat
   * @return void
   */
  public function send(
    ?string $message = null,
    int $code = 200,
    array $concat = []
  ) {
    $res = ['status' => $code >= 200 && $code < 300];

    if (!empty($message))
      $res['message'] = $message;

    $merge = array_merge($res, $concat);

    http_response_code($code);
    die(json_encode($merge, JSON_UNESCAPED_UNICODE));
  }

  /**
   * Возвращает пустой ответ на клиент
   * @return void
   */
  public function end() {
    die();
  }
}