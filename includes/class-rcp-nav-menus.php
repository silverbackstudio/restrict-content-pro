<?php

class RCP_Nav_Menus {

	public $member;
	public $subscription_levels;
	protected $menu_restrictions;

	public function __construct() {

		$this->subscription_levels = rcp_get_subscription_levels();

		if( is_admin() ) {
			$this->admin_init();
		}

		$this->init();
	}

	public function admin_init() {
		add_action( 'admin_footer', array( $this, 'admin_scripts' ) );
		add_action( 'save_post_nav_menu_item', array( $this, 'save_post_nav_menu_item'), 10, 3 );
	}

	public function init() {

		$this->menu_restrictions = $this->get_menu_restrictions();

		$this->member = new RCP_Member( get_current_user_id() );

		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_objects' ), 10, 2 );
	}

	public function admin_scripts() {

		if ( 'nav-menus' !== get_current_screen()->id  || empty( $this->subscription_levels ) ) {
			return;
		}

		wp_enqueue_script( 'underscore' );

?>
		<script type="text/javascript">

			jQuery(document).ready(function($) {

				var html = '';
				var levels = {};
				var description = '<?php _e( "Select the subscription levels that can view this menu item.", "rcp" ); ?>';
				// nonce
				$('<input type="hidden" name="rcp-nav-menu-nonce" value="<?php echo wp_create_nonce( "rcp-nav-menu-nonce" ); ?>" />').insertAfter('#menu-to-edit');
<?php

				foreach( $this->subscription_levels as $level ) :
					$level_id = absint( $level->id );
?>
					levels[<?php echo $level_id; ?>] = '<?php echo esc_html( $level->name ); ?>';
<?php
				endforeach;
?>

				$('#menu-to-edit .menu-item').each(function() {

					var id = $(this).attr( 'id' ), fields = '';

					var menu_item_id = id.split('-').pop();

					var html = build_inputs(menu_item_id);

					fields = '<p>' + description + '</p>';
					fields = fields + '<p>' + html + '</p>';

					$(this).find('.menu-item-actions').prepend( fields );
				});

				function build_inputs(menu_item_id) {
					var html = '';
					_.each( levels, function(value, key) {
						<?php
						// $saved_levels = get_post_meta( $key, 'rcp_nav_menu_item_levels_required' ); ?>
						html = html + '<label><input type="checkbox" id="rcp-nav-menu-item-'+key+'" name="rcp-nav-menu-item['+menu_item_id+']['+key+']" value="1" />'+value+'</label><br>';
					});
					return html;
				}
			});
		</script>
<?php
	}

	protected function get_menu_restrictions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'rcp_nav_menu_item_levels_required'", ARRAY_A );
	}

	public function filter_nav_menu_objects( $menu_items, $args ) {

		foreach( $menu_items as $key => $item ) {
			if( ! $this->should_show( $item->object_id ) ) {
				unset( $menu_items[ $key ] );
			}
		}

		return $menu_items;
	}

	public function should_show( $object_id ) {
		return $this->member->can_access( $object_id );
	}

	public function save_post_nav_menu_item( $post_id, $post, $update ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( empty( $_POST['rcp-nav-menu-nonce'] ) || ! wp_verify_nonce( $_POST['rcp-nav-menu-nonce'], 'rcp-nav-menu-nonce' ) ) {
			return;
		}

		if ( empty( $_POST['rcp-nav-menu-item'] ) ) {
			// @todo delete existing configs
			return;
		}

		foreach( $_POST['rcp-nav-menu-item'] as $key => $menu_item ) {
			$levels = array_keys( array_map( 'absint', $menu_item ) );
			update_post_meta( $key, 'rcp_nav_menu_item_levels_required', $levels );
		}
	}
}

function rcp_setup_nav_menus() {
	$rcp_nav_menus = new RCP_Nav_Menus;
}
add_action( 'plugins_loaded', 'rcp_setup_nav_menus', 100 );