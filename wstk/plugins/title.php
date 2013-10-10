<?php

class TitlePlugin extends WeAbstractPlugin {

	private $title = array();

	public static function info () {
		return array(
			'name'          => 'Title', 
			'description'   => 'HTML page title', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$app->hook('page.header.title', $this, 'onPageHeaderTitle');
		$app->hook('page.parser.tag.title', $this, 'onTitleTag');
	}

	public function onPageHeaderTitle ($hook, $page, $title) {
		$this->title = array_merge($this->title, $title);
	}

	public function onTitleTag ($hook, $parser, $params, $content) {
		if (!isset($params['format'])) return '';

		$title = $params['format'];

		preg_match_all('/\[([^\]]+)\]/i', $params['format'], $matches,  PREG_SET_ORDER  );

		foreach ($matches as $match) {
			$tag = $match[0];
			$key = $match[1];
			$value = $this->title[$key];
			$title = str_replace($tag, $value, $title);
		}

		return $title;
	}

}

WeApplication::instance()->registerPlugin('title', 'TitlePlugin');
