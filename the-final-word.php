<?php
/*
 * Plugin Name: The Final Word
 * Version: 1.0
 * Plugin URI: https://hugh.blog/
 * Description: Have the final word in a comment thread by marking a chosen comment as the 'top comment'.
 * Author: Hugh Lashbrooke
 * Author URI: https://hugh.blog/
 * Requires at least: 4.7
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

function tfw_enqueue_scripts () {

	$ver = '1.0';

	wp_enqueue_script( 'tfw-scripts', plugins_url( 'assets/scripts.js', __FILE__ ), array( 'jquery' ), $ver, false );
	wp_localize_script( 'tfw-scripts', 'tfw', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	wp_enqueue_style( 'tfw-css', plugins_url( 'assets/style.css', __FILE__ ), false, $ver, 'all' );
}
add_action( 'wp_enqueue_scripts', 'tfw_enqueue_scripts' );

function tfw_comment_actions ( $actions, $location, $comment, $comment_depth ) {

	$post_id = $comment->comment_post_ID;
	if( ! $post_id ) {
		return $actions;
	}

	if ( 'dropdown' === $location ) {
		$actions = (array) $actions;

		$top_comment = get_comment_meta( $comment->comment_ID, 'top_comment', true );

		if( $top_comment && 'top' == $top_comment ) {
			$actions[] = "<a class='o2-comment-top-remove o2-actions-border-top o2-warning-hover genericon genericon-close' data-comment_id='" . $comment->comment_ID . "'' href='#'>" . esc_html__( 'Top comment', 'the-final-word' ) . "</a>";
		} else {
			$actions[] = "<a class='o2-comment-top o2-actions-border-top genericon genericon-checkmark' data-comment_id='" . $comment->comment_ID . "'' href='#'>" . esc_html__( 'Top comment', 'the-final-word' ) . "</a>";
		}
	}

	return $actions;
}
add_filter( 'o2_comment_actions', 'tfw_comment_actions', 11, 4 );

function tfw_mark_top_comment () {
	if( ! isset( $_POST['comment_id'] ) || ! $_POST['comment_id'] ) {
		exit;
	}

	$return = 0;

	$comment_id = intval( $_POST['comment_id'] );

	if( $comment_id ) {

		// Get comment object
		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;

		// Remove existing top comment(s)
		$post_comments = get_comments( array( 'fields' => 'ids', 'post_id' => $post_id, 'meta_key' => 'top_comment' ) );
		if( 0 < count( $post_comments ) ) {
			foreach( $post_comments as $post_comment ) {
				delete_comment_meta( $post_comment, 'top_comment' );
			}
		}

		// Update meta for top comment and post
		update_comment_meta( $comment_id, 'top_comment', 'top' );
		update_post_meta( $post_id, 'post_top_comment', $comment_id );

		$return = $comment_id;
	}

	echo $return;

	exit;
}
add_action( 'wp_ajax_top-comment', 'tfw_mark_top_comment' );

function tfw_remove_top_comment () {

	if( ! isset( $_POST['comment_id'] ) || ! $_POST['comment_id'] ) {
		exit;
	}

	$return = 0;

	$comment_id = intval( $_POST['comment_id'] );

	if( $comment_id ) {

		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;

		delete_comment_meta( $comment_id, 'top_comment' );
		delete_post_meta( $post_id, 'post_top_comment' );

		$return = $comment_id;
	}

	echo $return;

	exit;
}
add_action( 'wp_ajax_top-comment-remove', 'tfw_remove_top_comment' );

function tfw_comment_class ( $classes, $class, $comment_id, $comment, $post_id ) {
	$top_comment = get_comment_meta( $comment_id, 'top_comment', true );

	if( $top_comment && 'top' == $top_comment ) {
		$classes[] = 'top-comment';
	}

	return $classes;
}
add_filter( 'comment_class', 'tfw_comment_class', 10, 5 );

function tfw_o2_post_fragment ( $fragment, $post_id ) {

	$post_top_comment = intval( get_post_meta( $post_id, 'post_top_comment', true ) );
	if( $post_top_comment ) {
		$top_comment = get_comment( $post_top_comment );
		$top_comment->comment_ID = 'display-top';
		$top_comment->comment_date = '1970-01-01 00:00:00';
		$top_comment->comment_date_gmt = '1970-01-01 00:00:00';
		$comment_fragment = o2_Fragment::get_fragment( $top_comment );
		array_unshift( $fragment['comments'], $comment_fragment );
	}

	return $fragment;
}
add_filter( 'o2_post_fragment', 'tfw_o2_post_fragment', 100, 2 );

function tfw_o2_comment_fragment( $fragment, $comment_id ) {

	if( 'display-top' == $comment_id ) {
		$fragment['cssClasses'] .= ' top-comment';

		$top_comment_id = intval( get_post_meta( $fragment['postID'], 'post_top_comment', true ) );
		$permalink = '#comment-' . $top_comment_id;

		$comment_label = '<p class="top-comment-label">' . __( 'Top comment', 'the-final-word' ) . '<br/><a href="' . $permalink . '" data-comment_anchor="comment-' . $top_comment_id . '">' . __( 'View in context', 'the-final-word' ) . '</a></p>';
		$fragment['contentFiltered'] .= $comment_label;
	}

	return $fragment;
}
add_filter( 'o2_comment_fragment', 'tfw_o2_comment_fragment', 10, 2 );