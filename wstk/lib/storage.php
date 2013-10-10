<?php

class WeStorage {

	public function __construct () {
	}

	public function page ($uname) {
		if (!isset($uname)) return null;
		
		$app = WeApplication::instance();
		$config = $app->config();
		$path = $config['pages_dir'] .'/'. $uname;

		if (!file_exists($path)) return null;

		$contents = file_get_contents($path);
		$page = new WePage($uname, $contents);
		$page->setModificationTime(filemtime($path));

		return $page;
	}

	public function pageHeaderRaw ($uname) {
		if (!isset($uname)) return null;
		
		$app = WeApplication::instance();
		$config = $app->config();
		$path = $config['pages_dir'] .'/'. $uname;

		if (!file_exists($path)) return null;

		$header_delimiter = '---';
		$delimiter_count = 0;

		$header_content = '';
		$handle = fopen($path, 'r');
		if ($handle) {
			while (($buffer = fgets($handle)) !== false) {
				if (trim($buffer) == $header_delimiter) {
					$delimiter_count++;

					if ($delimiter_count == 2) {
						break;
					}
				} else {
					$header_content .= $buffer;
				}
			}
		}
		fclose($handle);

		return $header_content;
	}

}
