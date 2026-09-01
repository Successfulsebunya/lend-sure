<?php
/**
 * Your Loan Ledger uninstall handler.
 *
 * Data is preserved by default. Database deletion occurs only when an
 * administrator explicitly enables the uninstall cleanup option in Tools.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( '1' !== get_option( 'lendsure_delete_data_on_uninstall', '0' ) ) {
    return;
}

require_once __DIR__ . '/includes/class-lendsure-db.php';
require_once __DIR__ . '/includes/class-lendsure-management.php';

LendSure_Management::delete_all_data( false );
