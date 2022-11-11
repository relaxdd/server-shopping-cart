<?php

namespace Relaxdd\Cart\Interfaces;

use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;

interface Middleware {
  /**
   * Запуск middleware для обработки или валидации $request
   * Если последний в цепочке - то функцию $next можно не вызывать
   *
   * @param Request $request
   * @param Response $response
   * @param callable $next Функция для вызова следующего middleware в цепочке
   * @return void
   */
  public function run(Request $request, Response $response, callable $next);
}