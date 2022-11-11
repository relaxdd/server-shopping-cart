<?php

use Relaxdd\Cart\Application;
use Relaxdd\Cart\Controller;
use Relaxdd\Cart\Middlewares\CheckAction;
use Relaxdd\Cart\Middlewares\CheckBody;
use function Relaxdd\Cart\Utils\isAllowedDomain;

require_once "./vendor/autoload.php";

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');
header("Content-Type: application/json; charset=UTF-8");

if ($match = isAllowedDomain()) {
  header('Access-Control-Allow-Origin: ' . $match);
}

// Show display errors
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Application
$app = new Application();
// Controller
$controller = new Controller();
// Use middlewares
$app->use(new CheckAction());
// Middlewares
$checkPostBody = new CheckBody("id", "qty", "token");

/* Init actions */

// @Get
$app->get("data", [$controller, "GetData"], [], "Получить текущую корзину");
// @Get
$app->get("token", [$controller, "GetToken"], [], "Сгенерировать новый токен");

// @Post
$app->post(
  "subscribe",
  [$controller, "PostSubscribe"],
  [new CheckBody("token")],
  "Подписаться на обновление корзины"
);

// @Post
$app->post(
  "set",
  [$controller, "PostSet"],
  [$checkPostBody],
  "Добавить элемент в корзину или его количество"
);

// @Post
$app->post(
  "add",
  [$controller, "PostAdd"],
  [$checkPostBody],
  "Установить новое кол-во для элемента корзины"
);

// @Post
$app->post(
  "delete",
  [$controller, "PostDelete"],
  [new CheckBody("id", "token")],
  "Удалить элемент из корзины"
);

// @Post
$app->post(
  "clear",
  [$controller, "PostClear"],
  [new CheckBody("token")],
  "Очистить корзину"
);

// @Post
$app->post(
  "check",
  [$controller, "PostCheck"],
  [new CheckBody("cart")],
  "Проверить актуальность корзины"
);

// Запуск приложения
$app->run();





