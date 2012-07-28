<?php

/**
 * @author Adam Kiss
 * @version 0.0.1
 * @since 2012-06-28
 */

class Template {
	
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

			// prepare variables and include template
			foreach ($this->variables as $k=>$v){ $$k = $v;	}
			include ($this->view);

			// get the result and close the buffer
			$renderResult = ob_get_contents();
		ob_end_clean();

		// return string
		return $renderResult;
	}

}