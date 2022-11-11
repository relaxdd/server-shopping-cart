<?php

namespace Relaxdd\Cart\Middlewares;

use Relaxdd\Cart\Http\Status;
use Relaxdd\Cart\Interfaces\Middleware;
use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;

class CheckAction implements Middleware {
  public function run(Request $request, Response $response, callable $next) {
    if ($request->query("action") === null) {
      $response->send("Не указан query параметр `action`", Status::BAD_REQUEST);
    }

    $next($request);
  }
}