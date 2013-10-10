<?php

class IncludePlugin extends WeAbstractPlugin {

	private $app;

	public static function info () {
		return array(
			'name'          => 'Include', 
			'description'   => 'Includes other pages', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.parser.tag.include', $this, 'onIncludeTag');
	}

	public function onIncludeTag ($hook, $parser, $params, $content) {
		if (!($uname = $params['uname'])) return null;

		if ($include = $this->app->page($uname)) {
			$data = array(
				'args' => $params	
			);
			$include->data()->insert('page', $data);

			return $include->render();
		}
		
		return '';
	}

}

WeApplication::instance()->registerPlugin('include', 'IncludePlugin');
