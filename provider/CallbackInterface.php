<?php

namespace ninazu\unwanted\provider;

interface CallbackInterface {

	public function syncLists($lists);

	public function getChunk($chunk_type_cid, $list_id);

	public function resetDatabase();

	public function getClientVersion();

	public function getClientName();
}
