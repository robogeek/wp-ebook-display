<?php
/*
 * Template Name: Nutritional Information
 */
// get_header();

global $wp_query;
$bookasset = $wp_query->query_vars['bookasset'];

echo '<p>bookasset = '. $bookasset .'</p>';


// get_footer();
?>