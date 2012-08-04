<?php

/**
 * @author Adam Kiss
 * @version 0.0.1
 * @since 2012-06-28
 */

class Render {

	/////////////////////////////////// VARIABLES /////////////////////////////////////

	/**
	 * Default view to be used (if no other master is used)
	 */
	private static $default_view = 'views/_masters/default';
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
		self::$masterTemplate = new HRTemplate($view ? $view : $default_view);
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

	/**
	 * Renders 'auto' template – file is 'views/{ProcessWire template}.php' and only variable populated is $page
	 *
	 * @return string            the template content (with variables replaced)
	 */
	public static function auto(){
		// populate '$page'
		$page = Wire::getFuel('page');

		// return ::partial populated with correct values
		return self::partial($page->template, array('page'=>$page));
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
	 * @return contents of the master template
	 */
	public static function master() {
		return self::$masterTemplate->render();
	}

}