<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?= sanitize($receipt['invoice_no']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 58mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 8px;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 6px 0; }
        .bold { font-weight: bold; }
        .item-row { display: flex; justify-content: space-between; margin: 2px 0; }
        .flex-between { display: flex; justify-content: space-between; }
        .btn-print {
            display: block;
            width: 100%;
            padding: 8px;
            background: #12131A;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-family: sans-serif;
            font-weight: bold;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        @media print {
            .btn-print { display: none !important; }
            body { padding: 0; width: 100%; }
            @page { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="btn-print" onclick="window.print()">Cetak Ulang Struk</button>

    <div class="center">
        <div class="bold" style="font-size: 13px;"><?= sanitize($settings['app_name'] ?? APP_NAME) ?></div>
        <div>PEPSI FOOD &amp; DRINK</div>
        <div style="font-size: 9px;"><?= sanitize($settings['address'] ?? 'Kampung Inggris Pare') ?></div>
        <div class="line"></div>
    </div>

    <div style="font-size: 10px;">
        <div class="flex-between">
            <span>No: <?= sanitize($receipt['invoice_no']) ?></span>
            <span>Kasir: <?= sanitize($receipt['cashier_name']) ?></span>
        </div>
        <div class="flex-between">
            <span>Tgl: <?= date('d/m/Y H:i', strtotime($receipt['created_at'])) ?></span>
            <span>Cust: <?= sanitize($receipt['customer_name']) ?></span>
        </div>
    </div>

    <div class="line"></div>

    <!-- Items -->
    <?php foreach ($receipt['items'] as $item): ?>
    <div style="margin-bottom: 3px;">
        <div class="bold"><?= sanitize($item['product_name']) ?></div>
        <div class="flex-between" style="font-size: 10px;">
            <span><?= $item['quantity'] ?> x <?= number_format($item['selling_price'], 0, ',', '.') ?></span>
            <span class="bold"><?= number_format($item['subtotal'], 0, ',', '.') ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <?php $payLabel = ['cash' => 'Tunai', 'qris' => 'QRIS'][$receipt['payment_method']] ?? strtoupper($receipt['payment_method']); ?>

    <div class="line"></div>

    <!-- Totals -->
    <div class="flex-between bold" style="font-size: 12px;">
        <span>TOTAL:</span>
        <span>Rp <?= number_format($receipt['total_amount'], 0, ',', '.') ?></span>
    </div>
    <div class="flex-between" style="font-size: 10px; margin-top: 2px;">
        <span>Bayar (<?= $payLabel ?>):</span>
        <span>Rp <?= number_format($receipt['cash_paid'], 0, ',', '.') ?></span>
    </div>
    <div class="flex-between" style="font-size: 10px;">
        <span>Kembalian:</span>
        <span>Rp <?= number_format($receipt['change_returned'], 0, ',', '.') ?></span>
    </div>

    <div class="line"></div>

    <div class="center" style="font-size: 9px; margin-top: 6px;">
        Terima Kasih Atas Kunjungan Anda!<br>
        Selamat Belajar di MR.PEPSI Pare
    </div>

</body>
</html>
