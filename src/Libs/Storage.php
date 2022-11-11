<?php

namespace Relaxdd\Cart\Libs;

use Error;
use function Relaxdd\Cart\Utils\arrayFind;
use function Relaxdd\Cart\Utils\indexOf;

class Storage {
  public string $filepath;

  /**
   * @param string $filename Полный относительный путь от DOCUMENT_ROOT
   */
  public function __construct(string $filename) {
    $this->filepath = $_SERVER["DOCUMENT_ROOT"] . $filename;
  }

  /**
   * Получает базу из файла
   *
   * @return array
   */
  public function get(): array {
    if (!is_readable($this->filepath))
      throw new Error("Файл не существует или не доступен для чтения!");

    $file = file_get_contents($this->filepath);
    $data = json_decode($file, true);

    if (empty($data))
      throw new Error("Файл storage пустой!");

    return $data;
  }

  /**
   * Перезаписывает файл базы
   *
   * @param array $data
   */
  public function set(array $data) {
    if (!is_writable($this->filepath))
      throw new Error("Файл не существует или не доступен для записи!");

    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    file_put_contents($this->filepath, $json);
  }

  /**
   * Находит в базе информацию по токену
   *
   * @param string $token
   * @return array|null
   */
  public function find(string $token): ?array {
    $data = $this->get();
    return arrayFind($data, fn($el) => ($el["token"] === $token));
  }

  /**
   * @param string $token
   * @param bool $value
   * @param string $key
   * @return void
   */
  public function change(string $token, bool $value, string $key = "changed") {
    $data = $this->get();
    $cb = fn(array $item) => ($item["token"] === $token);
    $index = indexOf($data, $cb);

    if ($index === -1)
      throw new Error("Токен не найден в базе!");

    if ($key === "token")
      throw new Error("Запрещено менять ключ токена");

    $data[$index][$key] = $value;
    $this->set($data);
  }

  /**
   * @param string $token
   * @param array $data
   * @return void
   */
  public function replace(string $token, array $data) {
    $listOfTokens = $this->get();
    $cb = fn(array $item) => ($item["token"] === $token);
    $index = indexOf($listOfTokens, $cb);

    if ($index === -1)
      throw new Error("Не найден токен в базе!");

    $listOfTokens[$index] = $data;
    $this->set($listOfTokens);
  }
}
