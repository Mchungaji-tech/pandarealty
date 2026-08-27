<?php
/**
 * Panda Realty - Create New Official Property Invoice
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_invoices');

$conn = get_db_connection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        $error = "Security session expired. Please refresh and try again.";
    } else {
        $client_name = clean_input($_POST['client_name'] ?? '');
        $client_email = clean_input($_POST['client_email'] ?? '');
        $client_phone = clean_input($_POST['client_phone'] ?? '');
        $client_address = clean_input($_POST['client_address'] ?? '');
        $property_id = (int)($_POST['property_id'] ?? 0);
        $currency = clean_input($_POST['currency'] ?? 'KES');
        $total_amount = (float)($_POST['total_amount'] ?? 0);
        $amount_paid = (float)($_POST['amount_paid'] ?? 0);
        $installment_months = (int)($_POST['installment_months'] ?? 1);
        $due_date = clean_input($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
        $notes = clean_input($_POST['notes'] ?? '');

        $balance_due = max(0, $total_amount - $amount_paid);
        $monthly_installment = ($installment_months > 1 && $balance_due > 0) ? ($balance_due / $installment_months) : 0;

        $status = ($balance_due <= 0) ? 'paid' : (($amount_paid > 0) ? 'partially_paid' : 'unpaid');
        $inv_number = 'INV-' . date('Y') . '-' . str_pad(random_int(100, 9999), 4, '0', STR_PAD_LEFT);

        $items_arr = [
            ['description' => clean_input($_POST['item_description'] ?? 'Real Estate Property Payment'), 'amount' => $total_amount, 'quantity' => 1]
        ];
        $items_json = db_escape(json_encode($items_arr));

        $prop_sql = $property_id > 0 ? $property_id : "NULL";
        $creator_id = (int)($_SESSION['user_id'] ?? 1);

        if (empty($client_name) || empty($client_phone) || $total_amount <= 0) {
            $error = "Please fill in Client Name, Phone, and Total Amount.";
        } else {
            $sql = "INSERT INTO invoices (invoice_number, property_id, client_name, client_email, client_phone, client_address, currency, total_amount, amount_paid, balance_due, deposit_paid, installment_months, monthly_installment, due_date, status, items_json, notes, created_by) 
                    VALUES ('$inv_number', $prop_sql, '$client_name', '$client_email', '$client_phone', '$client_address', '$currency', $total_amount, $amount_paid, $balance_due, $amount_paid, $installment_months, $monthly_installment, '$due_date', '$status', '$items_json', '$notes', $creator_id)";
            
            if (mysqli_query($conn, $sql)) {
                $new_inv_id = mysqli_insert_id($conn);
                log_security_action('INVOICE_GENERATED', "Created invoice #$inv_number for $client_name ($total_amount $currency)");
                
                // Safe redirect
                if (!headers_sent()) {
                    header("Location: ../invoice-view.php?id=$new_inv_id");
                    exit;
                } else {
                    echo "<script>window.location.href='../invoice-view.php?id=$new_inv_id';</script>";
                    exit;
                }
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

$admin_page_title = "Create New Invoice";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Generate Client Invoice & Installment Agreement</h3>
    <a href="invoices.php" class="btn" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--admin-border);">
        <i class="fas fa-arrow-left"></i> Back to Invoices
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form action="invoice-create.php" method="POST" class="admin-card">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

    <h4 style="font-size: 16px; color: var(--admin-accent); margin-bottom: 20px;">1. Client Information</h4>
    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Client Full Name *</label>
            <input type="text" name="client_name" placeholder="e.g. Dr. Evans Kipchumba" required>
        </div>

        <div class="admin-form-group">
            <label>Phone Number (WhatsApp) *</label>
            <input type="tel" name="client_phone" placeholder="0708 289 852" required>
        </div>

        <div class="admin-form-group">
            <label>Email Address</label>
            <input type="email" name="client_email" placeholder="client@example.com">
        </div>

        <div class="admin-form-group">
            <label>Postal / Physical Address</label>
            <input type="text" name="client_address" placeholder="P.O Box 4010, Eldoret">
        </div>
    </div>

    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px;">2. Property & Pricing Breakdown</h4>
    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Associated Property</label>
            <select name="property_id" id="invPropertySelect" onchange="autoFillPrice(this)">
                <option value="" data-price="0">General Real Estate Services</option>
                <?php
                $p_res = mysqli_query($conn, "SELECT id, title, price_kes FROM properties");
                if ($p_res) {
                    while ($p = mysqli_fetch_assoc($p_res)) {
                        echo '<option value="' . $p['id'] . '" data-price="' . (float)$p['price_kes'] . '">' . htmlspecialchars($p['title']) . ' (KES ' . number_format($p['price_kes']) . ')</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Currency</label>
            <select name="currency">
                <option value="KES" selected>KES (Kenyan Shillings)</option>
                <option value="USD">USD ($ United States Dollars)</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Total Agreed Amount *</label>
            <input type="number" step="0.01" name="total_amount" id="invTotalAmt" placeholder="2200000" required>
        </div>

        <div class="admin-form-group">
            <label>Initial Deposit / Paid Amount</label>
            <input type="number" step="0.01" name="amount_paid" placeholder="440000" value="0">
        </div>
    </div>

    <div class="admin-form-group">
        <label>Line Item Description *</label>
        <input type="text" name="item_description" id="invItemDesc" placeholder="e.g. Annex 50x100 Residential Plot (Plot No. 18) - Full Purchase" required>
    </div>

    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px;">3. Installment Plan & Payment Terms</h4>
    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Installment Duration (Months)</label>
            <select name="installment_months">
                <option value="1">1 Month (Lump sum payment)</option>
                <option value="6">6 Months Plan (0% Interest)</option>
                <option value="12" selected>12 Months Plan</option>
                <option value="18">18 Months Plan</option>
                <option value="24">24 Months Plan</option>
                <option value="36">36 Months Plan</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Payment Due Date</label>
            <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
        </div>
    </div>

    <div class="admin-form-group">
        <label>Payment Notes & Instructions (Displayed on Invoice)</label>
        <textarea name="notes" rows="3">Bank Name: KCB Bank / Equity Bank Kenya&#10;Account Name: Panda Realty Ltd&#10;Account No: 1234567890&#10;M-Pesa Paybill: 247247 | Account: Buyer Full Name</textarea>
    </div>

    <button type="submit" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 15px 30px; font-size: 14px; border-radius: 6px; cursor: pointer; border: none;">
        <i class="fas fa-file-invoice"></i> Generate & View Official Invoice
    </button>
</form>

<script>
function autoFillPrice(select) {
    const selected = select.options[select.selectedIndex];
    const price = selected.getAttribute('data-price');
    const text = selected.text;
    if (price && parseFloat(price) > 0) {
        document.getElementById('invTotalAmt').value = price;
        document.getElementById('invItemDesc').value = text + ' - Purchase & Title Transfer Agreement';
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
