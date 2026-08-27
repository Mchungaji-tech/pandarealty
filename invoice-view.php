<?php
/**
 * Panda Realty - Luxury Printable Invoice & Installment Receipt
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$invoice_id = (int)($_GET['id'] ?? 0);
$inv_num = clean_input($_GET['inv'] ?? '');
$conn = get_db_connection();

if ($invoice_id > 0) {
    $res = mysqli_query($conn, "SELECT i.*, p.title as property_title, p.location as property_location FROM invoices i LEFT JOIN properties p ON i.property_id = p.id WHERE i.id = $invoice_id LIMIT 1");
} elseif (!empty($inv_num)) {
    $num_safe = db_escape($inv_num);
    $res = mysqli_query($conn, "SELECT i.*, p.title as property_title, p.location as property_location FROM invoices i LEFT JOIN properties p ON i.property_id = p.id WHERE i.invoice_number = '$num_safe' LIMIT 1");
} else {
    die("Invalid invoice request.");
}

if (!$res || mysqli_num_rows($res) === 0) {
    die("Invoice not found.");
}

$inv = mysqli_fetch_assoc($res);
$items = json_decode($inv['items_json'] ?? '[]', true);
if (!is_array($items) || empty($items)) {
    $items = [
        ['description' => $inv['property_title'] ?: 'Real Estate Property Payment', 'amount' => $inv['total_amount'], 'quantity' => 1]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($inv['invoice_number']) ?> | Panda Realty</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0a0e17;
            --accent: #c5a059;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 40px 20px;
        }
        .invoice-wrapper {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .inv-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            letter-spacing: 1px;
            color: var(--primary);
        }
        .inv-brand p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .inv-meta { text-align: right; }
        .inv-meta h2 { font-size: 24px; color: var(--accent); font-weight: 700; margin-bottom: 6px; }
        .inv-meta p { font-size: 13px; color: #64748b; }
        .inv-addresses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            font-size: 14px;
        }
        .inv-addresses h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .inv-table th {
            background: #f1f5f9;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border);
        }
        .inv-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        .inv-totals {
            margin-left: auto;
            max-width: 350px;
            font-size: 14px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }
        .total-row.grand {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            border-top: 1px solid var(--primary);
            margin-top: 10px;
            padding: 12px 0;
        }
        .inv-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .no-print {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: var(--accent);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }
        @media print {
            body { background: white; padding: 0; }
            .invoice-wrapper { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="<?= htmlspecialchars(app_path('index')) ?>" style="color: #64748b; text-decoration: none; font-size: 14px;"><i class="fas fa-arrow-left"></i> Return to Panda Realty</a>
        <button type="button" class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div class="invoice-wrapper">
        <div class="inv-header">
            <div class="inv-brand">
                <h1>PANDA <span style="color: var(--accent);">REALTY</span></h1>
                <p><strong>Perpetuah Realtor</strong> • Eldoret Property Expert</p>
                <p><?= htmlspecialchars($contact_address) ?></p>
                <p>Phone: <?= htmlspecialchars($contact_phone) ?> | WhatsApp: +<?= htmlspecialchars($whatsapp_number) ?></p>
            </div>
            <div class="inv-meta">
                <h2>INVOICE</h2>
                <p><strong>#<?= htmlspecialchars($inv['invoice_number']) ?></strong></p>
                <p>Date: <?= date('F d, Y', strtotime($inv['created_at'])) ?></p>
                <p>Due Date: <?= date('F d, Y', strtotime($inv['due_date'])) ?></p>
                <div style="margin-top: 10px;">
                    <?php if ($inv['status'] === 'paid'): ?>
                        <span class="inv-badge badge-paid">PAID IN FULL</span>
                    <?php elseif ($inv['status'] === 'partially_paid'): ?>
                        <span class="inv-badge badge-partial">PARTIALLY PAID</span>
                    <?php else: ?>
                        <span class="inv-badge badge-unpaid">UNPAID</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="inv-addresses">
            <div>
                <h4>Billed To (Buyer / Client):</h4>
                <p><strong><?= htmlspecialchars($inv['client_name']) ?></strong></p>
                <p>Phone: <?= htmlspecialchars($inv['client_phone']) ?></p>
                <p>Email: <?= htmlspecialchars($inv['client_email']) ?></p>
                <?php if (!empty($inv['client_address'])): ?>
                    <p><?= htmlspecialchars($inv['client_address']) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <h4>Property & Agreement Details:</h4>
                <p><strong>Property:</strong> <?= htmlspecialchars($inv['property_title'] ?: 'Eldoret Property') ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($inv['property_location'] ?: 'Eldoret, Kenya') ?></p>
                <p><strong>Installment Scheme:</strong> <?= (int)$inv['installment_months'] ?> Months</p>
            </div>
        </div>

        <table class="inv-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price (<?= $inv['currency'] ?>)</th>
                    <th style="text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): 
                    $qty = $it['quantity'] ?? 1;
                    $amt = (float)($it['amount'] ?? 0);
                    $sub = $amt * $qty;
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($it['description']) ?></strong></td>
                        <td style="text-align: center;"><?= $qty ?></td>
                        <td style="text-align: right;"><?= number_format($amt, 2) ?></td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($sub, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="inv-totals">
            <div class="total-row">
                <span>Total Agreed Value:</span>
                <span><strong><?= $inv['currency'] ?> <?= number_format($inv['total_amount'], 2) ?></strong></span>
            </div>
            <div class="total-row">
                <span>Initial Deposit / Amount Paid:</span>
                <span style="color: #15803d;">- <?= $inv['currency'] ?> <?= number_format($inv['amount_paid'], 2) ?></span>
            </div>
            <div class="total-row grand">
                <span>Remaining Balance Due:</span>
                <span style="color: var(--accent);"><?= $inv['currency'] ?> <?= number_format($inv['balance_due'], 2) ?></span>
            </div>
            <?php if ($inv['installment_months'] > 1 && $inv['monthly_installment'] > 0): ?>
                <div class="total-row" style="margin-top: 10px; font-size: 13px; color: #64748b;">
                    <span>Monthly Installment Dues:</span>
                    <span><strong><?= $inv['currency'] ?> <?= number_format($inv['monthly_installment'], 2) ?> / mo</strong></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($inv['notes'])): ?>
            <div style="margin-top: 30px; padding: 15px; background: #f8fafc; border-left: 3px solid var(--accent); font-size: 13px; border-radius: 4px;">
                <strong>Payment Notes & Instructions:</strong><br>
                <?= nl2br(htmlspecialchars($inv['notes'])) ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid var(--border); padding-top: 20px;">
            Thank you for choosing Panda Realty. For assistance or verification, contact Perpetuah at 0708 289 852.<br>
            Designed & Developed by <strong>TekTrend</strong>
        </div>
    </div>

</body>
</html>
