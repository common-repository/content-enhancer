<?php
/*
 Plugin Name: Content Enhancer Plugin
 Plugin URI: http://twoggle.com/wordpress-plugins/content-enhancer
 Description: This plugin allows you to insert html/javascript code into your post's content in a variety of different ways.
 Version: 1.0
 Author: Gvanto
 Author URI: http://twoggle.com
 Licence: GPLv2

Copyright 2013 Gert van Tonder (email : gvanto@twoggle.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

*/
?>

<?php
add_action('admin_menu', //the action hook during which the function below is called 
           'content_enhancer_create_settings_submenu' //name of function to call (callback)
        );

function content_enhancer_create_settings_submenu() {
   add_options_page('Content Enhancer',  //page title
                     'Content Enhancer', //menu display name
                     'manage_options',          //capability (who can view this menu?)
                     'content_enhancer_menu',   //slug - unique menu handle
                     'content_enhancer_settings_page' //function callback which builds the option page
                     );
   
   //Call register settings function
   add_action('admin_init', 'content_enhancer_register_settings');
}

function content_enhancer_register_settings() {
   //register our settings:
   
   //post_top setting for adding content to top of all posts
   register_setting('content_enhancer_settings_group', //unique group name for our settings
                    'content_enhancer_post_top', //option name
                     'content_enhancer_sanitize_options' //sanitization callback function (defined below)
                   );
   
   //post_bottom setting for adding content to top of all posts
   register_setting('content_enhancer_settings_group', //unique group name for our settings
                    'content_enhancer_post_bottom', //option name
                     'content_enhancer_sanitize_options' //sanitization callback function (defined below)
                   );
   
   //merge_fields_keys
   register_setting('content_enhancer_settings_group', //unique group name for our settings
                    'content_enhancer_merge_fields_keys', //option name
                     'content_enhancer_validate_mf_keys' //sanitization callback function (defined below)
                   );
   
   //merge_fields_values
   register_setting('content_enhancer_settings_group', //unique group name for our settings
                    'content_enhancer_merge_fields_values', //option name
                     'content_enhancer_validate_mf_values' //sanitization callback function (defined below)
                   );
   
}

//removes element at index $rem_i from array, decrements index of rest of array
function remove_arr_element_shift_index($arr, $rem_i) {
   unset($arr[$rem_i]);   
   $new_arr = array_values($arr);
   return $new_arr;
}

function content_enhancer_validate_mf_keys($input) {
   
   //remove keys where 'Remove?' was selected:
   if(isset($_POST['remove_merge_field'])) {      
      //only checked checkboxes will appear in this array
      foreach($_POST['remove_merge_field'] as $key_index=>$v) {
         $input = remove_arr_element_shift_index($input, $key_index);
         //error_log("keys: key_index=$key_index");
      }     
   }
   
   //Check data is valid (key is proper format, data not empty)
   //use same format as short_code:
   $pattern = '/\[(\w+)\]/';
   error_log("pattern_sc=$pattern");
   
   foreach($input as $ikey=>$ival) {
      //error_log("k=$ikey ival=$ival");
      
      if(strlen($ival) < 1) { //key is empty, remove item
         $input = remove_arr_element_shift_index($input, $ikey);
      }
      else {
         if(!preg_match($pattern, $ival)) {
            add_settings_error('content_enhancer_merge_fields_keys', 'invalid_key_format', 'Save error: Invalid format. Please check where key is "' . $ival . '"');
         }     
      }
   }
   
   //check if last element in keys_array actually has values:
   return wp_kses_post($input);
}

function content_enhancer_validate_mf_values($input) {   
   //remove keys where 'Remove?' was selected:
   if(isset($_POST['remove_merge_field'])) {      
      //only checked checkboxes will appear in this array
      foreach($_POST['remove_merge_field'] as $key_index=>$v) {
         //unset($input[$key_index]);
         $input = remove_arr_element_shift_index($input, $key_index);
      }      
   }
   
   //check if last element in keys_array actually has values:
   return wp_kses_post($input);
}

/**
 * Function to sanitize settings form inputs - essential for security! 
 * @param type $input 
 */
function content_enhancer_sanitize_options($input) {
   //see http://codex.wordpress.org/Function_Reference/wp_kses_post
   return wp_kses_post($input);
}

function content_enhancer_settings_page() {
   $col_width = '80'; //column widths for textareas
   
   ?>   
<div class="wrap">
   <h2>Content Enhancer Options</h2>
   
   <form method="post" action="options.php">
      <?php settings_fields('content_enhancer_settings_group'); ?>
      <?php $ce_post_top = get_option('content_enhancer_post_top'); ?>
      <?php $ce_post_bottom = get_option('content_enhancer_post_bottom'); ?>
      <?php 
            //key-value pairs: $ce_merge_fields_keys[0]=>$ce_merge_fields_values[0]
            $ce_merge_fields_keys = get_option('content_enhancer_merge_fields_keys'); 
            $ce_merge_fields_values = get_option('content_enhancer_merge_fields_values'); 
      ?>
      
      <table class="form-table">
         
         <tr valign="top">
            <th scope="row">Top Of Post Insert:</th>
            <td>
               
               <textarea id="content_enhancer_post_top" name="content_enhancer_post_top" cols="<?php echo $col_width; ?>" rows="6"><?php echo $ce_post_top; ?></textarea>
               
            </td>
         </tr>
         
         <tr valign="top">
            <th scope="row">Bottom Of Post Insert:</th>
            <td>
               <textarea id="content_enhancer_post_bottom" name="content_enhancer_post_bottom" cols="<?php echo $col_width; ?>" rows="6"><?php echo $ce_post_bottom; ?></textarea>
            </td>
            
         </tr>
         
         <tr valign="top">
            <th scope="row"><strong>Merge Fields:</strong></th>
            <td>&nbsp;</td>
         </tr>
         <tr valign="top">            
            <td colspan="3">Create key value pairs for <a target="_blank" title="Merge Fields" href="http://twoggle.com/faq#Merge_Field">merging</a> into content. Note: key must be format '[{name}']
               where name can contain only letters, numbers and underscores '_'.
            </td>            
         </tr>
         <tr valign="top">
            <th scope="row"><strong>KEY:</strong></th>
            <td><strong>VALUE:</strong></td>
            <td><strong>Remove?</strong></td>
         </tr>
         
         <?php
         //we have no merge fields yet, add initial field:
         if(FALSE == $ce_merge_fields_keys || count($ce_merge_fields_keys) == 0) {
            
            //TODO: create this option during plugin activation!!!
            
            ?>
            
            <tr valign="top">
               <th scope="row"><input type="text" name="content_enhancer_merge_fields_keys[0]" maxlength="100" size="25" value="[my_twitter]"></th>
               <td><textarea name="content_enhancer_merge_fields_values[0]" cols="<?php echo $col_width; ?>" rows="6"><a href="http://twitter.com/twoggle">Follow Me On Twitter!</a></textarea></td>
               
               <td>&nbsp;</td>
            </tr>
         
            <?php
         }
         else { //we have some merge_fields! Display them for edit:
                        
            for($i = 0; $i < count($ce_merge_fields_keys); $i++) {
             ?>
               
               <tr valign="top">
                  <!-- Merge Field KEY -->
                  <th scope="row"><input type="text" name="content_enhancer_merge_fields_keys[<?php echo $i; ?>]" maxlength="100" size="25" value="<?php echo $ce_merge_fields_keys[$i]; ?>"></th>
               
                  <!-- Merge Field VALUE -->
                  <td><textarea name="content_enhancer_merge_fields_values[<?php echo $i; ?>]" cols="<?php echo $col_width; ?>" rows="6"><?php echo $ce_merge_fields_values[$i]; ?></textarea></td>

                  <!-- Remove Checkbox -->
                  <td><input type="checkbox" name="remove_merge_field[<?php echo $i; ?>]" value="1"/></td>
               </tr>
            
             <?php
            }
            
            //Add one extra field, for user to enter a new merge field:
            ?>            
               <tr valign="top">
               <th scope="row"><input type="text" name="content_enhancer_merge_fields_keys[<?php echo $i; ?>]" maxlength="100" size="25" value=""></th>
               <td><textarea name="content_enhancer_merge_fields_values[<?php echo $i; ?>]" cols="<?php echo $col_width; ?>" rows="6"></textarea></td>
               <td>(new)</td>
               </tr>               
            <?php
         }
         ?>
         
         
      </table>
      
      <p class="submit">
         <input type="submit" class="button-primary" value="Save Changes" />
      </p>
      
   </form>
   
   
</div><!-- c#wrap -->

<?php
}

//And, ACTION! No, filter :-)
add_filter('the_content', 'content_enhancer_process_content', 1);

/**
 * Pairs of key-value pairs
 * @return type hashmap <mergefield-key, mergefield-value> pair
 */
function getMergeFieldsHashMap() {
   $hmap = array();
   
   //key-value pairs: $ce_merge_fields_keys[0]=>$ce_merge_fields_values[0]
   $ce_merge_fields_keys = get_option('content_enhancer_merge_fields_keys'); 
   $ce_merge_fields_values = get_option('content_enhancer_merge_fields_values'); 
    
   foreach($ce_merge_fields_keys as $ki=>$kv) {
      $hmap[$kv] = $ce_merge_fields_values[$ki];
   }
   
   return $hmap;
}

function content_enhancer_process_content($content) {
   //insert top
   $ce_post_top = get_option('content_enhancer_post_top');
   if(FALSE != $ce_post_top && strlen($ce_post_top) > 1) {
      $content = '<div class="content-enhancer-post-top">' . $ce_post_top . '</div>' . $content;
   }
   
   //insert bottom
   $ce_post_bottom = get_option('content_enhancer_post_bottom');
   if(FALSE != $ce_post_bottom && strlen($ce_post_bottom) > 1 ) {
      $content = $content . '<div class="content-enhancer-post-bottom">' . $ce_post_bottom . '</div>';
   }
   
   //do in-document merge field replace:
   $hmap = getMergeFieldsHashMap();
   
   foreach($hmap as $mfkey=>$mfval) {
      $content = str_replace($mfkey, $mfval, $content);
   }
   
   return $content;
}


?>

