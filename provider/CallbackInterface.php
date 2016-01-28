<?php

namespace ninazu\unwanted\provider;

interface CallbackInterface {

	public function resetDatabase();

	public function getClientVersion();

	public function getClientName();
}