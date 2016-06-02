<?php
/**
 * Upgrade class
 *
 * This class handles database upgrade routines between versions
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.6
 */
class RCP_Upgrades {

	private $upgraded = false;

	public function __construct() {

		add_action( 'admin_init', array( $this, 'init' ), -9999 );

	}

	public function init() {

		$version = get_option( 'rcp_version' );

		$this->v26_upgrades();

		// If upgrades have occurred
		if ( $this->upgraded ) {
			update_option( 'rcp_version_upgraded_from', $version );
			update_option( 'rcp_version', RCP_PLUGIN_VERSION );
		}

	}

	private function v26_upgrades() {

		global $wpdb, $rcp_payments_db_version, $rcp_payments_db_name, $rcp_db_name, $rcp_db_version;

		$payment_db_version = get_option( 'rcp_payments_db_version' );
		$sub_db_version     = get_option( 'rcp_db_version' );

		if( version_compare( $rcp_payments_db_version, $payment_db_version, '>' ) ) {

			$wpdb->query(
				"ALTER TABLE " . $rcp_payments_db_name . "
				MODIFY `subscription` VARCHAR(200);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_payments_db_name . "
				MODIFY `subscription_key` VARCHAR(32);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_payments_db_name . "
				MODIFY `status` VARCHAR(12);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_payments_db_name . "
				MODIFY `transaction_id` VARCHAR(64);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_payments_db_name . "
				ADD KEY (subscription, user_id, subscription_key, transaction_id, status);"
			);

			update_option( 'rcp_payments_db_version', $rcp_payments_db_version );

			$this->upgraded = true;

		}

		if( version_compare( $rcp_db_version, $sub_db_version, '>' ) ) {

			$wpdb->query(
				"ALTER TABLE " . $rcp_db_name . "
				MODIFY `name` VARCHAR(200);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_db_name . "
				MODIFY `status` VARCHAR(12);"
			);

			$wpdb->query(
				"ALTER TABLE " . $rcp_db_name . "
				ADD KEY (name, status);"
			);

			update_option( 'rcp_db_version', $rcp_db_version );

			$this->upgraded = true;

		}
	}

}
new RCP_Upgrades;