<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$penalty_label = 'fixed' === $loan->penalty_type
    ? sprintf( '%1$s %2$s', $currency, number_format_i18n( (float) $loan->penalty_value, 0 ) )
    : sprintf( '%s%% of outstanding principal', number_format_i18n( (float) $loan->penalty_value, 2 ) );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( sprintf( 'Lend-Sure-Loan-Acknowledgement-LS-%06d', $loan->id ) ); ?></title>
<style>
@page{size:A4;margin:12mm}
*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;background:#f2f2f2;margin:0;padding:24px;line-height:1.45}.page{max-width:800px;margin:auto;background:#fff;padding:42px 50px;box-shadow:0 2px 12px rgba(0,0,0,.12)}.doc-header{display:flex;align-items:center;gap:22px;border-bottom:2px solid #111;padding-bottom:18px;margin-bottom:26px}.logo{width:105px;max-height:85px;object-fit:contain}.brand{flex:1}.company-name{font-size:25px;font-weight:700;margin:0 0 5px}.company-details{white-space:pre-line;color:#444;font-size:13px}.title{text-align:center;margin:26px 0}.title h1{font-size:22px;margin:0 0 6px}.sub{font-size:13px}.row{display:flex;gap:24px}.col{flex:1}.box{border:1px solid #ccc;padding:16px;margin:18px 0}.box h3{margin-top:0}.amount{font-size:18px;font-weight:700}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:40px 50px;margin-top:55px}.line{border-top:1px solid #111;padding-top:8px;margin-top:45px}.actions{text-align:center;margin:0 auto 18px;max-width:800px}.actions button{padding:11px 20px;font-size:15px;font-weight:600;cursor:pointer}.hint{font-size:12px;color:#555;margin-top:7px}table{width:100%;border-collapse:collapse}td{padding:7px 0;vertical-align:top}td:first-child{width:42%;font-weight:bold}.terms-list{margin:8px 0 0 20px}.legal-note{font-size:12px;color:#444;margin-top:22px}@media(max-width:700px){body{padding:10px}.page{padding:25px}.row,.doc-header{display:block}.logo{margin-bottom:12px}.signatures{grid-template-columns:1fr}}@media print{body{background:#fff;padding:0}.page{box-shadow:none;max-width:none;padding:0}.actions{display:none}.box{break-inside:avoid}.signatures{break-inside:avoid}}
</style>
</head>
<body>
<div class="actions">
    <button type="button" onclick="window.print()"><?php esc_html_e( 'Save PDF', 'lend-sure' ); ?></button>
    <div class="hint"><?php esc_html_e( 'In the browser dialog, choose “Save as PDF”. Printing remains available from the same dialog if needed.', 'lend-sure' ); ?></div>
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
    <h1><?php esc_html_e( 'LOAN ACKNOWLEDGEMENT & ACCEPTANCE', 'lend-sure' ); ?></h1>
    <div class="sub"><?php echo esc_html( sprintf( __( 'Loan Reference: LS-%06d', 'lend-sure' ), $loan->id ) ); ?></div>
</div>

<p><?php esc_html_e( 'This document records the loan agreement between the lender and borrower named below. By signing, the borrower confirms receipt of the principal and accepts the repayment terms stated in this acknowledgement.', 'lend-sure' ); ?></p>

<div class="row">
<div class="col box">
<h3><?php esc_html_e( 'Lender', 'lend-sure' ); ?></h3>
<p><strong><?php echo esc_html( $lender_name ); ?></strong><br><?php echo nl2br( esc_html( $lender_address ) ); ?><br><?php echo esc_html( $lender_phone ); ?></p>
</div>
<div class="col box">
<h3><?php esc_html_e( 'Borrower', 'lend-sure' ); ?></h3>
<p><strong><?php echo esc_html( $loan->full_name ); ?></strong><br><?php echo nl2br( esc_html( $loan->address ) ); ?><br><?php echo esc_html( $loan->phone ); ?><br><?php echo esc_html( $loan->national_id ); ?></p>
</div>
</div>

<div class="box">
<table>
<tr><td><?php esc_html_e( 'Principal received', 'lend-sure' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->original_principal, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Monthly interest rate', 'lend-sure' ); ?></td><td><?php echo esc_html( number_format_i18n( $loan->interest_rate, 2 ) . '%' ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial interest charged', 'lend-sure' ); ?></td><td><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->initial_interest, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Agreed late-payment penalty', 'lend-sure' ); ?></td><td><?php echo esc_html( $penalty_label ); ?></td></tr>
<tr><td><?php esc_html_e( 'Loan start date', 'lend-sure' ); ?></td><td><?php echo esc_html( $loan->start_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial due date', 'lend-sure' ); ?></td><td><?php echo esc_html( $loan->original_due_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Amount due under this acknowledgement', 'lend-sure' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $loan->original_principal + (float) $loan->initial_interest, 0 ) ); ?></td></tr>
<?php if ( $loan->purpose ) : ?><tr><td><?php esc_html_e( 'Purpose', 'lend-sure' ); ?></td><td><?php echo nl2br( esc_html( $loan->purpose ) ); ?></td></tr><?php endif; ?>
</table>
</div>

<h3><?php esc_html_e( 'Loan Terms', 'lend-sure' ); ?></h3>
<ul class="terms-list">
    <li><?php echo esc_html( sprintf( __( 'Interest is charged at %s%% per month.', 'lend-sure' ), number_format_i18n( $loan->interest_rate, 2 ) ) ); ?></li>
    <li><?php echo esc_html( sprintf( __( 'The agreed late-payment penalty is %s.', 'lend-sure' ), $penalty_label ) ); ?></li>
    <li><?php esc_html_e( 'Payments are allocated to outstanding interest first, then penalties, then principal.', 'lend-sure' ); ?></li>
    <li><?php esc_html_e( 'Any extension, partial payment, penalty, or balance adjustment will be recorded in the lender’s loan ledger.', 'lend-sure' ); ?></li>
</ul>

<?php if ( $loan->terms ) : ?>
<h3><?php esc_html_e( 'Additional Terms', 'lend-sure' ); ?></h3>
<p><?php echo nl2br( esc_html( $loan->terms ) ); ?></p>
<?php endif; ?>

<h3><?php esc_html_e( 'Acceptance', 'lend-sure' ); ?></h3>
<p><?php esc_html_e( 'I, the borrower named above, acknowledge receiving the principal amount shown in this document. I understand and accept the monthly interest rate, due date, agreed late-payment penalty, payment allocation rules, and any additional terms recorded above.', 'lend-sure' ); ?></p>

<div class="signatures">
<div><div class="line"><?php esc_html_e( 'Borrower Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Date:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Lender Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Date:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Witness Name & Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Phone / ID:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Borrower Thumbprint (optional)', 'lend-sure' ); ?></div></div>
</div>

<p class="legal-note"><?php esc_html_e( 'Keep the signed copy with the corresponding Lend Sure loan record for audit and reference purposes.', 'lend-sure' ); ?></p>
</main>
</body>
</html>
