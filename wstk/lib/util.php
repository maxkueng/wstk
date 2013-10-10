<?php

class WeUtil {

	public static function url ($params) {
		$reserved_params = array( 'uname', 'anchor' );

		$uname = $params['uname'];
		$anchor = $params['anchor'];
		$app = WeApplication::instance();
		$header = $app->storage()->pageHeaderRaw($uname);

		if (preg_match('/^route:\s+(.*)$/m', $header, $matches)) {
			$route = $matches[1];

			foreach ($params as $key => $value) {
				if (!in_array($key, $reserved_params)) {
					$route = str_replace(':'.$key, $value, $route);
				}
			}
		}

		$url =  '/'. $uname;

		if ($route) {
			$url .= '/'. $route;
		}

		if ($params['anchor']) {
			$url .= '#'. $params['anchor'];
		}

		return $url;
	}

}
