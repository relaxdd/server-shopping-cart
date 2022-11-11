<?php

namespace Relaxdd\Cart\Http;

class Method {
  public string $method;

  public function __construct(Request $request) {
    $this->method = $request->server("REQUEST_METHOD");
  }

  public function toString(): string {
    return $this->method;
  }

  public function isEquals(string $method): bool {
    return $this->method === $method;
  }

  const GET = "GET";
  const POST = "POST";
  const DELETE = "DELETE";
  const PUT = "PUT";
  const PATCH = "PATCH";
}