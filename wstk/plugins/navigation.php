<?php

class NavigationPlugin extends WeAbstractPlugin {

	private $app;
	private $navigations = array();

	public static function info () {
		return array(
			'name'          => 'Navigation', 
			'description'   => 'Handles navigations', 
			'version'       => '0.1', 
			'dependencies'  => array( 'liquid', 'yaml' )
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$app->hook('page.header.navigation', $this, 'onHeaderNavigation');
		$app->hook('page.parser.tag.navigation', $this, 'onNavigationTag');
	}

	public function onHeaderNavigation ($hook, $page, $navigation) {
		$key = key($navigation);
		$this->navigations[$key] = $navigation[$key];
	}

	public function onNavigationTag ($hook, $parser, $params, $content) {
		if (!($name = $params['name'])) return;
		if (!($template = $params['template'])) return;

		$data_uname = (isset($params['data'])) ? $params['data'] : null;

		$template_page = $this->app->page($template);

		$liquid = new LiquidTemplate();

		$app_data = $this->app->data();
		$page_data = $this->app->rootPage()->data();

		if ($data_uname) {
			$navdata_page = $this->app->page($data_uname);
			$navdata_page->render(); // run the parser
			$navdata_data = $navdata_page->data();
			$nav_name = $navdata_data['page']['name'];
			$nd = $navdata_data['page']['items'];

		} else if ($content) {
			if ($yaml = $this->app->pluginInstance('yaml')) {
				$navdata_data = $yaml->yaml($content);
				$nav_name = $navdata_data['name'];
				$nd = $navdata_data['items'];
			} else {
				$nav_name = '';
				$nd = array();
			}
		}

		for ($i = 0; $i < count($nd); $i++) {
			$nd[$i]['url'] = WeUtil::url($nd[$i]['link']);
			if ($nd[$i]['id'] == $this->navigations[$nav_name]) {
				$nd[$i]['selected'] = true;
			}
		}
		//$navdata_data->insert('page', array('items' => $nd), WE_DATA_REPLACE);
		
		$data = array_merge_recursive(
			array( 'app' => $app_data['app'] ), 
			array( 'page' => $page_data['page'] ), 
			array( 'navigation' => array(
				'name' => $nav_name, 
				'items' => $nd
			))
		);

		$liquid->parse($template_page->render());
		return $liquid->render($data);
	}
}

WeApplication::instance()->registerPlugin('navigation', 'NavigationPlugin');
