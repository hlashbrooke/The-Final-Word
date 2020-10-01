<?php
/*
 * Plugin Name: The Final Word
 * Version: 1.0.3
 * Plugin URI: https://wordpress.org/plugins/the-final-word/
 * Description: Have the final word in a comment thread by marking a chosen comment as the 'top comment'.
 * Author: Hugh Lashbrooke
 * Author URI: https://hugh.blog/
 * Requires at least: 4.7
 * Tested up to: 5.0
 *
 * Text Domain: the-final-word
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
	$ver = '1.0.1';

	// Set minified suffix as necessary
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Load plugin JS and localise
	wp_enqueue_script( 'tfw-scripts', plugins_url( 'assets/scripts' . $suffix . '.js', __FILE__ ), array( 'jquery' ), $ver, false );
	wp_localize_script( 'tfw-scripts', 'tfw', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	// Load plugin CSS
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

	// Only add the actions if the user is logged in
	if ( ! is_user_logged_in() ) {
		return $actions;
	}

	// Get the post ID for the current comment
	$post_id = $comment->comment_post_ID;
	if ( ! $post_id ) {
		return $actions;
	}

	// Only add the actions if the current user can edit the post
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $actions;
	}

	// Only add the top comment actions if this is the approved post dropdown
	if ( 'dropdown' == $location ) {

		//  Make sure we're dealing with an array here
		$actions = (array) $actions;

		// Check if this comment is already marked as the top comment
		$top_comment = get_comment_meta( $comment->comment_ID, 'top_comment', true );

		// Allow the top  comment label to be filtered
		$top_comment_label = apply_filters( 'top_comment_label', __( 'Top comment', 'the-final-word' ) );

		// Display top comment add/remove actions depending on context
		if ( $top_comment && 'top' == $top_comment ) {

			// Generate nonce for ajax request
			$nonce = wp_create_nonce( 'remove_top_comment_' . $comment->comment_ID );

			// Add action to dropdown
			$actions[] = "<a class='o2-comment-top-remove o2-comment-remove-top-answer o2-actions-border-top o2-warning-hover genericon genericon-close' data-comment_id='" . esc_attr( $comment->comment_ID ) . "' data-nonce='" . esc_attr( $nonce ) . "' href='#'>" . $top_comment_label . "</a>";
		} else {

			// Generate nonce for ajax request
			$nonce = wp_create_nonce( 'add_top_comment_' . $comment->comment_ID );

			// Add action to dropdown
			$actions[] = "<a class='o2-comment-top o2-comment-add-top-answer o2-actions-border-top genericon genericon-checkmark' data-comment_id='" . esc_attr( $comment->comment_ID ) . "' data-nonce='" . esc_attr( $nonce ) . "' href='#'>" . $top_comment_label . "</a>";
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

	// Set default return value
	$return = 0;

	// Check if a comment ID has been passed through the request
	if ( empty( $_POST['comment_id'] ) ) {
		wp_die( $return );
	}

	// Make sure we have a valid integer here
	$comment_id = intval( $_POST['comment_id'] );

	// Check the nonce before continuing
	check_ajax_referer( 'add_top_comment_' . $comment_id, 'nonce_data', true );

	// If ID is non-zero, then proceed
	if ( $comment_id ) {

		// Get comment object
		$comment = get_comment( $comment_id );

		// Get post ID for comment
		$post_id = $comment->comment_post_ID;

		// Check if current user has permissions to edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( $return );
		}

		// Remove existing top comment(s) before adding a new one - posts should only have one top comment
		$post_comments = get_comments( array( 'fields' => 'ids', 'post_id' => $post_id, 'meta_key' => 'top_comment' ) );
		if ( 0 < count( $post_comments ) ) {
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
	wp_die( $return );
}
add_action( 'wp_ajax_top-comment', 'tfw_mark_top_comment' );

/**
 * Remove a comment from being the top comment for a post - triggered via ajax
 * @return void
 */
function tfw_remove_top_comment () {

	// Set default return value
	$return = 0;

	// Check if a comment ID has been passed through the request
	if ( ! isset( $_POST['comment_id'] ) || ! $_POST['comment_id'] ) {
		wp_die( $return );
	}

	// Make sure we have a valid integer here
	$comment_id = intval( $_POST['comment_id'] );

	// Check the nonce before continuing
	check_ajax_referer( 'remove_top_comment_' . $comment_id, 'nonce_data', true );

	// If ID is non-zero, then proceed
	if ( $comment_id ) {

		// Get comment object
		$comment = get_comment( $comment_id );

		// Get post ID for comment
		$post_id = $comment->comment_post_ID;

		// Check if current user has permissions to edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( $return );
		}

		// Delete meta for top comment and post
		delete_comment_meta( $comment_id, 'top_comment' );
		delete_post_meta( $post_id, 'post_top_comment' );

		// Set comment ID as return value
		$return = $comment_id;
	}

	// Echo return value as this is an ajax request
	wp_die( $return );
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
	if ( $top_comment && 'top' == $top_comment ) {
		$classes[] = 'top-comment';
	}

	return $classes;
}
add_filter( 'comment_class', 'tfw_comment_class', 10, 5 );

/**
 * Modify the O2 post fragment - this duplicates the 'top comment' and adds it to the top of the comment thread
 * @param  array   $fragment The fragment data for the current post
 * @param  integer $post_id  The ID of the current post
 * @return array             The updated fragment data for the current post
 */
function tfw_o2_post_fragment ( $fragment, $post_id ) {

	// Get the comment ID of the top comment for the post
	$post_top_comment = intval( get_post_meta( $post_id, 'post_top_comment', true ) );

	// If we have a valid commment ID, then continue with duplicating it to the top of the thread
	if ( $post_top_comment ) {

		// Get the top comment object
		$top_comment = get_comment( $post_top_comment );

		// Check if the top comment still exists before continuing
		if ( ! is_null( $top_comment ) ) {
			// Filter the Comment fragment to use some specific details.
			add_filter( 'o2_comment_fragment', 'tfw_o2_comment_fragment', 100, 2 );

			// Get the comment fragment for the duplicated top comment using the modified data
			$comment_fragment = o2_Fragment::get_fragment( $top_comment );

			// Remove our filter so as not to affect other comments.
			remove_filter( 'o2_comment_fragment', 'tfw_o2_comment_fragment', 100, 2 );

			// Add the duplicated top comment fragment to the top of the comment thread
			array_unshift( $fragment['comments'], $comment_fragment );
		}
	}

	// Return the post fragment with the duplicated top comment added
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
	// Modify the duplicated top comment ID so that it will actually display (duplicate IDs are ignore when generating the thread)
	$fragment['id'] = 'display-top';

	// Set the date to 1970-01-01 00:00:00 to ensure it shows first
	$fragment['commentCreated'] = 0;
	$fragment['unixtime']       = 0;

	// Duplicated top comment won't display correctly for child comments, so ensuring it has no parent in this instance
	$fragment['parentID'] = 0;

	// Add the 'comment-display-top' class to the comment container
	$fragment['cssClasses'] .= ' comment-display-top';

	// Allow the top  comment label to be filtered
	$top_comment_label = apply_filters( 'top_comment_label', __( 'Top comment', 'the-final-word' ) );

	// Add the 'Top comment' label with a 'View in context' link
	$comment_label = '<p class="top-comment-label">' . $top_comment_label . '<br/><a href="#comment-' . $comment_id . '" data-comment_anchor="comment-' . $comment_id . '">' . __( 'View in context', 'the-final-word' ) . '</a></p>';

	// Update the comment content to include the label
	$fragment['contentFiltered'] .= $comment_label;

	// Return the comment fragment
	return $fragment;
}
