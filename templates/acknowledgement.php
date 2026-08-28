<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$lendsure_penalty_label = 'fixed' === $loan->penalty_type
    ? sprintf( '%1$s %2$s', $currency, number_format_i18n( (float) $loan->penalty_value, 0 ) )
    : sprintf(
        /* translators: %s: late-payment penalty percentage. */
        __( '%s%% of outstanding principal', 'your-loan-ledger' ),
        number_format_i18n( (float) $loan->penalty_value, 2 )
    );

/* translators: %d: numeric loan ID. */
$lendsure_document_title = sprintf( __( 'Your Loan Ledger - Loan Acknowledgement - LS-%06d', 'your-loan-ledger' ), $loan->id );
/* translators: %d: numeric loan ID. */
$lendsure_loan_reference = sprintf( __( 'Loan Reference: LS-%06d', 'your-loan-ledger' ), $loan->id );
/* translators: %s: monthly interest rate percentage. */
$lendsure_interest_term = sprintf( __( 'Interest is charged at %s%% per month.', 'your-loan-ledger' ), number_format_i18n( $loan->interest_rate, 2 ) );
/* translators: %s: agreed late-payment penalty description. */
$lendsure_penalty_term = sprintf( __( 'The agreed late-payment penalty is %s.', 'your-loan-ledger' ), $lendsure_penalty_label );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $lendsure_document_title ); ?></title>
<?php wp_print_styles( array( 'your-loan-ledger-acknowledgement' ) ); ?>
</head>
<body>
<div class="actions">
    <button id="yll-save-pdf" type="button"><?php esc_html_e( 'Save PDF', 'your-loan-ledger' ); ?></button>
    <div class="hint"><?php esc_html_e( 'In the browser dialog, choose “Save as PDF”. Printing remains available from the same dialog if needed.', 'your-loan-ledger' ); ?></div>
</div>
<main class="page">
<header class="doc-header">
    <?php if ( $company_logo_url ) : ?>
        <img class="logo" src="<?php echo esc_url( $company_logo_url ); ?>" alt="<?php echo esc_attr( $company_name ); ?>">
    <?php endif; ?>
    <div class="brand">
        <div class="company-name"><?php echo esc_html( $company_name ? $company_name : $lender_name ); ?></div>
        <?php if ( $company_details ) : ?><div class="company-details"><?php echo esc_html( $company_details ); ?></div><?php endif; ?>
    </div>
</header>

<div class="title">
    <h1><?php esc_html_e( 'LOAN ACKNOWLEDGEMENT & ACCEPTANCE', 'your-loan-ledger' ); ?></h1>
    <div class="sub"><?php echo esc_html( $lendsure_loan_reference ); ?></div>
</div>

<p><?php esc_html_e( 'This document records the loan agreement between the lender and borrower named below. By signing, the borrower confirms receipt of the principal and accepts the repayment terms stated in this acknowledgement.', 'your-loan-ledger' ); ?></p>

<div class="row">
<div class="col box">
<h3><?php esc_html_e( 'Lender', 'your-loan-ledger' ); ?></h3>
<p><strong><?php echo esc_html( $company_name ? $company_name : $lender_name ); ?></strong><?php if ( $company_details ) : ?><br><?php echo nl2br( esc_html( $company_details ) ); ?><?php endif; ?></p>
</div>
<div class="col box">
<h3><?php esc_html_e( 'Borrower', 'your-loan-ledger' ); ?></h3>
<p><strong><?php echo esc_html( $loan->full_name ); ?></strong><br><?php echo nl2br( esc_html( $loan->address ) ); ?><br><?php echo esc_html( $loan->phone ); ?><br><?php echo esc_html( $loan->national_id ); ?></p>
</div>
</div>

<div class="box">
<table>
<tr><td><?php esc_html_e( 'Principal received', 'your-loan-ledger' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->original_principal, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Monthly interest rate', 'your-loan-ledger' ); ?></td><td><?php echo esc_html( number_format_i18n( $loan->interest_rate, 2 ) . '%' ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial interest charged', 'your-loan-ledger' ); ?></td><td><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->initial_interest, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Agreed late-payment penalty', 'your-loan-ledger' ); ?></td><td><?php echo esc_html( $lendsure_penalty_label ); ?></td></tr>
<tr><td><?php esc_html_e( 'Loan start date', 'your-loan-ledger' ); ?></td><td><?php echo esc_html( $loan->start_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial due date', 'your-loan-ledger' ); ?></td><td><?php echo esc_html( $loan->original_due_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Amount due under this acknowledgement', 'your-loan-ledger' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $loan->original_principal + (float) $loan->initial_interest, 0 ) ); ?></td></tr>
<?php if ( $loan->purpose ) : ?><tr><td><?php esc_html_e( 'Purpose', 'your-loan-ledger' ); ?></td><td><?php echo nl2br( esc_html( $loan->purpose ) ); ?></td></tr><?php endif; ?>
</table>
</div>

<h3><?php esc_html_e( 'Loan Terms', 'your-loan-ledger' ); ?></h3>
<ul class="terms-list">
    <li><?php echo esc_html( $lendsure_interest_term ); ?></li>
    <li><?php echo esc_html( $lendsure_penalty_term ); ?></li>
    <li><?php esc_html_e( 'Payments are allocated to outstanding interest first, then penalties, then principal.', 'your-loan-ledger' ); ?></li>
    <li><?php esc_html_e( 'Any extension, partial payment, penalty, or balance adjustment will be recorded in the lender’s loan ledger.', 'your-loan-ledger' ); ?></li>
</ul>

<?php if ( $loan->terms ) : ?>
<h3><?php esc_html_e( 'Additional Terms', 'your-loan-ledger' ); ?></h3>
<p><?php echo nl2br( esc_html( $loan->terms ) ); ?></p>
<?php endif; ?>

<h3><?php esc_html_e( 'Acceptance', 'your-loan-ledger' ); ?></h3>
<p><?php esc_html_e( 'I, the borrower named above, acknowledge receiving the principal amount shown in this document. I understand and accept the monthly interest rate, due date, agreed late-payment penalty, payment allocation rules, and any additional terms recorded above.', 'your-loan-ledger' ); ?></p>

<div class="signatures">
<div><div class="line"><?php esc_html_e( 'Borrower Signature', 'your-loan-ledger' ); ?></div><p><?php esc_html_e( 'Date:', 'your-loan-ledger' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Lender / Authorized Signatory', 'your-loan-ledger' ); ?></div><p><?php esc_html_e( 'Date:', 'your-loan-ledger' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Witness 1 Name & Signature', 'your-loan-ledger' ); ?></div><p><?php esc_html_e( 'Phone / ID:', 'your-loan-ledger' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Witness 2 Name & Signature', 'your-loan-ledger' ); ?></div><p><?php esc_html_e( 'Phone / ID:', 'your-loan-ledger' ); ?> __________________</p></div>
</div>

<p class="legal-note"><?php esc_html_e( 'Keep the signed copy with the corresponding Your Loan Ledger loan record for audit and reference purposes.', 'your-loan-ledger' ); ?></p>
</main>
<?php wp_print_footer_scripts(); ?>
</body>
</html>
