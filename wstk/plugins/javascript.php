<?php

class JavaScriptPlugin extends WeAbstractPlugin {

	private $app;

	public static function info () {
		return array(
			'name'          => 'JavaScript', 
			'description'   => 'Include JavaScript', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.parser.tag.javascript', $this, 'onJavaScriptTag');
	}

	public function onJavaScriptTag ($hook, $parser, $params, $content = '') {
		$src = (isset($params['src'])) ? $params['src'] : null;
		$position = (isset($params['position'])) ? $params['position'] : 'inline';

		if (!in_array($position, array('head', 'eod', 'inline'))) return;

		if ($src) {
			$this->addExternal($position, $src);

		} else if ($content) {
			if ($position == 'inline') {
				return '
<script type="text/javascript">
/* <![CDATA[ */
'. $content .'
/* ]] */
</script>
';
			} else {
				$this->addCode($position, $content);
			}
		}

		return;
	}

	public function addCode ($position, $code) {
		$root_page = $this->app->rootPage();
		$root_page->data()->append('page.javascript.'. $position , array(
			'type' => 'code', 
			'code' => $code
		));

	} 

	public function addExternal ($position, $src) {
		$root_page = $this->app->rootPage();
		$root_page->data()->append('page.javascript.'. $position , array(
			'type' => 'external', 
			'src' => $src
		));
	}

}

WeApplication::instance()->registerPlugin('javascript', 'JavaScriptPlugin');
