<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_Reminders {
    private static $instance = null;
    const CRON_HOOK = 'lendsure_daily_reminder_digest';
    private $mail_error = '';

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

    public function capture_mail_error( $error ) {
        if ( is_wp_error( $error ) ) {
            $this->mail_error = $error->get_error_message();
        }
    }

    private function send_mail_safely( $to, $subject, $message ) {
        $this->mail_error = '';
        add_action( 'wp_mail_failed', array( $this, 'capture_mail_error' ), 10, 1 );

        try {
            $sent = wp_mail( $to, $subject, $message );
        } catch ( Throwable $error ) {
            $sent             = false;
            $this->mail_error = $error->getMessage();
        }

        remove_action( 'wp_mail_failed', array( $this, 'capture_mail_error' ), 10 );

        if ( ! $sent && '' === $this->mail_error ) {
            $this->mail_error = __( 'WordPress mail returned false. Check the site SMTP/mail configuration.', 'your-loan-ledger' );
        }

        return array(
            'sent'  => (bool) $sent,
            'error' => sanitize_text_field( $this->mail_error ),
        );
    }

    public static function timing_status( $due_date ) {
        $today = new DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() );
        $due   = DateTimeImmutable::createFromFormat( '!Y-m-d', $due_date, wp_timezone() );
        if ( ! $due ) {
            return array( 'key' => 'unknown', 'label' => __( 'Unknown', 'your-loan-ledger' ), 'days' => 0 );
        }

        $days  = (int) $today->diff( $due )->format( '%r%a' );
        $grace = max( 0, absint( get_option( 'lendsure_grace_days', 3 ) ) );

        if ( 0 === $days ) {
            return array( 'key' => 'due_today', 'label' => __( 'Due Today', 'your-loan-ledger' ), 'days' => 0 );
        }
        if ( $days > 0 && $days <= 7 ) {
            return array( 'key' => 'due_week', 'label' => __( 'Due This Week', 'your-loan-ledger' ), 'days' => $days );
        }
        if ( $days < 0 && abs( $days ) <= $grace ) {
            return array( 'key' => 'grace', 'label' => __( 'Grace Period', 'your-loan-ledger' ), 'days' => $days );
        }
        if ( $days < 0 ) {
            return array( 'key' => 'overdue', 'label' => __( 'Overdue', 'your-loan-ledger' ), 'days' => $days );
        }
        return array( 'key' => 'upcoming', 'label' => __( 'Upcoming', 'your-loan-ledger' ), 'days' => $days );
    }

    public static function get_due_loans( $limit = 100 ) {
        return LendSure_DB::get_due_loans( $limit );
    }

    private function log( $loan_id, $type, $recipient, $status, $message = '' ) {
        LendSure_DB::insert(
            'reminders',
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
        $rows        = self::get_due_loans( 250 );
        $items       = array();

        foreach ( $rows as $loan ) {
            $timing = self::timing_status( $loan->due_date );
            if ( $timing['days'] > $days_before ) {
                continue;
            }
            $items[] = sprintf(
                /* translators: 1: loan ID, 2: borrower name, 3: currency, 4: amount due, 5: due date, 6: timing status. */
                __( '#%1$d %2$s — %3$s %4$s — due %5$s — %6$s', 'your-loan-ledger' ),
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

        /* translators: %s: current date in YYYY-MM-DD format. */
        $subject = sprintf( __( 'Your Loan Ledger daily due-date summary — %s', 'your-loan-ledger' ), current_time( 'Y-m-d' ) );
        $message = __( "The following active loans need attention:\n\n", 'your-loan-ledger' ) . implode( "\n", $items ) . "\n\n" . admin_url( 'admin.php?page=lendsure-reminders' );
        $result  = $this->send_mail_safely( $recipient, $subject, $message );

        $log_message = $message . ( $result['error'] ? "\n\n" . __( 'Mail error:', 'your-loan-ledger' ) . ' ' . $result['error'] : '' );
        $this->log( 0, 'admin_digest', $recipient, $result['sent'] ? 'sent' : 'failed', $log_message );
    }

    public function send_borrower_reminder() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to send reminders.', 'your-loan-ledger' ) );
        }
        check_admin_referer( 'lendsure_send_borrower_reminder' );

        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $loan    = LendSure_DB::get_loan( $loan_id );

        if ( ! $loan || 'active' !== $loan->status || ! is_email( $loan->email ) ) {
            wp_die( esc_html__( 'A valid borrower email is required for this reminder.', 'your-loan-ledger' ) );
        }

        $timing   = self::timing_status( $loan->due_date );
        $currency = get_option( 'lendsure_currency', 'UGX' );
        $lender   = get_option( 'lendsure_company_name', get_bloginfo( 'name' ) );

        /* translators: %s: loan due date. */
        $subject = sprintf( __( 'Loan reminder — due %s', 'your-loan-ledger' ), $loan->due_date );

        $message = sprintf(
            /* translators: 1: borrower name, 2: lender/company name, 3: currency, 4: amount due, 5: due date, 6: timing status. */
            __( 'Hello %1$s,

This is a reminder regarding your loan with %2$s.

Amount currently due: %3$s %4$s
Due date: %5$s
Status: %6$s

Please contact the lender if you need to discuss payment or an extension.
', 'your-loan-ledger' ),
            $loan->full_name,
            $lender,
            $currency,
            number_format_i18n( LendSure_Calculator::total_due( $loan ), 0 ),
            $loan->due_date,
            $timing['label']
        );

        $borrower_result = $this->send_mail_safely( $loan->email, $subject, $message );
        $sent            = $borrower_result['sent'];
        $borrower_log    = $message . ( $borrower_result['error'] ? "\n\n" . __( 'Mail error:', 'your-loan-ledger' ) . ' ' . $borrower_result['error'] : '' );
        $this->log( $loan_id, 'borrower_email', $loan->email, $sent ? 'sent' : 'failed', $borrower_log );

        $lender_email = sanitize_email( get_option( 'lendsure_reminder_email', get_option( 'admin_email' ) ) );
        $copy_sent    = true;
        if ( is_email( $lender_email ) && strtolower( $lender_email ) !== strtolower( $loan->email ) ) {
            /* translators: %s: borrower name. */
            $copy_subject = sprintf( __( 'Copy: loan reminder sent to %s', 'your-loan-ledger' ), $loan->full_name );

            $copy_message = sprintf(
                /* translators: 1: borrower name, 2: borrower email address, 3: reminder message body. */
                __( 'A loan reminder was sent to %1$s (%2$s).

%3$s', 'your-loan-ledger' ),
                $loan->full_name,
                $loan->email,
                $message
            );
            $copy_result = $this->send_mail_safely( $lender_email, $copy_subject, $copy_message );
            $copy_sent   = $copy_result['sent'];
            $copy_log    = $copy_message . ( $copy_result['error'] ? "\n\n" . __( 'Mail error:', 'your-loan-ledger' ) . ' ' . $copy_result['error'] : '' );
            $this->log( $loan_id, 'lender_copy_email', $lender_email, $copy_sent ? 'sent' : 'failed', $copy_log );
        }

        if ( $sent && $copy_sent ) {
            $notice = __( 'Reminder email sent to the borrower and a lender/admin copy was sent where configured.', 'your-loan-ledger' );
        } elseif ( $sent ) {
            $notice = __( 'Borrower reminder email sent, but the lender/admin copy could not be sent.', 'your-loan-ledger' );
        } else {
            $notice = __( 'WordPress could not send the borrower reminder email. Check the site mail/SMTP configuration.', 'your-loan-ledger' );
            if ( ! empty( $borrower_result['error'] ) ) {
                $notice .= ' ' . $borrower_result['error'];
            }
        }

        $url = add_query_arg(
            array(
                'page'      => 'lendsure-loans',
                'action'    => 'view',
                'loan_id'   => $loan_id,
                'ls_notice' => $notice,
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $url );
        exit;
    }
}
