<?php

class DataPlugin extends WeAbstractPlugin {

	private $app;
	private $data_pages = array();

	public static function info () {
		return array(
			'name'          => 'Data', 
			'description'   => 'Data importer', 
			'version'       => '0.1', 
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.header.data', $this, 'onPageHeaderData');
	}

	public function onPageHeaderData ($hook, $page, $data_pages) {
		$this->data_pages[$page->uname()] = $data_pages;
		$page->parser()->hook('page.parser.after_tags', $this, 'onParserAfterTags');

	}

	public function onParserAfterTags ($hook, $parser) {
		$page = $parser->page();

		foreach ($this->data_pages[$page->uname()] as $key => $data_page_uname) {
			$data_page = $this->app->page($data_page_uname);
			$data_page->parser()->parseBody(); // parse the body to resolve dynamic values
			$data_page->parse();
			$data_page_data = $data_page->data();
			$page->data()->insert('page', array($key => $data_page_data['page']));
		}
	}
}

WeApplication::instance()->registerPlugin('data', 'DataPlugin');
