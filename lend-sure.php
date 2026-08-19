<?php
/**
 * Plugin Name: Lend Sure
 * Description: Lightweight personal loan tracking for WordPress with 20% monthly interest, partial payments, extensions, penalties, transaction history, and signed loan acknowledgement records.
 * Version: 1.1.0
 * Author: MoCursor
 * License: GPL-2.0-or-later
 * Text Domain: lend-sure
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LENDSURE_VERSION', '1.1.0' );
define( 'LENDSURE_FILE', __FILE__ );
define( 'LENDSURE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LENDSURE_URL', plugin_dir_url( __FILE__ ) );

require_once LENDSURE_DIR . 'includes/class-lendsure-db.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-calculator.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-reminders.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-admin.php';

register_activation_hook( __FILE__, array( 'LendSure_DB', 'activate' ) );
register_activation_hook( __FILE__, array( 'LendSure_Reminders', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LendSure_Reminders', 'deactivate' ) );

function lendsure_boot() {
    LendSure_DB::maybe_upgrade();
    LendSure_Reminders::instance();
    LendSure_Reminders::activate();
    LendSure_Admin::instance();
}
add_action( 'plugins_loaded', 'lendsure_boot' );
