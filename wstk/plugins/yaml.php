<?php

class YamlPlugin extends WeAbstractPlugin {

	public static function info () {
		return array(
			'name'          => 'YAML', 
			'description'   => 'YAML content parser', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$app->hook('page.header.type', $this, 'onPageHeaderType');
	}

	public function onPageHeaderType ($hook, $page, $type) {
		if (strtolower($type) == 'yaml-data') {
			$data = $this->yaml($page->parser()->body());
			$page->data()->insert('page', $data);
		}
	}

	public function yaml ($yaml) {
		return Spyc::YAMLLoad($yaml);
	}
}

WeApplication::instance()->registerPlugin('yaml', 'YamlPlugin');
