<?php

/**
 * @author Adam Kiss
 * @version 0.0.1
 * @since 2012-06-28
 */

public class Master {

	/////////////////////////////////// VARIABLES /////////////////////////////////////

	private static $default_view = 'masters/default';
	private static $masterTemplate = null;


	///////////////////////////////// SINGLETON INIT /////////////////////////////////////

	public static function init($view = false){
		// create singe master template
		self::$masterTemplate = new HMTemplate(if $view ? $view : $default_view);
	}

	///////////////////////////////////// OUTPUT /////////////////////////////////////

	/**
	 * Renders the master and returns it as a string
	 *
	 * @return contents of the master template
	 */
	public static function output() {
		return self::$masterTemplate->render();
	}

}