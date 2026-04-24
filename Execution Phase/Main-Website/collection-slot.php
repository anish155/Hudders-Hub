<?php
$activePage = 'collection';
$navBreadcrumb = ['Home', 'user', 'cart', 'collection'];

$slotDays = [
  ['day' => 'Wed', 'date' => '29 Apr', 'label' => 'Popular'],
  ['day' => 'Thu', 'date' => '30 Apr', 'label' => 'Quiet'],
  ['day' => 'Fri', 'date' => '01 May', 'label' => 'Fast pickup']
];

$timeSlots = [
  ['time' => '10:00 - 13:00', 'status' => 'selected'],
  ['time' => '13:00 - 16:00', 'status' => 'available'],
  ['time' => '16:00 - 19:00', 'status' => 'available']
];

$orderSummary = [
  ['label' => 'Fresh chicken breast x2', 'price' => 13.00],
  ['label' => 'Lamb mince x1', 'price' => 8.20],
  ['label' => 'Dry aged steak x3', 'price' => 27.00]
];

$orderSubTotal = 48.20;
$orderServiceFee = 2.40;
$orderTotal = 50.60;

include 'nav-bar-logged-in.php';
?>

<main class="collection-page">
  <section class="collection-hero">
    <div class="page-wrap">
      <h1>
        <span class="step-id">02</span>
        Collection slot
      </h1>
      <p>Pick a date and time for a fast market pickup.</p>
    </div>
  </section>

  <section class="collection-content">
    <div class="page-wrap collection-layout">
      <div class="slot-panel card-panel">
        <div class="panel-head">
          <h2>Select date</h2>
          <span>3 days available</span>
        </div>

        <div class="day-grid" id="day-grid">
          <?php foreach ($slotDays as $index => $day): ?>
            <button
              type="button"
              class="day-btn <?php echo $index === 2 ? 'is-active' : ''; ?>"
              data-day
              aria-pressed="<?php echo $index === 2 ? 'true' : 'false'; ?>"
            >
              <strong><?php echo htmlspecialchars($day['day']); ?></strong>
              <span><?php echo htmlspecialchars($day['date']); ?></span>
              <small><?php echo htmlspecialchars($day['label']); ?></small>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="panel-head panel-head-tight">
          <h2>Choose time</h2>
          <span>3 hour windows</span>
        </div>

        <div class="slot-grid" id="slot-grid">
          <?php foreach ($timeSlots as $slot): ?>
            <?php
            $status = $slot['status'];
            $isDisabled = $status === 'soldout';
            $isSelected = $status === 'selected';
            ?>
            <button
              type="button"
              class="time-slot <?php echo $status; ?> <?php echo $isSelected ? 'is-active' : ''; ?>"
              data-slot
              <?php echo $isDisabled ? 'disabled aria-disabled="true"' : ''; ?>
              aria-pressed="<?php echo $isSelected ? 'true' : 'false'; ?>"
            >
              <span><?php echo htmlspecialchars($slot['time']); ?></span>
              <small>
                <?php
                if ($status === 'limited') {
                  echo 'Few left';
                } elseif ($status === 'soldout') {
                  echo 'Full';
                } elseif ($status === 'selected') {
                  echo 'Selected';
                } else {
                  echo 'Available';
                }
                ?>
              </small>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <aside class="pickup-panel card-panel">
        <div class="panel-head">
          <h2>Pickup details</h2>
        </div>

        <div class="pickup-card">
          <h3>Hudders Butchers Stall</h3>
          <p>Queensgate Market Hall, Huddersfield HD1 2UJ</p>
          <ul>
            <li>Bring your order code at collection.</li>
            <li>Slot held for 15 minutes after start time.</li>
            <li>Free chilled bag included on request.</li>
          </ul>
        </div>

        <div class="order-summary-card">
          <h3>Order summary</h3>
          <div class="order-summary-list">
            <?php foreach ($orderSummary as $orderItem): ?>
              <div class="order-row">
                <span><?php echo htmlspecialchars($orderItem['label']); ?></span>
                <strong>GBP <?php echo number_format($orderItem['price'], 2); ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="order-row total-line">
            <span>Subtotal</span>
            <strong>GBP <?php echo number_format($orderSubTotal, 2); ?></strong>
          </div>
          <div class="order-row total-line">
            <span>Service fee</span>
            <strong>GBP <?php echo number_format($orderServiceFee, 2); ?></strong>
          </div>
          <div class="order-row grand-total">
            <span>Total</span>
            <strong>GBP <?php echo number_format($orderTotal, 2); ?></strong>
          </div>
        </div>

        <div class="selection-preview">
          <span>Date</span>
          <strong data-selected-day>Wed 29 Apr</strong>
        </div>
        <div class="selection-preview">
          <span>Time</span>
          <strong data-selected-time>10:00 - 13:00</strong>
        </div>

        <a class="btn btn-primary full-width" href="invoice.php">Confirm collection slot</a>
        <a class="btn btn-secondary full-width" href="cart-page.php">Back to cart</a>

        <p class="summary-note">Mock flow only. Slot selection is not persisted yet.</p>
      </aside>
    </div>
  </section>
</main>

<style>
  .collection-page {
    background: linear-gradient(180deg, #F7F6F3 0%, #FFFFFF 55%, #F7F6F3 100%);
    color: #0B140A;
    padding-top: 20px;
  }

  .collection-hero {
    padding: 22px 0 18px;
  }

  .collection-hero h1 {
    position: relative;
    font-size: 48px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding-top: 16px;
    margin-bottom: 10px;
  }

  .step-id {
    position: absolute;
    top: 0;
    left: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F260B;
    letter-spacing: 1px;
  }

  .collection-hero p {
    color: #5E6A63;
    font-size: 15px;
  }

  .collection-content {
    padding: 8px 0 70px;
  }

  .collection-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 24px;
    align-items: start;
  }

  .card-panel {
    background: #FFFFFF;
    border: 1px solid #DCE3DA;
    box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
    padding: 20px;
  }

  .panel-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #E3E9E1;
    padding-bottom: 12px;
    margin-bottom: 14px;
  }

  .panel-head h2 {
    font-size: 22px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .panel-head span {
    font-size: 13px;
    color: #6A756F;
  }

  .panel-head-tight {
    margin-top: 18px;
  }

  .day-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .day-btn {
    border: 1px solid #DCE3DA;
    background: #FAFBF8;
    padding: 12px 10px;
    display: grid;
    gap: 4px;
    text-align: left;
    cursor: pointer;
    transition: all 0.22s ease;
  }

  .day-btn strong {
    font-size: 15px;
    color: #0B140A;
  }

  .day-btn span {
    font-size: 13px;
    color: #334236;
  }

  .day-btn small {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.35px;
    color: #6A756F;
  }

  .day-btn:hover {
    border-color: #FF8C70;
    transform: translateY(-1px);
  }

  .day-btn.is-active {
    border-color: #FF5E3A;
    background: #FFF3EE;
    box-shadow: 0 8px 14px rgba(255, 94, 58, 0.18);
  }

  .slot-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .time-slot {
    border: 1px solid #DCE3DA;
    background: #FFFFFF;
    color: #0B140A;
    padding: 12px;
    display: grid;
    gap: 4px;
    text-align: left;
    cursor: pointer;
    transition: all 0.22s ease;
  }

  .time-slot span {
    font-size: 14px;
    font-weight: 700;
  }

  .time-slot small {
    font-size: 12px;
    color: #6A756F;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .time-slot:hover:not(:disabled) {
    border-color: #FF8C70;
    transform: translateY(-1px);
  }

  .time-slot.is-active {
    border-color: #FF5E3A;
    background: #FFF3EE;
    box-shadow: 0 8px 14px rgba(255, 94, 58, 0.18);
  }

  .pickup-panel {
    position: sticky;
    top: 160px;
    display: grid;
    gap: 12px;
  }

  .pickup-card {
    border: 1px solid #E3E9E1;
    background: #FAFBF8;
    padding: 14px;
  }

  .pickup-card h3 {
    font-size: 17px;
    margin-bottom: 6px;
  }

  .pickup-card p {
    font-size: 13px;
    color: #4D5D53;
    margin-bottom: 10px;
    line-height: 1.5;
  }

  .pickup-card ul {
    margin: 0;
    padding-left: 18px;
    display: grid;
    gap: 6px;
    color: #36453A;
    font-size: 12px;
  }

  .order-summary-card {
    border: 1px solid #E3E9E1;
    background: #FFFFFF;
    padding: 14px;
    display: grid;
    gap: 10px;
  }

  .order-summary-card h3 {
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.45px;
    color: #0B140A;
  }

  .order-summary-list {
    display: grid;
    gap: 8px;
  }

  .order-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 13px;
    color: #34453A;
  }

  .order-row strong {
    color: #0B140A;
  }

  .total-line {
    border-top: 1px solid #E9EEE7;
    padding-top: 8px;
  }

  .grand-total {
    border-top: 1px solid #DCE3DA;
    padding-top: 10px;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .selection-preview {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #E3E9E1;
    padding: 11px 12px;
    font-size: 14px;
  }

  .selection-preview span {
    color: #5E6A63;
  }

  .selection-preview strong {
    color: #0B140A;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 0;
    font-size: 16px;
    font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    text-decoration: none;
    line-height: 1;
  }

  .btn-primary {
    background: #FF5E3A;
    color: #FFFFFF;
  }

  .btn-primary:hover {
    background: #E3472C;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(255, 94, 58, 0.28);
  }

  .btn-secondary {
    background: #E4F7C5;
    color: #0B140A;
  }

  .btn-secondary:hover {
    background: #D6F0A7;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(15, 38, 11, 0.18);
  }

  .full-width {
    width: 100%;
  }

  .summary-note {
    font-size: 12px;
    color: #6A756F;
    margin-top: 4px;
  }

  @media (max-width: 980px) {
    .collection-layout {
      grid-template-columns: 1fr;
    }

    .pickup-panel {
      position: static;
    }

    .day-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .slot-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 700px) {
    .collection-hero h1 {
      font-size: 36px;
    }

    .day-grid {
      grid-template-columns: 1fr;
    }

    .slot-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
  (function () {
    const selectedDay = document.querySelector('[data-selected-day]');
    const selectedTime = document.querySelector('[data-selected-time]');

    document.querySelectorAll('[data-day]').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('[data-day]').forEach((other) => {
          other.classList.remove('is-active');
          other.setAttribute('aria-pressed', 'false');
        });

        btn.classList.add('is-active');
        btn.setAttribute('aria-pressed', 'true');

        const day = btn.querySelector('strong')?.textContent?.trim() || '';
        const date = btn.querySelector('span')?.textContent?.trim() || '';
        if (selectedDay) {
          selectedDay.textContent = `${day} ${date}`.trim();
        }
      });
    });

    document.querySelectorAll('[data-slot]').forEach((btn) => {
      if (btn.disabled) {
        return;
      }

      btn.addEventListener('click', () => {
        document.querySelectorAll('[data-slot]').forEach((other) => {
          other.classList.remove('is-active');
          other.setAttribute('aria-pressed', 'false');
          const tag = other.querySelector('small');
          if (tag && other.classList.contains('available')) {
            tag.textContent = 'Available';
          }
        });

        btn.classList.add('is-active');
        btn.setAttribute('aria-pressed', 'true');

        const time = btn.querySelector('span')?.textContent?.trim() || '';
        const status = btn.querySelector('small');
        if (status) {
          status.textContent = 'Selected';
        }
        if (selectedTime) {
          selectedTime.textContent = time;
        }
      });
    });
  })();
</script>

<?php include 'footer.php'; ?>
