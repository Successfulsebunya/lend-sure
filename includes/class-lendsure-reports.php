<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Management reporting for Your Loan Ledger.
 */
class LendSure_Reports {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ), 25 );
        add_action( 'admin_post_lendsure_export_business_report', array( $this, 'export_csv' ) );
    }

    public function menu() {
        add_submenu_page(
            'lendsure',
            __( 'Reports', 'your-loan-ledger' ),
            __( 'Reports', 'your-loan-ledger' ),
            'manage_options',
            'lendsure-reports',
            array( $this, 'render_page' )
        );
    }

    private function valid_date( $date ) {
        $parsed = DateTime::createFromFormat( 'Y-m-d', $date );
        return $parsed && $parsed->format( 'Y-m-d' ) === $date;
    }

    private function requested_range() {
        $today = current_time( 'Y-m-d' );
        $from  = wp_date( 'Y-01-01', current_time( 'timestamp' ) );
        $to    = $today;

        if ( isset( $_GET['from'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
            $candidate = sanitize_text_field( wp_unslash( $_GET['from'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $this->valid_date( $candidate ) ) {
                $from = $candidate;
            }
        }
        if ( isset( $_GET['to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
            $candidate = sanitize_text_field( wp_unslash( $_GET['to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $this->valid_date( $candidate ) ) {
                $to = $candidate;
            }
        }
        if ( $from > $to ) {
            $tmp  = $from;
            $from = $to;
            $to   = $tmp;
        }
        return array( $from, $to );
    }

    private function build_report( $from, $to ) {
        global $wpdb;

        $loans    = LendSure_DB::table( 'loans' );
        $payments = LendSure_DB::table( 'payments' );
        $grace    = max( 0, absint( get_option( 'lendsure_grace_days', 3 ) ) );
        $today    = current_time( 'Y-m-d' );
        $overdue_cutoff = wp_date( 'Y-m-d', strtotime( '-' . $grace . ' days', strtotime( $today ) ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-run report against plugin-owned ledger tables.
        $loan_period = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS loans_issued,
                    COUNT(DISTINCT borrower_id) AS borrowers_served,
                    COALESCE(SUM(original_principal),0) AS principal_issued
                 FROM %i
                 WHERE start_date BETWEEN %s AND %s AND status <> %s",
                $loans,
                $from,
                $to,
                'void'
            )
        );

        // Voided payments are zeroed operationally by the reversal workflow, so they do not inflate these sums.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-run report against plugin-owned ledger tables.
        $collections = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS payment_count,
                    COALESCE(SUM(amount),0) AS total_collected,
                    COALESCE(SUM(principal_component),0) AS principal_collected,
                    COALESCE(SUM(interest_component),0) AS interest_collected,
                    COALESCE(SUM(penalty_component),0) AS penalties_collected
                 FROM %i
                 WHERE payment_date BETWEEN %s AND %s AND amount > 0",
                $payments,
                $from,
                $to
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Current portfolio snapshot for management report.
        $portfolio = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS active_loans,
                    COALESCE(SUM(current_principal),0) AS outstanding_principal,
                    COALESCE(SUM(accrued_interest),0) AS outstanding_interest,
                    COALESCE(SUM(accrued_penalty),0) AS outstanding_penalties
                 FROM %i
                 WHERE status = %s",
                $loans,
                'active'
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Current overdue-risk snapshot for management report.
        $overdue = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS overdue_loans,
                    COALESCE(SUM(current_principal + accrued_interest + accrued_penalty),0) AS overdue_exposure
                 FROM %i
                 WHERE status = %s AND due_date < %s",
                $loans,
                'active',
                $overdue_cutoff
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Monthly issued-volume report from plugin-owned tables.
        $issued_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(start_date, '%%Y-%%m') AS month_key,
                    COUNT(*) AS loans_count,
                    COALESCE(SUM(original_principal),0) AS principal_issued
                 FROM %i
                 WHERE start_date BETWEEN %s AND %s AND status <> %s
                 GROUP BY DATE_FORMAT(start_date, '%%Y-%%m')
                 ORDER BY month_key ASC",
                $loans,
                $from,
                $to,
                'void'
            ),
            OBJECT_K
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Monthly collection report from plugin-owned tables.
        $collection_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(payment_date, '%%Y-%%m') AS month_key,
                    COALESCE(SUM(amount),0) AS total_collected,
                    COALESCE(SUM(principal_component),0) AS principal_collected,
                    COALESCE(SUM(interest_component + penalty_component),0) AS lending_income
                 FROM %i
                 WHERE payment_date BETWEEN %s AND %s AND amount > 0
                 GROUP BY DATE_FORMAT(payment_date, '%%Y-%%m')
                 ORDER BY month_key ASC",
                $payments,
                $from,
                $to
            ),
            OBJECT_K
        );

        $months = array();
        $cursor = new DateTimeImmutable( $from, wp_timezone() );
        $end    = new DateTimeImmutable( $to, wp_timezone() );
        $cursor = $cursor->modify( 'first day of this month' );
        $end    = $end->modify( 'first day of this month' );

        while ( $cursor <= $end ) {
            $key    = $cursor->format( 'Y-m' );
            $issued = isset( $issued_rows[ $key ] ) ? $issued_rows[ $key ] : null;
            $paid   = isset( $collection_rows[ $key ] ) ? $collection_rows[ $key ] : null;

            $months[] = array(
                'month'               => wp_date( 'M Y', $cursor->getTimestamp(), wp_timezone() ),
                'loans_count'         => $issued ? (int) $issued->loans_count : 0,
                'principal_issued'    => $issued ? (float) $issued->principal_issued : 0.0,
                'total_collected'     => $paid ? (float) $paid->total_collected : 0.0,
                'principal_collected' => $paid ? (float) $paid->principal_collected : 0.0,
                'lending_income'      => $paid ? (float) $paid->lending_income : 0.0,
            );
            $cursor = $cursor->modify( '+1 month' );
        }

        $principal_issued    = (float) $loan_period->principal_issued;
        $principal_collected = (float) $collections->principal_collected;
        $expected            = (float) $portfolio->outstanding_principal + (float) $portfolio->outstanding_interest + (float) $portfolio->outstanding_penalties;

        return array(
            'from'                  => $from,
            'to'                    => $to,
            'loans_issued'          => (int) $loan_period->loans_issued,
            'borrowers_served'      => (int) $loan_period->borrowers_served,
            'principal_issued'      => $principal_issued,
            'average_loan_size'     => (int) $loan_period->loans_issued > 0 ? $principal_issued / (int) $loan_period->loans_issued : 0,
            'payment_count'         => (int) $collections->payment_count,
            'total_collected'       => (float) $collections->total_collected,
            'principal_collected'   => $principal_collected,
            'interest_collected'    => (float) $collections->interest_collected,
            'penalties_collected'   => (float) $collections->penalties_collected,
            'lending_income'        => (float) $collections->interest_collected + (float) $collections->penalties_collected,
            'principal_recovery'    => $principal_issued > 0 ? ( $principal_collected / $principal_issued ) * 100 : 0,
            'active_loans'          => (int) $portfolio->active_loans,
            'outstanding_principal' => (float) $portfolio->outstanding_principal,
            'outstanding_interest'  => (float) $portfolio->outstanding_interest,
            'outstanding_penalties' => (float) $portfolio->outstanding_penalties,
            'expected_amount'       => $expected,
            'overdue_loans'         => (int) $overdue->overdue_loans,
            'overdue_exposure'      => (float) $overdue->overdue_exposure,
            'months'                => $months,
            'grace_days'            => $grace,
        );
    }

    private function money( $amount ) {
        return get_option( 'lendsure_currency', 'UGX' ) . ' ' . number_format_i18n( (float) $amount, 0 );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        list( $from, $to ) = $this->requested_range();
        $report = $this->build_report( $from, $to );
        ?>
        <div class="wrap yll-report-wrap">
            <h1><?php esc_html_e( 'Lending Performance Report', 'your-loan-ledger' ); ?></h1>
            <p class="description"><?php esc_html_e( 'A management view for team discussion. “Lending Income” means interest plus penalties collected; it is not accounting profit because operating expenses, taxes, write-offs, and other costs are not tracked.', 'your-loan-ledger' ); ?></p>

            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                <input type="hidden" name="page" value="lendsure-reports">
                <label><?php esc_html_e( 'From', 'your-loan-ledger' ); ?> <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
                <label><?php esc_html_e( 'To', 'your-loan-ledger' ); ?> <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
                <button class="button button-primary" type="submit"><?php esc_html_e( 'Generate Report', 'your-loan-ledger' ); ?></button>
                <button class="button js-yll-print" type="button"><?php esc_html_e( 'Print / Save PDF', 'your-loan-ledger' ); ?></button>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lendsure_export_business_report">
                <input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>">
                <input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>">
                <?php wp_nonce_field( 'lendsure_export_business_report' ); ?>
                <?php submit_button( __( 'Export CSV', 'your-loan-ledger' ), 'secondary', 'submit', false ); ?>
            </form>

            <?php /* translators: 1: report start date, 2: report end date. */ ?>
            <h2><?php echo esc_html( sprintf( __( 'Reporting Period: %1$s to %2$s', 'your-loan-ledger' ), $from, $to ) ); ?></h2>

            <h2><?php esc_html_e( 'Executive Summary', 'your-loan-ledger' ); ?></h2>
            <table class="widefat striped"><tbody>
                <tr><th><?php esc_html_e( 'Loans issued', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $report['loans_issued'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Borrowers served', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $report['borrowers_served'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Principal issued', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['principal_issued'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Average loan size', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['average_loan_size'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Total cash collected', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['total_collected'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Principal collected', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['principal_collected'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Interest collected', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['interest_collected'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Penalties collected', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['penalties_collected'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Lending Income', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['lending_income'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Principal collected vs principal issued', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( number_format_i18n( $report['principal_recovery'], 1 ) . '%' ); ?></td></tr>
            </tbody></table>
            <p class="description"><?php esc_html_e( 'The principal-collected percentage can exceed 100% when the selected period includes repayments for loans issued before the period.', 'your-loan-ledger' ); ?></p>

            <h2><?php esc_html_e( 'Current Portfolio & Risk', 'your-loan-ledger' ); ?></h2>
            <table class="widefat striped"><tbody>
                <tr><th><?php esc_html_e( 'Active loans', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $report['active_loans'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Outstanding principal', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['outstanding_principal'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Outstanding interest', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['outstanding_interest'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Outstanding penalties', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['outstanding_penalties'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Current expected amount', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['expected_amount'] ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Overdue loans', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $report['overdue_loans'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Overdue exposure', 'your-loan-ledger' ); ?></th><td><?php echo esc_html( $this->money( $report['overdue_exposure'] ) ); ?></td></tr>
            </tbody></table>
            <?php /* translators: %d: configured number of grace-period days. */ ?>
            <p class="description"><?php echo esc_html( sprintf( __( 'Overdue exposure uses the configured %d-day grace period.', 'your-loan-ledger' ), $report['grace_days'] ) ); ?></p>

            <h2><?php esc_html_e( 'Monthly Performance', 'your-loan-ledger' ); ?></h2>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Month', 'your-loan-ledger' ); ?></th><th><?php esc_html_e( 'Loans', 'your-loan-ledger' ); ?></th><th><?php esc_html_e( 'Principal Issued', 'your-loan-ledger' ); ?></th><th><?php esc_html_e( 'Cash Collected', 'your-loan-ledger' ); ?></th><th><?php esc_html_e( 'Principal Collected', 'your-loan-ledger' ); ?></th><th><?php esc_html_e( 'Lending Income', 'your-loan-ledger' ); ?></th></tr></thead><tbody>
            <?php foreach ( $report['months'] as $month ) : ?>
                <tr><td><?php echo esc_html( $month['month'] ); ?></td><td><?php echo esc_html( $month['loans_count'] ); ?></td><td><?php echo esc_html( $this->money( $month['principal_issued'] ) ); ?></td><td><?php echo esc_html( $this->money( $month['total_collected'] ) ); ?></td><td><?php echo esc_html( $this->money( $month['principal_collected'] ) ); ?></td><td><?php echo esc_html( $this->money( $month['lending_income'] ) ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

            <h2><?php esc_html_e( 'Management Discussion Notes', 'your-loan-ledger' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Compare monthly loan issuance with collection performance.', 'your-loan-ledger' ); ?></li>
                <li><?php esc_html_e( 'Review overdue exposure and identify loans needing follow-up.', 'your-loan-ledger' ); ?></li>
                <li><?php esc_html_e( 'Discuss whether lending volume is growing, stable, or declining.', 'your-loan-ledger' ); ?></li>
                <li><?php esc_html_e( 'Use Lending Income as a collection indicator, not as net company profit.', 'your-loan-ledger' ); ?></li>
            </ul>
        </div>
        <?php
    }

    public function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'your-loan-ledger' ) );
        }
        check_admin_referer( 'lendsure_export_business_report' );

        $from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
        $to   = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
        if ( ! $this->valid_date( $from ) || ! $this->valid_date( $to ) ) {
            wp_die( esc_html__( 'Invalid report date range.', 'your-loan-ledger' ) );
        }
        if ( $from > $to ) {
            $tmp  = $from;
            $from = $to;
            $to   = $tmp;
        }

        $report   = $this->build_report( $from, $to );
        $filename = 'your-loan-ledger-report-' . $from . '-to-' . $to . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

        $rows = array(
            array( 'Metric', 'Value' ),
            array( 'From', $from ),
            array( 'To', $to ),
            array( 'Loans Issued', $report['loans_issued'] ),
            array( 'Borrowers Served', $report['borrowers_served'] ),
            array( 'Principal Issued', $report['principal_issued'] ),
            array( 'Average Loan Size', $report['average_loan_size'] ),
            array( 'Total Collected', $report['total_collected'] ),
            array( 'Principal Collected', $report['principal_collected'] ),
            array( 'Interest Collected', $report['interest_collected'] ),
            array( 'Penalties Collected', $report['penalties_collected'] ),
            array( 'Lending Income', $report['lending_income'] ),
            array( 'Active Loans', $report['active_loans'] ),
            array( 'Outstanding Principal', $report['outstanding_principal'] ),
            array( 'Current Expected Amount', $report['expected_amount'] ),
            array( 'Overdue Loans', $report['overdue_loans'] ),
            array( 'Overdue Exposure', $report['overdue_exposure'] ),
            array(),
            array( 'Month', 'Loans', 'Principal Issued', 'Cash Collected', 'Principal Collected', 'Lending Income' ),
        );

        foreach ( $report['months'] as $month ) {
            $rows[] = array( $month['month'], $month['loans_count'], $month['principal_issued'], $month['total_collected'], $month['principal_collected'], $month['lending_income'] );
        }

        foreach ( $rows as $row ) {
            $escaped = array_map(
                static function ( $value ) {
                    $value = str_replace( '"', '""', (string) $value );
                    return '"' . $value . '"';
                },
                $row
            );
            echo implode( ',', $escaped ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV attachment output, values are CSV-escaped above.
        }
        exit;
    }
}
