<?php

namespace Relaxdd\Cart;

use Relaxdd\Cart\Http\Method;
use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;
use Relaxdd\Cart\Interfaces\Middleware;

class Application {
  private Request $request;
  private Response $response;
  private string $method;
  private ?string $action;
  /** @var Middleware[] $middlewares */
  private array $middlewares;
  /** @var Route[] $listOfRoutes */
  protected array $listOfRoutes;

  public function __construct() {
    $this->request = new Request([
      'GET' => $_GET,
      'POST' => $_POST,
      'FILES' => $_FILES,
      'SERVER' => $_SERVER
    ]);

    $this->response = new Response();
    $this->method = $this->request->server("REQUEST_METHOD");;
    $this->action = $this->request->query("action");
    $this->middlewares = [];
    $this->listOfRoutes = [];
  }

  public function run() {
    $this->runMiddlewares($this->middlewares);

    foreach ($this->listOfRoutes as $route) {
      if ($this->method !== $route->method) continue;
      if ($this->action !== $route->action) continue;

      // var_dump($route->middlewares);

      $this->runMiddlewares($route->middlewares);
      $route->callback($this->request, $this->response);
    }

    $listOfActions = $this->collectUnusedActions();

    $this->response->send(
      "Ни одно из существующих действий не было выполнено, список доступных действий:",
      200,
      ["actions" => $listOfActions]
    );
  }

  /**
   * @param Middleware $middleware
   * @return void
   */
  public function use(Middleware $middleware) {
    $this->middlewares[] = $middleware;
  }

  /**
   * @GetRoute
   *
   * @param string|null $action
   * @param callable $callback
   * @param Middleware[] $middlewares
   * @param string|null $description
   * @return void
   */
  public function get(?string $action, callable $callback, array $middlewares = [], ?string $description = null) {
    $this->append(Method::GET, $action, $callback, $middlewares, $description);
  }

  /**
   * @PostRoute
   *
   * @param string|null $action
   * @param callable $callback
   * @param Middleware[] $middlewares
   * @param string|null $description
   * @return void
   */
  public function post(?string $action, callable $callback, array $middlewares = [], ?string $description = null) {
    $this->append(Method::POST, $action, $callback, $middlewares, $description);
  }

  /**
   * TODO: По идее можно переписать с использованием linkedList
   *
   * @param array $middlewares
   * @param Request $request
   * @param Response $response
   * @param int $index
   * @return Request
   */
  protected function realizeNeedForCallNext(array $middlewares, Request $request, Response $response, int $index = 0): Request {
    $lRequest = $request;

    $next = function (Request $request) use (&$response, &$middlewares, &$index, &$lRequest) {
      $index++;

      if ($index === count($middlewares))
        $lRequest = $request;
      else
        $this->realizeNeedForCallNext($middlewares, $request, $response, $index);
    };

    $middlewares[$index]->run($request, $response, $next);

    return $lRequest;
  }

  /**
   * @param Middleware[] $middlewares
   * @return void
   */
  protected function runMiddlewares(array $middlewares) {
    if (!count($middlewares)) return;
    $this->request = $this->realizeNeedForCallNext($middlewares, $this->request, $this->response);
  }

  /**
   * @param string $method
   * @param string $action
   * @param callable $callback
   * @param Middleware[] $middlewares
   * @param string|null $description
   * @return void
   */
  protected function append(
    string $method,
    string $action,
    callable $callback,
    array $middlewares,
    ?string $description = null
  ) {
    $this->listOfRoutes[] =
      new Route($method, $action, $callback, $middlewares, $description);
  }

  /**
   * @return array
   */
  protected function collectUnusedActions(): array {
    $collect = function (Route $route) {
      $info = [
        "method" => $route->method,
        "action" => $route->action,
      ];

      if (!empty($route->description)) {
        $info["description"] = $route->description;
      }

      return $info;
    };

    return array_map($collect, $this->listOfRoutes);
  }
}


