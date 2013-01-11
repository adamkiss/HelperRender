<?php

/**
 * @author Adam Kiss
 * @version 0.8.0
 * @since 2012-06-28
 */

class HRTemplate {

	////////////////////////////////// VARIABLES /////////////////////////////////////

	/**
	 * Array of data to be passed to view
	 */
	private
		$data = array();
	/**
	 * View path to be included
	 */
	private
		$view = null;

	///////////////////////////////////// CREATE //////////////////////////////////////////

	/**
	 * Constructor
	 *
	 * @param string $fileName   Name of the view file to be used
	 */
	public function __construct($fileName){
		$this->view = Wire::getFuel('config')->paths->views . $fileName . '.php';
	}

	/////////////////////////////// DATA MANIPULATION /////////////////////////////////////
	
	/**
	 * Adds single item to the array (to be used by public methods)
	 *
	 * @param $key string     key, under which the $value is saved
	 * @param $value mixed    piece of data to be saved
	 * @param $force boolean  whether to force the save (if data exists, rewrite)
	 * 
	 */
	private function addDataItem($key, $value, $force = false){
		if ( !array_key_exists($key, $this->data) || $force === true ) {
			$this->data[$key] = $value;
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Adds data to the array to be rendered
	 *
	 * @param $multiple boolean   whether $keyOrArray is single key, or array of k=>v pairs (false, true respectively)
	 * @param $keyOrArray string  name under which the data will be available in the view, or array of k=>v pairs
	 * @param $value mixed        value to be available in the view
	 * @return int                number of added k=>v pairs
	 */
	public function addData($multiple, $keyOrArray, $value = null){
		if ($multiple) {
			
			// multiple values in $keyOrArray
			$result = 0;
			foreach($keyOrArray as $key=>$value){
				$result += $this->addDataItem($key, $value);
			}
			return $result;

		} else {
			
			// single pair of key and value
			return $this->addDataItem($keyOrArray, $value);

		}
	}

	/**
	 * Adds data to the array to be rendered, but forces rewrite if the key is taken
	 *
	 * @param $multiple boolean   whether $keyOrArray is single key, or array of k=>v pairs (false, true respectively)
	 * @param $keyOrArray string  name under which the data will be available in the view, or array of k=>v pairs
	 * @param $value mixed        value to be available in the view
	 * @return int                number of added k=>v pairs
	 */
	public function forceAddData($id,$data){
		if ($multiple) {
			
			// multiple values in $keyOrArray
			$result = 0;
			foreach($keyOrArray as $key=>$value){
				$result += $this->addDataItem($key, $value, true);
			}
			return $result;

		} else {
			
			// single pair of key and value
			return $this->addDataItem($keyOrArray, $value, true);

		}
	}

	/**
	 * Removes all data (replaces actual $data array with empty one)
	 */
	public function removeAllData(){
		$this->data = array();
	}

	///////////////////////////////// RENDER METHOD /////////////////////////////////////

	/**
	 * Renders view with data and returns it as a string
	 * 
	 * @return string Contents of a view (with data supplied)
	 */
	function render(){
		//init
		$renderResult = '';
		ob_start();

			// add global data
			$this->data = array_merge(Render::globals(), $this->data);

			// prepare variables and include template
			foreach ($this->data as $k=>$v){ $$k = $v; }
			require ($this->view);

			// get the result and close the buffer
			$renderResult = ob_get_contents();
		ob_end_clean();

		// return string
		return $renderResult;
	}

}