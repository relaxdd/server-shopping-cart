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

    if (empty($file)) {
      sleep(0.25);
      $this->get();
    }

    $data = json_decode($file, true);

    if (empty($data))
      throw new Error("Файл storage пустой! $file");

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
    $result = file_put_contents($this->filepath, $json);

    if ($result === false) {
      sleep(0.25);
      $this->set($data);
    }
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
   * @param string|null $changer
   * @return void
   */
  public function change(string $token, bool $value, ?string $changer) {
    [$data, $index] = $this->getDataWithTokenIndex($token);

    $data[$index]["changed"] = $value;
    $data[$index]["changer"] = $value ? $changer : null;

    $this->set($data);
  }


  public function setSubscribed(string $token, bool $isEntry) {
    [$data, $index] = $this->getDataWithTokenIndex($token);

    $data[$index]["listeners"] = $isEntry
      ? $data[$index]["listeners"] + 1
      : $data[$index]["listeners"] - 1;

    $this->set($data);
  }

  /**
   * @param string $token
   * @param array $replacer
   * @return void
   */
  public function replace(string $token, array $replacer) {
    [$data, $index] = $this->getDataWithTokenIndex($token);
    $data[$index] = $replacer;
    $this->set($data);
  }

  /**
   * @param string $token
   * @return array Массив из двух элементов, первый элемент array Storage::get(), второй int $index найденного элемента по токену
   */
  protected function getDataWithTokenIndex(string $token): array {
    $data = $this->get();
    $cb = fn(array $item) => ($item["token"] === $token);
    $index = indexOf($data, $cb);

    if ($index === -1)
      throw new Error("Токен не найден в базе!");

    return [$data, $index];
  }
}
