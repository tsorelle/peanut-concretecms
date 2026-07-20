<?php

namespace Tops\sys;

interface IHttpRequest
{
    public function get(string $key): string|null;
    public function getMethod(): string;
}