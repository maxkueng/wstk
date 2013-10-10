<?php

class LiquidPlugin extends WeAbstractPlugin {

	private $app;
	private $layouts = array();
	private $sections = array();

	public static function info () {
		return array(
			'name'          => 'Liquid', 
			'description'   => 'Liquid template engine for PHP', 
			'version'       => 'svn', 
			'uri'           => 'http://code.google.com/p/php-liquid/', 
			'license'       => 'MIT License'
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$conf = $app->config();
		require_once($conf['lib_dir'] .'/php-liquid/liquid.php');

		$app->hook('page.header.layout', $this, 'onPageHeaderLayout');
		$app->hook('page.header.type', $this, 'onPageHeaderType');
		$app->hook('page.parser.tag.section', $this, 'onSectionTag');
	}

	public function onSectionTag ($hook, $parser, $params, $content) {
		if (!($name = $params['name'])) return;
		$scope = $params['scope'];

		if (preg_match('/^([a-zA-Z0-9_-]+)(\[\])?$/', $name, $matches)) {
			$name = $matches[1];
			$array_suffix = $matches[2];
			$uname = $parser->page()->uname();
			$scope = ($scope == 'global') ? 'we_global' : $uname;
			$prefix = '';
			$suffix = '';

			if (!array_key_exists($scope, $this->sections)) {
				$this->sections[$scope] = array();
			}

			if ($array_suffix == '[]') {
				if (!is_array($this->sections[$scope][$name])) {
					$this->sections[$scope][$name] = array();
				}

				$this->sections[$scope][$name][] = array(
					'name' => $name, 
				);

				$prefix = '%%section:'. $scope .':'. $name .':'. count($this->sections[$scope][$name]) .'%%';
				$suffix = '%%endsection:'. $scope .':'. $name .':'. count($this->sections[$scope][$name]) .'%%';

				$this->sections[$scope][$name][count($this->sections[$scope][$name]) -1] = array(
					'prefix' => $prefix, 
					'suffix' => $suffix
				);

			} else {
				$prefix = '%%section:'. $scope .':'. $name .'%%';
				$suffix = '%%endsection:'. $scope .':'. $name .'%%';

				$this->sections[$scope][$name][] = array(
					'name' => $name, 
					'prefix' => $prefix, 
					'suffix' => $suffix
				);
			}
		}

		return $prefix ."\n". $content . $suffix ."\n";
	}

	public function onPageHeaderLayout ($hook, $page, $layout) {
		$this->layouts[$page->uname()] = $layout; 

		$page->parser()->hook('page.parser.after_body', $this, 'applyLayout');
	}

	public function onPageHeaderType ($hook, $page, $type) {
		if (strtolower($type) != 'liquid-content') return;

		$liquid = new LiquidTemplate();
		$page->parser()->parseBody();

		$app_data = $this->app->data();
		$page_data = $page->data();
		$rootpage_data = $this->app->rootPage()->data();

		$data = array_merge_recursive(
			array( 'app' => $app_data['app'] ), 
			array( 'this' => $page_data['page'] ), 
			array( 'page' => $rootpage_data['page'] )
		);

		$liquid->parse($page->parser()->body());
		$page->parser()->setBody($liquid->render($data));
	}

	public function applyLayout ($hook, $parser) {
		$page = $parser->page();
		$uname = $page->uname();
		$layout = $this->layouts[$uname];
		$liquid = new LiquidTemplate();
		$template = $this->app->page($layout);

		$page_content = $parser->body();
		$page_sections = $this->sections($uname);

		foreach ($page_sections as $sections_name => &$sections) {
			if (is_array($sections)) {
				foreach ($sections as $i => &$section) {
					preg_match('/^%%section:(.+):/iU', $section['prefix'], $matches);
					$section_scope = $matches[1];

					if (preg_match('/'. $section['prefix'] .'(.+)'. $section['suffix'] .'/ms', $page_content, $matches)) {
						$section_content = $matches[1];
						$page_content = preg_replace('/'. $section['prefix'] .'(.+)'. $section['suffix'] .'/ms', '', $page_content);

						$section['content'] = $section_content;
						$this->sections[$section_scope][$sections_name][$i]['content'] = $section_content;
					}
				}

			} else {
				preg_match('/^%%section:(.+):/iU', $section['prefix'], $matches);
				$section_scope = $matches[1];

				$section = $sections;
				if (preg_match('/'. $section['prefix'] .'(.+)'. $section['suffix'] .'/ms', $page_content, $matches)) {
					$section_content = $matches[1];
					$page_content = preg_replace('/'. $section['prefix'] .'(.+)'. $section['suffix'] .'/ms', '', $page_content);

					$section['content'] = $section_content;
					$this->sections[$section_scope][$sections_name]['content'] = $section_content;
				}
			}

		}

		//echo "---\n$uname\n";
		//echo $page_content;
		//print_r($page_sections);

		//print_r($this->sections);

		$app_data = $this->app->data();
		$rootpage_data = $this->app->rootPage()->data();
		$page_data = $page->data();
		$data = array_merge_recursive(
			array( 'app' => $app_data['app'] ), 
			array( 'page' => $rootpage_data['page'] ), 
			array( 'this' => $page_data['page'] ), 
			array( 'sections' => $this->sections($uname) ), 
			array( 'content' => $page_content )
		);
		//echo "###".$template->uname()."\n";
		//print_r($data);

		$liquid->parse($template->render());
		$parser->setBody($liquid->render($data));
	}

	public function sections ($uname) {
		//echo "---\n$uname\n";
		//print_r($this->sections);
		$sections = array();
		if (is_array($this->sections[$uname])) {
			$scope_sections = $this->sections[$uname];
			foreach ($scope_sections as $section_name => $section) {
				$sections[$section_name] = $section;
			}
		}

		if (is_array($this->sections['we_global'])) {
			$scope_sections = $this->sections['we_global'];
			foreach ($scope_sections as $section_name => $section) {
				$sections[$section_name] = $section;
			}
		}

		return $sections;
	}

}

WeApplication::instance()->registerPlugin('liquid', 'LiquidPlugin');
