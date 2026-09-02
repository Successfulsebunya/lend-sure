<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Data management, backup/restore, and correction controls for KuLoan Ledger.
 *
 * Internal LendSure_* and lendsure_* identifiers are intentionally retained for
 * backward compatibility with installations created before the public rename.
 */
class LendSure_Management {
    const BACKUP_FORMAT = 1;

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ), 30 );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_lendsure_export_backup', array( $this, 'export_backup' ) );
        add_action( 'admin_post_lendsure_restore_backup', array( $this, 'restore_backup' ) );
        add_action( 'admin_post_lendsure_void_payment', array( $this, 'void_payment' ) );
        add_action( 'admin_post_lendsure_void_loan', array( $this, 'void_loan' ) );
        add_action( 'admin_post_lendsure_delete_loan', array( $this, 'delete_loan' ) );
        add_action( 'admin_post_lendsure_save_cleanup_policy', array( $this, 'save_cleanup_policy' ) );
        add_action( 'admin_post_lendsure_erase_all_data', array( $this, 'erase_all_data' ) );
    }

    public function menu() {
        add_submenu_page(
            'lendsure',
            __( 'Tools', 'kuloan-ledger' ),
            __( 'Tools', 'kuloan-ledger' ),
            'manage_options',
            'lendsure-tools',
            array( $this, 'tools_page' )
        );
    }

    public function assets( $hook ) {
        if ( false === strpos( (string) $hook, 'lendsure' ) ) {
            return;
        }
        wp_enqueue_script(
            'kuloan-ledger-management',
            LENDSURE_URL . 'assets/management.js',
            array(),
            LENDSURE_VERSION,
            true
        );
    }

    private function guard( $nonce_action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'kuloan-ledger' ) );
        }
        check_admin_referer( $nonce_action );
    }

    private function redirect_tools( $message, $type = 'success' ) {
        $url = add_query_arg(
            array(
                'page'       => 'lendsure-tools',
                'yll_notice' => rawurlencode( $message ),
                'yll_type'   => 'error' === $type ? 'error' : 'success',
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $url );
        exit;
    }

    private static function table_names() {
        return array( 'borrowers', 'loans', 'payments', 'transactions', 'reminders' );
    }

    private static function columns_by_table() {
        return array(
            'borrowers' => array( 'id', 'full_name', 'phone', 'email', 'address', 'national_id', 'notes', 'created_at', 'updated_at' ),
            'loans' => array( 'id', 'borrower_id', 'original_principal', 'initial_interest', 'original_due_date', 'current_principal', 'interest_rate', 'accrued_interest', 'accrued_penalty', 'penalty_type', 'penalty_value', 'start_date', 'due_date', 'status', 'purpose', 'terms', 'acknowledgement_attachment_id', 'created_at', 'updated_at' ),
            'payments' => array( 'id', 'loan_id', 'amount', 'interest_component', 'penalty_component', 'principal_component', 'payment_date', 'method', 'reference', 'notes', 'created_at' ),
            'transactions' => array( 'id', 'loan_id', 'type', 'amount', 'transaction_date', 'note', 'meta', 'created_at' ),
            'reminders' => array( 'id', 'loan_id', 'type', 'recipient', 'status', 'message', 'created_at' ),
        );
    }

    private static function option_keys() {
        return array(
            'lendsure_currency',
            'lendsure_default_interest',
            'lendsure_default_duration_months',
            'lendsure_grace_days',
            'lendsure_penalty_type',
            'lendsure_penalty_value',
            'lendsure_lender_name',
            'lendsure_lender_phone',
            'lendsure_lender_address',
            'lendsure_company_name',
            'lendsure_company_details',
            'lendsure_company_logo_id',
            'lendsure_reminders_enabled',
            'lendsure_reminder_email',
            'lendsure_reminder_days_before',
            'lendsure_delete_data_on_uninstall',
        );
    }

    private function backup_dataset() {
        global $wpdb;

        $dataset = array();
        foreach ( self::table_names() as $name ) {
            $table = LendSure_DB::table( $name );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit administrator backup of plugin-owned custom tables.
            $dataset[ $name ] = $wpdb->get_results(
                $wpdb->prepare( 'SELECT * FROM %i ORDER BY id ASC', $table ),
                ARRAY_A
            );
        }
        return $dataset;
    }

    private function backup_settings() {
        $settings = array();
        foreach ( self::option_keys() as $key ) {
            $settings[ $key ] = get_option( $key, null );
        }
        return $settings;
    }

    private function backup_media( $dataset, $settings ) {
        $ids = array();
        if ( ! empty( $settings['lendsure_company_logo_id'] ) ) {
            $ids[] = absint( $settings['lendsure_company_logo_id'] );
        }
        if ( ! empty( $dataset['loans'] ) ) {
            foreach ( $dataset['loans'] as $loan ) {
                if ( ! empty( $loan['acknowledgement_attachment_id'] ) ) {
                    $ids[] = absint( $loan['acknowledgement_attachment_id'] );
                }
            }
        }
        $ids = array_values( array_unique( array_filter( $ids ) ) );
        if ( ! $ids ) {
            return array();
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! WP_Filesystem() ) {
            return array();
        }
        global $wp_filesystem;

        $media = array();
        foreach ( $ids as $id ) {
            $path = get_attached_file( $id );
            if ( ! $path || ! $wp_filesystem->exists( $path ) || ! $wp_filesystem->is_readable( $path ) ) {
                continue;
            }
            $contents = $wp_filesystem->get_contents( $path );
            if ( false === $contents ) {
                continue;
            }
            $media[ (string) $id ] = array(
                'filename' => sanitize_file_name( wp_basename( $path ) ),
                'mime'     => (string) get_post_mime_type( $id ),
                'title'    => (string) get_the_title( $id ),
                'data'     => base64_encode( $contents ),
            );
        }
        return $media;
    }

    public function export_backup() {
        $this->guard( 'lendsure_export_backup' );

        $dataset  = $this->backup_dataset();
        $settings = $this->backup_settings();
        $payload  = array(
            'manifest' => array(
                'product'        => 'KuLoan Ledger',
                'format_version' => self::BACKUP_FORMAT,
                'plugin_version' => LENDSURE_VERSION,
                'db_version'     => get_option( 'lendsure_db_version', '' ),
                'site_url'       => home_url( '/' ),
                'exported_at'    => current_time( 'mysql' ),
            ),
            'settings' => $settings,
            'data'     => $dataset,
            'media'    => $this->backup_media( $dataset, $settings ),
        );

        $filename = 'kuloan-ledger-backup-' . wp_date( 'Y-m-d-His' ) . '.json';
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
        echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }

    private function decode_uploaded_backup() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        if ( empty( $_FILES['backup_file'] ) || ! is_array( $_FILES['backup_file'] ) ) {
            return new WP_Error( 'missing_backup', __( 'Choose a backup JSON file.', 'kuloan-ledger' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $file = $_FILES['backup_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passed to WordPress upload handling after nonce/capability checks.
        if ( ! empty( $file['error'] ) ) {
            return new WP_Error( 'upload_error', __( 'The backup file could not be uploaded.', 'kuloan-ledger' ) );
        }
        if ( ! empty( $file['size'] ) && (int) $file['size'] > 20 * MB_IN_BYTES ) {
            return new WP_Error( 'backup_too_large', __( 'The backup file is larger than the 20 MB restore limit.', 'kuloan-ledger' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload(
            $file,
            array(
                'test_form' => false,
                'mimes'     => array( 'json' => 'application/json', 'txt' => 'text/plain' ),
            )
        );
        if ( isset( $uploaded['error'] ) ) {
            return new WP_Error( 'upload_failed', sanitize_text_field( $uploaded['error'] ) );
        }

        if ( ! WP_Filesystem() ) {
            wp_delete_file( $uploaded['file'] );
            return new WP_Error( 'filesystem', __( 'WordPress could not access the uploaded backup file.', 'kuloan-ledger' ) );
        }
        global $wp_filesystem;
        $json = $wp_filesystem->get_contents( $uploaded['file'] );
        wp_delete_file( $uploaded['file'] );
        if ( false === $json ) {
            return new WP_Error( 'read_failed', __( 'The uploaded backup could not be read.', 'kuloan-ledger' ) );
        }

        $payload = json_decode( $json, true );
        if ( ! is_array( $payload ) || empty( $payload['manifest']['product'] ) || 'KuLoan Ledger' !== $payload['manifest']['product'] ) {
            return new WP_Error( 'invalid_backup', __( 'This is not a valid KuLoan Ledger backup.', 'kuloan-ledger' ) );
        }
        if ( (int) ( $payload['manifest']['format_version'] ?? 0 ) !== self::BACKUP_FORMAT ) {
            return new WP_Error( 'unsupported_backup', __( 'This backup format is not supported by this version.', 'kuloan-ledger' ) );
        }
        return $payload;
    }

    private function restore_media( $media ) {
        $map = array();
        if ( ! is_array( $media ) ) {
            return $map;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ( $media as $old_id => $item ) {
            if ( empty( $item['data'] ) || empty( $item['filename'] ) ) {
                continue;
            }
            $decoded = base64_decode( (string) $item['data'], true );
            if ( false === $decoded ) {
                continue;
            }
            $filename = sanitize_file_name( (string) $item['filename'] );
            $upload   = wp_upload_bits( $filename, null, $decoded );
            if ( ! empty( $upload['error'] ) ) {
                continue;
            }
            $attachment_id = wp_insert_attachment(
                array(
                    'post_mime_type' => sanitize_mime_type( (string) ( $item['mime'] ?? '' ) ),
                    'post_title'     => sanitize_text_field( (string) ( $item['title'] ?? $filename ) ),
                    'post_status'    => 'inherit',
                ),
                $upload['file']
            );
            if ( is_wp_error( $attachment_id ) ) {
                continue;
            }
            $metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
            wp_update_attachment_metadata( $attachment_id, $metadata );
            $map[ (string) absint( $old_id ) ] = absint( $attachment_id );
        }
        return $map;
    }

    private function filter_row( $table_name, $row ) {
        $columns = self::columns_by_table();
        if ( ! isset( $columns[ $table_name ] ) || ! is_array( $row ) ) {
            return array();
        }
        return array_intersect_key( $row, array_flip( $columns[ $table_name ] ) );
    }

    public function restore_backup() {
        $this->guard( 'lendsure_restore_backup' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $confirmation = isset( $_POST['restore_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['restore_confirmation'] ) ) : '';
        if ( 'RESTORE' !== $confirmation ) {
            $this->redirect_tools( __( 'Restore cancelled: type RESTORE exactly to confirm.', 'kuloan-ledger' ), 'error' );
        }

        $payload = $this->decode_uploaded_backup();
        if ( is_wp_error( $payload ) ) {
            $this->redirect_tools( $payload->get_error_message(), 'error' );
        }

        global $wpdb;
        $media_map = $this->restore_media( $payload['media'] ?? array() );
        $data      = is_array( $payload['data'] ?? null ) ? $payload['data'] : array();
        $settings  = is_array( $payload['settings'] ?? null ) ? $payload['settings'] : array();

        if ( isset( $settings['lendsure_company_logo_id'] ) ) {
            $old_logo = (string) absint( $settings['lendsure_company_logo_id'] );
            if ( isset( $media_map[ $old_logo ] ) ) {
                $settings['lendsure_company_logo_id'] = $media_map[ $old_logo ];
            }
        }
        if ( ! empty( $data['loans'] ) ) {
            foreach ( $data['loans'] as &$loan ) {
                $old_ack = isset( $loan['acknowledgement_attachment_id'] ) ? (string) absint( $loan['acknowledgement_attachment_id'] ) : '0';
                if ( isset( $media_map[ $old_ack ] ) ) {
                    $loan['acknowledgement_attachment_id'] = $media_map[ $old_ack ];
                }
            }
            unset( $loan );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction protects administrator-requested ledger replacement.
        $wpdb->query( 'START TRANSACTION' );
        try {
            foreach ( array_reverse( self::table_names() ) as $name ) {
                $table = LendSure_DB::table( $name );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit administrator-confirmed restore of plugin-owned tables.
                $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );
            }
            foreach ( self::table_names() as $name ) {
                if ( empty( $data[ $name ] ) || ! is_array( $data[ $name ] ) ) {
                    continue;
                }
                $table = LendSure_DB::table( $name );
                foreach ( $data[ $name ] as $row ) {
                    $row = $this->filter_row( $name, $row );
                    if ( ! $row ) {
                        continue;
                    }
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Restoring validated fields into plugin-owned tables.
                    if ( false === $wpdb->insert( $table, $row ) ) {
                        throw new RuntimeException( 'Database restore failed.' );
                    }
                }
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Completes administrator-requested restore transaction.
            $wpdb->query( 'COMMIT' );
        } catch ( Throwable $e ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reverts partial restore.
            $wpdb->query( 'ROLLBACK' );
            $this->redirect_tools( __( 'Restore failed and database changes were rolled back.', 'kuloan-ledger' ), 'error' );
        }

        foreach ( self::option_keys() as $key ) {
            if ( array_key_exists( $key, $settings ) ) {
                update_option( $key, $settings[ $key ] );
            }
        }
        update_option( 'lendsure_db_version', LendSure_DB::DB_VERSION );
        LendSure_DB::flush_cache();
        $this->redirect_tools( __( 'Backup restored successfully.', 'kuloan-ledger' ) );
    }

    private function voided_payment_ids() {
        global $wpdb;
        $transactions = LendSure_DB::table( 'transactions' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin correction screen reads plugin-owned audit transactions.
        $rows = $wpdb->get_results(
            $wpdb->prepare( 'SELECT meta FROM %i WHERE type = %s ORDER BY id DESC', $transactions, 'payment_voided' )
        );
        $ids = array();
        foreach ( $rows as $row ) {
            $meta = json_decode( (string) $row->meta, true );
            if ( is_array( $meta ) && ! empty( $meta['payment_id'] ) ) {
                $ids[ absint( $meta['payment_id'] ) ] = true;
            }
        }
        return $ids;
    }

    private function payment_can_be_voided( $payment ) {
        global $wpdb;
        if ( ! $payment || (float) $payment->amount <= 0 ) {
            return false;
        }
        $voided = $this->voided_payment_ids();
        if ( isset( $voided[ absint( $payment->id ) ] ) ) {
            return false;
        }
        $payments = LendSure_DB::table( 'payments' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Integrity check before administrator payment reversal.
        $latest_id = (int) $wpdb->get_var(
            $wpdb->prepare( 'SELECT MAX(id) FROM %i WHERE loan_id = %d AND amount > 0', $payments, absint( $payment->loan_id ) )
        );
        if ( $latest_id !== (int) $payment->id ) {
            return false;
        }
        $transactions = LendSure_DB::table( 'transactions' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Integrity check before administrator payment reversal.
        $later = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM %i WHERE loan_id = %d AND created_at > %s AND type IN ( %s, %s, %s, %s, %s, %s )',
                $transactions,
                absint( $payment->loan_id ),
                $payment->created_at,
                'payment_received',
                'penalty_applied',
                'interest_charged',
                'capitalized',
                'extension',
                'payment_voided'
            )
        );
        return 0 === $later;
    }

    public function void_payment() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $payment_id = isset( $_POST['payment_id'] ) ? absint( wp_unslash( $_POST['payment_id'] ) ) : 0;
        $this->guard( 'lendsure_void_payment_' . $payment_id );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $reason = isset( $_POST['void_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['void_reason'] ) ) : '';
        if ( ! $payment_id || '' === $reason ) {
            $this->redirect_tools( __( 'A payment and reversal reason are required.', 'kuloan-ledger' ), 'error' );
        }

        global $wpdb;
        $payments = LendSure_DB::table( 'payments' );
        $loans    = LendSure_DB::table( 'loans' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-requested lookup in plugin-owned table.
        $payment = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $payments, $payment_id ) );
        if ( ! $this->payment_can_be_voided( $payment ) ) {
            $this->redirect_tools( __( 'This payment cannot be safely voided because it is already voided or later balance-changing activity exists.', 'kuloan-ledger' ), 'error' );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-requested lookup in plugin-owned table.
        $loan = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $loans, absint( $payment->loan_id ) ) );
        if ( ! $loan ) {
            $this->redirect_tools( __( 'The payment loan could not be found.', 'kuloan-ledger' ), 'error' );
        }

        $original = array(
            'payment_id'          => (int) $payment->id,
            'amount'              => (float) $payment->amount,
            'interest_component'  => (float) $payment->interest_component,
            'penalty_component'   => (float) $payment->penalty_component,
            'principal_component' => (float) $payment->principal_component,
            'payment_date'        => (string) $payment->payment_date,
            'reason'              => $reason,
            'voided_by'           => get_current_user_id(),
            'voided_at'           => current_time( 'mysql' ),
        );

        $new_principal = (float) $loan->current_principal + $original['principal_component'];
        $new_interest  = (float) $loan->accrued_interest + $original['interest_component'];
        $new_penalty   = (float) $loan->accrued_penalty + $original['penalty_component'];
        $new_status    = ( $new_principal + $new_interest + $new_penalty ) > 0 ? 'active' : $loan->status;
        $note          = trim( (string) $payment->notes );
        $void_note     = sprintf(
            /* translators: 1: original payment amount, 2: reason for voiding. */
            __( 'VOIDED. Original amount: %1$s. Reason: %2$s', 'kuloan-ledger' ),
            number_format_i18n( $original['amount'], 2 ),
            $reason
        );
        $note = $note ? $note . "\n" . $void_note : $void_note;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction keeps payment reversal atomic.
        $wpdb->query( 'START TRANSACTION' );
        $ok = true;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating plugin-owned loan balance during explicit reversal.
        $ok = $ok && false !== $wpdb->update(
            $loans,
            array(
                'current_principal' => $new_principal,
                'accrued_interest'   => $new_interest,
                'accrued_penalty'    => $new_penalty,
                'status'             => $new_status,
                'updated_at'         => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $loan->id ) ),
            array( '%f', '%f', '%f', '%s', '%s' ),
            array( '%d' )
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Zeroing operational payment components prevents totals from double-counting a voided payment; originals remain in audit transaction metadata.
        $ok = $ok && false !== $wpdb->update(
            $payments,
            array(
                'amount'              => 0,
                'interest_component'  => 0,
                'penalty_component'   => 0,
                'principal_component' => 0,
                'notes'               => $note,
            ),
            array( 'id' => $payment_id ),
            array( '%f', '%f', '%f', '%f', '%s' ),
            array( '%d' )
        );
        $transactions = LendSure_DB::table( 'transactions' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Writes immutable reversal audit record to plugin-owned ledger.
        $ok = $ok && false !== $wpdb->insert(
            $transactions,
            array(
                'loan_id'          => absint( $loan->id ),
                'type'             => 'payment_voided',
                'amount'           => -1 * $original['amount'],
                'transaction_date' => current_time( 'Y-m-d' ),
                'note'             => $reason,
                'meta'             => wp_json_encode( $original ),
                'created_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%s', '%s', '%s', '%s' )
        );
        if ( $ok ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Completes atomic reversal.
            $wpdb->query( 'COMMIT' );
            LendSure_DB::flush_cache();
            $this->redirect_tools( __( 'Payment voided and its balance allocation was restored.', 'kuloan-ledger' ) );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reverts failed reversal.
        $wpdb->query( 'ROLLBACK' );
        $this->redirect_tools( __( 'The payment could not be voided.', 'kuloan-ledger' ), 'error' );
    }

    public function void_loan() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $this->guard( 'lendsure_void_loan_' . $loan_id );
        if ( ! $loan_id ) {
            $this->redirect_tools( __( 'Invalid loan.', 'kuloan-ledger' ), 'error' );
        }
        $updated = LendSure_DB::update(
            'loans',
            array( 'status' => 'void', 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $loan_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        if ( false === $updated ) {
            $this->redirect_tools( __( 'The loan could not be voided.', 'kuloan-ledger' ), 'error' );
        }
        LendSure_DB::insert(
            'transactions',
            array(
                'loan_id'          => $loan_id,
                'type'             => 'loan_voided',
                'amount'           => 0,
                'transaction_date' => current_time( 'Y-m-d' ),
                'note'             => __( 'Loan voided/cancelled by administrator.', 'kuloan-ledger' ),
                'meta'             => wp_json_encode( array( 'user_id' => get_current_user_id() ) ),
                'created_at'       => current_time( 'mysql' ),
            )
        );
        $this->redirect_tools( __( 'Loan voided. Its history has been preserved.', 'kuloan-ledger' ) );
    }

    public function delete_loan() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $this->guard( 'lendsure_delete_loan_' . $loan_id );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $confirmation = isset( $_POST['delete_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_confirmation'] ) ) : '';
        if ( ! $loan_id || 'DELETE' !== $confirmation ) {
            $this->redirect_tools( __( 'Permanent deletion cancelled: type DELETE exactly.', 'kuloan-ledger' ), 'error' );
        }
        global $wpdb;
        $loan = LendSure_DB::get_loan( $loan_id );
        if ( ! $loan ) {
            $this->redirect_tools( __( 'Loan not found.', 'kuloan-ledger' ), 'error' );
        }
        foreach ( array( 'reminders', 'transactions', 'payments' ) as $name ) {
            $table = LendSure_DB::table( $name );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit administrator-confirmed cascade deletion of plugin-owned records.
            $wpdb->delete( $table, array( 'loan_id' => $loan_id ), array( '%d' ) );
        }
        $loans = LendSure_DB::table( 'loans' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit administrator-confirmed permanent deletion.
        $wpdb->delete( $loans, array( 'id' => $loan_id ), array( '%d' ) );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        if ( ! empty( $_POST['delete_media'] ) && ! empty( $loan->acknowledgement_attachment_id ) ) {
            wp_delete_attachment( absint( $loan->acknowledgement_attachment_id ), true );
        }
        LendSure_DB::flush_cache();
        $this->redirect_tools( __( 'Loan and its related ledger records were permanently deleted.', 'kuloan-ledger' ) );
    }

    public function save_cleanup_policy() {
        $this->guard( 'lendsure_save_cleanup_policy' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        update_option( 'lendsure_delete_data_on_uninstall', ! empty( $_POST['delete_on_uninstall'] ) ? '1' : '0' );
        $this->redirect_tools( __( 'Uninstall data policy saved.', 'kuloan-ledger' ) );
    }

    public static function delete_all_data( $delete_media = false ) {
        global $wpdb;
        $attachment_ids = array();
        if ( $delete_media ) {
            $logo = absint( get_option( 'lendsure_company_logo_id', 0 ) );
            if ( $logo ) {
                $attachment_ids[] = $logo;
            }
            $loans = LendSure_DB::table( 'loans' );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit data-erasure request gathers referenced plugin media.
            $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT acknowledgement_attachment_id FROM %i WHERE acknowledgement_attachment_id > 0', $loans ) );
            $attachment_ids = array_merge( $attachment_ids, array_map( 'absint', $ids ) );
        }

        foreach ( self::table_names() as $name ) {
            $table = LendSure_DB::table( $name );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Explicit administrator-requested removal of plugin-owned table.
            $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
        }
        foreach ( self::option_keys() as $key ) {
            delete_option( $key );
        }
        delete_option( 'lendsure_db_version' );
        LendSure_DB::flush_cache();

        if ( $delete_media ) {
            foreach ( array_unique( array_filter( $attachment_ids ) ) as $attachment_id ) {
                wp_delete_attachment( $attachment_id, true );
            }
        }
    }

    public function erase_all_data() {
        $this->guard( 'lendsure_erase_all_data' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $confirmation = isset( $_POST['erase_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['erase_confirmation'] ) ) : '';
        if ( 'ERASE' !== $confirmation ) {
            $this->redirect_tools( __( 'Data erasure cancelled: type ERASE exactly.', 'kuloan-ledger' ), 'error' );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is protected by guard()/check_admin_referer(); record ID may be read first only to construct the nonce action.
        $delete_media = ! empty( $_POST['delete_media'] );
        self::delete_all_data( $delete_media );
        deactivate_plugins( plugin_basename( LENDSURE_FILE ) );
        wp_safe_redirect( add_query_arg( 'yll_erased', '1', admin_url( 'plugins.php' ) ) );
        exit;
    }

    private function recent_payments() {
        global $wpdb;
        $payments  = LendSure_DB::table( 'payments' );
        $loans     = LendSure_DB::table( 'loans' );
        $borrowers = LendSure_DB::table( 'borrowers' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator correction screen reads plugin-owned tables.
        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT p.*, b.full_name FROM %i p INNER JOIN %i l ON l.id=p.loan_id INNER JOIN %i b ON b.id=l.borrower_id ORDER BY p.id DESC LIMIT %d',
                $payments,
                $loans,
                $borrowers,
                50
            )
        );
    }

    private function recent_loans() {
        global $wpdb;
        $loans     = LendSure_DB::table( 'loans' );
        $borrowers = LendSure_DB::table( 'borrowers' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator correction screen reads plugin-owned tables.
        return $wpdb->get_results(
            $wpdb->prepare( 'SELECT l.*, b.full_name FROM %i l INNER JOIN %i b ON b.id=l.borrower_id ORDER BY l.id DESC LIMIT %d', $loans, $borrowers, 50 )
        );
    }

    public function tools_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $currency = get_option( 'lendsure_currency', 'UGX' );
        $voided   = $this->voided_payment_ids();
        ?>
        <div class="wrap yll-tools-wrap">
            <h1><?php esc_html_e( 'KuLoan Ledger Tools', 'kuloan-ledger' ); ?></h1>
            <?php if ( isset( $_GET['yll_notice'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice. ?>
                <?php $notice = sanitize_text_field( wp_unslash( $_GET['yll_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                <?php $type = isset( $_GET['yll_type'] ) && 'error' === sanitize_key( wp_unslash( $_GET['yll_type'] ) ) ? 'error' : 'success'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Backup & Restore', 'kuloan-ledger' ); ?></h2>
            <p><?php esc_html_e( 'Download a complete portable backup before migration, major maintenance, data cleanup, or uninstall.', 'kuloan-ledger' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lendsure_export_backup">
                <?php wp_nonce_field( 'lendsure_export_backup' ); ?>
                <?php submit_button( __( 'Download Complete Backup', 'kuloan-ledger' ), 'primary', 'submit', false ); ?>
            </form>

            <h3><?php esc_html_e( 'Restore Backup', 'kuloan-ledger' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Restore replaces the current ledger dataset. Download a fresh backup first.', 'kuloan-ledger' ); ?></p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lendsure_restore_backup">
                <?php wp_nonce_field( 'lendsure_restore_backup' ); ?>
                <p><input required type="file" name="backup_file" accept="application/json,.json"></p>
                <p><label><?php esc_html_e( 'Type RESTORE to confirm:', 'kuloan-ledger' ); ?> <input required type="text" name="restore_confirmation" autocomplete="off"></label></p>
                <?php submit_button( __( 'Restore Backup', 'kuloan-ledger' ), 'secondary', 'submit', false ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Payment Corrections', 'kuloan-ledger' ); ?></h2>
            <p><?php esc_html_e( 'Payments are voided rather than silently deleted. Only the latest safe payment can be reversed; its original allocation is preserved in the audit transaction.', 'kuloan-ledger' ); ?></p>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'ID', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Loan / Borrower', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Date', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Amount', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Status / Action', 'kuloan-ledger' ); ?></th></tr></thead><tbody>
            <?php foreach ( $this->recent_payments() as $payment ) : ?>
                <tr>
                    <td><?php echo esc_html( $payment->id ); ?></td>
                    <td><?php echo esc_html( '#' . $payment->loan_id . ' — ' . $payment->full_name ); ?></td>
                    <td><?php echo esc_html( $payment->payment_date ); ?></td>
                    <td><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $payment->amount, 2 ) ); ?></td>
                    <td>
                        <?php if ( isset( $voided[ absint( $payment->id ) ] ) || (float) $payment->amount <= 0 ) : ?>
                            <strong><?php esc_html_e( 'Voided', 'kuloan-ledger' ); ?></strong>
                        <?php elseif ( $this->payment_can_be_voided( $payment ) ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="lendsure_void_payment">
                                <input type="hidden" name="payment_id" value="<?php echo esc_attr( $payment->id ); ?>">
                                <?php wp_nonce_field( 'lendsure_void_payment_' . $payment->id ); ?>
                                <input required type="text" name="void_reason" placeholder="<?php echo esc_attr__( 'Reason for reversal', 'kuloan-ledger' ); ?>">
                                <button class="button button-small" type="submit"><?php esc_html_e( 'Void Payment', 'kuloan-ledger' ); ?></button>
                            </form>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e( 'Locked by later activity', 'kuloan-ledger' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>

            <hr>
            <h2><?php esc_html_e( 'Loan Record Controls', 'kuloan-ledger' ); ?></h2>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Loan', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Borrower', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Status', 'kuloan-ledger' ); ?></th><th><?php esc_html_e( 'Actions', 'kuloan-ledger' ); ?></th></tr></thead><tbody>
            <?php foreach ( $this->recent_loans() as $loan ) : ?>
                <tr>
                    <td>#<?php echo esc_html( $loan->id ); ?></td>
                    <td><?php echo esc_html( $loan->full_name ); ?></td>
                    <td><?php echo esc_html( ucfirst( $loan->status ) ); ?></td>
                    <td>
                        <?php if ( 'void' !== $loan->status ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="lendsure_void_loan"><input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                                <?php wp_nonce_field( 'lendsure_void_loan_' . $loan->id ); ?>
                                <button class="button" type="submit"><?php esc_html_e( 'Void / Cancel Loan', 'kuloan-ledger' ); ?></button>
                            </form>
                        <?php endif; ?>
                        <details><summary><?php esc_html_e( 'Permanent delete', 'kuloan-ledger' ); ?></summary>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="lendsure_delete_loan"><input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                                <?php wp_nonce_field( 'lendsure_delete_loan_' . $loan->id ); ?>
                                <p><label><?php esc_html_e( 'Type DELETE:', 'kuloan-ledger' ); ?> <input required type="text" name="delete_confirmation" autocomplete="off"></label></p>
                                <p><label><input type="checkbox" name="delete_media" value="1"> <?php esc_html_e( 'Also delete this loan’s acknowledgement media', 'kuloan-ledger' ); ?></label></p>
                                <button class="button button-link-delete" type="submit"><?php esc_html_e( 'Permanently Delete Loan', 'kuloan-ledger' ); ?></button>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>

            <hr>
            <h2><?php esc_html_e( 'Uninstall & Data Cleanup', 'kuloan-ledger' ); ?></h2>
            <p><?php esc_html_e( 'Normal uninstall preserves your ledger. Enable deletion only when you intentionally want uninstall to remove the plugin database.', 'kuloan-ledger' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lendsure_save_cleanup_policy">
                <?php wp_nonce_field( 'lendsure_save_cleanup_policy' ); ?>
                <label><input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( get_option( 'lendsure_delete_data_on_uninstall', '0' ), '1' ); ?>> <?php esc_html_e( 'Delete KuLoan Ledger database data when the plugin is uninstalled', 'kuloan-ledger' ); ?></label>
                <?php submit_button( __( 'Save Uninstall Policy', 'kuloan-ledger' ), 'secondary', 'submit', false ); ?>
            </form>
            <h3><?php esc_html_e( 'Danger Zone: Erase All Data & Deactivate', 'kuloan-ledger' ); ?></h3>
            <p><?php esc_html_e( 'Download a backup first. This immediately removes plugin tables/settings and deactivates the plugin.', 'kuloan-ledger' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lendsure_erase_all_data">
                <?php wp_nonce_field( 'lendsure_erase_all_data' ); ?>
                <p><label><?php esc_html_e( 'Type ERASE:', 'kuloan-ledger' ); ?> <input required type="text" name="erase_confirmation" autocomplete="off"></label></p>
                <p><label><input type="checkbox" name="delete_media" value="1"> <?php esc_html_e( 'Also delete referenced company logo and acknowledgement media', 'kuloan-ledger' ); ?></label></p>
                <button class="button button-link-delete" type="submit"><?php esc_html_e( 'Erase All Data & Deactivate', 'kuloan-ledger' ); ?></button>
            </form>
        </div>
        <?php
    }
}
