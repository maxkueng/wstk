<?php

class WeRequest {

	private $query;
	private $uname;

	public function __construct ($query) {
		$this->query = $query;
		if (!$query) {
			$conf = WeApplication::instance()->config();
			$this->uname = $conf['default_page'];

		} else {
			$parts = explode('/', $query);
			$this->uname = $parts[0];
		}
	}

	public function query () {
		return $this->query;
	}

	public function uname () {
		return $this->uname;
	}

}
