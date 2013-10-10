<?php

class LinkPlugin extends WeAbstractPlugin {
	private $app;

	public static function info () {
		return array(
			'name'          => 'Link', 
			'description'   => 'Makes links', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.parser.tag.url', $this, 'onUrlTag');
	}

	public function onUrlTag ($hook, $parser, $params, $content) {
		if (!isset($params['uname'])) return '';

		$link = WeUtil::url($params);

		return $link;
	}

}

WeApplication::instance()->registerPlugin('link', 'LinkPlugin');
