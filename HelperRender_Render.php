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

	///////////////////////////////// PAGE EXTRACTION ////////////////////////////////

	/**
	 * Extracts the fields (and custom values assigned via getChanges()) from $page and returns
	 * as an array prepared for inclusion in the render functions
	 *
	 * @param Page $page        page to have data extracted from
	 * @return array            array of values in the page
	 */
	private static function extractValues(Page $page){
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

	public static function page(){
		$page = Wire::getFuel('page');
		return self::partial($page->template, self::extractValues($page));
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
		foreach($collection as $collectionItem) { $collectionArray []= array('item'=>$collectionItem); }
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
		self::$masterTemplate->addData(true, $data);
		return self::$masterTemplate->render();
	}

	/**
	 * Auto-renders master with $page fields in the $content
	 *
	 * @param array $additionalData     optional data (good to use if you want 'auto' way, but need more data)
	 */
	public static function auto($additionalData = array()){
		self::init();
		$autoMasterData = array_merge(array('content'=>Render::page()), $additionalData);
		echo self::master($autoMasterData);
	}
}