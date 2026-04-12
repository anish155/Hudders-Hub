<?php include 'nav-bar.php'; ?>

<main class="invoice-page">
  <section class="invoice-hero">
    <div class="page-wrap">
      <p class="hero-kicker">HuddersHub Invoice</p>
      <h1>Invoice preview (testing)</h1>
      <p class="hero-sub">This invoice is a static demo for layout testing.</p>
    </div>
  </section>

  <section class="invoice-content">
    <div class="page-wrap">
      <div class="invoice-card">
        <div class="invoice-header">
          <div>
            <h2>Invoice #HH-2026-0412</h2>
            <p>Date: April 12, 2026</p>
            <p>Status: Pending</p>
          </div>
          <div class="invoice-meta">
            <span>Trader: Hudders Butchers</span>
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
          <button class="btn btn-primary">Send</button>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
  .invoice-page {
    background: linear-gradient(180deg, #f5f7fb 0%, #FFFFFF 100%);
    color: #111111;
    padding-top: 24px;
  }

  .invoice-hero {
    padding: 40px 0 28px;
  }

  .invoice-hero h1 {
    font-size: 36px;
    letter-spacing: -0.4px;
    margin-bottom: 10px;
  }

  .hero-kicker {
    text-transform: uppercase;
    letter-spacing: 1.2px;
    font-size: 12px;
    color: #FF5E3A;
    font-weight: 700;
  }

  .hero-sub {
    color: #6B7280;
    max-width: 520px;
  }

  .invoice-content {
    padding: 22px 0 60px;
  }

  .invoice-card {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 28px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  }

  .invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    border-bottom: 1px solid #E5E7EB;
    padding-bottom: 18px;
    margin-bottom: 18px;
  }

  .invoice-header h2 {
    font-size: 22px;
    margin-bottom: 6px;
  }

  .invoice-header p {
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 4px;
  }

  .invoice-meta {
    display: grid;
    gap: 6px;
    font-size: 13px;
    color: #222222;
    text-align: right;
  }

  .invoice-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  .invoice-table th,
  .invoice-table td {
    border: 1px solid #E5E7EB;
    padding: 10px 12px;
    text-align: left;
  }

  .invoice-table th {
    background: #F5F5F5;
    font-weight: 700;
  }

  .invoice-table tfoot td {
    font-weight: 600;
  }

  .invoice-table .total-row td {
    font-weight: 700;
    color: #0F260B;
  }

  .invoice-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 18px;
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
