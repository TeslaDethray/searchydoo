<?php
/*
Plugin Name: Searchydoo
Plugin URI: http://radicaldesigns.org
Description: Allows the user to allow or disallow certain content types in WordPress
Version: 1.0
Author:  Sara McCutcheon & Joshua Chavanne
License: GPL3
*/

define( 'SEARCHYDOO_PUGIN_NAME', 'Searchydoo');
define( 'SEARCHYDOO_PLUGIN_DIRECTORY', 'searchydoo');
define( 'SEARCHYDOO_CURRENT_VERSION', '0.1' );
define( 'SEARCHYDOO_CURRENT_BUILD', '1' );
define( 'SEARCHYDOO_LOGPATH', str_replace('\\', '/', WP_CONTENT_DIR).'/searchydoo-logs/');
define( 'SEARCHYDOO_DEBUG', false);
define( 'EMU2_I18N_DOMAIN', 'searchydoo' );

require_once('searchydoo_logfilehandling.php');

function searchydoo_set_lang_file() {
  # set the language file
  $currentLocale = get_locale();
  if(!empty($currentLocale)) {
    $moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
    if (@file_exists($moFile) && is_readable($moFile)) {
      load_textdomain(EMU2_I18N_DOMAIN, $moFile);
    }

  }
}
searchydoo_set_lang_file();

// create custom plugin settings menu
//add_action( 'admin_menu', 'searchydoo_create_menu' );

//call register settings function
add_action( 'admin_init', 'searchydoo_register_settings' );

register_activation_hook(__FILE__, 'searchydoo_activate');
register_deactivation_hook(__FILE__, 'searchydoo_deactivate');
register_uninstall_hook(__FILE__, 'searchydoo_uninstall');

// activating the default values
function searchydoo_activate() {
  add_option('searchydoo_option_3', 'any_value');
}

// deactivating
function searchydoo_deactivate() {
  // needed for proper deletion of every option
  delete_option('searchydoo_option_3');
}

// uninstalling
function searchydoo_uninstall() {
  # delete all data stored
  delete_option('searchydoo_option_3');
  // delete log files and folder only if needed
  if (function_exists('searchydoo_deleteLogFolder')) searchydoo_deleteLogFolder();
}

function searchydoo_create_menu() {

  // create new top-level menu
  add_menu_page( 
  __('HTML Title', EMU2_I18N_DOMAIN),
  __('HTML Title', EMU2_I18N_DOMAIN),
  0,
  SEARCHYDOO_PLUGIN_DIRECTORY.'/searchydoo_settings_page.php',
  '',
  plugins_url('/images/icon.png', __FILE__));
  
  
  add_submenu_page( 
  SEARCHYDOO_PLUGIN_DIRECTORY.'/searchydoo_settings_page.php',
  __("HTML Title", EMU2_I18N_DOMAIN),
  __("Menu title", EMU2_I18N_DOMAIN),
  0,
  SEARCHYDOO_PLUGIN_DIRECTORY.'/searchydoo_settings_page.php'
  );
  
  // or create options menu page
  add_options_page(__('HTML Title 3', EMU2_I18N_DOMAIN), __("Menu title 3", EMU2_I18N_DOMAIN), 9,  SEARCHYDOO_PLUGIN_DIRECTORY.'/searchydoo_settings_page.php');

  // or create sub menu page
  $parent_slug="index.php";  # For Dashboard
  #$parent_slug="edit.php";    # For Posts
  // more examples at http://codex.wordpress.org/Administration_Menus
  add_submenu_page( $parent_slug, __("HTML Title 4", EMU2_I18N_DOMAIN), __("Menu title 4", EMU2_I18N_DOMAIN), 9, SEARCHYDOO_PLUGIN_DIRECTORY.'/searchydoo_settings_page.php');
}

function searchydoo_register_settings() {
  //register settings
  register_setting( 'searchydoo-settings-group', 'new_option_name' );
  register_setting( 'searchydoo-settings-group', 'some_other_option' );
  register_setting( 'searchydoo-settings-group', 'option_etc' );
}

// check if debug is activated
function searchydoo_debug() {
  # only run debug on localhost
  if ($_SERVER["HTTP_HOST"]=="localhost" && defined('EPS_DEBUG') && EPS_DEBUG==true) return true;
}

/***************************************************
 * searchydoo_get_page_categories
 * @return a hash containing the categories of published pages
  ***************************************************/
function searchydoo_get_page_categories() {
  $args = array(
          'type' => 'page',
          'orderby' => 'name',
          'order' => 'ASC'
  );
  $page_categories = get_categories($args);
  return $page_categories;
}

/***************************************************
 * searchydoo_get_custom_types
 * @param string $type_name is the term name used to subsume the 'categories'
 * @return a hash containing the category objects of custom posts 
 * defined by argument
  ***************************************************/
function searchydoo_get_taxonomy_terms($type_name) {
 $args = array(          
    'orderby' => 'name',
    'order' => 'ASC'
  );
  $post_categories = get_terms($type_name,$args);
  return $post_categories;  
}


/***************************************************
 * searchydoo_content_type_filter
 * 
 *
 * for reference - we can include any of these - note 
 * the names below are the proper keys
 
 * post,page,attachment,revision,nav_menu_item,video,book,podcast,
 * uncut-podcast,premium_post,acf
 
 *
 ***************************************************/

 
 
 function searchydoo_render_content_type_filter(){
  
  $content_types_to_include = searchydoo_get_applicable_types();
  $valid_types = array(); 
    
   
   foreach($content_types_to_include as $this_type){
    $to_push = get_post_type_object($this_type);
    array_push($valid_types,$to_push);  
   }  // get_post_type_object wasn't accepting an array
 
   echo '<div id="post_type_select">';
   echo '<label for="post_type">Post Type:</label>';
   echo '<select name="post_type">';
   echo '<option value="any">Search all</option>';
   foreach($valid_types as $post_type){
     $label = $post_type->labels->name;
     $machine_name = $post_type->name;  
      echo '<option value="'.$machine_name.'">'.$label.'</option>';
    }
 echo '</select>'; 
 echo '</div>';
 }

 
/******************************************
searchydoo_render_tax_lists

to replace render filter lists


******************************************/ 
 
 
 function searchydoo_render_tax_lists($tax_vocabs){
 $tax_vocabs = array('category','book-types','video-type');
       
  foreach($tax_vocabs as $tax_vocab){
    $this_list = searchydoo_get_taxonomy_terms($tax_vocab);
    
    $label = get_taxonomy($tax_vocab);
    
    //echo var_dump($this_list);
    echo '<div id="tax-select-'.$tax_vocab.'" class="tax-select">';
    echo '<label for="'.$tax_vocab.'">'.$label->label.': </label>';
    echo '<select name="'.$tax_vocab.'">';
    echo '<option value="">Search all</option>';
    
    foreach($this_list as $this_obj){ 
      $name = $this_obj->name;
      $slug = $this_obj->slug;
      $term_id = $this_obj->term_id;  
      echo '<option value="'.$slug.'">'.$name.'</option>';     
    }
  
    echo '</select>';
    echo '</div>';
  }
  
  
 }
 

/***************************************************
 * searchydoo_render_filter_lists -- Deprecating
 * 
 *  @param array $data is a collection of objects that are the terms for the list
 *  @param string $taxonomy is an ID keyed which the form element will be named
 *  @param string $label is an optional override to the label generated from $taxonomy
 *  @param return returns the output instead of printing
 *  @return string of HTML radio button of filter heading and its subcategories as checkboxes
  ***************************************************/
function searchydoo_render_filter_lists($data, $taxonomy, $label = null, $return = false) {
  if(!isset($label)) {
    $label = ucwords($taxonomy);
  }
  $component = '<input type="radio" name="taxonomy" value="'.$taxonomy.'" class="taxonomy" id="'.$taxonomy.'">'.$label.'<br />';

  if(isset($data) && (count($data) > 0)) {
    $component .= '<blockquote class="'.$taxonomy.' content-type" style="display: none;">';
    foreach($data as $some_objs){
      $name = $some_objs->name;
      $slug = $some_objs->slug;
      $term_id = $some_objs->term_id; 
      
      $component .= '<div class="term_group">';
      $component .= '<input type="radio" class="term" name="term" value="'.$slug.'" disabled="disabled" class="'.$slug.'" id="search-'.$slug.'"/>'.$name.'<br />';
      $component .= '</div>';
    }
    $component .= '</blockquote>';
  }
  if($return) {
    return $component;
  }
  echo $component;
}

/***************************************************
 * searchydoo_render_text_search
 * 
 *  Renders Keyword Search Field 
 *  @param return returns the output instead of printing
 *  @return string of HTML text search box and div
  ***************************************************/
function searchydoo_render_text_search($return = false){
  $component = '<div class="search-form-area">
  <label for="rsearch">Keyword Search:</label>
  <input type="text" id="rsearch" placeholder="Search..." name="s" value="'.$_GET['s'].'"/>
  </div>';
  if($return) {
    return $component;
  }
  echo $component;
}

/***************************************************  
 *  searchydoo_render_sort_columns
 *
 *  Renders Options of Columns to sort by
 *  @param return returns the output instead of printing
 *  @return string of HTML sort type option and div
***************************************************/
function searchydoo_render_sort_columns($return = false){
  $component = '
  <div class="sort-columns-area">
  <label for="orderby">Sort By:</label>
  <select id="orderby" name="orderby">
    <option value="title">Alphabetical</option>
    <option value="date">Post Date</option>
  </select>
  </div>';
  if($return) {
    return $component;
  }
  echo $component;
}

/***************************************************  
 *  searchydoo_render_sort_options
 *
 *  Renders Sort Option Fields
 *  @param return returns the output instead of printing
 *  @return string of HTML sort option field and div
***************************************************/
function searchydoo_render_sort_options($return = false){
  $component = '
  <div class="sort-options-area">
    <label for="order">Sort Direction:</label>
    <select id="order" name="order">
      <option value="ASC">Ascending</option>
      <option value="DESC">Descending</option>
    </select>
  </div>';
  if($return) {
    return $component;
  }
  echo $component;
}

/***************************************************
 *  searchydoo_render_submit_button
 *
 *  Renders submit button
 *  @param return returns the output instead of printing
 *  @return string of HTML submit button
***************************************************/
function searchydoo_render_submit_button($return = false){
  $component = '    
    <input type="submit" id="search_submit" value="Submit"/>
  ';
  if($return) {
    return $component;
  }
  echo $component;
}

/***************************************************
 *  searchydoo_render_jquery
 *
 *  Renders jQuery to control form
 *  @param return returns the output instead of printing
 *  @return string of jQuery
***************************************************/
function searchydoo_render_jquery($return = false) {
  $component = '<script type="text/javascript" src="'.plugins_url().'/searchydoo/searchydoo.js"></script>';
  $component .= '<script type="text/javascript">';
  foreach($_GET as $var => $get_item) {
    $component .= 'jQuery("input[value=\''.$get_item.'\']").click();';
  }
  $component .= '</script>';

  if($return) {
    return $component;
  }
  echo $component;
}

function list_them_simply($some_array) {
  foreach($some_array as $some_objs){
    echo '<br />';
    echo $some_objs->name;
      
  }
  echo '<br />';
}

function searchydoo_execute() {
  echo '<h1 class="post-title">Search</h1>';   
  
  echo '<form id="searchydoo" role="search" method="get" id="searchform" action="'.home_url('/').'">';
  
  searchydoo_render_text_search();
  
  //need to eliminate the below
  /*
  searchydoo_render_filter_lists(searchydoo_get_taxonomy_terms('category'),'category', 'Articles');
  searchydoo_render_filter_lists(searchydoo_get_taxonomy_terms('book-types'), 'book-types', 'Books');  
  searchydoo_render_filter_lists(searchydoo_get_taxonomy_terms('video-type'), 'video-type', 'Videos');
  */
  // eliminate above after refactoring
  
  searchydoo_render_content_type_filter();
  
  //functions:
  //searchydoo_render_content_type_filter(no args, or excluded post types as args? or included?);
  searchydoo_render_tax_lists($tax_vocabs);
  
  
  searchydoo_render_sort_columns();
  searchydoo_render_sort_options();
  searchydoo_render_submit_button();
  echo "</form>";
  searchydoo_render_jquery();
}

add_shortcode('searchydoo', 'searchydoo_execute');
add_shortcode('sdoo', 'searchydoo_execute');

function searchydoo_get_applicable_types() {
  return array('post', 'video','podcast', 'book');
}

function searchydoo_search_filter($query) {
  if(is_search()) {
    $the_query = $query->query;
    if(!isset($the_query['post_type'])) $query->set('post_type', searchydoo_get_applicable_types());
    //die('<pre>'.print_r($query,true).'</pre>');
  }
  return $query;
}

add_filter('pre_get_posts','searchydoo_search_filter');
