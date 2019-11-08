<?php
/**
 * Plugin Name:       Fix Distributor Post Parents
 * Plugin URI:        https://tipit.net/
 * Description:       Fixes post parents connections on distributed posts.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hugo Moran
 * Author URI:        https://tipit.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html

 * Fix Distributor Post Parents is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.

 * Fix Distributor Post Parents is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Fix Distributor Post Parents. If not, see https://www.gnu.org/licenses/gpl-2.0.html

 * @package Fix Distributor Post Parents
 */

/**
 * Adds the corresponding post parent to a distributed post
 * right after being distributed by the dt_push_post action.
 *
 * @param int $post_id Post ID of the newly created post.
 * @return void
 */
function fpp_add_post_parent( $post_id ) {
	$post_parent = get_post_meta( $post_id, 'dt_original_post_parent', true );
	if ( ! empty( $post_parent ) ) {
		$args = array(
			'meta_key'       => 'dt_original_post_id',
			'meta_value'     => $post_parent,
			'post_type'      => 'any',
			'posts_per_page' => -1,
		);

		$query            = new WP_Query( $args );
		$distributed_post = $query->posts[0];

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_parent' => $distributed_post->ID,
			)
		);
	}
}
// add_action( 'dt_push_post', 'fpp_add_post_parent', 10, 1 );

/**
 * Looks through all distributed posts for missing post parents.
 * Then switches to their original site for a post reference, finally
 * it will search for that distributed post in our current site, grab
 * the correct ID and attach it as a post parent.
 *
 * @return void
 */
function fpp_fix_post_parents() {
	$starting_blog = get_current_blog_id();

	// Search through site for distributed posts.
	$args = array(
		'meta_key'       => 'dt_original_blog_id',
		'post_type'      => 'any',
		'posts_per_page' => -1,
	);

	$posts_query       = new WP_Query( $args );
	$distributed_posts = $posts_query->posts;

	// Getting the original blog ID and post ID from every distributed post.
	$og_blog_and_post_ids = array();
	foreach ( $distributed_posts as $post ) {
		// If a post without parent is found.
		if ( wp_get_post_parent_id( $post->ID ) === 0 ) {
			// Grab their original data and add it to an array.
			$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id' )[0];
			$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id' )[0];

			if ( ! isset( $og_blog_and_post_ids[ $original_blog_id ] ) ) {
				$og_blog_and_post_ids[ $original_blog_id ] = array();
			}

			array_push(
				$og_blog_and_post_ids[ $original_blog_id ],
				array(
					'og_post_id' => $original_post_id,
					'post_id'    => $post->ID,
				)
			);

		}
	}
	// for each dt_og_post_id found in this array, add a parent.
	$correct_post_parents = array();

	foreach ( $og_blog_and_post_ids as $blog_id => $post_id ) {
		switch_to_blog( $blog_id );

		foreach ( $post_id as $post ) {
			$post_parent_id = wp_get_post_parent_id( $post['og_post_id'] );

			if ( 0 !== $post_parent_id ) {
				array_push(
					$correct_post_parents,
					array(
						'post_id'          => $post['post_id'],
						'og_post_parent' => $post_parent_id,
					)
				);
			}
		}
	}
	switch_to_blog( $starting_blog );

	foreach ( $correct_post_parents as $cpp ) {

		$args = array(
			'meta_key'       => 'dt_original_post_id',
			'meta_value'     => $cpp['og_post_parent'],
			'post_type'      => 'any',
			'posts_per_page' => -1,
		);

		$posts_query       = new WP_Query( $args );
		$distributed_posts = $posts_query->posts;
		foreach ( $distributed_posts as $post ) {
			wp_update_post(
				array(
					'ID'          => $cpp['post_id'],
					'post_parent' => $post->ID,
				)
			);
		}
	}

}
add_action( 'init', 'fpp_fix_post_parents' );
