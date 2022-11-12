<?php

namespace Relaxdd\Cart\Models;

use Error;
use Exception;
use Relaxdd\Cart\Libs\Cookie;
use function Relaxdd\Cart\Utils\getMainDomain;
use function Relaxdd\Cart\Utils\indexOf;

class CartModel {
  private Cookie $cookie;

  public function __construct() {
    $domain = getMainDomain();
    $this->cookie = new Cookie($_COOKIE, 604800, $domain);;
  }

  /**
   * @param string $id
   * @return void
   * @throws Exception
   */
  public function deleteItem(string $id) {
    $cart = $this->getCart();
    $cb = fn($item) => ($item["id"] != $id);
    $removed = array_values(array_filter($cart, $cb));

    $this->cookie->set("OSVETILO_CART", json_encode($removed));
  }

  /**
   * @return array
   */
  public function getCart(): array {
    $cookie = $this->cookie->get("OSVETILO_CART") ?? "[]";
    return json_decode($cookie, true);
  }

  /**
   * @throws Exception
   */
  public function clearCart() {
    $this->cookie->set("OSVETILO_CART");
  }

  /**
   * @param string $id
   * @param string $qty
   * @return bool
   * @throws Exception
   */
  public function setItemQty(string $id, string $qty): bool {
    $cart = $this->getCart();
    $cb = fn($el) => ($el["id"] === $id);
    $index = indexOf($cart, $cb);
    $qty = (int) $qty;

    if ($index !== -1)
      $cart[$index]["qty"] = $qty;
    else {
      $index = count($cart);

      $cart[] = array(
        "id" => $id,
        "qty" => $qty,
      );
    }

    // Обновляем корзину
    $this->cookie->set("OSVETILO_CART", json_encode($cart));

    // Проверка
    $check = $this->getCart();
    return $check[$index]["qty"] === $qty;
  }

  /**
   * @param string $id
   * @param string $qty
   * @return bool
   * @throws Exception
   */
  public function setCart(string $id, string $qty): bool {
    $newItem = ["id" => $id, "qty" => $qty];

    $cart = $this->getCart();
    $pushed = $this->pushCartItem($cart, $newItem);

    if (count($cart) > 65)
      throw new Error("Overflow of the basket");

    // Устанавливаем значение
    $this->cookie->set("OSVETILO_CART", json_encode($pushed));

    // Проверка
    $check_cart = $this->getCart();
    $validate = false;
    $cb = fn($elem) => $elem["id"] === $newItem["id"];
    $index = indexOf($check_cart, $cb);

    if ($index !== -1) {
      $check_qty = $check_cart[$index]["qty"] === $cart[$index]["qty"] + (int) $newItem["qty"];
      if ($check_qty)
        $validate = true;
    }

    return $validate;
  }

  /**
   * Проверяет наличие изменений в корзине
   *
   * @param array $cart
   * @return false|array
   */
  public function checkChanges(array $cart) {
    $serverCart = $this->getCart();
    return $serverCart !== $cart ? $serverCart : false;
  }

  /**
   * @param array $cart Корзина со всеми элементами $item
   * @param array $item Элемент который нужно добавить или обновить кол-во существующего
   * @return array
   */
  protected function pushCartItem(array $cart, array $item): array {
    $callback = fn($el) => $el["id"] === $item["id"];
    $index = indexOf($cart, $callback);

    if ($index !== -1)
      $cart[$index]["qty"] += (int) $item["qty"];
    else {
      $cart[] = [
        "id" => $item["id"],
        "qty" => (int) $item["qty"]
      ];
    }

    return $cart;
  }
}