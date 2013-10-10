<?php

define('WE_DATA_MERGE', 0);
define('WE_DATA_REPLACE', 1);

class WeData implements ArrayAccess {

	private $container = array();

	public function __construct ($data = array()) {
		$this->container = $data;
	}

	// ArrayAccess::offsetSet()
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->container[] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	}

	// ArrayAccess::offsetExists()
	public function offsetExists($offset) {
		return isset($this->container[$offset]);
	}

	// ArrayAccess::offsetUnset()
	public function offsetUnset($offset) {
		unset($this->container[$offset]);
	}

	// ArrayAccess::offsetGet()
	public function offsetGet($offset) {
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	}

	public function insert ($section, $data, $mode = WE_DATA_MERGE) {
		if ($mode == WE_DATA_REPLACE) {
			$this->container = array_replace_recursive($this->container, array($section => $data));

		} else {
			$this->container = array_merge_recursive($this->container, array($section => $data));
		}
	}

	public function append ($section, $data) {
		$current_branch = &$this->container;
		$tree = explode('.', $section);

		foreach ($tree as $branch) {
			if (!is_array($current_branch[$branch])) {
				$current_branch[$branch] = array();
			}
			$current_branch = &$current_branch[$branch];
		}

		$current_branch[] = $data;
	}

}

if (!function_exists('array_replace_recursive'))
{
  function array_replace_recursive($array, $array1)
  {
    function recurse($array, $array1)
    {
      foreach ($array1 as $key => $value)
      {
        // create new key in $array, if it is empty or not an array
        if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
        {
          $array[$key] = array();
        }

        // overwrite the value in the base array
        if (is_array($value))
        {
          $value = recurse($array[$key], $value);
        }
        $array[$key] = $value;
      }
      return $array;
    }

    // handle the arguments, merge one by one
    $args = func_get_args();
    $array = $args[0];
    if (!is_array($array))
    {
      return $array;
    }
    for ($i = 1; $i < count($args); $i++)
    {
      if (is_array($args[$i]))
      {
        $array = recurse($array, $args[$i]);
      }
    }
    return $array;
  }
}
