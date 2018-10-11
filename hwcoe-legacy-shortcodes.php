<?php
/*
Plugin Name: Legacy Shortcodes
Description: Selected shortcodes from UF/Gator Engineering Template Responsive
Version: 1.0
License: GPL
Author: Herbert Wertheim College of Engineering
Author URI: http://www.eng.ufl.edu
*/

// insert HTML sitemap (http://wordpress.org/extend/plugins/html-sitemap/)
// adds an HTML (Not XML) sitemap of your blog pages (not posts) by entering the shortcode [html-sitemap].
// example: [html-sitemap depth=4 exclude=24]
function hwcoe_legacy_html_sitemap_shortcode( $args, $content = null )
{
	if( is_feed() )
		return '';
		
	$args['echo'] = 0;
	$args['title_li'] = '';
	unset($args['link_before']);
	unset($args['link_after']);
	if( isset($args['child_of']) && $args['child_of'] == 'CURRENT' )
		$args['child_of'] = get_the_ID();
	else if( isset($args['child_of']) && $args['child_of'] == 'PARENT' )
	{
		$post = &get_post( get_the_ID() );
		if( $post->post_parent )
			$args['child_of'] = $post->post_parent;
		else
			unset( $args['child_of'] );
	}
	
	$html = wp_list_pages($args);

	// Remove the classes added by WordPress
	$html = preg_replace('/( class="[^"]+")/is', '', $html);
	return '<ul>'. $html .'</ul>';
}
add_shortcode('html-sitemap', 'hwcoe_legacy_html_sitemap_shortcode');


// insert a tag cloud using a shortcode
function hwcoe_legacy_tagcloud_shortcode($atts) {
		extract(shortcode_atts(array(
		"taxonomy" => 'post_tag',
		"num" => '45',
		"format" => 'flat',
		"smallest" => '8',
		"largest" => '22',
		"orderby" => 'name',
		"order" => 'ASC',
		), $atts));

		$order = strtoupper($order);
		
		$tag_cloud = wp_tag_cloud(apply_filters('shortcode_widget_tag_cloud_args', array('taxonomy' => $taxonomy, 'echo' => false, 'number' => $num, 'format' => $format, 'smallest' => $smallest, 'largest' => $largest, 'orderby' => $orderby, 'order' => $order, "taxonomy" => $taxonomy) ));
	
		return $tag_cloud;
}
add_shortcode('tagcloud', 'hwcoe_legacy_tagcloud_shortcode');


// Display posts via shortcode
// From: http://www.billerickson.net/shortcode-to-display-posts/
add_shortcode('display-posts', 'hwcoe_legacy_display_posts_shortcode');
function hwcoe_legacy_display_posts_shortcode($atts) {
	// Pull in shortcode attributes and set defaults
	extract( shortcode_atts( array(
		'post_type' => 'post',
		'post_parent' => false,
		'id' => false,
		'tag' => '',
		'category' => '',
		'posts_per_page' => '10',
		'order' => 'DESC',
		'orderby' => 'date',
		'include_date' => false,
		'dateformat' => 'l, F jS, Y',
		'include_excerpt' => false,
		'include_content' => false,
		'image_size' => false,
		'wrapper' => 'div',
		'taxonomy' => false,
		'tax_term' => false,
		'tax_operator' => 'IN'
	), $atts ) );
	
	// Set up initial query for post
	$args = array(
		'post_type' => explode( ',', $post_type ),
		'tag' => $tag,
		'category_name' => $category,
		'posts_per_page' => $posts_per_page,
		'order' => $order,
		'orderby' => $orderby,
	);
	
	// If Post IDs
	if( $id ) {
		$posts_in = explode( ',', $id );
		$args['post__in'] = $posts_in;
	}
	
	
	// If taxonomy attributes, create a taxonomy query
	if ( !empty( $taxonomy ) && !empty( $tax_term ) ) {
	
		// Term string to array
		$tax_term = explode( ', ', $tax_term );
		
		// Validate operator
		if( !in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) )
			$tax_operator = 'IN';
					
		$tax_args = array(
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $tax_term,
					'operator' => $tax_operator
				)
			)
		);
		$args = array_merge( $args, $tax_args );
	}
	
	// If post parent attribute, set up parent
	if( $post_parent ) {
		if( 'current' == $post_parent ) {
			global $post;
			$post_parent = $post->ID;
		}
		$args['post_parent'] = $post_parent;
	}
	
	// Set up html elements used to wrap the posts. 
	// Default is ul/li, but can also be ol/li and div/div
	$wrapper_options = array( 'ul', 'ol', 'div' );
	if( !in_array( $wrapper, $wrapper_options ) )
		$wrapper = 'ul';
	if( 'div' == $wrapper )
		$inner_wrapper = 'div';
	else
		$inner_wrapper = 'li';

	
	$listing = new WP_Query( apply_filters( 'display_posts_shortcode_args', $args, $atts ) );
	if ( !$listing->have_posts() )
		return apply_filters ('display_posts_shortcode_no_results', false );
		
	$inner = '';
	while ( $listing->have_posts() ): $listing->the_post(); global $post;
			
		if ( $image_size && has_post_thumbnail() )  $image = '<a class="image" href="'. get_permalink() .'">'. get_the_post_thumbnail($post->ID, $image_size).'</a> ';
		else $image = '';

		$title = '<h2><a href="'. get_permalink() .'">'. get_the_title() .'</a></h2>';
		
		
		if ($include_date) $date = '<p class="published">'. get_the_date($dateformat) .'</p>';
		else $date = '';
		
		if ($include_excerpt) $excerpt = '<p>' . get_the_excerpt() . '</p>';
		else $excerpt = '';

		if( $include_content ) {
			add_filter( 'shortcode_atts_display-posts', 'hwcoe_legacy_display_posts_off', 10, 3 );
			/** This filter is documented in wp-includes/post-template.php */
			$content = '<div class="content">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
			remove_filter( 'shortcode_atts_display-posts', 'hwcoe_legacy_display_posts_off', 10, 3 );
		}
		else $content = '';
		
		$output = '<' . $inner_wrapper . ' class="entry">' . $image . $title . $date . $excerpt . $content . '</' . $inner_wrapper . '>';
		
		$inner .= apply_filters( 'display_posts_shortcode_output', $output, $atts, $image, $title, $date, $excerpt, $content, $inner_wrapper );
		
	endwhile; wp_reset_query();
	
	$open = apply_filters( 'display_posts_shortcode_wrapper_open', '<' . $wrapper . ' class="display-posts-listing">' );
	$close = apply_filters( 'display_posts_shortcode_wrapper_close', '</' . $wrapper . '>' );
	$return = $open . $inner . $close;

	return $return;
}

// split content into two columns

function hwcoe_legacy_shortcode_leftcol($atts, $content = null) {
	extract(shortcode_atts(array(
		'autop' => '0',
	), $atts));
	global $replacelinebreaks;

	$content = do_shortcode($content);

	$left_float = "<div class='col-md-6'>";
		if ($replacelinebreaks=='1')
			$left_float .= wpautop($content);
		else
			$left_float .= $content;
		
	$left_float .= "</div>";

	return $left_float;
}
add_shortcode('left', 'hwcoe_legacy_shortcode_leftcol');

function hwcoe_legacy_shortcode_rightcol($atts, $content = null) {
	extract(shortcode_atts(array(
		'autop' => '0',
	), $atts));
	global $replacelinebreaks;

	$content = do_shortcode($content);

	$right_float = "<div class='col-md-6'>";
		if ($replacelinebreaks=='1')
			$right_float .= wpautop($content);
		else
			$right_float .= $content;
			
	$right_float .= "</div>";
	$right_float .= "<div class=\"cf\">&nbsp;</div>";

	return $right_float;
}
add_shortcode('right', 'hwcoe_legacy_shortcode_rightcol');

function hwcoe_legacy_shortcode_clear_floats($atts, $content = null) {
	extract(shortcode_atts(array(
				'autop' => '1',
	), $atts));
	$content = do_shortcode($content);
		
	$float_clear = "<div class=\"cf\">&nbsp;</div>";
	
	return $float_clear;
}
add_shortcode('clear', 'hwcoe_legacy_shortcode_clear_floats');


?>