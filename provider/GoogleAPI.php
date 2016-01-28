<?php
namespace ninazu\unwanted\provider;

use yii\base\Exception;

class GoogleAPI {

	private $apiVersion = '3.1';

	private $apiUrl = 'https://safebrowsing.google.com/safebrowsing/';

	private $apiKey;

	private $callback;

	private $clientVersion;

	private $clientName;

	public function __construct($apiKey, $callback) {
		if (!$callback instanceof CallbackInterface) {
			throw new Exception('Callback is not implemented of CallbackInterface');
		}

		$this->apiKey = $apiKey;
		$this->clientName = $callback->getClientName();
		$this->clientVersion = $callback->getClientVersion();
		$this->callback = $callback;
	}

	private function formatFields($fields) {
		return "";
	}

	public function getList() {
		return $this->queryBuilder('list');
	}

	public function getLookup($fields) {
		$options = [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $this->formatFields($fields)
		];
	}

	private function queryBuilder($method, $options = null) {
		if (!in_array($method, ['downloads', 'list', 'api/lookup'])) {
			throw new Exception('');
		}

		$getQuery = http_build_query([
			'key' => $this->apiKey,
			'client' => $this->clientName,
			'appver' => $this->clientVersion,
			'pver' => $this->apiVersion,
		]);

		$url = "{$this->apiUrl}{$method}?{$getQuery}";

		return self::downloader($url, $options);
	}

	private static function downloader($url, $options) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (is_array($options)) {
			curl_setopt_array($ch, $options);
		}

		$data = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] > 299) {
			return null;
		}

		return [$info, $data];
	}
}