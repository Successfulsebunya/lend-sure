<?php
/**
 * Plugin Name: Your Loan Ledger
 * Description: Lightweight personal loan tracking for WordPress with 20% monthly interest, partial payments, extensions, penalties, transaction history, and signed loan acknowledgement records.
 * Version: 1.4.0
 * Plugin URI: https://github.com/Successfulsebunya/lend-sure
 * Author: Moses Cursor
 * Author URI: https://mosescursor.com
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: your-loan-ledger
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LENDSURE_VERSION', '1.4.0' );
define( 'LENDSURE_FILE', __FILE__ );
define( 'LENDSURE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LENDSURE_URL', plugin_dir_url( __FILE__ ) );

require_once LENDSURE_DIR . 'includes/class-lendsure-db.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-calculator.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-reminders.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-reports.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-management.php';
require_once LENDSURE_DIR . 'includes/class-lendsure-admin.php';

register_activation_hook( __FILE__, array( 'LendSure_DB', 'activate' ) );
register_activation_hook( __FILE__, array( 'LendSure_Reminders', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LendSure_Reminders', 'deactivate' ) );

function lendsure_boot() {
    LendSure_DB::maybe_upgrade();
    LendSure_Reminders::instance();
    LendSure_Reminders::activate();
    LendSure_Reports::instance();
    LendSure_Management::instance();
    LendSure_Admin::instance();
}
add_action( 'plugins_loaded', 'lendsure_boot' );
