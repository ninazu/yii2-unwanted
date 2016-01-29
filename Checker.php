<?php

namespace ninazu\unwanted;

use ninazu\unwanted\models\CheckList;
use ninazu\unwanted\models\Chunk;
use ninazu\unwanted\provider\CallbackInterface;
use ninazu\unwanted\provider\GoogleAPI;
use Yii;
use yii\base\Component;

/**
 * @property-read \ninazu\unwanted\provider\GoogleAPI $api
 */
class Checker extends Component implements CallbackInterface {

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

	public function run() {
		$this->api = new GoogleAPI($this->api_key, $this);
		$a = $this->api->download();
		var_dump(array(__LINE__, __CLASS__, $a));
	}

	public static function update() {
		foreach (self::$lists as $list_cid => $list_name) {
			//$require .= $this->formattedRequest($value);
		}
	}

	public static function check($url) {

		return $url ? true : false;
	}

	public function getChunk($chunk_type_cid, $list_id) {
		$attributes = [
			'list_id' => $list_id,
			'chunk_type_cid' => $chunk_type_cid,
		];

		$lastChunk = Chunk::find()->where($attributes)->one();

		if (!$lastChunk) {
			$lastChunk = new Chunk($attributes);
			$lastChunk->start = 1;
			$lastChunk->finish = $this->api->getChunkSize();
			$lastChunk->save();
		}
		
		return [
			'start' => $lastChunk->finish + 1,
			'finish' => $lastChunk->finish + 1 + $this->api->getChunkSize(),
		];
	}

	public function syncLists($lists) {
		$activeList = CheckList::find()->indexBy('id')->all();
		$result = [];

		foreach ($activeList as $list) {
			$result[$list->id] = $list->list_name;
		}

		$needAdd = array_diff($lists, $result);
		$needRemove = array_diff($result, $lists);

		foreach ($needAdd as $listName) {
			$checkList = new CheckList(['list_name' => $listName]);
			$checkList->save();
			$result[$checkList->id] = $checkList->list_name;
		}

		foreach ($needRemove as $id => $checkList) {
			$activeList[$id]->delete();
			unset($result[$id]);
		}

		return $result;
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
