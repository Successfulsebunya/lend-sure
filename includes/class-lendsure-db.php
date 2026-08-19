<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_DB {
    const DB_VERSION = '1.1.0';

    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'lendsure_' . $name;
    }

    public static function activate() {
        self::create_tables();
        self::seed_options();
        update_option( 'lendsure_db_version', self::DB_VERSION );
    }

    public static function maybe_upgrade() {
        if ( get_option( 'lendsure_db_version' ) !== self::DB_VERSION ) {
            self::activate();
        }
    }

    private static function seed_options() {
        add_option( 'lendsure_currency', 'UGX' );
        add_option( 'lendsure_default_interest', '20' );
        add_option( 'lendsure_default_duration_months', '1' );
        add_option( 'lendsure_grace_days', '3' );
        add_option( 'lendsure_penalty_type', 'percentage' );
        add_option( 'lendsure_penalty_value', '5' );
        add_option( 'lendsure_lender_name', get_bloginfo( 'name' ) );
        add_option( 'lendsure_lender_phone', '' );
        add_option( 'lendsure_lender_address', '' );
        add_option( 'lendsure_reminders_enabled', '1' );
        add_option( 'lendsure_reminder_email', get_option( 'admin_email' ) );
        add_option( 'lendsure_reminder_days_before', '3' );
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $borrowers = self::table( 'borrowers' );
        $loans = self::table( 'loans' );
        $payments = self::table( 'payments' );
        $transactions = self::table( 'transactions' );
        $reminders = self::table( 'reminders' );

        $sql_borrowers = "CREATE TABLE {$borrowers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(190) NOT NULL,
            phone varchar(80) DEFAULT '',
            email varchar(190) DEFAULT '',
            address text NULL,
            national_id varchar(120) DEFAULT '',
            notes text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY full_name (full_name)
        ) {$charset};";

        $sql_loans = "CREATE TABLE {$loans} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            borrower_id bigint(20) unsigned NOT NULL,
            original_principal decimal(20,2) NOT NULL DEFAULT 0,
            initial_interest decimal(20,2) NOT NULL DEFAULT 0,
            original_due_date date NOT NULL,
            current_principal decimal(20,2) NOT NULL DEFAULT 0,
            interest_rate decimal(8,4) NOT NULL DEFAULT 20,
            accrued_interest decimal(20,2) NOT NULL DEFAULT 0,
            accrued_penalty decimal(20,2) NOT NULL DEFAULT 0,
            start_date date NOT NULL,
            due_date date NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'active',
            purpose text NULL,
            terms text NULL,
            acknowledgement_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY borrower_id (borrower_id),
            KEY due_date (due_date),
            KEY status (status)
        ) {$charset};";

        $sql_payments = "CREATE TABLE {$payments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            loan_id bigint(20) unsigned NOT NULL,
            amount decimal(20,2) NOT NULL DEFAULT 0,
            interest_component decimal(20,2) NOT NULL DEFAULT 0,
            penalty_component decimal(20,2) NOT NULL DEFAULT 0,
            principal_component decimal(20,2) NOT NULL DEFAULT 0,
            payment_date date NOT NULL,
            method varchar(100) DEFAULT '',
            reference varchar(190) DEFAULT '',
            notes text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY loan_id (loan_id),
            KEY payment_date (payment_date)
        ) {$charset};";

        $sql_transactions = "CREATE TABLE {$transactions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            loan_id bigint(20) unsigned NOT NULL,
            type varchar(50) NOT NULL,
            amount decimal(20,2) NOT NULL DEFAULT 0,
            transaction_date date NOT NULL,
            note text NULL,
            meta longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY loan_id (loan_id),
            KEY type (type),
            KEY transaction_date (transaction_date)
        ) {$charset};";

        $sql_reminders = "CREATE TABLE {$reminders} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            loan_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL,
            recipient varchar(190) DEFAULT '',
            status varchar(30) NOT NULL DEFAULT 'sent',
            message longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY loan_id (loan_id),
            KEY type (type),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta( $sql_borrowers );
        dbDelta( $sql_loans );
        dbDelta( $sql_payments );
        dbDelta( $sql_transactions );
        dbDelta( $sql_reminders );
    }
}
