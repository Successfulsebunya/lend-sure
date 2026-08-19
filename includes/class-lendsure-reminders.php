<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_Reminders {
    private static $instance = null;
    const CRON_HOOK = 'lendsure_daily_reminder_digest';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( self::CRON_HOOK, array( $this, 'send_daily_digest' ) );
        add_action( 'admin_post_lendsure_send_borrower_reminder', array( $this, 'send_borrower_reminder' ) );
    }

    public static function activate() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    public static function timing_status( $due_date ) {
        $today = new DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() );
        $due = DateTimeImmutable::createFromFormat( '!Y-m-d', $due_date, wp_timezone() );
        if ( ! $due ) {
            return array( 'key' => 'unknown', 'label' => __( 'Unknown', 'lend-sure' ), 'days' => 0 );
        }

        $days = (int) $today->diff( $due )->format( '%r%a' );
        $grace = max( 0, absint( get_option( 'lendsure_grace_days', 3 ) ) );

        if ( 0 === $days ) {
            return array( 'key' => 'due_today', 'label' => __( 'Due Today', 'lend-sure' ), 'days' => 0 );
        }
        if ( $days > 0 && $days <= 7 ) {
            return array( 'key' => 'due_week', 'label' => __( 'Due This Week', 'lend-sure' ), 'days' => $days );
        }
        if ( $days < 0 && abs( $days ) <= $grace ) {
            return array( 'key' => 'grace', 'label' => __( 'Grace Period', 'lend-sure' ), 'days' => $days );
        }
        if ( $days < 0 ) {
            return array( 'key' => 'overdue', 'label' => __( 'Overdue', 'lend-sure' ), 'days' => $days );
        }
        return array( 'key' => 'upcoming', 'label' => __( 'Upcoming', 'lend-sure' ), 'days' => $days );
    }

    public static function get_due_loans( $limit = 100 ) {
        global $wpdb;
        $loans = LendSure_DB::table( 'loans' );
        $borrowers = LendSure_DB::table( 'borrowers' );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, b.full_name, b.phone, b.email
                 FROM {$loans} l
                 INNER JOIN {$borrowers} b ON b.id=l.borrower_id
                 WHERE l.status='active'
                 ORDER BY l.due_date ASC
                 LIMIT %d",
                max( 1, absint( $limit ) )
            )
        );
    }

    private function log( $loan_id, $type, $recipient, $status, $message = '' ) {
        global $wpdb;
        $wpdb->insert(
            LendSure_DB::table( 'reminders' ),
            array(
                'loan_id'    => absint( $loan_id ),
                'type'       => sanitize_key( $type ),
                'recipient'  => sanitize_text_field( $recipient ),
                'status'     => sanitize_key( $status ),
                'message'    => wp_strip_all_tags( $message ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function send_daily_digest() {
        if ( '1' !== get_option( 'lendsure_reminders_enabled', '1' ) ) {
            return;
        }

        $recipient = sanitize_email( get_option( 'lendsure_reminder_email', get_option( 'admin_email' ) ) );
        if ( ! is_email( $recipient ) ) {
            return;
        }

        $days_before = max( 0, absint( get_option( 'lendsure_reminder_days_before', 3 ) ) );
        $rows = self::get_due_loans( 250 );
        $items = array();

        foreach ( $rows as $loan ) {
            $timing = self::timing_status( $loan->due_date );
            if ( $timing['days'] > $days_before ) {
                continue;
            }
            $items[] = sprintf(
                '#%1$d %2$s — %3$s %4$s — due %5$s — %6$s',
                $loan->id,
                $loan->full_name,
                get_option( 'lendsure_currency', 'UGX' ),
                number_format_i18n( LendSure_Calculator::total_due( $loan ), 0 ),
                $loan->due_date,
                $timing['label']
            );
        }

        if ( ! $items ) {
            return;
        }

        $subject = sprintf( __( 'Lend Sure daily due-date summary — %s', 'lend-sure' ), current_time( 'Y-m-d' ) );
        $message = __( "The following active loans need attention:\n\n", 'lend-sure' ) . implode( "\n", $items ) . "\n\n" . admin_url( 'admin.php?page=lendsure-reminders' );
        $sent = wp_mail( $recipient, $subject, $message );
        $this->log( 0, 'admin_digest', $recipient, $sent ? 'sent' : 'failed', $message );
    }

    public function send_borrower_reminder() {
        $loan_id = absint( $_POST['loan_id'] ?? 0 );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to send reminders.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_send_borrower_reminder_' . $loan_id );

        global $wpdb;
        $loans = LendSure_DB::table( 'loans' );
        $borrowers = LendSure_DB::table( 'borrowers' );
        $loan = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT l.*, b.full_name, b.email FROM {$loans} l INNER JOIN {$borrowers} b ON b.id=l.borrower_id WHERE l.id=%d",
                $loan_id
            )
        );

        if ( ! $loan || 'active' !== $loan->status || ! is_email( $loan->email ) ) {
            wp_die( esc_html__( 'A valid borrower email is required for this reminder.', 'lend-sure' ) );
        }

        $timing = self::timing_status( $loan->due_date );
        $currency = get_option( 'lendsure_currency', 'UGX' );
        $lender = get_option( 'lendsure_lender_name', get_bloginfo( 'name' ) );
        $subject = sprintf( __( 'Loan reminder — due %s', 'lend-sure' ), $loan->due_date );
        $message = sprintf(
            __( "Hello %1$s,\n\nThis is a reminder regarding your loan with %2$s.\n\nAmount currently due: %3$s %4$s\nDue date: %5$s\nStatus: %6$s\n\nPlease contact the lender if you need to discuss payment or an extension.\n", 'lend-sure' ),
            $loan->full_name,
            $lender,
            $currency,
            number_format_i18n( LendSure_Calculator::total_due( $loan ), 0 ),
            $loan->due_date,
            $timing['label']
        );

        $sent = wp_mail( $loan->email, $subject, $message );
        $this->log( $loan_id, 'borrower_email', $loan->email, $sent ? 'sent' : 'failed', $message );

        $notice = $sent ? __( 'Borrower reminder email sent.', 'lend-sure' ) : __( 'WordPress could not send the borrower reminder email.', 'lend-sure' );
        $url = add_query_arg(
            array( 'page' => 'lendsure-loans', 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => $notice ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $url );
        exit;
    }
}
