<?php
/*
  Plugin Name: eBook Display
  Plugin URI: https://github.com/robogeek/wp-ebook-display
  Description: Display EPUB eBook content on a Wordpress site
  Version: 0.1.4
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

function ebookdisplay_upload_field() {
    echo '<input type="file" name="ebookdisplay_upload_field" />';
}

function ebookdisplay_add_metaboxes() {
    add_meta_box("ebookdisplay_upload_field", "Upload eBook to display", "ebookdisplay_upload_field", "ebook_displayer", 'side');
}
add_action('add_meta_boxes', 'ebookdisplay_add_metaboxes', 10, 2);

function ebookdisplay_handle_upload_field($post_ID, $post) {
    if (!empty($_FILES['ebookdisplay_upload_field']['name'])) {
        $upload = wp_handle_upload($_FILES['ebookdisplay_upload_field']);
        if (!isset($upload['error'])) {
            if (!add_post_meta($post_ID, 'ebook_path', $upload['file'], true)) {
               update_post_meta($post_ID, 'ebook_path', $upload['file']);
            }
            if (!add_post_meta($post_ID, 'ebook_url', $upload['url'], true)) {
               update_post_meta($post_ID, 'ebook_url', $upload['url']);
            }
            if (!add_post_meta($post_ID, 'ebook_mimetype', $upload['type'], true)) {
               update_post_meta($post_ID, 'ebook_mimetype', $upload['type']);
            }
        } else {
            error_log($upload['error']);
        }
    } else {
        error_log('empty $_FILES ebookdisplay_upload_field name');
    }
}
add_action('wp_insert_post', 'ebookdisplay_handle_upload_field', 10, 2);

