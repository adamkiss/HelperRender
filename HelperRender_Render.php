<?php

/**
 * @author Adam Kiss
 * @version 0.0.1
 * @since 2012-06-28
 */

class Render {

	/////////////////////////////////// VARIABLES /////////////////////////////////////

	private static $default_view = 'masters/default';
	private static $masterTemplate = null;

	////////////////////////////////// SINGLETON INIT /////////////////////////////////

	public static function init($view = false){
		// create singe master template
		self::$masterTemplate = new HRTemplate($view ? $view : $default_view);
	}

	/////////////////////////////// TEMPLATE RENDERING ///////////////////////////////

	public static function partial($fileName, $data = array()){
		$partial = new HRTemplate($fileName);
		$partial->addData(true, $data);
		return $partial->render();
	}

	/**
	 * Renders template with set of items
	 *
	 * @return contents of the master template
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