<?php
$navBreadcrumb = ['Home', 'user', 'cart', 'collection', 'pay', 'invoice'];
include 'nav-bar.php';
?>

<main class="invoice-page">
  <section class="payment-strip">
    <div class="page-wrap">
      <div class="payment-success">
        <div class="success-icon">✓</div>
        <div>
          <h2>Payment successful</h2>
          <p>Thank you for shopping local.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="invoice-hero">
    <div class="page-wrap">
      <div class="invoice-title">
        <h1>
          <span class="invoice-step">03</span>
          Invoice
        </h1>
      </div>
    </div>
  </section>

  <section class="invoice-content">
    <div class="page-wrap">
      <div class="invoice-card">
        <div class="invoice-header">
          <div>
            <div class="invoice-title-row">
              <h2>Invoice #HH-2026-0412</h2>
            </div>
            <p>Date: April 12, 2026</p>
            <p>Payment: PayPal</p>
          </div>
          <div class="invoice-meta">
            <span>Trader: Hudders Butchers</span>
            <span>Shop owners: A. Khan, S. Patel</span>
            <span>Customer: Jamie Sutton</span>
            <span>Collection: Wed 10:00-13:00</span>
          </div>
        </div>

        <table class="invoice-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qty</th>
              <th>Unit price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Fresh chicken breast</td>
              <td>2</td>
              <td>GBP 6.50</td>
              <td>GBP 13.00</td>
            </tr>
            <tr>
              <td>Lamb mince</td>
              <td>1</td>
              <td>GBP 8.20</td>
              <td>GBP 8.20</td>
            </tr>
            <tr>
              <td>Dry aged steak</td>
              <td>3</td>
              <td>GBP 9.00</td>
              <td>GBP 27.00</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3">Subtotal</td>
              <td>GBP 48.20</td>
            </tr>
            <tr>
              <td colspan="3">Service fee</td>
              <td>GBP 2.40</td>
            </tr>
            <tr class="total-row">
              <td colspan="3">Total</td>
              <td>GBP 50.60</td>
            </tr>
          </tfoot>
        </table>

        <div class="invoice-actions">
          <button class="btn btn-secondary">Download</button>
          <button class="btn btn-primary">Shop more</button>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
  .invoice-page {
    background: linear-gradient(180deg, #F7F6F3 0%, #FFFFFF 55%, #F7F6F3 100%);
    color: #0B140A;
    padding-top: 24px;
  }


  .payment-strip {
    padding: 8px 0 6px;
  }

  .invoice-hero {
    padding: 24px 0 22px;
  }

  .invoice-hero h1 {
    font-size: 48px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    margin-bottom: 10px;
    position: relative;
    padding-top: 16px;
  }

  .invoice-step {
    position: absolute;
    top: 0;
    left: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F260B;
    letter-spacing: 1px;
  }

  .invoice-content {
    padding: 12px 0 70px;
  }

  .payment-success {
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, #0F260B 0%, #143d12 55%, #1f5b1d 100%);
    color: #FFFFFF;
    padding: 16px 18px;
    border-radius: 0;
    box-shadow: 0 16px 32px rgba(15, 38, 11, 0.22);
  }

  .payment-success h2 {
    font-size: 22px;
    margin-bottom: 4px;
  }

  .payment-success p {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
  }

  .success-icon {
    width: 40px;
    height: 40px;
    border-radius: 0;
    background: rgba(255, 255, 255, 0.15);
    display: grid;
    place-items: center;
    font-size: 18px;
    font-weight: 700;
  }


  .invoice-card {
    background: #FFFFFF;
    border: 1px dashed #D1D5DB;
    border-radius: 0;
    padding: 28px 26px;
    box-shadow: 0 18px 34px rgba(15, 38, 11, 0.14);
    font-family: "Courier New", monospace;
    position: relative;
  }

  .invoice-card::before,
  .invoice-card::after {
    content: '';
    position: absolute;
    left: 24px;
    right: 24px;
    height: 1px;
    background: repeating-linear-gradient(
      to right,
      #D1D5DB,
      #D1D5DB 6px,
      transparent 6px,
      transparent 12px
    );
  }

  .invoice-card::before {
    top: 10px;
  }

  .invoice-card::after {
    bottom: 10px;
  }

  .invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    border-bottom: 1px solid #DCE3DA;
    padding-bottom: 18px;
    margin-bottom: 18px;
    padding-top: 6px;
  }

  .invoice-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .status-pill {
    background: rgba(255, 94, 58, 0.12);
    color: #FF5E3A;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 4px 10px;
    border-radius: 999px;
  }

  .invoice-header h2 {
    font-size: 18px;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .invoice-header p {
    font-size: 12px;
    color: #5E6A63;
    margin-bottom: 4px;
  }

  .invoice-meta {
    display: grid;
    gap: 6px;
    font-size: 12px;
    color: #0B140A;
    text-align: right;
  }

  .invoice-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }

  .invoice-table th,
  .invoice-table td {
    border-bottom: 1px dashed #D1D5DB;
    padding: 8px 4px;
    text-align: left;
  }

  .invoice-table th {
    background: transparent;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  .invoice-table tfoot td {
    font-weight: 600;
  }

  .invoice-table .total-row td {
    font-weight: 700;
    color: #0F260B;
    border-bottom: none;
  }

  .invoice-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 18px;
  }

  .invoice-actions .btn-secondary {
    color: #FFFFFF;
  }

  @media (max-width: 900px) {
    .invoice-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .invoice-meta {
      text-align: left;
    }
  }
</style>

<?php include 'footer.php'; ?>
