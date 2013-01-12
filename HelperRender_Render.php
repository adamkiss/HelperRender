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
  private static $default_view = 'application';
  /**
   * View as selected by user (if one is given, null otherwise)
   */
  private static $user_selected_view = null;
  /**
   * Master Template object
   */
  private static $master_template = null;

  ////////////////////////////////// SINGLETON INIT /////////////////////////////////

  /**
   * Init handling function; if master template isn't created, it creates one
   */
  private static function init_master_on_demand(){
    if (is_null(self::$master_template))
      self::init_master();
  }

  ///////////////////////////////// FILENAME HELPER /////////////////////////////////

  /**
   * Translates slug into full path
   *
   * @param string $slug file name or short path
   * @param string $ext  extension of the file (default php, otherwise unused)
   * @return string
   */
  private static function get_path_from_slug($slug, $ext='php'){
    return wire('config')->paths->views.$slug.'.'.$ext;
  }

  /**
   * Switches underscore in $slug on/off
   *
   * @param boolean $underscore_first Test case with underscore first (partials)
   * @param string $slug              Slug to test
   * @return string
   */
  private static function switch_slug_underscore($slug){
    $last_slash = strrpos($slug, '/');
    if ($last_slash !== false){
      $slug_path = substr($slug, 0, $last_slash+1);
      $slug_file = substr($slug, $last_slash+1);
    }else{
      $slug_path = '';
      $slug_file = $slug;
    }

    $slug_file = ($slug_file[0] === '_') ? substr($slug_file, 1) : '_'.$slug_file;

    return $slug_path.$slug_file;
  }

  /**
   * Tests slugs both with/out underscored slugs (partial/one & partial/_one)
   * If one is found, it is returned. If not, original slug is returned
   *
   * @param boolean $underscore_first Test case with underscore first (partials)
   * @param string $slug              Slug to test
   * @return string
   */
  public static function test_slug_with_underscore($slug, $underscore_first=false){
    $original_slug = $underscore_first ?
      self::switch_slug_underscore($slug) : $slug;
    $slug_to_return = false;

    $first_test = self::get_path_from_slug($original_slug);
    if (file_exists($first_test))
      return $original_slug;

    $new_slug = self::switch_slug_underscore($original_slug);
    $second_test = self::get_path_from_slug($new_slug);

    return file_exists($second_test) ? $new_slug : $original_slug;
  }

  /**
   * Finds (and returns) path to correct file for slug
   *
   * @param string $slug file name or short path
   * @return string
   */
  public static function get_file($slug, $underscore_first=false){
    return self::get_path_from_slug(
      self::test_slug_with_underscore($slug, $underscore_first)
    );
  }


  ////////////////////////// MASTER TEMPLATE MANIPULATION ///////////////////////////

  /**
   * Initializes master template object
   */
  private static function init_master(){
    // create single master template
    self::$master_template = new TemplateFile(
      is_null(self::$view) ?
        self::$view :
        self::$default_view
    );
  }

  public static function set_master($filename){
    self::init_master_on_demand();

    self::$master_template->setFilename(self::get_file($filename));
  }

  /**
   * WireData->set wrapper for master variables
   *
   * @param string $key      key, under which the $value is saved
   *                         if it's "data", $value is to be understood as collection
   * @param mixed $value     piece of data to be saved
   */
  public static function add_to_master($key, $value){
    self::init_master_on_demand();

    self::$master_template->set($key, $value);
  }

  //////////////////////////////// GLOBAL VARIABLES ////////////////////////////////

  /**
   * Adds variables to array of global values (issues warning if it exists already)
   *
   * @param string $var     key, if 'data', $value is parsed as array of k/v pairs
   * @param mixed $value    value (or possibly array of k/v pairs)
   */
  public static function add_to_global($var, $value){
    TemplateFile::setGlobal($key, $value, false);
  }

  ///////////////////////////////// PAGE EXTRACTION ////////////////////////////////

  /**
   * Extracts the fields (and custom values assigned via getChanges()) from $page and returns
   * as an array prepared for inclusion in the render functions
   *
   * @param Page $page        page to have data extracted from
   * @return array            array of values in the page
   */
  public static function extract_values(Page $page){
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
   * @param string $filename   file name of template
   * @param string $data       data to be used to render the partial
   * @return string            the template content (with variables replaced)
   */
  public static function partial($filename, $data=array(), $underscore_first=true){
    //hack: associative array check. It
    if (is_array($data)){
      $array_keys_count = count(array_filter(array_keys($data), 'is_string'));
    }
    $partial = new TemplateFile(self::get_file($filename, $underscore_first));
    $partial->set('data', $data);
    return $partial->render();
  }

  public static function page($additional_data=array(), $filename=null){
    $page = Wire::getFuel('page');
    return self::partial(is_null($filename) ? $page->template : $filename,
      array_merge(self::extract_values($page), $additional_data), false
    );
  }

  /**
   * Renders looped template with data and optional separator file, returns it as a string
   *
   * @param string $filename               file name of template
   * @param string $data                   data to be used to render the partial
   * @param string $separatorFileName      data to be used to render the partial
   * @return string                        the template content (with variables replaced)
   */
  public static function loop($filename, $data = array(), $object_name='item', $separator_filename = false){

    $loop_item = new TemplateFile(self::get_file($filename));
    $loop_separator = ($separator_filename) ?
      new TemplateFile(self::get_file($separator_filename)) : false;
    $result = array(); $count = 0; $items_count = count($data);

    foreach ($data as $item){
      
      $loop_item->setArray(array(
        $object_name => $item,
        $object_name.'_first' => ++$count === 1,
        $object_name.'_last'  => $count === $items_count,
        $object_name.'_odd'   => $count % 2 === 1,
        $object_name.'_even'  => $count % 2 === 0,
        $object_name.'_count' => $item_count,
        $object_name.'_nr'    => $count
      ));
      $result []= $loop_item->render();

      if($loop_separator && ($count < $items_count)) {
        $loop_item->setArray(array(
          $object_name => $item,
          $object_name.'_odd'   => $count % 2 === 1,
          $object_name.'_even'  => $count % 2 === 0,
          $object_name.'_count' => $item_count,
          $object_name.'_nr'    => $count
        ));
        $result []= $loop_separator->render();
      }

    }

    return implode('', $result);
  }

  public static function collection($filename, $collection, $object_name = 'item'$separator_filename = false){
    return self::loop($filename, $collection, $object_name, $separator_filename);
  }

  ///////////////////////////////////// OUTPUT /////////////////////////////////////

  /**
   * Renders the master and returns it as a string
   *
   * @param array $data data to be pushed into the master
   * @return contents of the master template
   */
  public static function master($data = array(), $echo=true) {
    //make init option: if file wasn't initialized, initialize it for developer
    if (!self::$master_template) { self::init(); }

    self::$master_template->set('data', $data);
    if ($echo)
      echo self::$master_template->render();
    else
      return self::$master_template->render();
  }

  /**
   * Auto-renders master with $page fields in the $content, with possible additional data
   *
   * @param array $additionalData     optional data (good to use if you want 'auto' way, but need more data)
   */
  public static function auto($additional_data = array(), $filename=null){
    self::master(array(
      'content'=>Render::page($additional_data, $filename)
    ));
  }
}