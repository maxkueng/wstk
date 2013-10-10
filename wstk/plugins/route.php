<?php

class RoutePlugin extends WeAbstractPlugin {

	private $app;
	private $params = array();

	public static function info () {
		return array(
			'name'          => 'Route', 
			'description'   => 'Route', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.header.route', $this, 'onPageHeaderRoute');
	}

	public function onPageHeaderRoute ($hook, $page, $header) {
		preg_match_all('/:([[:alnum:]]+)/i', $header, $matches);
		$keys = $matches[1];
		$values = explode('/', $this->app->request()->query());

		foreach ($keys as $i => $key) {
			if (isset($values[$i + 1])) {
				$this->params[$key] = $values[$i + 1];
			} else {
				$this->params[$key] = null;
			}
		}

	}

	public function params () {
		return $this->params;
	}
}

WeApplication::instance()->registerPlugin('route', 'RoutePlugin');
