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

add_action( 'dt_push_post', 'fpp_add_post_parent', 10, 1 );
/**
 * Adds the corresponding post parent to a distributed post
 * right after being distributed.
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
