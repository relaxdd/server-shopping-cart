<?php

namespace Relaxdd\Cart;

use Error;
use Exception;
use Relaxdd\Cart\Data\Message;
use Relaxdd\Cart\Http\Request;
use Relaxdd\Cart\Http\Response;
use Relaxdd\Cart\Http\Status;
use Relaxdd\Cart\Models\CartModel;
use Relaxdd\Cart\Models\SubscribeModel;
use function Relaxdd\Cart\Utils\arrayEvery;

class Controller {
  private CartModel $cartModel;
  private SubscribeModel $subscribeModel;

  public function __construct() {
    $this->cartModel = new CartModel();
    $this->subscribeModel = new SubscribeModel();
  }

  // @Get
  public function GetData(Request $_, Response $res) {
    $cart = $this->cartModel->getCart();

    $data = [
      "count" => count($cart),
      "cart" => $cart
    ];

    $res->send(
      null,
      Status::SUCCESS,
      $data
    );
  }

  // @Get
  public function GetToken(Request $_, Response $res) {
    try {
      $token = $this->subscribeModel->generateToken();
    } catch (Error $error) {
      $res->send($error, Status::SERVER_ERROR);
      exit;
    }

    $res->send(
      "Токен был успешно сгенерирован",
      Status::SUCCESS,
      ["token" => $token]
    );
  }

  // @Post
  public function PostSubscribe(Request $req, Response $res) {
    ["token" => $token, "changer" => $changer] = $req->getRequestBody();

    try {
      $this->subscribeModel->subscribe($token, $changer);
    } catch (Error $error) {
      $res->send($error, Status::SERVER_ERROR);
    }

    $res->send("Корзина недавно была обновлена!");
  }

  // @Post
  public function PostSet(Request $req, Response $res) {
    ["id" => $id, "qty" => $qty, "token" => $token, "changer" => $changer]
      = $req->getRequestBody();

    $validate = false;

    try {
      $validate = $this->cartModel->setCart($id, $qty);
    } catch (Error $error) {
      $res->send($error, Status::BAD_REQUEST);
    } catch (Exception $exception) {
      $res->send($exception, Status::SERVER_ERROR);
    }

    $this->setChanged($token, $changer, $res);

    /*  */

    if ($validate)
      $res->send(Message::SUCCESS_MESSAGE);
    else
      $res->send(Message::CART_SET_ERROR, Status::SERVER_ERROR);
  }

  // @Post
  public function PostAdd(Request $req, Response $res) {
    ["id" => $id, "qty" => $qty, "token" => $token, "changer" => $changer]
      = $req->getRequestBody();

    $result = null;

    try {
      $result = $this->cartModel->setItemQty($id, $qty);
    } catch (Exception $exception) {
      $res->send($exception, Status::SERVER_ERROR);
    }

    $this->setChanged($token, $changer, $res);

    if ($result) $res->send(Message::SUCCESS_MESSAGE);
    else $res->send(Message::CART_SET_ERROR, Status::SERVER_ERROR);
  }

  // @Post
  public function PostDelete(Request $req, Response $res) {
    ["id" => $id, "token" => $token, "changer" => $changer] = $req->getRequestBody();

    try {
      $this->cartModel->deleteItem($id);
    } catch (Exception $exception) {
      $res->send("Failed to delete trash item from cookies: $exception", Status::SERVER_ERROR);
    }

    $this->setChanged($token, $changer, $res);
    $res->send(Message::SUCCESS_MESSAGE);
  }

  // @Post
  public function PostClear(Request $req, Response $res) {
    ["token" => $token, "changer" => $changer] = $req->getRequestBody();

    try {
      $this->cartModel->clearCart();
    } catch (Exception $exception) {
      $res->send("Failed to clear the grocery cart from cookies: $exception", Status::SERVER_ERROR);
    }

    $this->setChanged($token, $changer, $res);
    $res->send(Message::SUCCESS_MESSAGE);
  }

  // @Post
  public function PostCheck(Request $req, Response $res) {
    /** @var string $json */
    $json = $req->body("cart");
    /** @var array $cart */
    $cart = json_decode($json, true);

    if (!is_array($cart))
      $res->send("Передан не валидный параметр запроса cart", Status::BAD_REQUEST);

    $callback = fn($item) => !empty($item["id"]) && !empty($item["qty"]);

    if (!arrayEvery($cart, $callback))
      $res->send("Передан не валидный параметр запроса cart", Status::BAD_REQUEST);

    $changed = $this->cartModel->checkChanges($cart);

    $res->send(null, Status::SUCCESS, ["changed" => $changed]);
  }

  /**
   * TODO: возможно не самое лучшее решение
   *
   * @param string $token
   * @param string $changer
   * @param Response $res
   * @return void
   */
  protected function setChanged(string $token, string $changer, Response $res) {
    try {
      $this->subscribeModel->setChanged($token, $changer, true);
    } catch (Error $error) {
      $res->send(Message::CHANGED_ERROR, Status::SERVER_ERROR);
    }
  }
}
