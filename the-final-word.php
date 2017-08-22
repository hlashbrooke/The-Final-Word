<?php
/*
 * Plugin Name: The Final Word
 * Version: 1.0
 * Plugin URI: https://github.com/hlashbrooke/The-Final-Word
 * Description: Have the final word in a comment thread by marking a chosen comment as the 'top comment'.
 * Author: Hugh Lashbrooke
 * Author URI: https://hugh.blog/
 * Requires at least: 4.8
 * Tested up to: 4.8.1
 *
 * Text Domain: the-final-word
 * Domain Path: /languages/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load plugin JS and CSS
 * @return void
 */
function tfw_enqueue_scripts () {

	//  Set version number for JS and CSS
	$ver = '1.0';

	// Set minified suffix as necessary
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Load plugin JS and localise
	wp_enqueue_script( 'tfw-scripts', plugins_url( 'assets/scripts' . $suffix . '.js', __FILE__ ), array( 'jquery' ), $ver, false );
	wp_localize_script( 'tfw-scripts', 'tfw', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	// Load  plugin CSS
	wp_enqueue_style( 'tfw-css', plugins_url( 'assets/style.css', __FILE__ ), false, $ver, 'all' );
}
add_action( 'wp_enqueue_scripts', 'tfw_enqueue_scripts' );

/**
 * Add 'Top comment' actions to the comment action dropdown
 * @param  array   $actions       The existing list of comment actions
 * @param  string  $location      The location of the actions list
 * @param  object  $comment       The WP_Comment object for the current comment
 * @param  integer $comment_depth The depth of the comment in the thread
 * @return array                  The updated list of comment actions
 */
function tfw_comment_actions ( $actions, $location, $comment, $comment_depth ) {

	// Get the post ID for the current comment
	$post_id = $comment->comment_post_ID;
	if( ! $post_id ) {
		return $actions;
	}

	// Only add the top comment actions if this is the approved post dropdown
	if ( 'dropdown' == $location ) {

		//  Make sure we're dealing with an array here
		$actions = (array) $actions;

		// Check if this comment is already marked as the top comment
		$top_comment = get_comment_meta( $comment->comment_ID, 'top_comment', true );

		// Display top comment add/remove actions depending on context
		if( $top_comment && 'top' == $top_comment ) {
			$actions[] = "<a class='o2-comment-top-remove o2-actions-border-top o2-warning-hover genericon genericon-close' data-comment_id='" . $comment->comment_ID . "'' href='#'>" . esc_html__( 'Top comment', 'the-final-word' ) . "</a>";
		} else {
			$actions[] = "<a class='o2-comment-top o2-actions-border-top genericon genericon-checkmark' data-comment_id='" . $comment->comment_ID . "'' href='#'>" . esc_html__( 'Top comment', 'the-final-word' ) . "</a>";
		}
	}

	// Return updated actions
	return $actions;
}
add_filter( 'o2_comment_actions', 'tfw_comment_actions', 11, 4 );

/**
 * Mark a comment as the top comment for the post - triggered via ajax
 * @return void
 */
function tfw_mark_top_comment () {

	// Check if a comment ID has been passed through the request
	if( ! isset( $_POST['comment_id'] ) || ! $_POST['comment_id'] ) {
		exit;
	}

	//  Set default return value
	$return = 0;

	// Make sure we have a valid integer here
	$comment_id = intval( $_POST['comment_id'] );

	// If ID is non-zero, then proceed
	if( $comment_id ) {

		// Get comment object
		$comment = get_comment( $comment_id );

		// Get post ID for comment
		$post_id = $comment->comment_post_ID;

		// Remove existing top comment(s) before adding a new one - posts should only have one top comment
		$post_comments = get_comments( array( 'fields' => 'ids', 'post_id' => $post_id, 'meta_key' => 'top_comment' ) );
		if( 0 < count( $post_comments ) ) {
			foreach( $post_comments as $post_comment ) {
				delete_comment_meta( $post_comment, 'top_comment' );
			}
		}

		// Update meta for top comment and post
		update_comment_meta( $comment_id, 'top_comment', 'top' );
		update_post_meta( $post_id, 'post_top_comment', $comment_id );

		// Set comment ID as return value
		$return = $comment_id;
	}

	// Echo return value as this is an ajax request
	echo $return;

	exit;
}
add_action( 'wp_ajax_top-comment', 'tfw_mark_top_comment' );

/**
 * Remove a comment from being the top comment for a post - triggered via ajax
 * @return void
 */
function tfw_remove_top_comment () {

	// Check if a comment ID has been passed through the request
	if( ! isset( $_POST['comment_id'] ) || ! $_POST['comment_id'] ) {
		exit;
	}

	//  Set default return value
	$return = 0;

	// Make sure we have a valid integer here
	$comment_id = intval( $_POST['comment_id'] );

	// If ID is non-zero, then proceed
	if( $comment_id ) {

		// Get comment object
		$comment = get_comment( $comment_id );

		// Get post ID for comment
		$post_id = $comment->comment_post_ID;

		// Delete meta for top comment and post
		delete_comment_meta( $comment_id, 'top_comment' );
		delete_post_meta( $post_id, 'post_top_comment' );

		// Set comment ID as return value
		$return = $comment_id;
	}

	// Echo return value as this is an ajax request
	echo $return;

	exit;
}
add_action( 'wp_ajax_top-comment-remove', 'tfw_remove_top_comment' );

/**
 * Add the 'top-comment' class to the comment display on the front-end
 * @param  array   $classes    The existing list of comment classes
 * @param  string  $class      The current class
 * @param  integer $comment_id The ID of the current comment
 * @param  object  $comment    The WP_Comment object for the current comment
 * @param  integer $post_id    The ID of the current post
 * @return array               The updated list of comment classes
 */
function tfw_comment_class ( $classes, $class, $comment_id, $comment, $post_id ) {

	// Check if this comment is the top comment
	$top_comment = get_comment_meta( $comment_id, 'top_comment', true );

	// Add 'top-comment' class to commment class array
	if( $top_comment && 'top' == $top_comment ) {
		$classes[] = 'top-comment';
	}

	return $classes;
}
add_filter( 'comment_class', 'tfw_comment_class', 10, 5 );

/**
 * Modify the O2 post fragment
 * @param  array   $fragment The fragment data for the current post
 * @param  integer $post_id  The ID of the current post
 * @return array             The updated fragment data for the current post
 */
function tfw_o2_post_fragment ( $fragment, $post_id ) {

	// Get the comment ID of the top comment for the post
	$post_top_comment = intval( get_post_meta( $post_id, 'post_top_comment', true ) );

	// If we have a valid commment ID, then continue
	if( $post_top_comment ) {

		// Get the top comment object
		$top_comment = get_comment( $post_top_comment );

		// Modify the top comment ID so that it will actually display (duplicate IDs are ignore when generating the thread)
		$top_comment->comment_ID = 'display-top';

		// Set the date to 1 Jjanuary 1970 to ensure that top comment display at the opt of the list
		$top_comment->comment_date = '1970-01-01 00:00:00';
		$top_comment->comment_date_gmt = '1970-01-01 00:00:00';

		// Get the comment fragment for the top comment
		$comment_fragment = o2_Fragment::get_fragment( $top_comment );

		// Add the top comment fragment to the top of the comment thread
		array_unshift( $fragment['comments'], $comment_fragment );
	}

	// Return the post fragment
	return $fragment;
}
add_filter( 'o2_post_fragment', 'tfw_o2_post_fragment', 100, 2 );

/**
 * Modify the O2 comment fragment
 * @param  array   $fragment    The fragment data for the current comment
 * @param  integer $comment_id  The ID of the current comment
 * @return array                The updated fragment data for the current comment
 */
function tfw_o2_comment_fragment( $fragment, $comment_id ) {

	// Check if this is the top comment set to display at the top of the thread
	if( 'display-top' == $comment_id ) {

		// Add the 'top-comment' class to the comment container
		$fragment['cssClasses'] .= ' top-comment';

		// Get the ID of the posts' top comment
		$top_comment_id = intval( get_post_meta( $fragment['postID'], 'post_top_comment', true ) );

		// Add the 'Top comment' label with a 'View in context' link
		$comment_label = '<p class="top-comment-label">' . __( 'Top comment', 'the-final-word' ) . '<br/><a href="#comment-' . $top_comment_id . '" data-comment_anchor="comment-' . $top_comment_id . '">' . __( 'View in context', 'the-final-word' ) . '</a></p>';

		// Update the comment content to include the label
		$fragment['contentFiltered'] .= $comment_label;
	}

	// Return the comment fragment
	return $fragment;
}
add_filter( 'o2_comment_fragment', 'tfw_o2_comment_fragment', 10, 2 );