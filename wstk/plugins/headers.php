<?php

class HeadersPlugin extends WeAbstractPlugin {

	private $app;

	public static function info () {
		return array(
			'name'          => 'Headers', 
			'description'   => 'HTTP headers', 
			'version'       => '0.1', 
			'dependencies'  => array( )
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.header.headers', $this, 'onPageHeaderHeaders');
	}

	public function onPageHeaderHeaders ($hook, $page, $headers) {
		$root_page = $this->app->rootPage();

		foreach ($headers as $header => $value) {
			$root_page->addHeader($header, $value);
		}
	}



}

WeApplication::instance()->registerPlugin('headers', 'HeadersPlugin');
