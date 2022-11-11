<?php

use Relaxdd\Cart\Application;
use Relaxdd\Cart\Data\Message;
use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;
use Relaxdd\Cart\Http\Status;
use Relaxdd\Cart\Middlewares\CheckAction;
use Relaxdd\Cart\Middlewares\CheckBody;
use Relaxdd\Cart\Models\CartModel;
use Relaxdd\Cart\Models\SubscribeModel;
use function Relaxdd\Cart\Utils\isAllowedDomain;

// Composer autoload
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

// Middlewares
$checkPostBody = new CheckBody("id", "qty", "token");

// Application
$app = new Application();
$app->use(new CheckAction());

// Models
$cartModel = new CartModel();
$subscribeModel = new SubscribeModel();

// TODO: Перенести все actions в контроллер

// @Get
$app->get("data", function (Request $req, Response $res) use ($cartModel) {
  $cart = $cartModel->getCart();

  $data = [
    "count" => count($cart),
    "cart" => $cart
  ];

  $res->send(
    null,
    Status::SUCCESS,
    $data
  );
}, [], "Получить текущую корзину");

// @Get
$app->get("token", function (Request $_, Response $res) use ($subscribeModel) {
  try {
    $token = $subscribeModel->generateToken();
  } catch (Error $error) {
    $res->send($error, Status::SERVER_ERROR);
    exit;
  }

  $res->send(
    "Токен был успешно сгенерирован",
    Status::SUCCESS,
    ["token" => $token]
  );
}, [], "Сгенерировать новый токен");

// @Get
$app->get("subscribe", function (Request $req, Response $res) use ($subscribeModel) {
  $token = $req->query("token");

  try {
    $subscribeModel->subscribe($token);
  } catch (Error $error) {
    $res->send($error, Status::SERVER_ERROR);
  }

  $res->send("Корзина недавно была обновлена!");
}, [new CheckBody("token")], "Подписаться на обновление корзины");

// @Post
$app->post("set", function (Request $req, Response $res) use ($subscribeModel, $cartModel) {
  ["id" => $id, "qty" => $qty, "token" => $token] = $req->getRequestBody();

  $validate = false;

  try {
    $validate = $cartModel->setCart($id, $qty);
  } catch (Error $error) {
    $res->send($error, Status::BAD_REQUEST);
  } catch (Exception $exception) {
    $res->send($exception, Status::SERVER_ERROR);
  }

  try {
    $subscribeModel->setChanged($token, true);
  } catch (Error $error) {
    $res->send(Message::CHANGED_ERROR, Status::SERVER_ERROR);
  }

  /*  */

  if ($validate)
    $res->send(Message::SUCCESS_MESSAGE);
  else
    $res->send(Message::CART_SET_ERROR, Status::SERVER_ERROR);
}, [$checkPostBody], "Добавить элемент в корзину или его количество");

// @Post
$app->post("add", function (Request $req, Response $res) use ($cartModel, $subscribeModel) {
  ["id" => $id, "qty" => $qty, "token" => $token] = $req->getRequestBody();

  $result = null;

  try {
    $result = $cartModel->setItemQty($id, $qty);
  } catch (Exception $exception) {
    $res->send($exception, Status::SERVER_ERROR);
  }

  try {
    $subscribeModel->setChanged($token, true);
  } catch (Error $error) {
    $res->send(Message::CHANGED_ERROR, Status::SERVER_ERROR);
  }

  if ($result) $res->send(Message::SUCCESS_MESSAGE);
  else $res->send(Message::CART_SET_ERROR, Status::SERVER_ERROR);
}, [$checkPostBody], "Установить новое кол-во для элемента корзины");

// @Post
$app->post("delete", function (Request $req, Response $res) use ($subscribeModel, $cartModel) {
  $id = $req->body("id");
  $token = $req->body("token");

  try {
    $cartModel->deleteItem($id);
  } catch (Exception $exception) {
    $res->send("Failed to delete trash item from cookies: $exception", Status::SERVER_ERROR);
  }

  try {
    $subscribeModel->setChanged($token, true);
  } catch (Error $error) {
    $res->send(Message::CHANGED_ERROR, Status::SERVER_ERROR);
  }

  $res->send(Message::SUCCESS_MESSAGE);
}, [new CheckBody("id", "token")], "Удалить элемент из корзины");

// @Post
$app->post("clear", function (Request $req, Response $res) use ($subscribeModel, $cartModel) {
  $token = $req->body("token");

  try {
    $cartModel->clearCart();
  } catch (Exception $exception) {
    $res->send("Failed to clear the grocery cart from cookies: $exception", Status::SERVER_ERROR);
  }

  try {
    $subscribeModel->setChanged($token, true);
  } catch (Error $error) {
    $res->send(Message::CHANGED_ERROR, Status::SERVER_ERROR);
  }

  $res->send(Message::SUCCESS_MESSAGE);
}, [new CheckBody("token")], "Очистить корзину");

// @Post
$app->post("check", function (Request $req, Response $res) {
  // TODO("Not implemented");
  $res->send("Привет из блока check!");
}, [], "Проверить актуальность корзины");

$app->run();





