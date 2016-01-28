<?php

namespace ninazu\unwanted;

use ninazu\unwanted\provider\CallbackInterface;
use ninazu\unwanted\provider\GoogleAPI;
use Yii;
use yii\base\Component;

class Checker extends Component implements CallbackInterface {

	public $db;

	public $api_key;

	const LIST_PHISHING = 1;

	const LIST_MALWARE = 2;

	const LIST_UNWANTED = 3;

	const LIST_ACME_WHITE = 4;

	const TYPE_A = 1;

	const TYPE_S = 2;

	private static $lists = [
		self::LIST_PHISHING => 'googpub-phish-shavar',
		self::LIST_MALWARE => 'goog-malware-shavar',
		self::LIST_UNWANTED => 'goog-unwanted-shavar',
		self::LIST_ACME_WHITE => 'acme-white-shavar',
	];

	private $api;

	/**
	 * @var \yii\db\Connection
	 */
	private static $db_connect;

	public function run() {
		self::$db_connect = Yii::$app->{$this->db};
		$this->api = new GoogleAPI($this->api_key, $this);
		$a = $this->api->getList();
		var_dump(array(__LINE__,__CLASS__,$a));
	}

	public static function update() {
		foreach (self::$lists as $list_cid => $list_name) {
			//$require .= $this->formattedRequest($value);
		}
	}

	public static function check($url) {

		return $url ? true : false;
	}


	public function resetDatabase() {

	}

	public function getClientVersion() {
		return Yii::getVersion();
	}

	public function getClientName() {
		return Yii::$app->name;
	}
}