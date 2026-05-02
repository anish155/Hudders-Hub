<?php
$activePage = 'cart';
$navBreadcrumb = ['Home', 'user', 'cart'];

$cartItems = [
  [
    'name' => 'Fresh chicken breast',
    'trader' => 'Hudders Butchers',
    'price' => 6.50,
    'qty' => 2,
    'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg'
  ],
  [
    'name' => 'Lamb mince',
    'trader' => 'Hudders Butchers',
    'price' => 8.20,
    'qty' => 1,
    'image' => 'Asstes/Item-image/green-broccoli.jpg'
  ],
  [
    'name' => 'Dry aged steak',
    'trader' => 'Hudders Butchers',
    'price' => 9.00,
    'qty' => 3,
    'image' => 'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg'
  ]
];

$forYouItems = [
  [
    'name' => 'Premium chicken wings',
    'price' => 'GBP 5.90',
    'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg'
  ],
  [
    'name' => 'Lean beef mince',
    'price' => 'GBP 7.40',
    'image' => 'Asstes/Item-image/green-broccoli.jpg'
  ],
  [
    'name' => 'Marinated lamb chops',
    'price' => 'GBP 11.80',
    'image' => 'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg'
  ],
  [
    'name' => 'Family grill pack',
    'price' => 'GBP 15.20',
    'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg'
  ]
];

$subTotal = 0;
foreach ($cartItems as $item) {
  $subTotal += $item['price'] * $item['qty'];
}
$serviceFee = 2.40;
$total = $subTotal + $serviceFee;

include 'nav-bar-logged-in.php';
?>

<main class="cart-page">
  <section class="cart-hero">
    <div class="page-wrap">
      <h1>
        <span class="step-id">01</span>
        Cart
      </h1>
      <p>Review your items before choosing a collection slot.</p>
    </div>
  </section>

  <section class="cart-content">
    <div class="page-wrap cart-layout">
      <!-- Dynamic Items Container -->
      <div id="cart-items-container" class="cart-items card-panel">
        



<div class="panel-head">
          <h2>Items (<?php echo count($cartItems); ?>)</h2>
          <span>Ready for pickup today</span>
        </div>

        <?php foreach ($cartItems as $index => $item): ?>
          <article class="cart-item" data-item-row>
            <div class="item-image-wrap">
              <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>

            <div class="item-meta">
              <h3><?php echo htmlspecialchars($item['name']); ?></h3>
              <p><?php echo htmlspecialchars($item['trader']); ?></p>
              <button type="button" class="remove-btn">Remove</button>
            </div>

            <div class="item-actions">
              <div class="qty-control" data-qty-control>
                <button type="button" class="qty-btn" data-qty-minus aria-label="Decrease quantity">-</button>
                <span class="qty-value" data-qty-value><?php echo (int) $item['qty']; ?></span>
                <button type="button" class="qty-btn" data-qty-plus aria-label="Increase quantity">+</button>
              </div>
              <div class="item-pricing">
                <span class="unit-price">GBP <?php echo number_format($item['price'], 2); ?></span>
                <strong class="line-total" data-line-total data-unit-price="<?php echo number_format($item['price'], 2, '.', ''); ?>">
                  GBP <?php echo number_format($item['price'] * $item['qty'], 2); ?>
                </strong>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <div class="panel-loading">Loading your basket...</div>
      </div>

      <!-- Summary Sidebar -->
     <aside class="cart-summary card-panel">
        <div class="panel-head">
          <h2>Summary</h2>
        </div>

        <div class="summary-row">
          <span>Subtotal</span>
          <strong data-subtotal>GBP <?php echo number_format($subTotal, 2); ?></strong>
        </div>
        <div class="summary-row">
          <span>Service fee</span>
          <strong data-fee data-fixed-fee="<?php echo number_format($serviceFee, 2, '.', ''); ?>">GBP <?php echo number_format($serviceFee, 2); ?></strong>
        </div>
        <div class="summary-row total-row">
          <span>Total</span>
          <strong data-total>GBP <?php echo number_format($total, 2); ?></strong>
        </div>

        <a class="btn btn-primary full-width" href="collection-slot.php">Choose collection slot</a>
        <a class="btn btn-secondary full-width" href="homepage.php">Continue shopping</a>

        <p class="summary-note">Prices are static demo values for this UI prototype.</p>
      </aside>
    </div>
  </section>

<section class="cart-for-you">
    <div class="page-wrap">
      <div class="section-head">
        <h2>For you</h2>
      </div>

      <div class="for-you-grid">
        <?php foreach ($forYouItems as $item): ?>
          <article class="for-you-card">
            <div class="for-you-image-wrap">
              <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>
            <div class="for-you-info">
              <h3><?php echo htmlspecialchars($item['name']); ?></h3>
              <strong><?php echo htmlspecialchars($item['price']); ?></strong>
              <button type="button" class="mini-add-btn">Add to cart</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>



  <section class="cart-banner">
    <div class="page-wrap">
      <a href="homepage.php" class="promo-banner-link" aria-label="Browse this week market specials">
        <img src="Asstes/Banner/banner-2.png" alt="HuddersHub weekly market specials">
      </a>
    </div>
  </section>
</main>



<style>
  .cart-page {
    background: linear-gradient(180deg, #F7F6F3 0%, #FFFFFF 55%, #F7F6F3 100%);
    color: #0B140A;
    padding-top: 20px;
  }

  .cart-hero {
    padding: 22px 0 18px;
  }

  .cart-hero h1 {
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

  .cart-hero p {
    color: #5E6A63;
    font-size: 15px;
  }

  .cart-content {
    padding: 8px 0 70px;
  }

  .cart-layout {
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

  .cart-items {
    display: grid;
    gap: 12px;
  }

  .cart-item {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    border: 1px solid #E3E9E1;
    padding: 12px;
    background: #FFFFFF;
  }

  .item-image-wrap {
    width: 92px;
    height: 92px;
    border: 1px solid #E3E9E1;
    overflow: hidden;
    background: #F7F6F3;
  }

  .item-image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .item-meta h3 {
    font-size: 17px;
    margin-bottom: 4px;
    color: #0B140A;
  }

  .item-meta p {
    font-size: 13px;
    color: #5E6A63;
    margin-bottom: 8px;
  }

  .remove-btn {
    border: none;
    background: transparent;
    color: #FF5E3A;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    cursor: pointer;
    padding: 0;
  }

  .item-actions {
    display: grid;
    gap: 10px;
    justify-items: end;
  }

  .qty-control {
    display: inline-grid;
    grid-template-columns: 34px 42px 34px;
    border: 1px solid #DCE3DA;
    background: #FAFBF8;
    align-items: center;
  }

  .qty-btn {
    border: none;
    background: transparent;
    color: #0F260B;
    font-size: 18px;
    font-weight: 700;
    height: 34px;
    cursor: pointer;
  }

  .qty-value {
    display: grid;
    place-items: center;
    font-size: 14px;
    font-weight: 700;
    color: #0B140A;
    border-left: 1px solid #DCE3DA;
    border-right: 1px solid #DCE3DA;
    height: 34px;
  }

  .item-pricing {
    text-align: right;
    display: grid;
    gap: 2px;
  }

  .unit-price {
    color: #5E6A63;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .line-total {
    font-size: 18px;
    color: #0B140A;
  }

  .cart-summary {
    position: sticky;
    top: 160px;
    display: grid;
    gap: 12px;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 15px;
    color: #1E2A1C;
  }

  .summary-row strong {
    font-size: 16px;
  }

  .total-row {
    border-top: 1px solid #E3E9E1;
    padding-top: 12px;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .total-row strong {
    font-size: 20px;
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

  .cart-for-you {
    padding: 4px 0 24px;
  }

  .section-head {
    margin-bottom: 14px;
  }

  .section-head h2 {
    font-size: 36px;
    text-transform: uppercase;
    letter-spacing: 0.45px;
    color: #0F260B;
  }

  .for-you-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
  }

  .for-you-card {
    background: #FFFFFF;
    border: 1px solid #DCE3DA;
    box-shadow: 0 12px 24px rgba(15, 38, 11, 0.1);
    overflow: hidden;
    transition: all 0.25s ease;
  }

  .for-you-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 28px rgba(15, 38, 11, 0.16);
    border-color: rgba(255, 94, 58, 0.38);
  }

  .for-you-image-wrap {
    height: 170px;
    background: #F7F6F3;
    border-bottom: 1px solid #E3E9E1;
  }

  .for-you-image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .for-you-info {
    padding: 12px;
    display: grid;
    gap: 8px;
  }

  .for-you-info h3 {
    font-size: 16px;
    color: #0B140A;
  }

  .for-you-info strong {
    font-size: 15px;
    color: #0F260B;
  }

  .mini-add-btn {
    border: none;
    background: #0B1C08;
    color: #FFFFFF;
    padding: 10px 12px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
  }

  .mini-add-btn:hover {
    background: #143311;
  }

  .cart-banner {
    padding: 0 0 16px;
  }

  .promo-banner-link {
    display: block;
    overflow: hidden;
    border: 1px solid #DCE3DA;
    box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
  }

  .promo-banner-link img {
    width: 100%;
    height: clamp(170px, 25vw, 290px);
    object-fit: cover;
    display: block;
  }

  @media (max-width: 980px) {
    .cart-layout {
      grid-template-columns: 1fr;
    }

    .cart-summary {
      position: static;
    }

    .for-you-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 700px) {
    .cart-hero h1 {
      font-size: 36px;
    }

    .cart-item {
      grid-template-columns: 74px minmax(0, 1fr);
    }

    .item-image-wrap {
      width: 74px;
      height: 74px;
    }

    .item-actions {
      grid-column: 1 / -1;
      justify-items: start;
      width: 100%;
      grid-template-columns: 1fr auto;
      align-items: center;
    }

    .item-pricing {
      text-align: left;
    }

    .section-head h2 {
      font-size: 30px;
    }

    .for-you-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
(function() {
  const container = document.getElementById('cart-items-container');
  const totalEl = document.getElementById('summary-total');
  const countEl = document.getElementById('cart-count-header');

  function renderCart() {
    const cart = JSON.parse(localStorage.getItem('hh_cart') || '[]');
    let html = '<div class="panel-head"><h2>Items (' + cart.length + ')</h2></div>';
    let total = 0;

    if (cart.length === 0) {
      html += '<p>Your cart is empty.</p>';
    } else {
      cart.forEach((item, index) => {
        const price = parseFloat(item.price.replace('£', ''));
        const lineTotal = price * item.qty;
        total += lineTotal;

        html += `
          <article class="cart-item">
            <div class="item-meta">
              <h3>${item.name}</h3>
              <p>Unit Price: ${item.price}</p>
              <button onclick="removeFromCart(${index})" class="remove-btn">Remove</button>
            </div>
            <div class="item-actions">
              <div class="qty-control">
                <button onclick="updateQty(${index}, -1)">-</button>
                <span class="qty-value">${item.qty}</span>
                <button onclick="updateQty(${index}, 1)">+</button>
              </div>
              <strong>GBP ${lineTotal.toFixed(2)}</strong>
            </div>
          </article>`;
      });
    }

    container.innerHTML = html;
    countEl.textContent = cart.length;
    totalEl.textContent = 'GBP ' + (total + 2.40).toFixed(2); // Adding service fee
  }

  window.updateQty = (index, change) => {
    let cart = JSON.parse(localStorage.getItem('hh_cart'));
    cart[index].qty = Math.max(1, cart[index].qty + change);
    localStorage.setItem('hh_cart', JSON.stringify(cart));
    renderCart();
  };

  window.removeFromCart = (index) => {
    let cart = JSON.parse(localStorage.getItem('hh_cart'));
    cart.splice(index, 1);
    localStorage.setItem('hh_cart', JSON.stringify(cart));
    renderCart();
  };

  renderCart();
})();
</script>

<?php include 'footer.php'; ?>
