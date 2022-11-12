<?php

namespace Relaxdd\Cart\Models;

use Error;
use Relaxdd\Cart\Libs\Storage;

class SubscribeModel {
  const POLLING_FREQUENCY = 0.2;
  private Storage $storage;

  public function __construct() {
    $this->storage = new Storage("/server/cart/src/store.json");
  }

  /**
   * Генерирует новый токен, записывает его в базу
   *
   * @return string Новый токен
   */
  public function generateToken(): string {
    $id = uniqid();
    $data = $this->storage->get();

    $data[] = array(
      "token" => $id,
      "changed" => false
    );

    $this->storage->set($data);

    return $id;
  }

  /**
   * Подписывается на обновление корзины
   *
   * @param string $token
   * @param string $changer
   * @return void
   */
  public function subscribe(string $token, string $changer) {
    // $this->storage->setSubscribed($token, true);

    while (true) {
      $tokenData = $this->findToken($token);

      // Если нет клиента с таким токеном
      if (empty($tokenData)) {
        throw new Error("Not found token in storage");
      }

      // Проверка на обновление корзины
      // TODO: Сделать так что бы убавлялись слушатели в неконтролируемых ситуациях
      if ($tokenData["changed"] === true && $tokenData["changer"] !== $changer) {
        // $qty = $this->findToken($token)["listeners"];
        $this->storage->change($token, false, $changer);

        break;
      }

      sleep(self::POLLING_FREQUENCY);
    }

    // $this->storage->setSubscribed($token, false);
  }

  /**
   * Меняет статус изменения в базе по токену
   *
   * @param string $token
   * @param string $changer
   * @param bool $value
   * @return void
   */
  public function setChanged(string $token, string $changer, bool $value) {
    $this->storage->change($token, $value, $changer);
  }

  /**
   * @param string $token
   * @return array
   */
  protected function findToken(string $token): array {
    $data = $this->storage->find($token);

    if (empty($data))
      throw new Error("Токен не найден в базе!");

    return $data;
  }
}