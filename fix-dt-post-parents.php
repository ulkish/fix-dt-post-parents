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
add_action( 'dt_push_post', 'fpp_add_post_parent', 10, 1 );
// Adds admin page to the network panel.
add_action( 'network_admin_menu', 'fpp_create_page' );
// Adds main function to the network page button.
add_action( 'admin_post_fpp_fix', 'fpp_fix_all_blogs' );

/**
 * Adds the corresponding post parent to a distributed post
 * right after being distributed by the dt_push_post action.
 *
 * @param int $post_id Post ID of the newly created post.
 * @return void
 */
function fpp_add_post_parent( $post_id ) {
	$post_parent      = get_post_meta( $post_id, 'dt_original_post_parent', true );
	$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$fixed            = get_post_meta( $post_id, 'dt_parent_fixed', true );
	$post_parent_old  = wp_get_post_parent_id( $post_id );

	// If this post has a post parent (either old or new).
	if ( ! empty( $post_parent ) || 0 !== $post_parent_old ) {
		// If this post hasn't been fixed before.
		if ( empty( $fixed ) ) {
			$starting_blog_id = get_current_blog_id();
			// Switch to the original blog.
			switch_to_blog( $original_blog_id );
			// Get the post parent slug.
			$post = get_post( $post_parent );
			$slug = $post->post_name;
			// Switch back to destination blog.
			switch_to_blog( $starting_blog_id );
			// Look for a post with that slug.
			$true_post_parent = get_page_by_path( $slug, OBJECT, 'page' );
			// If a post with that slug exists on this blog, continue.
			if ( ! empty( $true_post_parent ) ) {
				// Set its true post parent and a flag to avoid fixing it again.
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_parent' => $true_post_parent->ID,
					)
				);
				update_post_meta( $post_id, 'dt_parent_fixed', 1 );
			}
		}
	}
}

/**
 * Looks through all distributed posts for missing post parents.
 * Then switches to their original site for a post reference, finally
 * it will search for that distributed post in our current site, grab
 * the correct ID and attach it as a post parent.
 *
 * @param int $blog_id Blog ID of the blog on which the fix will run.
 * @return void
 */
function fpp_fix_post_parents( $blog_id ) {
	switch_to_blog( $blog_id );
	$starting_blog = get_current_blog_id();

	// Search through site for distributed posts.
	$args = array(
		'meta_key'       => 'dt_original_blog_id',
		'post_type'      => 'any',
		'posts_per_page' => -1,
	);

	$posts_query       = new WP_Query( $args );
	if ( $posts_query->have_posts() ) {
		$distributed_posts = $posts_query->posts;

		// Getting the original blog ID and post ID from every distributed post.
		$original_blog_and_post_ids = array();
		foreach ( $distributed_posts as $post ) {
			$unlinked = get_post_meta( $post->ID, 'dt_unlinked', true );
			// If a post without parent is found AND is still linked to its original post.
			if ( ( wp_get_post_parent_id( $post->ID ) === 0 ) && ( ! $unlinked ) ) {
				// Grab their original data and add it to an array.
				$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id' )[0];
				$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id' )[0];

				if ( ! isset( $original_blog_and_post_ids[ $original_blog_id ] ) ) {
					$original_blog_and_post_ids[ $original_blog_id ] = array();
				}

				array_push(
					$original_blog_and_post_ids[ $original_blog_id ],
					array(
						'original_post_id' => $original_post_id,
						'post_id'          => $post->ID,
					)
				);

			}
		}
		// For each dt_original_post_id found in this array, add a parent.
		$correct_post_parents = array();
		foreach ( $original_blog_and_post_ids as $blog_id => $post_id ) {
			switch_to_blog( $blog_id );
			foreach ( $post_id as $post ) {
				$post_parent_id = wp_get_post_parent_id( $post['original_post_id'] );

				if ( 0 !== $post_parent_id ) {
					array_push(
						$correct_post_parents,
						array(
							'post_id'        => $post['post_id'],
							'original_post_parent' => $post_parent_id,
						)
					);
				}
			}
		}
		// Come back to the starting blog and set the correct post parent.
		switch_to_blog( $starting_blog );
		foreach ( $correct_post_parents as $correct_post_parent ) {
			$args = array(
				'meta_key'       => 'dt_original_post_id',
				'meta_value'     => $correct_post_parent['original_post_parent'],
				'post_type'      => 'any',
				'posts_per_page' => -1,
			);

			$posts_query = new WP_Query( $args );
			if ( $posts_query->have_posts() ) {
				$distributed_posts = $posts_query->posts;
				foreach ( $distributed_posts as $post ) {
					wp_update_post(
						array(
							'ID'          => $correct_post_parent['post_id'],
							'post_parent' => $post->ID,
						)
					);
				}
			}
		}
		// Creating a notice to let the user know the fix ran.
		$notice_message = '<div class="updated notice">
		<p>Success! Your post-parent connections have been fixed.</p>
		</div>';
		set_transient( 'fpp_ran', $notice_message, 5 );
	}
}

/**
 * Goes through every blog in the site and executes the
 * main function of the plugin.
 *
 * @return void
 */
function fpp_fix_all_blogs() {
	$starting_blog = get_current_blog_id();
	$sites         = get_sites();

	foreach ( $sites as $site ) {
		fpp_fix_post_parents( $site->blog_id );
	}
	switch_to_blog( $starting_blog );
	wp_redirect( admin_url( '/network/settings.php?page=fix-dt-post-parents/' ) );
}

/**
 * Creates a network admin page.
 *
 * @return void
 */
function fpp_create_page() {
		add_submenu_page(
			'settings.php',
			'Distributor Parents Fixer',
			'Distributor Parents Fixer',
			'manage_options',
			'fix-dt-post-parents',
			'fpp_admin_page'
		);
}

/**
 * Fills the network admin page.
 *
 * @return void
 */
function fpp_admin_page() {
	$notice = get_transient( 'fpp_ran' );
	if ( $notice ) {
		echo $notice;
	}
	?>
	<div class="wrap">
		<h2>Fix Distributor Post Parents</h2>
		<p>This button will fix any missing post parent-child connections
		lost in previously distributed posts/pages.
		</p>
		<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
			<input type="hidden" name="action" value="fpp_fix">
			<input type="submit" class="button button-primary" value="Fix all blogs">
		</form>
	</div>
	<?php
}
