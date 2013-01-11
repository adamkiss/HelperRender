<?php

/**
 * @author Adam Kiss
 * @version 0.8.0
 * @since 2012-06-28
 */

class Render {

	/////////////////////////////////// VARIABLES /////////////////////////////////////

	/**
	 * Default view to be used (if no other master is used)
	 */
	private static $default_view = '_master/application';	
	/**
	 * Master Template object
	 */
	private static $masterTemplate = null;
	/**
	 * Master variables (to be included in master call (even before it ))
	 */
	private static $render_master_variables = array();
	/**
	 * Global variables (to be included in each render:: call throughout request)
	 */
	private static $render_globals = array();

	////////////////////////////////// SINGLETON INIT /////////////////////////////////

	/**
	 * Init function – initializes master template object, based on optional parameter
	 *
	 * @param string $view   optional view to be used as 'master'
	 */
	public static function init($view = false){
		// create singe master template
		self::$masterTemplate = new HRTemplate($view ? $view : self::$default_view);
	}

	//////////////////////////////// MASTER VARIABLES ////////////////////////////////
	
	/**
	 * Adds single item to the global variables array
	 *
	 * @param string $key      key, under which the $value is saved
	 * @param mixed $value     piece of data to be saved
	 * 
	 */
	private static function add_master_variable($key, $value){
		if (!array_key_exists($key, self::$render_master_variables)) {
			self::$render_master_variables[$key] = $value;
		} else {
			trigger_error('Render::add_master_variable: You can\'t overwrite existing global key',E_WARNING);
		}
	}

	/**
	 * Adds variables to array of global values (issues warning if it exists already)
	 *
	 * @param string|array $var   if string, it acts as a key in globals, array
	 */
	public static function add_master($var, $value=null){
		// passed: array of values to add
		if (is_array($var)){
			foreach($var as $key=>$value){
				self::add_master_variable($key, $value);
			}
			// give programmer notice of incorrect usage
			if (!is_null($value)){ trigger_error('Render::add_master: $value not null, while $var is array. Incorrect data?', E_NOTICE); }

		// passed: key/value combo
		}else if (is_string($var)&&!is_null($value)){
			self::add_master_variable($var, $value);

		// anything else
		}else{
			throw new WireException('Incorrect data passed to Render::add_master(string|array $var, mixed $value)');
		}
	}

	/**
	 * Returns global variables' array
	 *
	 * @return array Render::$render_global
	 */
	public static function master_variables(){
		return self::$render_master_variables;
	}

	//////////////////////////////// GLOBAL VARIABLES ////////////////////////////////
	
	/**
	 * Adds single item to the global variables array
	 *
	 * @param string $key      key, under which the $value is saved
	 * @param mixed $value     piece of data to be saved
	 * 
	 */
	private static function add_global_variable($key, $value){
		if (!array_key_exists($key, self::$render_globals)) {
			self::$render_globals[$key] = $value;
		} else {
			trigger_error('Render::add_global: You can\'t overwrite existing global key',E_WARNING);
		}
	}

	/**
	 * Adds variables to array of global values (issues warning if it exists already)
	 *
	 * @param string|array $var   if string, it acts as a key in globals, array
	 */
	public static function add_global($var, $value=null){
		// passed: array of values to add
		if (is_array($var)){
			foreach($var as $key=>$value){
				self::add_global_variable($key, $value);
			}
			// give programmer notice of incorrect usage
			if (!is_null($value)){ trigger_error('Render::add_global: $value not null, while $var is array. Incorrect data?', E_NOTICE); }

		// passed: key/value combo
		}else if (is_string($var)&&!is_null($value)){
			self::add_global_variable($var, $value);

		// anything else
		}else{
			throw new WireException('Incorrect data passed to Render::add_global(string|array $var, mixed $value)');
		}
	}

	/**
	 * Returns global variables' array
	 *
	 * @return array Render::$render_global
	 */
	public static function globals(){
		return self::$render_globals;
	}

	///////////////////////////////// PAGE EXTRACTION ////////////////////////////////

	/**
	 * Extracts the fields (and custom values assigned via getChanges()) from $page and returns
	 * as an array prepared for inclusion in the render functions
	 *
	 * @param Page $page        page to have data extracted from
	 * @return array            array of values in the page
	 */
	public static function extractValues(Page $page){
		$data = array();

		// first: get fields
		foreach($page->template->fields as $field){
			$data[$field->name] = $page->get($field->name);
			if(
				$field->type instanceof FieldtypeDatetime
			||$field->type instanceof FieldtypePageTitle
			||$field->type instanceof FieldtypeText
			||$field->type instanceof FieldtypeTextarea
			){
				$data[$field->name.'_UF'] = $page->getUnformatted($field->name);
			}
		}

		// second: get 'data' and filter custom values
		foreach ($page->getChanges() as $key){
			if(!array_key_exists($key, $data)) {
				$data[$key] = $page->get($key);
			}
		}

		return $data;
	}

	/////////////////////////////// TEMPLATE RENDERING ///////////////////////////////

	/**
	 * Renders template with data, returns it as a string
	 *
	 * @param string $fileName   file name of template
	 * @param string $data       data to be used to render the partial
	 * @return string            the template content (with variables replaced)
	 */
	public static function partial($fileName, $data = array()){
		$partial = new HRTemplate($fileName);
		$partial->addData(true, $data);
		return $partial->render();
	}

	public static function page($additional_data = array()){
		$page = Wire::getFuel('page');
		return self::partial($page->template,
			array_merge(self::extractValues($page), $additional_data)
		);
	}

	/**
	 * Renders looped template with data and optional separator file, returns it as a string
	 *
	 * @param string $fileName               file name of template
	 * @param string $data                   data to be used to render the partial
	 * @param string $separatorFileName      data to be used to render the partial
	 * @return string                        the template content (with variables replaced)
	 */
	public static function loop($fileName, $data = array(), $separatorFileName = false){

		$loopItem = new HRTemplate($fileName);
		$loopSeparator = ($separatorFileName) ? new HRTemplate($separatorFileName) : false;
		$result = array(); $count = 0; $itemsCount = count($data);

		foreach ($data as $item){
			
			$loopItem->removeAllData();
			$loopItem->addData(true, $item);
			$loopItem->addData(false, 'HM_Count', ++$count);
			$result []= $loopItem->render();

			if($loopSeparator && $count < $itemsCount) {
				$loopSeparator->addData(false, 'HM_Count', $count);
				$result []= $loopSeparator->render();
			}

		}

		return implode('', $result);
	}

	public static function collection($fileName, $collection, $separatorFileName = false){
		$collectionArray = array();
		foreach($collection as $collectionItem) {
			$collectionArray []= array('item'=>$collectionItem);
		}
		return self::loop($fileName, $collectionArray, $separatorFileName);
	}

	///////////////////////////////////// OUTPUT /////////////////////////////////////

	/**
	 * Renders the master and returns it as a string
	 *
	 * @param array $data data to be pushed into the master
	 * @return contents of the master template
	 */
	public static function master( $data = array() ) {
		//make init option: if file wasn't initialized, initialize it for developer
		if (!self::$masterTemplate) { self::init(); }

		self::$masterTemplate->addData(true, $data);
		self::$masterTemplate->addData(true, self::$render_master_variables);
		return self::$masterTemplate->render();
	}

	/**
	 * Auto-renders master with $page fields in the $content, with possible additional data
	 *
	 * @param array $additionalData     optional data (good to use if you want 'auto' way, but need more data)
	 */
	public static function auto($additionalData = array()){
		self::init();
		echo self::master(array(
			'content'=>Render::page($additionalData)
		));
	}
}