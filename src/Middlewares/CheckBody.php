<?php

namespace Relaxdd\Cart\Middlewares;

use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;
use Relaxdd\Cart\Http\Status;
use Relaxdd\Cart\Interfaces\Middleware;

class CheckBody implements Middleware {
  public array $args = [];

  public function __construct(...$args) {
    $this->args = array_merge($this->args, $args);
  }

  // TODO: Надо будет по гуглить про метод __invoke, вроде тогда можно будет вызывать класс как функцию
  public function run(Request $request, Response $response, callable $next) {
    $is_valid = $request->validateBody($this->args);

    if (!$is_valid) {
      $response->send(
        "В теле запроса не указаны все нужные параметры: " . implode(", ", $this->args),
        Status::BAD_REQUEST
      );
    }

    $next($request);
  }
}