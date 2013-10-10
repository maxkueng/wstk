<?php

class WeParser {

	private $page;
	private $headers = array();
	private $body;
	private $raw_body;
	private $hooks = array();
	private $tag_depth = array();
	private $tag_count = array();
	private $tags = array();
	private $body_parsed = false;

	public function __construct ($raw = '', $page = null) {
		$this->page = $page;

		preg_match(
			'/^
				(
					---\s*\n     # ---
					(.*)\n       # actual headers
					---\s*       # ---
					\n\n         # separator newlines
				)?               # headers are optional
				(.*)             # body
			$/xsm', $raw, $matches);

		$header = $matches[1];
		$this->raw_body = $matches[3];
		$this->body = $matches[3];
		$this->headers = Spyc::YAMLLoad($header);
	}

	public function page () {
		return $this->page;
	}

	public function headers () {
		return $this->headers;
	}

	public function body () {
		return $this->body;
	}

	public function setBody ($body) {
		$this->body = $body;
	}

	public function render () {
		$this->parseBody();
		return $this->body;
	}

	public function parseBody () {
		if ($this->body_parsed) return; 

		$this->invoke('page.parser.before_body', $this);
		$this->parseTags();
		$this->invoke('page.parser.after_body', $this);

		$this->body_parsed = true;
	}

	public function parseTags () {
		$this->body = preg_replace_callback('/
			\{\{
				(\#|\/)
				([a-z0-9_-]+)
				(
					\s
					([^}]+)

				)?
				(\/)?
			\}\}	
		/imUxs', array($this, 'tagCallback'), $this->body);

		// NOTE: Looping backwards to achieve inside-out parsing
		//       to properly handle nested tags

		$this->invoke('page.parser.before_tokenize', $this);
		$tag_tokens = array_keys($this->tags);
		for ($i = count($tag_tokens) -1; $i >= 0; $i--) {
			$tag = &$this->tags[$tag_tokens[$i]];
			$hash = $tag_tokens[$i];

			if (isset($tag['selfclosing'])) {
				$tag['ret'] = $this->invokeOne('page.parser.tag.'. $tag['selfclosing']['name'], $this, $tag['selfclosing']['params'], null);

			} else if (isset($tag['open']) && isset($tag['close'])) {
				preg_match('/open\:'. $hash .'(.+)close\:'. $hash .'/ims', $this->body, $matches);
				$content = trim($matches[1]);
				$tag['ret'] = $this->invokeOne('page.parser.tag.'. $tag['open']['name'], $this, $tag['open']['params'], $content);
			}
		}

		$this->invoke('page.parser.after_tokenize', $this);

		$this->invoke('page.parser.before_tags', $this);

		//for ($i = count($tag_tokens) -1; $i >= 0; $i--) {
		for ($i = 0; $i < count($tag_tokens); $i++) {
			$tag = &$this->tags[$tag_tokens[$i]];
			$hash = $tag_tokens[$i];

			if (isset($tag['selfclosing'])) {
				$this->body = preg_replace('/selfclosing\:'. $hash .'/', $tag['ret'], $this->body);

			} else if (isset($tag['open']) && isset($tag['close'])) {
				$this->body = preg_replace('/open\:'. $hash .'(.+)close\:'. $hash .'/ims', $tag['ret'], $this->body);
			}
		}

		$this->invoke('page.parser.after_tags', $this);
	}

	private function tagCallback ($matches) {
		if ($matches[1] == '#' && $matches[5] == '/') {
			$tag = array(
				'type'          => 'selfclosing',  
				'name'          => $matches[2], 
				'params_str'    => $matches[4], 
				'params'        => $this->tagParamsArray($matches[4])
			);

			$this->tag_depth[$tag['name']] += 1;
			$this->tag_count[$tag['name']] += 1;

			$tag['id'] = md5($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$tag['debug'] = ($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$this->tags[$tag['id']][$tag['type']] = $tag;

		} else if ($matches[1] == '#') {
			$tag = array(
				'type'          => 'open',  
				'name'          => $matches[2], 
				'params_str'    => $matches[4], 
				'params'        => $this->tagParamsArray($matches[4])
			);

			$this->tag_depth[$tag['name']] += 1;
			$this->tag_count[$tag['name']] += 1;

			$tag['id'] = md5($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$tag['debug'] = ($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$this->tags[$tag['id']][$tag['type']] = $tag;

		} else if ($matches[1] == '/') {
			$tag = array(
				'type'          => 'close', 
				'name'          => $matches[2], 
				'params_str'    => $matches[4], 
				'params'        => $this->tagParamsArray($matches[4])
			);

			$tag['id'] = md5($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$tag['debug'] = ($this->tag_count[$tag['name']] .'-'. $this->tag_depth[$tag['name']] .':'. $tag['name']);
			$this->tags[$tag['id']][$tag['type']] = $tag;

			$this->tag_depth[$tag['name']] -= 1;
		}

		return $tag['type'] .':'. $tag['id'];
	}

	private function tagParamsArray ($params_str) {
		preg_match_all('/([a-z0-9]+)="([^"]+)"/i', $params_str, $matches, PREG_SET_ORDER);

		$params = array();
		foreach ($matches as $match) {
			$params[$match[1]] = $match[2];
		}

		return $params;
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

	public function invokeOne () {
		$argv = func_get_args();
		$hook = $argv[0];

		if (isset($this->hooks[$hook])) {
			$callback = $this->hooks[$hook][0];
			return call_user_func_array($callback, $argv);
		}

		return null;
	}

}
