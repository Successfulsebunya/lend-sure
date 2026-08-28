<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_DB {
    const DB_VERSION = '1.3.0';
    const CACHE_GROUP = 'lend_sure';

    private static $tables = array(
        'borrowers',
        'loans',
        'payments',
        'transactions',
        'reminders',
    );

    public static function table( $name ) {
        global $wpdb;

        $name = sanitize_key( $name );
        if ( ! in_array( $name, self::$tables, true ) ) {
            return '';
        }

        return $wpdb->prefix . 'lendsure_' . $name;
    }

    public static function activate() {
        $previous_version = get_option( 'lendsure_db_version', '' );
        self::create_tables();
        self::seed_options();
        self::migrate( $previous_version );
        update_option( 'lendsure_db_version', self::DB_VERSION );
        self::flush_cache();
    }

    public static function maybe_upgrade() {
        if ( get_option( 'lendsure_db_version' ) !== self::DB_VERSION ) {
            self::activate();
        }
    }

    private static function migrate( $previous_version ) {
        if ( $previous_version && version_compare( $previous_version, '1.2.0', '<' ) ) {
            global $wpdb;

            $type  = 'fixed' === get_option( 'lendsure_penalty_type', 'percentage' ) ? 'fixed' : 'percentage';
            $value = max( 0, (float) get_option( 'lendsure_penalty_value', 5 ) );
            $table = self::table( 'loans' );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration write; activate() flushes the Your Loan Ledger object cache after migration.
            $wpdb->query(
                $wpdb->prepare(
                    'UPDATE %i SET penalty_type = %s, penalty_value = %f',
                    $table,
                    $type,
                    $value
                )
            );
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
        add_option( 'lendsure_company_name', get_bloginfo( 'name' ) );
        add_option( 'lendsure_company_details', '' );
        add_option( 'lendsure_company_logo_id', '0' );
        add_option( 'lendsure_reminders_enabled', '1' );
        add_option( 'lendsure_reminder_email', get_option( 'admin_email' ) );
        add_option( 'lendsure_reminder_days_before', '3' );
    }

    public static function flush_cache() {
        wp_cache_flush_group( self::CACHE_GROUP );
    }

    private static function cached_query( $cache_key, $callback ) {
        $found  = false;
        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

        if ( $found ) {
            return $cached;
        }

        $value = call_user_func( $callback );
        wp_cache_set( $cache_key, $value, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $value;
    }

    public static function get_loan( $loan_id ) {
        global $wpdb;

        $loan_id = absint( $loan_id );
        if ( ! $loan_id ) {
            return null;
        }

        $loans     = self::table( 'loans' );
        $borrowers = self::table( 'borrowers' );
        $cache_key = 'loan_' . $loan_id;

        return self::cached_query(
            $cache_key,
            static function () use ( $wpdb, $loans, $borrowers, $loan_id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                return $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT l.*, b.full_name, b.phone, b.email, b.address, b.national_id
                        FROM %i l
                        INNER JOIN %i b ON b.id = l.borrower_id
                        WHERE l.id = %d',
                        $loans,
                        $borrowers,
                        $loan_id
                    )
                );
            }
        );
    }

    public static function get_due_loans( $limit = 100 ) {
        global $wpdb;

        $limit     = max( 1, absint( $limit ) );
        $loans     = self::table( 'loans' );
        $borrowers = self::table( 'borrowers' );
        $cache_key = 'due_loans_' . $limit;

        return self::cached_query(
            $cache_key,
            static function () use ( $wpdb, $loans, $borrowers, $limit ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT l.*, b.full_name, b.phone, b.email
                        FROM %i l
                        INNER JOIN %i b ON b.id = l.borrower_id
                        WHERE l.status = 'active'
                        ORDER BY l.due_date ASC
                        LIMIT %d",
                        $loans,
                        $borrowers,
                        $limit
                    )
                );
            }
        );
    }

    public static function get_dashboard_totals() {
        global $wpdb;

        $loans = self::table( 'loans' );

        return self::cached_query(
            'dashboard_totals',
            static function () use ( $wpdb, $loans ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT COUNT(*) AS active_count,
                            COALESCE(SUM(current_principal), 0) AS principal,
                            COALESCE(SUM(accrued_interest), 0) AS interest,
                            COALESCE(SUM(accrued_penalty), 0) AS penalty,
                            COALESCE(SUM(current_principal + accrued_interest + accrued_penalty), 0) AS expected_total
                        FROM %i
                        WHERE status = 'active'",
                        $loans
                    )
                );
            }
        );
    }

    public static function get_borrower( $borrower_id ) {
        global $wpdb;

        $borrower_id = absint( $borrower_id );
        if ( ! $borrower_id ) {
            return null;
        }

        $borrowers = self::table( 'borrowers' );

        return self::cached_query(
            'borrower_' . $borrower_id,
            static function () use ( $wpdb, $borrowers, $borrower_id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT * FROM %i WHERE id = %d',
                        $borrowers,
                        $borrower_id
                    )
                );
            }
        );
    }

    public static function get_borrowers( $for_select = false ) {
        global $wpdb;

        $borrowers = self::table( 'borrowers' );
        $cache_key = $for_select ? 'borrowers_select' : 'borrowers_all';

        return self::cached_query(
            $cache_key,
            static function () use ( $wpdb, $borrowers, $for_select ) {
                if ( $for_select ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                    return $wpdb->get_results(
                        $wpdb->prepare(
                            'SELECT id, full_name, phone FROM %i ORDER BY full_name ASC',
                            $borrowers
                        )
                    );
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT * FROM %i ORDER BY full_name ASC',
                        $borrowers
                    )
                );
            }
        );
    }

    public static function get_loans() {
        global $wpdb;

        $loans     = self::table( 'loans' );
        $borrowers = self::table( 'borrowers' );

        return self::cached_query(
            'loans_all',
            static function () use ( $wpdb, $loans, $borrowers ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT l.*, b.full_name FROM %i l INNER JOIN %i b ON b.id = l.borrower_id ORDER BY l.id DESC',
                        $loans,
                        $borrowers
                    )
                );
            }
        );
    }

    public static function get_payments( $loan_id ) {
        global $wpdb;

        $loan_id  = absint( $loan_id );
        $payments = self::table( 'payments' );

        return self::cached_query(
            'payments_' . $loan_id,
            static function () use ( $wpdb, $payments, $loan_id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT * FROM %i WHERE loan_id = %d ORDER BY payment_date DESC, id DESC',
                        $payments,
                        $loan_id
                    )
                );
            }
        );
    }

    public static function get_transactions( $loan_id ) {
        global $wpdb;

        $loan_id      = absint( $loan_id );
        $transactions = self::table( 'transactions' );

        return self::cached_query(
            'transactions_' . $loan_id,
            static function () use ( $wpdb, $transactions, $loan_id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT * FROM %i WHERE loan_id = %d ORDER BY transaction_date DESC, id DESC',
                        $transactions,
                        $loan_id
                    )
                );
            }
        );
    }

    public static function get_reminder_logs( $limit = 30 ) {
        global $wpdb;

        $limit     = max( 1, absint( $limit ) );
        $reminders = self::table( 'reminders' );

        return self::cached_query(
            'reminders_' . $limit,
            static function () use ( $wpdb, $reminders, $limit ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a plugin-owned custom table; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT * FROM %i ORDER BY id DESC LIMIT %d',
                        $reminders,
                        $limit
                    )
                );
            }
        );
    }

    public static function get_monthly_performance( $months = 12 ) {
        global $wpdb;

        $months = min( 24, max( 3, absint( $months ) ) );
        $tz     = wp_timezone();
        $now    = new DateTimeImmutable( 'now', $tz );
        $start  = $now->modify( 'first day of this month' )->modify( '-' . ( $months - 1 ) . ' months' );

        $loans    = self::table( 'loans' );
        $payments = self::table( 'payments' );

        $cache_key = 'monthly_performance_' . $months . '_' . $start->format( 'Y-m' );

        return self::cached_query(
            $cache_key,
            static function () use ( $wpdb, $loans, $payments, $months, $start, $tz ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                $loan_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(start_date, '%%Y-%%m') AS month_key,
                            COUNT(*) AS loans_count,
                            COALESCE(SUM(original_principal), 0) AS principal_issued
                        FROM %i
                        WHERE start_date >= %s
                        GROUP BY DATE_FORMAT(start_date, '%%Y-%%m')
                        ORDER BY month_key ASC",
                        $loans,
                        $start->format( 'Y-m-d' )
                    ),
                    OBJECT_K
                );

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                $payment_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(payment_date, '%%Y-%%m') AS month_key,
                            COALESCE(SUM(amount), 0) AS repayments_collected,
                            COALESCE(SUM(interest_component + penalty_component), 0) AS income_collected
                        FROM %i
                        WHERE payment_date >= %s
                        GROUP BY DATE_FORMAT(payment_date, '%%Y-%%m')
                        ORDER BY month_key ASC",
                        $payments,
                        $start->format( 'Y-m-d' )
                    ),
                    OBJECT_K
                );

                $series = array();
                for ( $i = 0; $i < $months; $i++ ) {
                    $month     = $start->modify( '+' . $i . ' months' );
                    $month_key = $month->format( 'Y-m' );
                    $issued    = isset( $loan_rows[ $month_key ] ) ? $loan_rows[ $month_key ] : null;
                    $received  = isset( $payment_rows[ $month_key ] ) ? $payment_rows[ $month_key ] : null;

                    $series[] = array(
                        'month_key'            => $month_key,
                        'label'                => wp_date( 'M Y', $month->getTimestamp(), $tz ),
                        'loans_count'          => $issued ? (int) $issued->loans_count : 0,
                        'principal_issued'     => $issued ? (float) $issued->principal_issued : 0.0,
                        'repayments_collected' => $received ? (float) $received->repayments_collected : 0.0,
                        'income_collected'     => $received ? (float) $received->income_collected : 0.0,
                    );
                }

                return $series;
            }
        );
    }

    public static function get_export_loans() {
        global $wpdb;

        $loans     = self::table( 'loans' );
        $borrowers = self::table( 'borrowers' );

        return self::cached_query(
            'export_loans',
            static function () use ( $wpdb, $loans, $borrowers ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading plugin-owned custom tables; result is object-cached.
                return $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT l.*, b.full_name, b.phone, b.email FROM %i l INNER JOIN %i b ON b.id = l.borrower_id ORDER BY l.id DESC',
                        $loans,
                        $borrowers
                    ),
                    ARRAY_A
                );
            }
        );
    }

    public static function insert( $table_name, $data, $format = null ) {
        global $wpdb;

        $table = self::table( $table_name );
        if ( ! $table ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Writing to a plugin-owned custom table through wpdb's insert API.
        $result = $wpdb->insert( $table, $data, $format );
        if ( false !== $result ) {
            self::flush_cache();
        }

        return $result;
    }

    public static function insert_id() {
        global $wpdb;
        return absint( $wpdb->insert_id );
    }

    public static function update( $table_name, $data, $where, $format = null, $where_format = null ) {
        global $wpdb;

        $table = self::table( $table_name );
        if ( ! $table ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write; the Your Loan Ledger object cache is flushed immediately after a successful update.
        $result = $wpdb->update( $table, $data, $where, $format, $where_format );
        if ( false !== $result ) {
            self::flush_cache();
        }

        return $result;
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $borrowers    = self::table( 'borrowers' );
        $loans        = self::table( 'loans' );
        $payments     = self::table( 'payments' );
        $transactions = self::table( 'transactions' );
        $reminders    = self::table( 'reminders' );

        $sql_borrowers = "CREATE TABLE {$borrowers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(190) NOT NULL,
            phone varchar(80) DEFAULT '',
            email varchar(190) DEFAULT '',
            address text NULL,
            national_id varchar(120) DEFAULT '',
            notes text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NULL,
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
            penalty_type varchar(20) NOT NULL DEFAULT 'percentage',
            penalty_value decimal(20,4) NOT NULL DEFAULT 5,
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
