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

// Adds fix to the Distributor plugin.
add_action( 'dt_push_post', 'fpp_add_post_parent', 10, 3 );
// Adds function to store the original post meta ID.
add_action( 'dt_push_post_args', 'fpp_store_post_parent', 10, 2 );

/**
 * Adds the corresponding post parent to a distributed post
 * right after being distributed by the dt_push_post action.
 *
 * @param int   $post_id The newly created post ID.
 * @param int   $original_post_id The original post ID.
 * @param array $args The arguments passed into wp_insert_post.
 * @return void
 */
function fpp_add_post_parent( $post_id, $original_post_id, $args ) {

	// If this is  a new post and exists, continue.
	if ( ! empty( $args['remote_post_id'] ) && get_post( $args['remote_post_id'] ) ) {

		$post_parent = get_post_meta( $post_id, 'dt_original_post_parent', true );
		if ( ! empty( $post_parent ) ) {
			// Search for that post parent on this blog.
			$args = array(
				'meta_key'       => 'dt_original_post_id',
				'meta_value'     => $post_parent,
				'post_type'      => 'any',
				'posts_per_page' => -1,
			);
			$post_query = new WP_Query( $args );
			// If found, set it as the local post parent.
			if ( $post_query->have_posts() ) {
				$local_parent = $post_query->posts[0]->ID;
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_parent' => $local_parent,
					)
				);
			}
		}
	}
}

/**
 * Stores the original post parent before it's broken by Distributor.
 *
 * @param array  $post_body The request body to be send.
 * @param object $post WP_Post being distributed.
 * @return array $post_body The request body to be send.
 */
function fpp_store_post_parent( $post_body, $post ) {

	// If we're updating a post instead of posting, continue.
	if ( isset( $post_body['ID'] ) ) {
		$existing_parent          = wp_get_post_parent_id( $post_body['ID'] );
		$post_body['post_parent'] = $existing_parent;
	}

	return $post_body;
}

