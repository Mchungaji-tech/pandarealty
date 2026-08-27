<?php
/**
 * Panda Realty - Invoicing & Installments Management
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_invoices');

$conn = get_db_connection();
$msg = '';

// Handle Status Change
if (isset($_GET['mark_paid']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE invoices SET status = 'paid', balance_due = 0, amount_paid = total_amount WHERE id = $id");
    log_security_action('INVOICE_PAID', "Invoice #$id marked as paid in full");
    $msg = "Invoice marked as paid in full.";
}

// Fetch Invoices
$res = mysqli_query($conn, "SELECT i.*, p.title as property_title FROM invoices i LEFT JOIN properties p ON i.property_id = p.id ORDER BY i.id DESC");
$invoices = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $invoices[] = $row;
    }
}

$admin_page_title = "Invoices & Installment Plans";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Invoicing & Installments Tracker</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Generate and print official property purchase invoices and installment payment receipts.</p>
    </div>

    <a href="invoice-create.php" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px; font-size: 13px;">
        <i class="fas fa-file-invoice-dollar"></i> Generate New Invoice
    </a>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client Name</th>
                    <th>Property Title</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance Due</th>
                    <th>Installments</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="9" style="text-align: center; padding: 30px; color: var(--admin-muted);">No invoices generated yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($inv['client_name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($inv['client_phone']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($inv['property_title'] ?: 'Real Estate Payment') ?></td>
                            <td><strong><?= $inv['currency'] ?> <?= number_format($inv['total_amount'], 2) ?></strong></td>
                            <td style="color: #34d399;"><?= $inv['currency'] ?> <?= number_format($inv['amount_paid'], 2) ?></td>
                            <td style="color: var(--admin-accent);"><strong><?= $inv['currency'] ?> <?= number_format($inv['balance_due'], 2) ?></strong></td>
                            <td>
                                <?= (int)$inv['installment_months'] ?> Months<br>
                                <span style="font-size: 10px; color: var(--admin-muted);"><?= $inv['currency'] ?> <?= number_format($inv['monthly_installment'], 2) ?>/mo</span>
                            </td>
                            <td>
                                <span class="status-pill <?= $inv['status'] === 'paid' ? 'success' : ($inv['status'] === 'partially_paid' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper($inv['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="../invoice-view.php?id=<?= $inv['id'] ?>" target="_blank" class="btn-icon" title="View & Print Official Invoice"><i class="fas fa-print"></i></a>
                                    <?php if ($inv['status'] !== 'paid'): ?>
                                        <a href="invoices.php?mark_paid=1&id=<?= $inv['id'] ?>" class="btn-icon" title="Mark Paid in Full" onclick="return confirm('Confirm full payment settlement?')"><i class="fas fa-check-double"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
