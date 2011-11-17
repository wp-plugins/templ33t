<?php

class Templ33tPluginHandler {

	static $loaded = array();

	function __construct() {



	}

	static function classify($pname = null) {

		return !empty($pname) ? (strpos($pname, 'Templ33t') !== 0 ? 'Templ33t'.ucwords($pname) : $pname) : false;

	}

	static function load($cname = null) {

		if(!empty($cname)) {

			if(!array_key_exists($cname, self::$loaded)) {

				$class = self::classify($cname);
				$cpath = realpath(dirname(__FILE__)) . '/' . $cname . '/templ33t_' . $cname . '.php';
				require_once($cpath);

				self::$loaded[$cname] = $class;

				return $class;

			} else {

				return self::$loaded[$cname];

			}

		} else {

			return false;

		}

	}

	static function instantiate($cname = null, $config = null) {

		if(!empty($cname) && $class = self::load($cname)) {

			$n = new $class();
			if(!empty($config)) $n->configure($config);

			return $n;

		} else {

			return false;

		}

	}

}

?>
