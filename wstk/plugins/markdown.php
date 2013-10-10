<?php

class MarkdownPlugin extends WeAbstractPlugin {

	private $app;

	public static function info () {
		return array(
			'name'          => 'Markdown', 
			'description'   => 'Markdown syntax parser with SmartyPants', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$conf = $app->config();
		require_once($conf['lib_dir'] .'/php-markdown-extra/markdown.php');
		require_once($conf['lib_dir'] .'/php-smartypants/smartypants.php');

		$app->hook('page.header.syntax', $this, 'onPageHeaderSyntax');
		$app->hook('page.parser.tag.markdown', $this, 'onMarkdownTag');
	}

	public function onMarkdownTag ($hook, $parser, $params, $content) {
		$content = Markdown($content);
		$content = SmartyPants($content);

		return $content;
	}

	public function onPageHeaderSyntax ($hook, $page, $syntax) {
		if (strtolower($syntax) == 'markdown') {
			$page->parser()->hook('page.parser.before_tags', $this, 'onParserBeforeBody');
		}
	}

	public function onParserBeforeBody ($hook, $parser) {
		$body = $parser->body();
		$body = Markdown($body);
		$body = SmartyPants($body);
		$parser->setBody($body);
	}

}

WeApplication::instance()->registerPlugin('markdown', 'MarkdownPlugin');
