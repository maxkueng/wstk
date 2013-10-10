<?php

class WePage implements IWeHookable {

	private $uname;
	private $parser;
	private $modification_time;
	private $data;
	private $headers = array();
	private $hooks = array();
	private $parsed = false;

	public function __construct ($uname, $raw = null) {
		$this->uname = $uname;
		$this->data = new WeData();
		$this->parser = new WeParser($raw, $this);
		$this->data()->insert('page', array('uname' => $uname));
	}

	public function __toString () {
		return $this->render();
	}

	public function uname () {
		return $this->uname;
	}

	public function parser () {
		return $this->parser;
	}

	public function data () {
		return $this->data;
	}

	public function modificationTime () {
		return $this->modification_time;
	}

	public function setModificationTime ($time) {
		$this->data->insert('page', array( 'modification_time' => $time	));	
		$this->modification_time = $time;
	}

	public function sendHeaders () {
		foreach ($this->headers as $header => $value) {
			header($header .': '. $value);
		}
	}

	public function addHeader ($header, $value) {
		$this->headers[$header] = $value;
	}

	public function parse () {
		if ($this->parsed) return;

		$this->data()->insert('page', $this->parser->headers());

		foreach ($this->parser->headers() as $header => $value) {
			$this->invoke('page.header', $this, $header, $value);
			$this->invoke('page.header.'. strtolower($header), $this, $value);
		}

		$this->parser->parseBody();
		$this->parsed = true;
	}

	public function render () {
		$this->parse();
		return $this->parser->render();
	}

	public function hook ($hook, IWeObserver $object, $callback) {
		if (preg_match('/^page\.parser\./', $hook)) {
			// transfer hook to parser
			$this->parser->hook($hook, $object, $callback);

		} else {
			$this->hooks[$hook][] = array($object, $callback);
		}
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
