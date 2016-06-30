<?php

/*******************************************
* Restrict Content Meta Box
*******************************************/

function rcp_get_metabox_fields() {

	//custom meta boxes
	$rcp_prefix = 'rcp_';

	$rcp_meta_box  = array(
		'id'       => 'rcp_meta_box',
		'title'    => __( 'Restrict this content', 'rcp' ),
		'context'  => 'normal',
		'priority' => apply_filters( 'rcp_metabox_priority', 'high' ),
		'fields'   => array() // No longer used
	);

	return apply_filters( 'rcp_metabox_fields', $rcp_meta_box );
}

// Add meta box


function rcp_add_meta_boxes() {
	$rcp_meta_box = rcp_get_metabox_fields();
	$post_types   = get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' );
	$exclude      = apply_filters( 'rcp_metabox_excluded_post_types', array( 'forum', 'topic', 'reply', 'product', 'attachment' ) );

	foreach ( $post_types as $page ) {
		if( ! in_array( $page->name, $exclude ) ) {
			add_meta_box( $rcp_meta_box['id'], $rcp_meta_box['title'], 'rcp_render_meta_box', $page->name, $rcp_meta_box['context'], $rcp_meta_box['priority'] );
		}
	}
}
add_action( 'admin_menu', 'rcp_add_meta_boxes' );


// Callback function to show fields in meta box
function rcp_render_meta_box() {
	global $post;

	$rcp_meta_box = rcp_get_metabox_fields();

	// Use nonce for verification
	echo '<input type="hidden" name="rcp_meta_box" value="'. wp_create_nonce( basename( __FILE__ ) ) . '" />';

	do_action( 'rcp_metabox_fields_before' );

	include RCP_PLUGIN_DIR . 'includes/admin/metabox-view.php';

	do_action( 'rcp_metabox_fields_after' );

}

// Save data from meta box
function rcp_save_meta_data( $post_id ) {

	// verify nonce
	if ( ! isset( $_POST['rcp_meta_box'] ) || ! wp_verify_nonce( $_POST['rcp_meta_box'], basename( __FILE__ ) ) ) {
		return;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {

		return;

	}

	$is_paid     = false;
	$restrict_by = sanitize_text_field( $_POST['rcp_restrict_by'] );

	switch( $restrict_by ) {

		case 'unrestricted' :

			delete_post_meta( $post_id, 'rcp_access_level' );
			delete_post_meta( $post_id, 'rcp_subscription_level' );
			delete_post_meta( $post_id, 'rcp_user_level' );

			break;


		case 'subscription-level' :

			$level_set = sanitize_text_field( $_POST['rcp_subscription_level_any_set'] );

			switch( $level_set ) {

				case 'any' :

					update_post_meta( $post_id, 'rcp_subscription_level', 'any' );

					$levels = rcp_get_subscription_levels();
					foreach( $levels as $level ) {

						if( ! empty( $level->price ) ) {
							$is_paid = true;
							break;
						}

					}

					break;


				case 'any-paid' :

					$is_paid = true;
					update_post_meta( $post_id, 'rcp_subscription_level', 'any-paid' );

					break;

				case 'specific' :

					$levels = array_map( 'absint', $_POST[ 'rcp_subscription_level' ] );

					foreach( $levels as $level ) {

						$price = rcp_get_subscription_price( $level );
						if( ! empty( $price ) ) {
							$is_paid = true;
							break;
						}

					}

					update_post_meta( $post_id, 'rcp_subscription_level', $levels );

					break;

			}

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_access_level' );

			break;


		case 'access-level' :

			update_post_meta( $post_id, 'rcp_access_level', absint( $_POST[ 'rcp_access_level' ] ) );

			$levels = rcp_get_subscription_levels();
			foreach( $levels as $level ) {

				if( ! empty( $level->price ) ) {
					$is_paid = true;
					break;
				}

			}

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_subscription_level' );

			break;

		case 'registered-users' :

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_access_level' );

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_subscription_level' );

			$levels = rcp_get_subscription_levels();
			foreach( $levels as $level ) {

				if( ! empty( $level->price ) ) {
					$is_paid = true;
					break;
				}

			}

			break;

	}


	$show_excerpt = isset( $_POST['rcp_show_excerpt'] );
	$hide_in_feed = isset( $_POST['rcp_hide_from_feed'] );
	$user_role    = sanitize_text_field( $_POST[ 'rcp_user_level' ] );

	update_post_meta( $post_id, 'rcp_show_excerpt', $show_excerpt );
	update_post_meta( $post_id, 'rcp_hide_from_feed', $hide_in_feed );
	if ( 'unrestricted' !== $_POST['rcp_restrict_by'] ) {
		update_post_meta( $post_id, 'rcp_user_level', $user_role );
	}
	update_post_meta( $post_id, '_is_paid', $is_paid );

}
add_action( 'save_post', 'rcp_save_meta_data' );