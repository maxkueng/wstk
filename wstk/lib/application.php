<?php

class WeApplication implements IWeHookable, IWeObserver {

	private static $instance;
	private $storage;
	private $config;
	private $data;
	private $request;
	private $plugins = array();
	private $hooks = array();
	private $root_page;

	public function __construct ($conf) {
		$this->config = new WeConfig($conf);
		$this->data = new WeData();
		$this->storage = new WeStorage();
	}

	public function output () {
		$this->root_page->sendHeaders();
		echo $this->root_page->render();
	}

	public function instance ($conf = array()) {
		if (!isset(self::$instance)) {
			$c = __CLASS__;

			self::$instance = new $c($conf);
		}

		return self::$instance;
	}

	public function start () {
		$this->loadPlugins();

		$this->invoke('app.init', $this);

		$this->data->insert('app', array(
			'site_name' => $this->config['site_name'], 
			'encoding' => $this->config['character_encoding'], 
			'time' => time()
		));

		if (!empty($_SERVER['REDIRECT_QUERY_STRING'])) {
			$query = preg_replace('/^q\=/', '', $_SERVER['REDIRECT_QUERY_STRING']);
		} else {
			$query = $_GET['q'];
		}
		$this->request = new WeRequest($query);
		$page = $this->page($this->request->uname());

		if ($page == null) {
			$page = $this->page($this->config['error_404_page']);
		}

		$this->root_page = $page;
		$this->invoke('app.root_page_init', $this);

	}

	public function page ($uname) {
		$page = $this->storage()->page($uname);
		
		if ($page != null) {
			// transfer page hooks 
			foreach ($this->hooks as $hook => $callbacks) {
				if (preg_match('/^page\./', $hook)) {
					foreach ($callbacks as $callback) {
						$page->hook($hook, $callback[0], $callback[1]);
					}
				}
			}
		}

		return $page;
	}

	public function request () {
		return $this->request;
	}

	public function rootPage () {
		return $this->root_page;
	}

	public function storage () {
		return $this->storage;
	}

	public function config () {
		return $this->config;
	}

	public function data () {
		return $this->data;
	}

	private function loadPlugins () {
		$handle = opendir($this->config['plugins_dir']);
		while ($file = readdir($handle)) {
			if (!in_array($file, array( '.', '..' ))) {
				include_once($this->config['plugins_dir'] .'/'. $file);
			}
		}
	}

	public function registerPlugin ($plugin, $class_name) {
		$this->plugins[$plugin]['class'] = $class_name;
		$this->plugins[$plugin]['instance'] = new $class_name($this);
	}

	public function pluginInstance ($plugin) {
		if (isset($this->plugins[$plugin])) {
			return $this->plugins[$plugin]['instance'];
		}

		return null;
	}

	public function hook ($hook, IWeObserver $object, $callback) {
		$this->hooks[$hook][] = array($object, $callback);
	}

	public function invoke () {
		$argv = func_get_args();
		$hook = $argv[0];

		if (isset($this->hooks[$hook])) {
			$observers = $this->hooks[$hook];
			foreach ($observers as $callback) {
				call_user_func_array($callback, $argv);
			}
		}
	}

}
