<?php
/*
  Plugin Name: eBook Display
  Plugin URI: https://github.com/robogeek/wp-ebook-display
  Description: Display EPUB eBook content on a Wordpress site
  Version: 0.1.5
  Author: David Herron
  License: GPLv2 or later

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


add_action( 'init', 'ebookdisplay_init' );

/**
 * Initialize the ebook_page post type and other initializaton
 */
function ebookdisplay_init() {
    
    register_post_type('ebook_displayer',
    array(
        'labels' => array(
            'name' => __('eBook Displayer'),
            'singular_name' => __('eBook Display'),
            'add_new'            => _x('Add New', 'displayer', 'your-plugin-textdomain'),
            'add_new_item'       => __('Add New eBook Displayer', 'your-plugin-textdomain'),
            'new_item'           => __('New eBook Displayer', 'your-plugin-textdomain'),
            'edit_item'          => __('Edit eBook Displayer', 'your-plugin-textdomain'),
            'view_item'          => __('View eBook Displayer', 'your-plugin-textdomain'),
            'all_items'          => __('All eBook Displayers', 'your-plugin-textdomain'),
            'search_items'       => __('Search eBook Displayers', 'your-plugin-textdomain'),
            // 'parent_item_colon'  => __('Parent Books:', 'your-plugin-textdomain'),
            'not_found'          => __('No books found.', 'your-plugin-textdomain'),
            'not_found_in_trash' => __('No books found in Trash.', 'your-plugin-textdomain')
        ),
        'public'         => true,
        'has_archive'    => true,
        'rewrite'        => array('slug' => 'ebook', 'feeds' => false, 'with_front' => true),
        'hierarchical'   => false,
		'supports'       => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'page-attributes'),
		'show_ui'        => true,
		'taxonomies'     => array('post_tag')
    )
  );
    
}

add_action('post_edit_form_tag', 'post_edit_form_tag');
function post_edit_form_tag() {
    echo ' enctype="multipart/form-data"';
}

function ebookdisplay_upload_field() {
    wp_nonce_field(plugin_basename(__FILE__), 'ebookdisplay_upload_field_nonce');
    echo '<input type="file" id="ebookdisplay_upload_field" name="ebookdisplay_upload_field" />';
}

function ebookdisplay_add_metaboxes() {
    add_meta_box("ebookdisplay_upload_field", "Upload eBook to display",
                 "ebookdisplay_upload_field", "ebook_displayer",
                 'normal', 'high');
}
add_action('add_meta_boxes', 'ebookdisplay_add_metaboxes', 10, 2);

function ebookdisplay_save_custom_meta_data($id) {
 
    /* --- security verification --- */
    if(!wp_verify_nonce($_POST['ebookdisplay_upload_field_nonce'], plugin_basename(__FILE__))) {
      return $id;
    } // end if
       
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return $id;
    } // end if
       
    if('page' == $_POST['post_type']) {
      if(!current_user_can('edit_page', $id)) {
        return $id;
      } // end if
    } else {
        if(!current_user_can('edit_page', $id)) {
            return $id;
        } // end if
    } // end if
    /* - end security verification - */
    
    if(!empty($_FILES['ebookdisplay_upload_field']['name'])) {
         
        foreach ($_FILES['ebookdisplay_upload_field'] as $key => $value) {
            error_log('TRACE $_FILES["ebookdisplay_upload_field"]   '. $key .' => '. $value);
        }
        
        // This required installing the WP Extra Types plugin and tick the box for .epub
        // How to enable .epub upload w/o requiring that plugin
        
        // Should be possible to use plupload
        
        // How to make the form display the uploaded file?
        // When that function is executed there's probably a global variable for post ID
        // Hence can use that variable to find the post meta and build the form appropriately
        
        // If $_FILES has an uploadable file it indicates a new file is being uploaded
        // Hence, if an existing file is attached then it should be deleted beforehand
        
        // Setup the array of supported file types. In this case, it's just EPUB.
        $supported_types = array('application/epub+zip');
         
        // Get the file type of the upload
        // Check if the type is supported. If not, throw an error.
        if(in_array($_FILES['ebookdisplay_upload_field']['type'], $supported_types)) {
 
            // Use the WordPress API to upload the file
            $upload = wp_upload_bits($_FILES['ebookdisplay_upload_field']['name'], null,
                   file_get_contents($_FILES['ebookdisplay_upload_field']['tmp_name']));
     
            if(isset($upload['error']) && $upload['error'] != 0) {
                wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
            } else {
                
                if (!add_post_meta($id, 'ebook_path', $upload['file'], true)) {
                   update_post_meta($id, 'ebook_path', $upload['file']);
                }
                if (!add_post_meta($id, 'ebook_url', $upload['url'], true)) {
                   update_post_meta($id, 'ebook_url', $upload['url']);
                }
                if (!add_post_meta($id, 'ebook_mimetype', $upload['type'], true)) {
                   update_post_meta($id, 'ebook_mimetype', $upload['type']);
                }
                
                // Now that we've got the file uploaded, extract its contents
                
                $path_parts = pathinfo($upload['file']);
                $extract_to = $path_parts['dirname'] .'/'. $path_parts['filename'];
                
                $zip = new ZipArchive;
                $res = $zip->open($upload['file']);
                if ($res === TRUE) {
                  // extract it to the path we determined above
                  $zip->extractTo($extract_to);
                  $zip->close();
                  // echo "WOOT! $file extracted to $path";
                } else {
                  error_log("Doh! I couldn't open ". $upload['file']);
                }
                
                if (!add_post_meta($id, 'ebook_extracted', $extract_to, true)) {
                   update_post_meta($id, 'ebook_extracted', $extract_to);
                }
                
            } // end if/else
 
        } else {
            error_log("The file type that you've uploaded is not an EPUB.");
        } // end if/else
         
    } else {
        error_log('ebookdisplay_save_custom_meta_data: empty $_FILES ebookdisplay_upload_field name');
        // error_log(print_r($_FILES));
    } // end if
     
    
    
} // end save_custom_meta_data
add_action('save_post', 'ebookdisplay_save_custom_meta_data');
