<?php
/**
 * Liquid for PHP
 * 
 * @package Liquid
 * @copyright Copyright (c) 2006 Mateo Murphy, 
 * based on Liquid for Ruby (c) 2006 Tobias Luetke
 * @license http://www.opensource.org/licenses/mit-license.php
 *
 */

/*
 * Non liquid specific support classes and functions
 * 
 */

/**
 * A support class for regular expressions
 *
 * @package Liquid
 */
class LiquidRegexp {
	
	/**
	 * The regexp pattern
	 *
	 * @var string
	 */
	var $pattern;

	
	/**
	 * The matches from the last method called
	 *
	 * @var array;
	 */
	var $matches;
	
	/**
	 * Constructor
	 *
	 * @param string $pattern
	 * @return Regexp
	 */
	function LiquidRegexp($pattern) {
		
		if (substr($pattern, '0', 1) != '/') {
			$pattern = '/'.$this->quote($pattern).'/';
			
		}
		
		$this->pattern = $pattern;	
		
	}
	
	/**
	 * Quotes regular expression characters
	 *
	 * @param string $string
	 * @return string
	 */
	function quote($string) {
		return preg_quote($string, '/');
		
	}
	
	/**
	 * Returns an array of matches for the string in the same way as Ruby's scan method
	 *
	 * @param string $string
	 * @return array
	 */
	function scan($string) {
		
		$result = preg_match_all($this->pattern, $string, $matches);

		if (count($matches) == 1) {
			return $matches[0];
		}
		
		array_shift($matches);
		
		$result = array();
		
		foreach($matches as $match_key => $sub_matches) {
			foreach($sub_matches as $sub_match_key => $sub_match) {
				$result[$sub_match_key][$match_key] = $sub_match;
				
			}
		}
		
		return $result;
		
	}
	
	/**
	 * Matches the given string. Only matches once.
	 *
	 * @param string $string
	 * @return int 1 if there was a match, 0 if there wasn't
	 */
	function match($string) {
		return preg_match($this->pattern, $string, $this->matches);		
	}
	
	/**
	 * Matches the given string. Matches all.
	 *
	 * @param string $string
	 * @return int The number of matches
	 */
	function match_all($string) {
		return preg_match_all($this->pattern, $string, $this->matches);
		
	}
	
	/**
	 * Splits the given string
	 *
	 * @param string $string
	 * @param int $limit Limits the amount of results returned
	 * @return array
	 */
	function split($string, $limit = null) {
		return preg_split($this->pattern, $string, $limit);
	}
}

if (!function_exists('array_flatten')) {

	/**
	 * Flatten a multidimensional array into a single array. Does not maintain keys.
	 *
	 * @param array $array
	 * @return array 
	 */
	function array_flatten($array) {
		
		$return = array();
		
		foreach ($array as $element) {
			if (is_array($element)) {
				$return = array_merge($return, array_flatten($element));	
			} else {
				$return[] = $element;	
			}
		}
		
		return $return;	
		
	}

}

if (!function_exists('property_exists')) {
	
	/**
	 * Checks if the object or class has a property 
	 *
	 * property_exists() returns TRUE even if the property has the value NULL. 
	 * 
	 * @param mixed $class
	 * @param string $property
	 */
	function property_exists($class, $property) {
 		if (is_object($class)) {
 			$vars = get_object_vars($class);
 			
 		} else {
 			$vars = get_class_vars($class);
 		}
		
 		return array_key_exists($property, $vars);
 		
	}
	
	
}

?>