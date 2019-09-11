<?php

namespace TradeChest\Provider;

interface Provider{
	public function save(): void;
	public function set($key,$data): void;
	public function get($key);
	public function delete($key): void;
	public function exists($key): bool;
	public function setAll(array $data): void;
	public function getAll($key): array;
}