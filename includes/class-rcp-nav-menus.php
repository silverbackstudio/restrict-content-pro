<?php

class RCP_Nav_Menus {
	
	public $member;
	public $subscription_levels;

	public function __construct() {
		
		$this->subscription_levels = rcp_get_subscription_levels();

		if( is_admin() ) {
			$this->admin_init();
		}

		$this->init();
	}

	public function admin_init() {
		add_action( 'admin_footer', array( $this, 'admin_scripts' ) );
	}

	public function init() {

		$this->member = new RCP_Member( get_current_user_id() );

		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_objects' ), 10, 2 );
	}

	public function admin_scripts() {
?>
		<script type="text/javascript">

			jQuery(document).ready(function($) {

				var html = '';
				var description = '<?php _e( "Select the subscription levels that can view this menu item.", "rcp" ); ?>';
<?php

				foreach( $this->subscription_levels as $level ) :
?>
					html = html + '<label><input type="checkbox" name="rcp-menu-item-IDHERE[]" value="1"/>&nbsp;<?php echo $level->name; ?></label>&nbsp;';
<?php
				endforeach;
?>

				$('#menu-to-edit .menu-item').each(function() {

					var id = $(this).attr( 'id' ), fields = '';

					id = id.replace( 'menu-item-', '' );
					html = html.replace( 'IDHERE', id );
					fields = '<p>' + description + '</p>';
					fields = fields + '<p>' + html + '</p>';

					$(this).find('.menu-item-actions').prepend( fields );
				});
			});
		</script>
<?php
	}

	public function filter_nav_menu_objects( $menu_items, $args ) {

		//echo '<pre>'; print_r( $menu_items ); echo '</pre>';
		
		foreach( $menu_items as $key => $item ) {

			if( ! $this->should_show( $item ) ) {
				unset( $menu_items[ $key ] );
			}

		}

		return $menu_items;
	}

	public function should_show( $item ) {
		return $this->member->is_active();
	}
}

function rcp_setup_nav_menus() {
	$rcp_nav_menus = new RCP_Nav_Menus;
}
add_action( 'plugins_loaded', 'rcp_setup_nav_menus', 100 );