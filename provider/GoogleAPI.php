<?php
namespace ninazu\unwanted\provider;

use yii\base\Exception;

class GoogleAPI {

	#region API details

	private $apiVersion = '3.0';

	private $apiUrl = 'https://safebrowsing.google.com/safebrowsing/';

	#endregion

	#region Constants

	const METHOD_LIST = 'list';

	const METHOD_DOWNLOAD = 'downloads';

	const METHOD_LOOKUP = 'api/lookup';

	const CHUNK_TYPE_ADD = 'a';

	const CHUNK_TYPE_SUB = 's';

	const CHUNK_TYPE_ADD_CID = 1;

	const CHUNK_TYPE_SUB_CID = 2;

	const REQUEST_API_VERSION = 'pver';

	const REQUEST_API_KEY = 'key';

	const REQUEST_CLIENT_NAME = 'client';

	const REQUEST_CLIENT_VERSION = 'appver';

	const PHP_EOL = PHP_EOL;

	const PHP_LF = "\n";

	#endregion

	private $apiKey;

	private $callback;

	private $clientVersion;

	private $clientName;

	private $lengthInKb;

	private static $allowedMethod = [
		self::METHOD_LIST,
		self::METHOD_DOWNLOAD,
		self::METHOD_LOOKUP
	];

	private static $allowedChunkType = [
		self::CHUNK_TYPE_ADD => self::CHUNK_TYPE_ADD_CID,
		self::CHUNK_TYPE_SUB => self::CHUNK_TYPE_SUB_CID,
	];

	/**
	 * GoogleAPI constructor.
	 * @param string $apiKey
	 * @param CallbackInterface $callback
	 * @param int $lengthInKb
	 * @throws Exception
	 */
	public function __construct($apiKey, $callback, $lengthInKb = 1) {
		if (!$callback instanceof CallbackInterface) {
			throw new Exception('Callback is not implemented of CallbackInterface');
		}

		$this->lengthInKb = $lengthInKb;
		$this->apiKey = $apiKey;
		$this->clientName = strtolower(preg_replace('/[^A-Za-z0-9.]/', '', $callback->getClientName()));
		$this->clientVersion = preg_replace('/[^0-9.]/', '', $callback->getClientVersion());
		$this->callback = $callback;
	}

	public function getChunkSize() {
		return $this->lengthInKb * 1024;
	}

	private function formatFields($fields) {
		return "";
	}

	private static function addEOL($text) {
		return "{$text}" . self::PHP_EOL;
	}

	private static function addLF($text) {
		return "{$text}" . self::PHP_LF;
	}

	private static function removeEOL($text) {
		return trim($text, self::PHP_LF);
	}

	private static function removeLF($text) {
		return trim($text, self::PHP_EOL);
	}

	public function download() {
		$availableList = $this->callback->syncLists($this->getList());
		$lists = '';

		foreach ($availableList as $list_id => $listName) {
			foreach (self::$allowedChunkType as $chunkType=>$chunkTypeCid) {
				$chunk = $this->callback->getChunk($chunkTypeCid, $list_id);
				$chunk->start = '';
				$chunk->finish = '';

				$lists .= self::addLF($listName);
			}
		}

		$sizeInKb = 1;
		$RBNFBody = "{$sizeInKb}\n {$lists}" . PHP_EOL;

		$options = [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $RBNFBody
		];

		$response = $this->queryBuilder(self::METHOD_DOWNLOAD, $options);

		return self::prepareResponse($response);
	}

	public function getLookup($fields) {
	}

	/**
	 * Return list for check
	 * @return array
	 * @throws Exception
	 */
	public function getList() {
		$response = $this->queryBuilder(self::METHOD_LIST);

		return self::prepareResponse($response);
	}

	/**
	 * Explode response
	 * @param string $data
	 * @return array
	 */
	private static function prepareResponse($data) {
		return explode(self::PHP_LF, self::removeEOL($data));
	}

	/**
	 * Prepare request
	 * @param string $method
	 * @param null $options
	 * @return mixed|null
	 * @throws Exception
	 */
	private function queryBuilder($method, $options = null) {
		if (!in_array($method, self::$allowedMethod)) {
			throw new Exception('');
		}

		$getQuery = http_build_query([
			self::REQUEST_API_KEY => $this->apiKey,
			self::REQUEST_API_VERSION => $this->apiVersion,
			self::REQUEST_CLIENT_NAME => $this->clientName,
			self::REQUEST_CLIENT_VERSION => $this->clientVersion,
		]);

		$url = "{$this->apiUrl}{$method}?{$getQuery}";

		return self::request($url, $options);
	}

	/**
	 * Get response to API
	 * @param string $url API full URL
	 * @param array $options CURL options
	 * @return mixed|null
	 */
	private static function request($url, $options) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (is_array($options)) {
			curl_setopt_array($ch, $options);
		}

		$data = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($status_code != 200) {
			return null;
		}

		return $data;
	}
}
