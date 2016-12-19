<?php
/**
 * Note to developers: this is a temporary solution until https://core.trac.wordpress.org/ticket/18584 lands in core.
 * Do not rely on this class to exist in this same format in future versions of RCP.
 */

final class RCP_Nav_Menus {

	private $member;
	private $subscription_levels;
	private $member_subscription_id;

	public function __construct() {

		$this->subscription_levels = rcp_get_subscription_levels();

		if( is_admin() ) {
			$this->admin_init();
		}

		$this->init();
	}

	public function admin_init() {
		add_filter( 'wp_edit_nav_menu_walker', array( $this, 'override_walker_nav_menu_edit' ) );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_menu_item_restrictions' ), 10, 3 );
	}

	public function override_walker_nav_menu_edit() {
		return 'RCP_Walker_Nav_Menu_Edit';
	}

	public function init() {

		$this->member = new RCP_Member( get_current_user_id() );
		$this->member_subscription_id = $this->member->get_subscription_id();

		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_objects' ), 10, 2 );
	}

	/**
	 * Removes nav menu items from the menu if the user doesn't have access.
	 */
	public function filter_nav_menu_objects( $menu_items, $args ) {

		if ( current_user_can( 'manage_options' ) ) {
			return $menu_items;
		}

		foreach( $menu_items as $key => $item ) {

			$saved_levels = (array) get_post_meta( $item->ID, 'rcp_nav_menu_item_levels_required', true );

			if ( ! empty( $saved_levels[0] ) && ! in_array( $this->member_subscription_id, $saved_levels ) ) {
				unset( $menu_items[ $key ] );
				continue;
			}

			if( ! $this->member->can_access( $item->object_id ) ) {
				unset( $menu_items[ $key ] );
			}
		}

		return $menu_items;
	}

	/**
	 * Saves the nav menu item restrictions.
	 */
	public function save_menu_item_restrictions( $menu_id, $menu_item_db_id, $args ) {

		if ( empty( $_POST['rcp-nav-menu-nonce'] ) || ! wp_verify_nonce( $_POST['rcp-nav-menu-nonce'], 'rcp-nav-menu-nonce' ) ) {
			return;
		}

		if ( empty( $_POST['rcp-nav-menu-item'][$menu_item_db_id] ) ) {
			delete_post_meta( $menu_item_db_id, 'rcp_nav_menu_item_levels_required' );
			return;
		}

		$levels = array_keys( array_map( 'absint', $_POST['rcp-nav-menu-item'][$menu_item_db_id] ) );

		update_post_meta( $menu_item_db_id, 'rcp_nav_menu_item_levels_required', $levels );
	}

	public function get_subscription_levels() {
		return $this->subscription_levels;
	}
}

/**
 * Loads the RCP_Nav_Menus class.
 *
 * @since 2.7
 */
function rcp_setup_nav_menus() {

	if ( version_compare( get_bloginfo( 'version' ), 4.7, '<' ) ) {
		return;
	}

	global $rcp_nav_menus;
	$rcp_nav_menus = new RCP_Nav_Menus;
}
add_action( 'plugins_loaded', 'rcp_setup_nav_menus', 100 );

/**
 * Loads the RCP_Walker_Nav_Menu_Edit class to add
 * custom controls to the menu edit screen.
 *
 * @since 2.7
 */
function rcp_load_walker_nav_menu_edit() {

	if ( version_compare( get_bloginfo( 'version' ), 4.7, '<' ) ) {
		return;
	}

	if ( is_admin() ) {
		require_once RCP_PLUGIN_DIR . 'includes/class-rcp-walker-nav-menu-edit.php';
	}
}
add_action( 'admin_init', 'rcp_load_walker_nav_menu_edit' );