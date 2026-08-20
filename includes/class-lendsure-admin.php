<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_Admin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

        add_action( 'admin_post_lendsure_add_borrower', array( $this, 'add_borrower' ) );
        add_action( 'admin_post_lendsure_update_borrower', array( $this, 'update_borrower' ) );
        add_action( 'admin_post_lendsure_add_loan', array( $this, 'add_loan' ) );
        add_action( 'admin_post_lendsure_add_payment', array( $this, 'add_payment' ) );
        add_action( 'admin_post_lendsure_extend_loan', array( $this, 'extend_loan' ) );
        add_action( 'admin_post_lendsure_apply_penalty', array( $this, 'apply_penalty' ) );
        add_action( 'admin_post_lendsure_upload_ack', array( $this, 'upload_ack' ) );
        add_action( 'admin_post_lendsure_acknowledgement', array( $this, 'acknowledgement' ) );
        add_action( 'admin_post_lendsure_save_settings', array( $this, 'save_settings' ) );
        add_action( 'admin_post_lendsure_export_loans', array( $this, 'export_loans' ) );
    }

    public function menu() {
        add_menu_page(
            __( 'Lend Sure', 'lend-sure' ),
            __( 'Lend Sure', 'lend-sure' ),
            'manage_options',
            'lendsure',
            array( $this, 'dashboard_page' ),
            'dashicons-money-alt',
            26
        );

        add_submenu_page( 'lendsure', __( 'Dashboard', 'lend-sure' ), __( 'Dashboard', 'lend-sure' ), 'manage_options', 'lendsure', array( $this, 'dashboard_page' ) );
        add_submenu_page( 'lendsure', __( 'Borrowers', 'lend-sure' ), __( 'Borrowers', 'lend-sure' ), 'manage_options', 'lendsure-borrowers', array( $this, 'borrowers_page' ) );
        add_submenu_page( 'lendsure', __( 'Loans', 'lend-sure' ), __( 'Loans', 'lend-sure' ), 'manage_options', 'lendsure-loans', array( $this, 'loans_page' ) );
        add_submenu_page( 'lendsure', __( 'Reminders', 'lend-sure' ), __( 'Reminders', 'lend-sure' ), 'manage_options', 'lendsure-reminders', array( $this, 'reminders_page' ) );
        add_submenu_page( 'lendsure', __( 'Settings', 'lend-sure' ), __( 'Settings', 'lend-sure' ), 'manage_options', 'lendsure-settings', array( $this, 'settings_page' ) );
    }

    public function assets( $hook ) {
        if ( false === strpos( $hook, 'lendsure' ) ) {
            return;
        }
        wp_enqueue_style( 'lendsure-admin', LENDSURE_URL . 'assets/admin.css', array(), LENDSURE_VERSION );
    }

    private function guard( $nonce_action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( $nonce_action );
    }

    private function redirect( $page, $args = array() ) {
        $url = add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
        wp_safe_redirect( $url );
        exit;
    }

    private function money( $amount ) {
        return get_option( 'lendsure_currency', 'UGX' ) . ' ' . number_format_i18n( (float) $amount, 0 );
    }

    private function today() {
        return current_time( 'Y-m-d' );
    }

    private function now() {
        return current_time( 'mysql' );
    }

    private function add_months( $date, $months ) {
        $tz = wp_timezone();
        $source = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
        if ( ! $source ) {
            return $date;
        }
        $day = (int) $source->format( 'd' );
        $target = $source->modify( 'first day of this month' )->modify( '+' . (int) $months . ' month' );
        $last_day = (int) $target->format( 't' );
        return $target->setDate( (int) $target->format( 'Y' ), (int) $target->format( 'm' ), min( $day, $last_day ) )->format( 'Y-m-d' );
    }

    private function get_loan( $loan_id ) {
        return LendSure_DB::get_loan( $loan_id );
    }

    private function add_transaction( $loan_id, $type, $amount, $date, $note = '', $meta = array() ) {
        LendSure_DB::insert(
            'transactions',
            array(
                'loan_id'          => absint( $loan_id ),
                'type'             => sanitize_key( $type ),
                'amount'           => (float) $amount,
                'transaction_date' => sanitize_text_field( $date ),
                'note'             => sanitize_text_field( $note ),
                'meta'             => $meta ? wp_json_encode( $meta ) : '',
                'created_at'       => $this->now(),
            ),
            array( '%d', '%s', '%f', '%s', '%s', '%s', '%s' )
        );
    }

    private function notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice query parameter; no state change occurs.
        if ( empty( $_GET['ls_notice'] ) ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice query parameter; value is unslashed and sanitized before output.
        $message = sanitize_text_field( wp_unslash( $_GET['ls_notice'] ) );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
    }

    public function dashboard_page() {
        $totals         = LendSure_DB::get_dashboard_totals();
        $active_count   = $totals ? (int) $totals->active_count : 0;
        $principal      = $totals ? (float) $totals->principal : 0.0;
        $interest       = $totals ? (float) $totals->interest : 0.0;
        $penalty        = $totals ? (float) $totals->penalty : 0.0;
        $expected_total = $totals ? (float) $totals->expected_total : 0.0;
        $due            = LendSure_DB::get_due_loans( 100 );
        $performance    = LendSure_DB::get_monthly_performance( 12 );

        $due_today = 0.0;
        $due_week  = 0.0;
        $grace     = 0.0;
        $overdue   = 0.0;
        foreach ( $due as $loan ) {
            $timing = LendSure_Reminders::timing_status( $loan->due_date );
            $amount = LendSure_Calculator::total_due( $loan );
            if ( 'due_today' === $timing['key'] ) {
                $due_today += $amount;
            } elseif ( 'due_week' === $timing['key'] ) {
                $due_week += $amount;
            } elseif ( 'grace' === $timing['key'] ) {
                $grace += $amount;
            } elseif ( 'overdue' === $timing['key'] ) {
                $overdue += $amount;
            }
        }

        $max_issued = 0.0;
        $max_income = 0.0;
        foreach ( $performance as $point ) {
            $max_issued = max( $max_issued, (float) $point['principal_issued'] );
            $max_income = max( $max_income, (float) $point['income_collected'] );
        }
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php esc_html_e( 'Lend Sure', 'lend-sure' ); ?></h1>
            <?php $this->notice(); ?>

            <div class="ls-cards ls-cards-5">
                <div class="ls-card"><span><?php esc_html_e( 'Active Loans', 'lend-sure' ); ?></span><strong><?php echo esc_html( $active_count ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Outstanding Principal', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $principal ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Outstanding Interest', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $interest ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Outstanding Penalties', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $penalty ) ); ?></strong></div>
                <div class="ls-card ls-card-highlight"><span><?php esc_html_e( 'Total Expected Amount', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $expected_total ) ); ?></strong></div>
            </div>

            <div class="ls-cards">
                <div class="ls-card ls-card-warning"><span><?php esc_html_e( 'Due Today', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $due_today ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Due This Week', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $due_week ) ); ?></strong></div>
                <div class="ls-card ls-card-grace"><span><?php esc_html_e( 'In Grace Period', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $grace ) ); ?></strong></div>
                <div class="ls-card ls-card-danger"><span><?php esc_html_e( 'Overdue', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $overdue ) ); ?></strong></div>
            </div>

            <div class="ls-toolbar">
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-loans&action=new' ) ); ?>"><?php esc_html_e( 'Add Loan', 'lend-sure' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-borrowers&action=new' ) ); ?>"><?php esc_html_e( 'Add Borrower', 'lend-sure' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-reminders' ) ); ?>"><?php esc_html_e( 'Open Reminders', 'lend-sure' ); ?></a>
            </div>

            <section class="ls-panel ls-analytics-panel">
                <div class="ls-panel-heading">
                    <div>
                        <h2><?php esc_html_e( '12-Month Lending Performance', 'lend-sure' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Loan volume shows principal issued. Lending income shows interest and penalties actually collected; it is not accounting profit.', 'lend-sure' ); ?></p>
                    </div>
                </div>
                <div class="ls-chart-legend">
                    <span><i class="ls-legend-swatch ls-legend-issued"></i><?php esc_html_e( 'Principal issued', 'lend-sure' ); ?></span>
                    <span><i class="ls-legend-swatch ls-legend-income"></i><?php esc_html_e( 'Lending income collected', 'lend-sure' ); ?></span>
                </div>
                <div class="ls-performance-chart" role="img" aria-label="<?php esc_attr_e( 'Monthly lending performance for the last 12 months', 'lend-sure' ); ?>">
                    <?php foreach ( $performance as $point ) :
                        $issued_height = $max_issued > 0 ? max( 2, round( ( (float) $point['principal_issued'] / $max_issued ) * 100, 1 ) ) : 0;
                        $income_height = $max_income > 0 ? max( 2, round( ( (float) $point['income_collected'] / $max_income ) * 100, 1 ) ) : 0;
                        ?>
                        <div class="ls-chart-month">
                            <div class="ls-chart-bars">
                                <div class="ls-chart-bar ls-chart-issued" style="<?php echo esc_attr( '--ls-bar-height:' . $issued_height . '%' ); ?>" title="<?php echo esc_attr( $this->money( $point['principal_issued'] ) ); ?>"></div>
                                <div class="ls-chart-bar ls-chart-income" style="<?php echo esc_attr( '--ls-bar-height:' . $income_height . '%' ); ?>" title="<?php echo esc_attr( $this->money( $point['income_collected'] ) ); ?>"></div>
                            </div>
                            <span class="ls-chart-label"><?php echo esc_html( $point['label'] ); ?></span>
                            <small><strong><?php echo esc_html( (int) $point['loans_count'] ); ?></strong> <?php echo esc_html( _n( 'loan', 'loans', (int) $point['loans_count'], 'lend-sure' ) ); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e( 'Each bar series is scaled to its own highest month so the chart emphasizes direction and trend. Use the loan register and payment records for exact monetary comparisons.', 'lend-sure' ); ?></p>
            </section>

            <h2><?php esc_html_e( 'Due-Date Workflow', 'lend-sure' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Borrower', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Due Date', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Total Due', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Timing', 'lend-sure' ); ?></th><th></th></tr></thead>
                <tbody>
                <?php if ( ! $due ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No active loans yet.', 'lend-sure' ); ?></td></tr>
                <?php else : foreach ( array_slice( $due, 0, 20 ) as $loan ) :
                    $timing = LendSure_Reminders::timing_status( $loan->due_date );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $loan->full_name ); ?></td>
                        <td><?php echo esc_html( $loan->due_date ); ?></td>
                        <td><?php echo esc_html( $this->money( LendSure_Calculator::total_due( $loan ) ) ); ?></td>
                        <td><span class="ls-badge is-<?php echo esc_attr( $timing['key'] ); ?>"><?php echo esc_html( $timing['label'] ); ?></span></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-loans&action=view&loan_id=' . (int) $loan->id ) ); ?>"><?php esc_html_e( 'Open', 'lend-sure' ); ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function borrowers_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state change occurs.
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

        if ( 'new' === $action ) {
            $this->borrower_form();
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state change occurs.
        $borrower_id = isset( $_GET['borrower_id'] ) ? absint( wp_unslash( $_GET['borrower_id'] ) ) : 0;
        if ( 'edit' === $action && $borrower_id ) {
            $borrower = LendSure_DB::get_borrower( $borrower_id );
            if ( ! $borrower ) {
                wp_die( esc_html__( 'Borrower not found.', 'lend-sure' ) );
            }
            $this->borrower_form( $borrower );
            return;
        }

        $rows = LendSure_DB::get_borrowers();
        ?>
        <div class="wrap lendsure-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Borrowers', 'lend-sure' ); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-borrowers&action=new' ) ); ?>"><?php esc_html_e( 'Add New', 'lend-sure' ); ?></a>
            <?php $this->notice(); ?>
            <table class="widefat striped ls-table-margin">
                <thead><tr><th><?php esc_html_e( 'Name', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Phone', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Email', 'lend-sure' ); ?></th><th><?php esc_html_e( 'National ID', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Actions', 'lend-sure' ); ?></th></tr></thead>
                <tbody>
                <?php if ( ! $rows ) : ?><tr><td colspan="5"><?php esc_html_e( 'No borrowers yet.', 'lend-sure' ); ?></td></tr>
                <?php else : foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->full_name ); ?></td>
                        <td><?php echo esc_html( $row->phone ); ?></td>
                        <td><?php echo esc_html( $row->email ); ?></td>
                        <td><?php echo esc_html( $row->national_id ); ?></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-borrowers&action=edit&borrower_id=' . (int) $row->id ) ); ?>"><?php esc_html_e( 'Edit', 'lend-sure' ); ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function borrower_form( $borrower = null ) {
        $is_edit = ! empty( $borrower );
        $title   = $is_edit ? __( 'Edit Borrower', 'lend-sure' ) : __( 'Add Borrower', 'lend-sure' );
        $action  = $is_edit ? 'lendsure_update_borrower' : 'lendsure_add_borrower';
        $nonce   = $is_edit ? 'lendsure_update_borrower' : 'lendsure_add_borrower';

        $full_name   = $is_edit ? $borrower->full_name : '';
        $phone       = $is_edit ? $borrower->phone : '';
        $email       = $is_edit ? $borrower->email : '';
        $address     = $is_edit ? $borrower->address : '';
        $national_id = $is_edit ? $borrower->national_id : '';
        $notes       = $is_edit ? $borrower->notes : '';
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php echo esc_html( $title ); ?></h1>
            <?php $this->notice(); ?>
            <?php if ( $is_edit ) : ?>
                <p class="description"><?php esc_html_e( 'Changes apply to this borrower record and will appear in future generated loan documents. Previously uploaded signed acknowledgements remain unchanged.', 'lend-sure' ); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ls-form-card">
                <input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
                <?php if ( $is_edit ) : ?><input type="hidden" name="borrower_id" value="<?php echo esc_attr( $borrower->id ); ?>"><?php endif; ?>
                <?php wp_nonce_field( $nonce ); ?>
                <p><label><?php esc_html_e( 'Full Name', 'lend-sure' ); ?><input required name="full_name" type="text" class="regular-text" value="<?php echo esc_attr( $full_name ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Phone', 'lend-sure' ); ?><input name="phone" type="text" class="regular-text" value="<?php echo esc_attr( $phone ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Email', 'lend-sure' ); ?><input name="email" type="email" class="regular-text" value="<?php echo esc_attr( $email ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Address', 'lend-sure' ); ?><textarea name="address" rows="3" class="large-text"><?php echo esc_textarea( $address ); ?></textarea></label></p>
                <p><label><?php esc_html_e( 'National ID / Identification', 'lend-sure' ); ?><input name="national_id" type="text" class="regular-text" value="<?php echo esc_attr( $national_id ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Notes', 'lend-sure' ); ?><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></label></p>
                <?php submit_button( $is_edit ? __( 'Update Borrower', 'lend-sure' ) : __( 'Save Borrower', 'lend-sure' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function loans_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state change occurs.
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        if ( 'new' === $action ) {
            $this->loan_form();
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state change occurs.
        $loan_id = isset( $_GET['loan_id'] ) ? absint( wp_unslash( $_GET['loan_id'] ) ) : 0;
        if ( 'view' === $action && $loan_id ) {
            $this->loan_detail( $loan_id );
            return;
        }

        $rows = LendSure_DB::get_loans();

        $register_totals = array(
            'count'                 => 0,
            'original_principal'    => 0.0,
            'outstanding_principal' => 0.0,
            'interest_due'          => 0.0,
            'penalty_due'           => 0.0,
            'total_due'             => 0.0,
        );

        foreach ( $rows as $loan ) {
            $register_totals['count']++;
            $register_totals['original_principal']    += (float) $loan->original_principal;
            $register_totals['outstanding_principal'] += (float) $loan->current_principal;
            $register_totals['interest_due']          += (float) $loan->accrued_interest;
            $register_totals['penalty_due']           += (float) $loan->accrued_penalty;
            $register_totals['total_due']             += LendSure_Calculator::total_due( $loan );
        }
        ?>
        <div class="wrap lendsure-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Loans', 'lend-sure' ); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-loans&action=new' ) ); ?>"><?php esc_html_e( 'Add New', 'lend-sure' ); ?></a>
            <a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lendsure_export_loans' ), 'lendsure_export_loans' ) ); ?>"><?php esc_html_e( 'Export CSV', 'lend-sure' ); ?></a>
            <?php $this->notice(); ?>
            <table class="widefat striped ls-table-margin">
                <thead><tr><th>#</th><th><?php esc_html_e( 'Borrower', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Principal', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Interest', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Due', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Total Due', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Status', 'lend-sure' ); ?></th><th></th></tr></thead>
                <tbody>
                <?php if ( ! $rows ) : ?><tr><td colspan="8"><?php esc_html_e( 'No loans yet.', 'lend-sure' ); ?></td></tr>
                <?php else : foreach ( $rows as $loan ) :
                    $timing = 'active' === $loan->status ? LendSure_Reminders::timing_status( $loan->due_date ) : array( 'key' => 'paid', 'label' => __( 'Paid', 'lend-sure' ) );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $loan->id ); ?></td>
                        <td><?php echo esc_html( $loan->full_name ); ?></td>
                        <td><?php echo esc_html( $this->money( $loan->current_principal ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $loan->interest_rate, 2 ) . '%' ); ?></td>
                        <td><?php echo esc_html( $loan->due_date ); ?></td>
                        <td><?php echo esc_html( $this->money( LendSure_Calculator::total_due( $loan ) ) ); ?></td>
                        <td><span class="ls-badge is-<?php echo esc_attr( $timing['key'] ); ?>"><?php echo esc_html( $timing['label'] ); ?></span></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-loans&action=view&loan_id=' . (int) $loan->id ) ); ?>"><?php esc_html_e( 'Manage', 'lend-sure' ); ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>

            <section class="ls-panel ls-register-totals">
                <h2><?php esc_html_e( 'Loan Register Totals', 'lend-sure' ); ?></h2>
                <div class="ls-cards ls-cards-3">
                    <div class="ls-card"><span><?php esc_html_e( 'Loans Listed', 'lend-sure' ); ?></span><strong><?php echo esc_html( $register_totals['count'] ); ?></strong></div>
                    <div class="ls-card"><span><?php esc_html_e( 'Original Principal Issued', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $register_totals['original_principal'] ) ); ?></strong></div>
                    <div class="ls-card"><span><?php esc_html_e( 'Outstanding Principal', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $register_totals['outstanding_principal'] ) ); ?></strong></div>
                    <div class="ls-card"><span><?php esc_html_e( 'Interest Due', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $register_totals['interest_due'] ) ); ?></strong></div>
                    <div class="ls-card"><span><?php esc_html_e( 'Penalties Due', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $register_totals['penalty_due'] ) ); ?></strong></div>
                    <div class="ls-card ls-card-highlight"><span><?php esc_html_e( 'Current Total Expected', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $register_totals['total_due'] ) ); ?></strong></div>
                </div>
                <p class="description"><?php esc_html_e( 'Original Principal Issued includes historical loans. Current totals reflect the balances still stored on the loan register.', 'lend-sure' ); ?></p>
            </section>
        </div>
        <?php
    }

    private function loan_form() {
        $borrowers = LendSure_DB::get_borrowers( true );
        $rate = (float) get_option( 'lendsure_default_interest', 20 );
        $penalty_type = get_option( 'lendsure_penalty_type', 'percentage' );
        $penalty_value = (float) get_option( 'lendsure_penalty_value', 5 );
        $months = max( 1, absint( get_option( 'lendsure_default_duration_months', 1 ) ) );
        $start = $this->today();
        $due = $this->add_months( $start, $months );
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php esc_html_e( 'Add Loan', 'lend-sure' ); ?></h1>
            <?php if ( ! $borrowers ) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e( 'Add a borrower before creating a loan.', 'lend-sure' ); ?></p></div>
                <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-borrowers&action=new' ) ); ?>"><?php esc_html_e( 'Add Borrower', 'lend-sure' ); ?></a></p>
            <?php return; endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ls-form-card">
                <input type="hidden" name="action" value="lendsure_add_loan">
                <?php wp_nonce_field( 'lendsure_add_loan' ); ?>
                <p><label><?php esc_html_e( 'Borrower', 'lend-sure' ); ?>
                    <select name="borrower_id" required>
                        <option value=""><?php esc_html_e( 'Select borrower', 'lend-sure' ); ?></option>
                        <?php foreach ( $borrowers as $b ) : ?><option value="<?php echo esc_attr( $b->id ); ?>"><?php echo esc_html( $b->full_name . ( $b->phone ? ' — ' . $b->phone : '' ) ); ?></option><?php endforeach; ?>
                    </select>
                </label></p>
                <p><label><?php esc_html_e( 'Principal Amount', 'lend-sure' ); ?><input required min="1" step="0.01" name="principal" type="number"></label></p>
                <p><label><?php esc_html_e( 'Monthly Interest (%)', 'lend-sure' ); ?><input required min="0" step="0.01" name="interest_rate" type="number" value="<?php echo esc_attr( $rate ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Late Penalty Type', 'lend-sure' ); ?><select name="penalty_type"><option value="percentage" <?php selected( $penalty_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage of outstanding principal', 'lend-sure' ); ?></option><option value="fixed" <?php selected( $penalty_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'lend-sure' ); ?></option></select></label></p>
                <p><label><?php esc_html_e( 'Late Penalty Value', 'lend-sure' ); ?><input required min="0" step="0.01" name="penalty_value" type="number" value="<?php echo esc_attr( $penalty_value ); ?>"></label></p>
                <p class="description"><?php esc_html_e( 'This is the agreed late-payment penalty for this loan and will appear in the acknowledgement terms. You can still edit the amount when applying a penalty.', 'lend-sure' ); ?></p>
                <p><label><?php esc_html_e( 'Start Date', 'lend-sure' ); ?><input required name="start_date" type="date" value="<?php echo esc_attr( $start ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Due Date', 'lend-sure' ); ?><input required name="due_date" type="date" value="<?php echo esc_attr( $due ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Purpose', 'lend-sure' ); ?><textarea name="purpose" rows="2" class="large-text"></textarea></label></p>
                <p><label><?php esc_html_e( 'Additional Terms', 'lend-sure' ); ?><textarea name="terms" rows="4" class="large-text" placeholder="Optional terms to appear on the acknowledgement."></textarea></label></p>
                <p class="description"><?php esc_html_e( 'The first month interest is calculated immediately when the loan is saved.', 'lend-sure' ); ?></p>
                <?php submit_button( __( 'Create Loan & Generate Acknowledgement', 'lend-sure' ) ); ?>
            </form>
        </div>
        <?php
    }

    private function loan_detail( $loan_id ) {
        $loan = $this->get_loan( $loan_id );
        if ( ! $loan ) {
            wp_die( esc_html__( 'Loan not found.', 'lend-sure' ) );
        }

        $payments     = LendSure_DB::get_payments( $loan_id );
        $transactions = LendSure_DB::get_transactions( $loan_id );
        $ack_url   = $loan->acknowledgement_attachment_id ? wp_get_attachment_url( (int) $loan->acknowledgement_attachment_id ) : '';
        $total_due = LendSure_Calculator::total_due( $loan );
        $timing    = 'active' === $loan->status ? LendSure_Reminders::timing_status( $loan->due_date ) : array( 'key' => 'paid', 'label' => __( 'Paid', 'lend-sure' ) );

        /* translators: 1: loan ID, 2: borrower full name. */
        $loan_heading = sprintf( __( 'Loan #%1$d — %2$s', 'lend-sure' ), $loan->id, $loan->full_name );

        $agreed_penalty = 'fixed' === $loan->penalty_type
            ? $this->money( $loan->penalty_value )
            : number_format_i18n( $loan->penalty_value, 2 ) . '% ' . __( 'of outstanding principal', 'lend-sure' );

        /* translators: %s: borrower email address. */
        $reminder_intro = sprintf( __( 'Send a payment reminder to %s. The message includes the current amount due, due date and timing status.', 'lend-sure' ), $loan->email );
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php echo esc_html( $loan_heading ); ?></h1>
            <?php $this->notice(); ?>

            <div class="ls-cards">
                <div class="ls-card"><span><?php esc_html_e( 'Principal', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $loan->current_principal ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Interest Due', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $loan->accrued_interest ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Penalty Due', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $loan->accrued_penalty ) ); ?></strong></div>
                <div class="ls-card"><span><?php esc_html_e( 'Total Due', 'lend-sure' ); ?></span><strong><?php echo esc_html( $this->money( $total_due ) ); ?></strong></div>
            </div>

            <div class="ls-grid-2">
                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Loan Details', 'lend-sure' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Borrower:', 'lend-sure' ); ?></strong> <?php echo esc_html( $loan->full_name ); ?></p>
                    <p><strong><?php esc_html_e( 'Phone:', 'lend-sure' ); ?></strong> <?php echo esc_html( $loan->phone ); ?></p>
                    <p><strong><?php esc_html_e( 'Original principal:', 'lend-sure' ); ?></strong> <?php echo esc_html( $this->money( $loan->original_principal ) ); ?></p>
                    <p><strong><?php esc_html_e( 'Interest:', 'lend-sure' ); ?></strong> <?php echo esc_html( number_format_i18n( $loan->interest_rate, 2 ) . '% / month' ); ?></p>
                    <p><strong><?php esc_html_e( 'Agreed late penalty:', 'lend-sure' ); ?></strong> <?php echo esc_html( $agreed_penalty ); ?></p>
                    <p><strong><?php esc_html_e( 'Projected next-month interest:', 'lend-sure' ); ?></strong> <?php echo esc_html( $this->money( LendSure_Calculator::interest( $loan->current_principal, $loan->interest_rate ) ) ); ?></p>
                    <p><strong><?php esc_html_e( 'Start:', 'lend-sure' ); ?></strong> <?php echo esc_html( $loan->start_date ); ?></p>
                    <p><strong><?php esc_html_e( 'Due:', 'lend-sure' ); ?></strong> <?php echo esc_html( $loan->due_date ); ?></p>
                    <p><strong><?php esc_html_e( 'Status:', 'lend-sure' ); ?></strong> <span class="ls-badge is-<?php echo esc_attr( $timing['key'] ); ?>"><?php echo esc_html( $timing['label'] ); ?></span></p>
                </section>

                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Loan Acknowledgement', 'lend-sure' ); ?></h2>
                    <p><?php esc_html_e( 'Open the acknowledgement and use the single Save PDF action to store a clean PDF copy. You can print that PDF later if a paper signature is required.', 'lend-sure' ); ?></p>
                    <p><a class="button button-primary" target="_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lendsure_acknowledgement&loan_id=' . (int) $loan->id ), 'lendsure_acknowledgement' ) ); ?>"><?php esc_html_e( 'Save Acknowledgement PDF', 'lend-sure' ); ?></a></p>
                    <?php if ( $ack_url ) : ?>
                        <p><strong><?php esc_html_e( 'Signed copy:', 'lend-sure' ); ?></strong> <a target="_blank" href="<?php echo esc_url( $ack_url ); ?>"><?php esc_html_e( 'Open uploaded document', 'lend-sure' ); ?></a></p>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="lendsure_upload_ack">
                        <input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                        <?php wp_nonce_field( 'lendsure_upload_ack' ); ?>
                        <input required type="file" name="ack_file" accept="application/pdf,image/jpeg,image/png">
                        <?php submit_button( $ack_url ? __( 'Replace Signed Copy', 'lend-sure' ) : __( 'Upload Signed Copy', 'lend-sure' ), 'secondary', 'submit', false ); ?>
                    </form>
                </section>
            </div>

            <?php if ( 'paid' !== $loan->status && is_email( $loan->email ) ) : ?>
                <section class="ls-panel ls-reminder-panel">
                    <h2><?php esc_html_e( 'Borrower Reminder', 'lend-sure' ); ?></h2>
                    <p><?php echo esc_html( $reminder_intro ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="lendsure_send_borrower_reminder">
                        <input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                        <?php wp_nonce_field( 'lendsure_send_borrower_reminder' ); ?>
                        <?php submit_button( __( 'Send Email Reminder', 'lend-sure' ), 'secondary', 'submit', false ); ?>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ( 'paid' !== $loan->status ) : ?>
            <div class="ls-grid-3">
                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Record Payment', 'lend-sure' ); ?></h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="lendsure_add_payment"><input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                        <?php wp_nonce_field( 'lendsure_add_payment' ); ?>
                        <p><label><?php esc_html_e( 'Amount', 'lend-sure' ); ?><input required min="0.01" max="<?php echo esc_attr( $total_due ); ?>" step="0.01" type="number" name="amount"></label></p>
                        <p><label><?php esc_html_e( 'Payment Date', 'lend-sure' ); ?><input required type="date" name="payment_date" value="<?php echo esc_attr( $this->today() ); ?>"></label></p>
                        <p><label><?php esc_html_e( 'Method', 'lend-sure' ); ?><input type="text" name="method" placeholder="Cash, Mobile Money, Bank..."></label></p>
                        <p><label><?php esc_html_e( 'Reference', 'lend-sure' ); ?><input type="text" name="reference"></label></p>
                        <?php submit_button( __( 'Record Payment', 'lend-sure' ), 'primary', 'submit', false ); ?>
                    </form>
                </section>

                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Extend Loan', 'lend-sure' ); ?></h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="lendsure_extend_loan"><input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                        <?php wp_nonce_field( 'lendsure_extend_loan' ); ?>
                        <p><label><?php esc_html_e( 'Extension Date', 'lend-sure' ); ?><input required type="date" name="extension_date" value="<?php echo esc_attr( $this->today() ); ?>"></label></p>
                        <p class="description"><?php esc_html_e( 'Use the actual date the extension was agreed, even when you are recording it later.', 'lend-sure' ); ?></p>
                        <p><label><?php esc_html_e( 'Months', 'lend-sure' ); ?><input required min="1" max="24" type="number" name="months" value="1"></label></p>
                        <p><label><input type="checkbox" name="capitalize" value="1" checked> <?php esc_html_e( 'Capitalize unpaid interest and penalty into the new principal', 'lend-sure' ); ?></label></p>
                        <p class="description"><?php esc_html_e( 'A fresh month of interest is then calculated at the loan interest rate.', 'lend-sure' ); ?></p>
                        <?php submit_button( __( 'Extend Loan', 'lend-sure' ), 'secondary', 'submit', false ); ?>
                    </form>
                </section>

                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Apply Penalty', 'lend-sure' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'The fields start with the penalty agreed when the loan was created. Edit them here only when you intentionally need a different one-off charge.', 'lend-sure' ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="lendsure_apply_penalty"><input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                        <?php wp_nonce_field( 'lendsure_apply_penalty' ); ?>
                        <p><label><?php esc_html_e( 'Penalty Type', 'lend-sure' ); ?><select name="penalty_type"><option value="percentage" <?php selected( $loan->penalty_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage of outstanding principal', 'lend-sure' ); ?></option><option value="fixed" <?php selected( $loan->penalty_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'lend-sure' ); ?></option></select></label></p>
                        <p><label><?php esc_html_e( 'Penalty Value', 'lend-sure' ); ?><input required min="0" step="0.01" type="number" name="penalty_value" value="<?php echo esc_attr( $loan->penalty_value ); ?>"></label></p>
                        <p><label><?php esc_html_e( 'Note', 'lend-sure' ); ?><input type="text" name="note" value="Late payment penalty"></label></p>
                        <?php submit_button( __( 'Apply Penalty', 'lend-sure' ), 'secondary', 'submit', false ); ?>
                    </form>
                </section>
            </div>
            <?php endif; ?>

            <div class="ls-grid-2">
                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Payments', 'lend-sure' ); ?></h2>
                    <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Date', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Amount', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Breakdown', 'lend-sure' ); ?></th></tr></thead><tbody>
                    <?php if ( ! $payments ) : ?><tr><td colspan="3"><?php esc_html_e( 'No payments.', 'lend-sure' ); ?></td></tr><?php else : foreach ( $payments as $p ) : ?>
                        <tr><td><?php echo esc_html( $p->payment_date ); ?></td><td><?php echo esc_html( $this->money( $p->amount ) ); ?></td><td><?php echo esc_html( sprintf( 'Interest %s | Penalty %s | Principal %s', $this->money_plain( $p->interest_component ), $this->money_plain( $p->penalty_component ), $this->money_plain( $p->principal_component ) ) ); ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody></table>
                </section>

                <section class="ls-panel">
                    <h2><?php esc_html_e( 'Transaction History', 'lend-sure' ); ?></h2>
                    <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Date', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Type', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Amount', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Note', 'lend-sure' ); ?></th></tr></thead><tbody>
                    <?php if ( ! $transactions ) : ?><tr><td colspan="4"><?php esc_html_e( 'No transactions.', 'lend-sure' ); ?></td></tr><?php else : foreach ( $transactions as $t ) : ?>
                        <tr><td><?php echo esc_html( $t->transaction_date ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $t->type ) ) ); ?></td><td><?php echo esc_html( $this->money( $t->amount ) ); ?></td><td><?php echo esc_html( $t->note ); ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody></table>
                </section>
            </div>
        </div>
        <?php
    }

    private function money_plain( $amount ) {
        return get_option( 'lendsure_currency', 'UGX' ) . ' ' . number_format_i18n( (float) $amount, 0 );
    }

    public function reminders_page() {
        $rows = LendSure_Reminders::get_due_loans( 250 );
        $logs = LendSure_DB::get_reminder_logs( 30 );
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php esc_html_e( 'Loan Reminders', 'lend-sure' ); ?></h1>
            <?php $this->notice(); ?>
            <p><?php esc_html_e( 'Use this screen as the daily follow-up list. Timing is calculated from each active loan due date and your grace-period setting.', 'lend-sure' ); ?></p>

            <table class="widefat striped ls-table-margin">
                <thead><tr><th><?php esc_html_e( 'Borrower', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Due Date', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Timing', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Amount Due', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Email', 'lend-sure' ); ?></th><th></th></tr></thead>
                <tbody>
                <?php if ( ! $rows ) : ?><tr><td colspan="6"><?php esc_html_e( 'No active loans.', 'lend-sure' ); ?></td></tr>
                <?php else : foreach ( $rows as $loan ) :
                    $timing = LendSure_Reminders::timing_status( $loan->due_date );
                    if ( 'upcoming' === $timing['key'] && $timing['days'] > 7 ) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html( $loan->full_name ); ?></td>
                        <td><?php echo esc_html( $loan->due_date ); ?></td>
                        <td><span class="ls-badge is-<?php echo esc_attr( $timing['key'] ); ?>"><?php echo esc_html( $timing['label'] ); ?></span></td>
                        <td><?php echo esc_html( $this->money( LendSure_Calculator::total_due( $loan ) ) ); ?></td>
                        <td><?php echo esc_html( $loan->email ?: '—' ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=lendsure-loans&action=view&loan_id=' . (int) $loan->id ) ); ?>"><?php esc_html_e( 'Manage', 'lend-sure' ); ?></a>
                            <?php if ( is_email( $loan->email ) ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ls-inline-form">
                                    <input type="hidden" name="action" value="lendsure_send_borrower_reminder">
                                    <input type="hidden" name="loan_id" value="<?php echo esc_attr( $loan->id ); ?>">
                                    <?php wp_nonce_field( 'lendsure_send_borrower_reminder' ); ?>
                                    <button class="button button-small" type="submit"><?php esc_html_e( 'Email Reminder', 'lend-sure' ); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Recent Reminder Activity', 'lend-sure' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Date', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Type', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Recipient', 'lend-sure' ); ?></th><th><?php esc_html_e( 'Status', 'lend-sure' ); ?></th></tr></thead>
                <tbody>
                <?php if ( ! $logs ) : ?><tr><td colspan="4"><?php esc_html_e( 'No reminder activity yet.', 'lend-sure' ); ?></td></tr>
                <?php else : foreach ( $logs as $log ) : ?>
                    <tr><td><?php echo esc_html( $log->created_at ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $log->type ) ) ); ?></td><td><?php echo esc_html( $log->recipient ); ?></td><td><?php echo esc_html( ucfirst( $log->status ) ); ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap lendsure-wrap">
            <h1><?php esc_html_e( 'Lend Sure Settings', 'lend-sure' ); ?></h1>
            <?php $this->notice(); ?>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ls-form-card">
                <input type="hidden" name="action" value="lendsure_save_settings">
                <?php wp_nonce_field( 'lendsure_save_settings' ); ?>
                <h2><?php esc_html_e( 'Loan Defaults', 'lend-sure' ); ?></h2>
                <p><label><?php esc_html_e( 'Currency', 'lend-sure' ); ?><input name="currency" type="text" value="<?php echo esc_attr( get_option( 'lendsure_currency', 'UGX' ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Default Monthly Interest (%)', 'lend-sure' ); ?><input name="interest" min="0" step="0.01" type="number" value="<?php echo esc_attr( get_option( 'lendsure_default_interest', 20 ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Default Duration (months)', 'lend-sure' ); ?><input name="duration" min="1" max="24" type="number" value="<?php echo esc_attr( get_option( 'lendsure_default_duration_months', 1 ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Grace Period (days)', 'lend-sure' ); ?><input name="grace_days" min="0" type="number" value="<?php echo esc_attr( get_option( 'lendsure_grace_days', 3 ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Penalty Type', 'lend-sure' ); ?><select name="penalty_type"><option value="percentage" <?php selected( get_option( 'lendsure_penalty_type', 'percentage' ), 'percentage' ); ?>><?php esc_html_e( 'Percentage of outstanding principal', 'lend-sure' ); ?></option><option value="fixed" <?php selected( get_option( 'lendsure_penalty_type', 'percentage' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'lend-sure' ); ?></option></select></label></p>
                <p><label><?php esc_html_e( 'Penalty Value', 'lend-sure' ); ?><input name="penalty_value" min="0" step="0.01" type="number" value="<?php echo esc_attr( get_option( 'lendsure_penalty_value', 5 ) ); ?>"></label></p>

                <h2><?php esc_html_e( 'Acknowledgement Header / Company', 'lend-sure' ); ?></h2>
                <p><label><?php esc_html_e( 'Company / Business Name', 'lend-sure' ); ?><input name="company_name" type="text" class="regular-text" value="<?php echo esc_attr( get_option( 'lendsure_company_name', get_bloginfo( 'name' ) ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Company Details', 'lend-sure' ); ?><textarea name="company_details" rows="4" class="large-text" placeholder="Registration details, email, website, address or other header information."><?php echo esc_textarea( get_option( 'lendsure_company_details', '' ) ); ?></textarea></label></p>
                <?php $company_logo_id = absint( get_option( 'lendsure_company_logo_id', 0 ) ); $company_logo_url = $company_logo_id ? wp_get_attachment_image_url( $company_logo_id, 'medium' ) : ''; ?>
                <?php if ( $company_logo_url ) : ?><p><img class="ls-company-logo-preview" src="<?php echo esc_url( $company_logo_url ); ?>" alt="<?php esc_attr_e( 'Current company logo', 'lend-sure' ); ?>"></p><?php endif; ?>
                <p><label><?php esc_html_e( 'Company Logo', 'lend-sure' ); ?><input name="company_logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif"></label></p>
                <?php if ( $company_logo_id ) : ?><p><label class="ls-check-label"><input name="remove_company_logo" type="checkbox" value="1"> <?php esc_html_e( 'Remove current company logo', 'lend-sure' ); ?></label></p><?php endif; ?>
                <p class="description"><?php esc_html_e( 'These company details also represent the lender on the acknowledgement. A compact horizontal or square logo works best.', 'lend-sure' ); ?></p>

                <h2><?php esc_html_e( 'Due-Date Reminders', 'lend-sure' ); ?></h2>
                <p><label class="ls-check-label"><input name="reminders_enabled" type="checkbox" value="1" <?php checked( get_option( 'lendsure_reminders_enabled', '1' ), '1' ); ?>> <?php esc_html_e( 'Enable daily admin due-date digest', 'lend-sure' ); ?></label></p>
                <p><label><?php esc_html_e( 'Digest Email', 'lend-sure' ); ?><input name="reminder_email" type="email" class="regular-text" value="<?php echo esc_attr( get_option( 'lendsure_reminder_email', get_option( 'admin_email' ) ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Start Reminding Before Due Date (days)', 'lend-sure' ); ?><input name="reminder_days_before" min="0" max="30" type="number" value="<?php echo esc_attr( get_option( 'lendsure_reminder_days_before', 3 ) ); ?>"></label></p>
                <p class="description"><?php esc_html_e( 'WordPress sends the digest through wp_mail. WP-Cron is traffic-driven, so the message is daily but not guaranteed at an exact clock time.', 'lend-sure' ); ?></p>
                <?php submit_button( __( 'Save Settings', 'lend-sure' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function add_borrower() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_add_borrower' );

        $name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
        if ( '' === $name ) {
            wp_die( esc_html__( 'Borrower name is required.', 'lend-sure' ) );
        }

        LendSure_DB::insert(
            'borrowers',
            array(
                'full_name'   => $name,
                'phone'       => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
                'email'       => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
                'address'     => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
                'national_id' => isset( $_POST['national_id'] ) ? sanitize_text_field( wp_unslash( $_POST['national_id'] ) ) : '',
                'notes'       => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
                'created_at'  => $this->now(),
                'updated_at'  => $this->now(),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        $this->redirect( 'lendsure-borrowers', array( 'ls_notice' => __( 'Borrower saved.', 'lend-sure' ) ) );
    }

    public function update_borrower() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_update_borrower' );

        $borrower_id = isset( $_POST['borrower_id'] ) ? absint( wp_unslash( $_POST['borrower_id'] ) ) : 0;
        $name        = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';

        if ( ! $borrower_id || '' === $name || ! LendSure_DB::get_borrower( $borrower_id ) ) {
            wp_die( esc_html__( 'A valid borrower and borrower name are required.', 'lend-sure' ) );
        }

        $updated = LendSure_DB::update(
            'borrowers',
            array(
                'full_name'   => $name,
                'phone'       => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
                'email'       => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
                'address'     => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
                'national_id' => isset( $_POST['national_id'] ) ? sanitize_text_field( wp_unslash( $_POST['national_id'] ) ) : '',
                'notes'       => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
                'updated_at'  => $this->now(),
            ),
            array( 'id' => $borrower_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            wp_die( esc_html__( 'The borrower could not be updated.', 'lend-sure' ) );
        }

        $this->redirect(
            'lendsure-borrowers',
            array(
                'action'      => 'edit',
                'borrower_id' => $borrower_id,
                'ls_notice'   => __( 'Borrower details updated.', 'lend-sure' ),
            )
        );
    }

    public function add_loan() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_add_loan' );

        $borrower_id  = isset( $_POST['borrower_id'] ) ? absint( wp_unslash( $_POST['borrower_id'] ) ) : 0;
        $principal    = isset( $_POST['principal'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['principal'] ) ) ) : 0.0;
        $rate         = isset( $_POST['interest_rate'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['interest_rate'] ) ) ) : (float) get_option( 'lendsure_default_interest', 20 );
        $penalty_type = isset( $_POST['penalty_type'] ) && 'fixed' === sanitize_key( wp_unslash( $_POST['penalty_type'] ) ) ? 'fixed' : 'percentage';
        $penalty_value = isset( $_POST['penalty_value'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['penalty_value'] ) ) ) : (float) get_option( 'lendsure_penalty_value', 5 );
        $start        = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
        $due          = isset( $_POST['due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) : '';

        if ( ! $borrower_id || $principal <= 0 || ! $this->valid_date( $start ) || ! $this->valid_date( $due ) ) {
            wp_die( esc_html__( 'Please provide valid loan details.', 'lend-sure' ) );
        }
        if ( strtotime( $due ) < strtotime( $start ) ) {
            wp_die( esc_html__( 'Due date cannot be before the start date.', 'lend-sure' ) );
        }

        $interest = LendSure_Calculator::interest( $principal, $rate );
        $now      = $this->now();
        $created  = LendSure_DB::insert(
            'loans',
            array(
                'borrower_id'        => $borrower_id,
                'original_principal' => $principal,
                'initial_interest'   => $interest,
                'original_due_date'  => $due,
                'current_principal'  => $principal,
                'interest_rate'      => $rate,
                'accrued_interest'   => $interest,
                'accrued_penalty'    => 0,
                'penalty_type'       => $penalty_type,
                'penalty_value'      => $penalty_value,
                'start_date'         => $start,
                'due_date'           => $due,
                'status'             => 'active',
                'purpose'            => isset( $_POST['purpose'] ) ? sanitize_textarea_field( wp_unslash( $_POST['purpose'] ) ) : '',
                'terms'              => isset( $_POST['terms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['terms'] ) ) : '',
                'created_at'         => $now,
                'updated_at'         => $now,
            ),
            array( '%d', '%f', '%f', '%s', '%f', '%f', '%f', '%f', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $created ) {
            wp_die( esc_html__( 'The loan could not be saved.', 'lend-sure' ) );
        }

        $loan_id = LendSure_DB::insert_id();
        $this->add_transaction( $loan_id, 'loan_issued', $principal, $start, __( 'Loan principal issued.', 'lend-sure' ) );

        /* translators: %s: monthly interest rate percentage. */
        $interest_note = sprintf( __( 'Initial monthly interest at %s%%.', 'lend-sure' ), number_format_i18n( $rate, 2 ) );
        $this->add_transaction( $loan_id, 'interest_charged', $interest, $start, $interest_note );
        $this->redirect( 'lendsure-loans', array( 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => __( 'Loan created. The acknowledgement is ready to save as PDF.', 'lend-sure' ) ) );
    }

    public function add_payment() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_add_payment' );

        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $loan    = $this->get_loan( $loan_id );
        if ( ! $loan || 'paid' === $loan->status ) {
            wp_die( esc_html__( 'Loan is unavailable for payment.', 'lend-sure' ) );
        }

        $amount = isset( $_POST['amount'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) : 0.0;
        $date   = isset( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : '';
        if ( $amount <= 0 || ! $this->valid_date( $date ) ) {
            wp_die( esc_html__( 'Please enter a valid payment.', 'lend-sure' ) );
        }
        $total = LendSure_Calculator::total_due( $loan );
        if ( $amount > $total ) {
            wp_die( esc_html__( 'Payment cannot exceed the total amount due.', 'lend-sure' ) );
        }

        $allocation    = LendSure_Calculator::allocate_payment( $amount, $loan );
        $new_interest  = max( 0, (float) $loan->accrued_interest - $allocation['interest'] );
        $new_penalty   = max( 0, (float) $loan->accrued_penalty - $allocation['penalty'] );
        $new_principal = max( 0, (float) $loan->current_principal - $allocation['principal'] );
        $new_status    = ( $new_interest + $new_penalty + $new_principal ) <= 0.009 ? 'paid' : 'active';

        LendSure_DB::update(
            'loans',
            array(
                'current_principal' => $new_principal,
                'accrued_interest'  => $new_interest,
                'accrued_penalty'   => $new_penalty,
                'status'            => $new_status,
                'updated_at'        => $this->now(),
            ),
            array( 'id' => $loan_id ),
            array( '%f', '%f', '%f', '%s', '%s' ),
            array( '%d' )
        );

        LendSure_DB::insert(
            'payments',
            array(
                'loan_id'             => $loan_id,
                'amount'              => $amount,
                'interest_component'  => $allocation['interest'],
                'penalty_component'   => $allocation['penalty'],
                'principal_component' => $allocation['principal'],
                'payment_date'        => $date,
                'method'              => isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '',
                'reference'           => isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '',
                'notes'               => '',
                'created_at'          => $this->now(),
            ),
            array( '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s' )
        );

        $this->add_transaction( $loan_id, 'payment_received', -$amount, $date, __( 'Payment received.', 'lend-sure' ), $allocation );
        $this->redirect( 'lendsure-loans', array( 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => __( 'Payment recorded and balance recalculated.', 'lend-sure' ) ) );
    }

    public function extend_loan() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_extend_loan' );

        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $loan    = $this->get_loan( $loan_id );
        if ( ! $loan || 'paid' === $loan->status ) {
            wp_die( esc_html__( 'Loan cannot be extended.', 'lend-sure' ) );
        }

        $months         = isset( $_POST['months'] ) ? min( 24, max( 1, absint( wp_unslash( $_POST['months'] ) ) ) ) : 1;
        $extension_date = isset( $_POST['extension_date'] ) ? sanitize_text_field( wp_unslash( $_POST['extension_date'] ) ) : $this->today();
        if ( ! $this->valid_date( $extension_date ) || $extension_date < $loan->start_date || $extension_date > $this->today() ) {
            wp_die( esc_html__( 'Please provide a valid extension date between the loan start date and today.', 'lend-sure' ) );
        }
        $capitalize = ! empty( $_POST['capitalize'] );
        $old_due    = $loan->due_date;
        $new_due    = $this->add_months( $old_due, $months );
        $new_principal = (float) $loan->current_principal;
        $new_interest  = (float) $loan->accrued_interest;
        $new_penalty   = (float) $loan->accrued_penalty;

        if ( $capitalize ) {
            $capitalized    = $new_interest + $new_penalty;
            $new_principal += $capitalized;
            $new_interest   = 0;
            $new_penalty    = 0;
            if ( $capitalized > 0 ) {
                $this->add_transaction( $loan_id, 'capitalized', $capitalized, $extension_date, __( 'Outstanding interest and penalty capitalized into principal.', 'lend-sure' ) );
            }
        }

        $fresh_interest = LendSure_Calculator::interest( $new_principal, $loan->interest_rate ) * $months;
        $new_interest  += $fresh_interest;

        LendSure_DB::update(
            'loans',
            array(
                'current_principal' => $new_principal,
                'accrued_interest'  => $new_interest,
                'accrued_penalty'   => $new_penalty,
                'due_date'          => $new_due,
                'updated_at'        => $this->now(),
            ),
            array( 'id' => $loan_id ),
            array( '%f', '%f', '%f', '%s', '%s' ),
            array( '%d' )
        );

        /* translators: 1: number of extension months, 2: previous due date, 3: new due date. */
        $extension_note = sprintf( __( 'Loan extended %1$d month(s): %2$s → %3$s.', 'lend-sure' ), $months, $old_due, $new_due );
        $this->add_transaction( $loan_id, 'extension', 0, $extension_date, $extension_note, array( 'capitalize' => $capitalize, 'extension_date' => $extension_date ) );

        /* translators: %d: number of extension months. */
        $interest_note = sprintf( __( 'Interest charged for %d extension month(s).', 'lend-sure' ), $months );
        $this->add_transaction( $loan_id, 'interest_charged', $fresh_interest, $extension_date, $interest_note );
        $this->redirect( 'lendsure-loans', array( 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => __( 'Loan extended and new interest calculated.', 'lend-sure' ) ) );
    }

    public function apply_penalty() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_apply_penalty' );

        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        $loan    = $this->get_loan( $loan_id );
        if ( ! $loan || 'paid' === $loan->status ) {
            wp_die( esc_html__( 'Penalty cannot be applied.', 'lend-sure' ) );
        }

        $type  = isset( $_POST['penalty_type'] ) && 'fixed' === sanitize_key( wp_unslash( $_POST['penalty_type'] ) ) ? 'fixed' : 'percentage';
        $value = isset( $_POST['penalty_value'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['penalty_value'] ) ) ) : (float) $loan->penalty_value;
        $penalty = LendSure_Calculator::penalty( $loan, $type, $value );
        if ( $penalty <= 0 ) {
            wp_die( esc_html__( 'Penalty value must be greater than zero.', 'lend-sure' ) );
        }

        LendSure_DB::update(
            'loans',
            array(
                'accrued_penalty' => (float) $loan->accrued_penalty + $penalty,
                'updated_at'      => $this->now(),
            ),
            array( 'id' => $loan_id ),
            array( '%f', '%s' ),
            array( '%d' )
        );

        $note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : __( 'Penalty applied.', 'lend-sure' );
        $this->add_transaction( $loan_id, 'penalty_charged', $penalty, $this->today(), $note, array( 'penalty_type' => $type, 'penalty_value' => $value ) );
        $this->redirect( 'lendsure-loans', array( 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => __( 'Penalty applied.', 'lend-sure' ) ) );
    }

    public function upload_ack() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_upload_ack' );

        $loan_id = isset( $_POST['loan_id'] ) ? absint( wp_unslash( $_POST['loan_id'] ) ) : 0;
        if ( empty( $_FILES['ack_file']['name'] ) ) {
            wp_die( esc_html__( 'Choose a document to upload.', 'lend-sure' ) );
        }

        $allowed  = array( 'pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png' );
        $filename = sanitize_file_name( wp_unslash( $_FILES['ack_file']['name'] ) );
        $check    = wp_check_filetype( $filename, $allowed );
        if ( empty( $check['type'] ) ) {
            wp_die( esc_html__( 'Only PDF, JPG, JPEG, and PNG files are allowed.', 'lend-sure' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'ack_file', 0, array(), array( 'test_form' => false ) );
        if ( is_wp_error( $attachment_id ) ) {
            wp_die( esc_html( $attachment_id->get_error_message() ) );
        }

        LendSure_DB::update(
            'loans',
            array(
                'acknowledgement_attachment_id' => absint( $attachment_id ),
                'updated_at'                    => $this->now(),
            ),
            array( 'id' => $loan_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );
        $this->add_transaction( $loan_id, 'acknowledgement_uploaded', 0, $this->today(), __( 'Signed acknowledgement uploaded.', 'lend-sure' ), array( 'attachment_id' => absint( $attachment_id ) ) );
        $this->redirect( 'lendsure-loans', array( 'action' => 'view', 'loan_id' => $loan_id, 'ls_notice' => __( 'Signed acknowledgement saved with this loan.', 'lend-sure' ) ) );
    }

    public function acknowledgement() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this document.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_acknowledgement' );

        $loan_id = isset( $_GET['loan_id'] ) ? absint( wp_unslash( $_GET['loan_id'] ) ) : 0;
        $loan    = $this->get_loan( $loan_id );
        if ( ! $loan ) {
            wp_die( esc_html__( 'Loan not found.', 'lend-sure' ) );
        }
        $currency         = get_option( 'lendsure_currency', 'UGX' );
        $company_name     = get_option( 'lendsure_company_name', get_bloginfo( 'name' ) );
        $company_details  = get_option( 'lendsure_company_details', '' );
        $company_logo_id  = absint( get_option( 'lendsure_company_logo_id', 0 ) );
        $company_logo_url = $company_logo_id ? wp_get_attachment_image_url( $company_logo_id, 'medium' ) : '';
        $lender_name      = $company_name ? $company_name : get_bloginfo( 'name' );
        $total            = LendSure_Calculator::total_due( $loan );
        include LENDSURE_DIR . 'templates/acknowledgement.php';
        exit;
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_save_settings' );

        $currency      = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'UGX';
        $interest      = isset( $_POST['interest'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['interest'] ) ) ) : 20;
        $duration      = isset( $_POST['duration'] ) ? max( 1, absint( wp_unslash( $_POST['duration'] ) ) ) : 1;
        $grace_days    = isset( $_POST['grace_days'] ) ? max( 0, absint( wp_unslash( $_POST['grace_days'] ) ) ) : 3;
        $penalty_type  = isset( $_POST['penalty_type'] ) && 'fixed' === sanitize_key( wp_unslash( $_POST['penalty_type'] ) ) ? 'fixed' : 'percentage';
        $penalty_value = isset( $_POST['penalty_value'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['penalty_value'] ) ) ) : 5;
        $company_name  = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        $company_details = isset( $_POST['company_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['company_details'] ) ) : '';

        update_option( 'lendsure_currency', $currency );
        update_option( 'lendsure_default_interest', $interest );
        update_option( 'lendsure_default_duration_months', $duration );
        update_option( 'lendsure_grace_days', $grace_days );
        update_option( 'lendsure_penalty_type', $penalty_type );
        update_option( 'lendsure_penalty_value', $penalty_value );
        update_option( 'lendsure_company_name', $company_name );
        update_option( 'lendsure_company_details', $company_details );
        update_option( 'lendsure_lender_name', $company_name );

        if ( ! empty( $_POST['remove_company_logo'] ) ) {
            update_option( 'lendsure_company_logo_id', 0 );
        } elseif ( ! empty( $_FILES['company_logo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $logo_id = media_handle_upload( 'company_logo', 0, array(), array( 'test_form' => false ) );
            if ( is_wp_error( $logo_id ) || ! wp_attachment_is_image( $logo_id ) ) {
                if ( ! is_wp_error( $logo_id ) ) {
                    wp_delete_attachment( $logo_id, true );
                }
                wp_die( esc_html__( 'The company logo could not be saved. Please upload a valid image.', 'lend-sure' ) );
            }
            update_option( 'lendsure_company_logo_id', absint( $logo_id ) );
        }

        update_option( 'lendsure_reminders_enabled', ! empty( $_POST['reminders_enabled'] ) ? '1' : '0' );
        $reminder_email = isset( $_POST['reminder_email'] ) ? sanitize_email( wp_unslash( $_POST['reminder_email'] ) ) : get_option( 'admin_email' );
        update_option( 'lendsure_reminder_email', is_email( $reminder_email ) ? $reminder_email : get_option( 'admin_email' ) );
        $reminder_days = isset( $_POST['reminder_days_before'] ) ? absint( wp_unslash( $_POST['reminder_days_before'] ) ) : 3;
        update_option( 'lendsure_reminder_days_before', min( 30, max( 0, $reminder_days ) ) );
        $this->redirect( 'lendsure-settings', array( 'ls_notice' => __( 'Settings saved.', 'lend-sure' ) ) );
    }

    public function export_loans() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export loans.', 'lend-sure' ) );
        }
        check_admin_referer( 'lendsure_export_loans' );

        $rows = LendSure_DB::get_export_loans();

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=lend-sure-loans-' . gmdate( 'Y-m-d' ) . '.csv' );

        $out = fopen( 'php://output', 'w' );
        if ( false === $out ) {
            wp_die( esc_html__( 'The CSV export stream could not be opened.', 'lend-sure' ) );
        }

        fputcsv( $out, array( 'Loan ID', 'Borrower', 'Phone', 'Email', 'Original Principal', 'Current Principal', 'Interest Rate %', 'Interest Due', 'Penalty Due', 'Start Date', 'Due Date', 'Status', 'Signed Acknowledgement' ) );
        foreach ( $rows as $row ) {
            fputcsv(
                $out,
                array(
                    $row['id'],
                    $row['full_name'],
                    $row['phone'],
                    $row['email'],
                    $row['original_principal'],
                    $row['current_principal'],
                    $row['interest_rate'],
                    $row['accrued_interest'],
                    $row['accrued_penalty'],
                    $row['start_date'],
                    $row['due_date'],
                    $row['status'],
                    ! empty( $row['acknowledgement_attachment_id'] ) ? 'Yes' : 'No',
                )
            );
        }
        exit;
    }

    private function valid_date( $date ) {
        $d = DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }
}
