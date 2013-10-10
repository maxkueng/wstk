<?php

class GeSHiPlugin extends WeAbstractPlugin {

	private $title = array();

	public static function info () {
		return array(
			'name'          => 'GeSHi', 
			'description'   => 'Generic Syntax Highlighter', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$conf = $app->config();
		require_once($conf['lib_dir'] .'/geshi/geshi.php');
		$app->hook('page.parser.tag.code', $this, 'onCodeTag');
	}

	public function onCodeTag ($hook, $parser, $params, $content) {
		if (!isset($params['language']) && !isset($params['l'])) return '';

		$lang = (isset($params['language'])) ? $params['language'] : $params['l'];
		$ts = (isset($params['ts'])) ? $params['ts'] : 4;
		$numbers = ($params['ln'] == 'on') ? true : false;

		$source = $content;
		$geshi = new GeSHi($source, $lang);
		$geshi->set_overall_class('code');
		$geshi->set_header_type(GESHI_HEADER_PRE_VALID);
		$geshi->set_tab_width($ts);
		if ($numbers) {
			$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
		}
		return $geshi->parse_code();
	}

}

WeApplication::instance()->registerPlugin('title', 'GeSHiPlugin');
