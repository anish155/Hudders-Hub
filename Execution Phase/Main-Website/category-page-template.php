<?php
/**
 * HuddersHub — Shared Category Page Template
 * Used by: greengrocer.php, fishmonger.php, delicatessen.php, bakery.php, butcher.php
 *
 * Expects the calling file to have already set:
 *   $categoryName, $categoryIcon, $categoryDescription,
 *   $categoryAccent, $categoryBg, $subcategories, $products
 * and to have defined format_discount($price, $originalPrice)
 */

function renderCategoryPage(
  string $categoryName,
  string $categoryIcon,
  string $categoryDescription,
  string $categoryAccent,
  string $categoryBg,
  array  $subcategories,
  array  $products,
  string $discountFn
): void {
  $defaultReviews = 216;
?>

<!-- =========================================================================
     CATEGORY HERO
     ========================================================================= -->
<section class="cat-hero" style="--cat-accent:<?php echo $categoryAccent; ?>; --cat-bg:<?php echo $categoryBg; ?>;">
  <div class="page-wrap cat-hero-inner">
    <div class="cat-hero-text">
      <span class="cat-icon-chip">
        <span class="material-icons-outlined"><?php echo htmlspecialchars($categoryIcon); ?></span>
      </span>
      <h1 class="cat-hero-title"><?php echo htmlspecialchars($categoryName); ?></h1>
      <p class="cat-hero-desc"><?php echo htmlspecialchars($categoryDescription); ?></p>
    </div>
    <div class="cat-hero-stats">
      <div class="cat-stat">
        <span class="cat-stat-num"><?php echo count($products); ?>+</span>
        <span class="cat-stat-label">Products</span>
      </div>
      <div class="cat-stat">
        <span class="cat-stat-num">4.8★</span>
        <span class="cat-stat-label">Avg Rating</span>
      </div>
      <div class="cat-stat">
        <span class="cat-stat-num">Daily</span>
        <span class="cat-stat-label">Restocked</span>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================================
     FILTER / SUBCATEGORY BAR
     ========================================================================= -->
<div class="cat-filter-bar" style="--cat-accent:<?php echo $categoryAccent; ?>;">
  <div class="page-wrap cat-filter-inner">
    <div class="cat-tabs" id="cat-tabs">
      <?php foreach ($subcategories as $i => $sub): ?>
        <button class="cat-tab <?php echo $i === 0 ? 'active' : ''; ?>"
                data-filter="<?php echo $i === 0 ? 'all' : htmlspecialchars($sub); ?>">
          <?php echo htmlspecialchars($sub); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="cat-sort-wrap">
      <label class="cat-sort-label" for="cat-sort">Sort:</label>
      <select class="cat-sort-select" id="cat-sort">
        <option value="default">Featured</option>
        <option value="price-asc">Price: Low–High</option>
        <option value="price-desc">Price: High–Low</option>
        <option value="rating">Top Rated</option>
      </select>
    </div>
  </div>
</div>

<!-- =========================================================================
     PRODUCTS GRID
     ========================================================================= -->
<section class="cat-products-section" style="--cat-accent:<?php echo $categoryAccent; ?>; --cat-bg:<?php echo $categoryBg; ?>;">
  <div class="page-wrap">

    <!-- Result count -->
    <p class="cat-result-count" id="cat-result-count">
      Showing <strong><?php echo count($products); ?></strong> products
    </p>

    <div class="cat-products-grid" id="cat-products-grid">
      <?php foreach ($products as $product):
        $discountText = $discountFn($product['price'], $product['original_price'] ?? '');
        $reviewCount  = $product['reviews'] ?? $defaultReviews;
        $isOut        = $product['stock'] === 'out-of-stock';
        $subcat       = $product['subcategory'] ?? '';
      ?>
      <div class="product-card <?php echo $isOut ? 'is-out-of-stock' : ''; ?>"
           data-subcategory="<?php echo htmlspecialchars($subcat); ?>"
           data-price="<?php echo floatval(preg_replace('/[^0-9.]/', '', $product['price'])); ?>"
           data-rating="<?php echo $reviewCount; ?>">
        <div class="product-card-inner" data-link="product.php?name=<?php echo urlencode($product['name']); ?>">
          <div class="product-image-wrapper">
            <?php if ($isOut): ?>
              <span class="stock-badge out">OUT OF STOCK</span>
            <?php elseif (!empty($product['is_sale'])): ?>
              <span class="stock-badge sale">ON SALE</span>
            <?php elseif (!empty($product['is_new'])): ?>
              <span class="stock-badge new">NEW</span>
            <?php endif; ?>
            <button class="favorite-btn" type="button" aria-label="Add to favourites">
              <span class="material-icons-outlined">favorite_border</span>
            </button>
            <div class="product-image">
              <img src="<?php echo htmlspecialchars($product['image']); ?>"
                   alt="<?php echo htmlspecialchars($product['name']); ?>"
                   loading="lazy">
            </div>
          </div>
          <div class="product-info">
            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
            <span class="product-unit"><?php echo htmlspecialchars($product['unit'] ?? ''); ?></span>
            <div class="price-row">
              <div class="price-group">
                <span class="current-price"><?php echo $product['price']; ?></span>
                <?php if (!empty($product['original_price'])): ?>
                  <span class="original-price"><?php echo $product['original_price']; ?></span>
                <?php endif; ?>
              </div>
              <?php if ($discountText): ?>
                <span class="discount-text"><?php echo $discountText; ?></span>
              <?php endif; ?>
            </div>
            <div class="rating-row">
              <div class="rating-stars">
                <?php for ($s = 0; $s < 5; $s++): ?>
                  <span class="material-icons-outlined"><?php echo $s < 4 ? 'star' : 'star_half'; ?></span>
                <?php endfor; ?>
              </div>
              <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
            </div>
            <button class="add-to-cart-btn cat-add-to-cart"
                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                    data-price="<?php echo $product['price']; ?>"
                    <?php echo $isOut ? 'disabled aria-disabled="true"' : ''; ?>>
              <span class="material-icons-outlined">shopping_cart</span>
              <?php echo $isOut ? 'Out of stock' : 'Add to cart'; ?>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div class="cat-empty" id="cat-empty" style="display:none;">
      <span class="material-icons-outlined">search_off</span>
      <p>No products found in this category.</p>
    </div>

  </div>
</section>

<!-- =========================================================================
     CART TOAST NOTIFICATION
     ========================================================================= -->
<div class="cart-toast" id="cart-toast" role="status" aria-live="polite">
  <span class="material-icons-outlined">check_circle</span>
  <span id="cart-toast-msg">Item added to cart</span>
</div>

<!-- =========================================================================
     CSS
     ========================================================================= -->
<style>
  /* ── Design tokens (inherited from homepage) ──────────────────────────── */
  :root {
    --secondary-color: #0F260B;
    --secondary-color-soft: rgba(15,38,11,.14);
    --accent-sale: #FF5E3A;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    line-height: 1.6;
    color: #1B2419;
    background: linear-gradient(180deg,#F7F6F3 0%,#fff 35%,#F7F6F3 100%);
  }

  h1,h2,h3,h4,h5,h6 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    line-height: 1.3;
    color: #1a1a1a;
  }

  a   { text-decoration: none; color: inherit; transition: all .3s ease; }
  img { max-width: 100%; height: auto; display: block; }
  button { font-family: inherit; transition: all .3s ease; cursor: pointer; }

  .page-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

  /* ── Hero ─────────────────────────────────────────────────────────────── */
  .cat-hero {
    background: var(--cat-bg);
    border-bottom: 1px solid rgba(15,38,11,.08);
    padding: 56px 0 48px;
  }

  .cat-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    flex-wrap: wrap;
  }

  .cat-hero-text { flex: 1; min-width: 260px; }

  .cat-icon-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px; height: 52px;
    background: var(--cat-accent);
    border-radius: 0;
    margin-bottom: 16px;
  }

  .cat-icon-chip .material-icons-outlined {
    font-size: 28px;
    color: #fff;
  }

  .cat-hero-title {
    font-size: clamp(40px, 6vw, 64px);
    font-weight: 800;
    color: #0F260B;
    letter-spacing: -.03em;
    line-height: 1.05;
    margin-bottom: 12px;
  }

  .cat-hero-desc {
    font-size: 16px;
    color: #4A6741;
    max-width: 440px;
    line-height: 1.6;
  }

  .cat-hero-stats {
    display: flex;
    gap: 40px;
    flex-shrink: 0;
  }

  .cat-stat { text-align: center; }

  .cat-stat-num {
    display: block;
    font-size: 28px;
    font-weight: 800;
    color: var(--cat-accent);
    letter-spacing: -.02em;
    line-height: 1.1;
  }

  .cat-stat-label {
    font-size: 12px;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 600;
  }

  /* ── Filter bar ───────────────────────────────────────────────────────── */
  .cat-filter-bar {
    background: #fff;
    border-bottom: 1px solid rgba(15,38,11,.08);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(15,38,11,.06);
  }

  .cat-filter-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 14px;
    padding-bottom: 14px;
    flex-wrap: wrap;
  }

  .cat-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
  }

  .cat-tab {
    background: transparent;
    border: 1.5px solid #DCE3DA;
    color: #4A6741;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 0;
    transition: all .25s ease;
    white-space: nowrap;
  }

  .cat-tab:hover {
    border-color: var(--cat-accent);
    color: var(--cat-accent);
  }

  .cat-tab.active {
    background: var(--cat-accent);
    border-color: var(--cat-accent);
    color: #fff;
  }

  .cat-sort-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .cat-sort-label {
    font-size: 13px;
    color: #6B7280;
    font-weight: 600;
  }

  .cat-sort-select {
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: #0F260B;
    border: 1.5px solid #DCE3DA;
    background: #fff;
    padding: 6px 12px;
    border-radius: 0;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%230F260B' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 30px;
    transition: border-color .25s;
  }

  .cat-sort-select:focus { outline: none; border-color: var(--cat-accent); }

  /* ── Products section ─────────────────────────────────────────────────── */
  .cat-products-section {
    padding: 40px 0 80px;
    background: #F7F6F3;
  }

  .cat-result-count {
    font-size: 14px;
    color: #6B7280;
    margin-bottom: 24px;
  }

  .cat-result-count strong { color: #0F260B; }

  /* ── Product grid (5 col → responsive) ───────────────────────────────── */
  .cat-products-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
    align-items: stretch;
  }

  @media (max-width: 1100px) { .cat-products-grid { grid-template-columns: repeat(4, minmax(0,1fr)); } }
  @media (max-width: 860px)  { .cat-products-grid { grid-template-columns: repeat(3, minmax(0,1fr)); } }
  @media (max-width: 620px)  { .cat-products-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
  @media (max-width: 420px)  { .cat-products-grid { grid-template-columns: 1fr; } }

  /* ── Product card (matches homepage) ─────────────────────────────────── */
  .product-card {
    background: transparent;
    border: none;
    cursor: pointer;
    height: 100%;
  }

  .product-card.is-out-of-stock { opacity: .7; }

  .product-card.is-out-of-stock .product-image img { filter: grayscale(100%); }

  .product-card-inner {
    background: #fff;
    border-radius: 0;
    padding: 12px;
    transition: transform .35s cubic-bezier(.4,0,.2,1),
                box-shadow .35s cubic-bezier(.4,0,.2,1),
                border-color .35s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 16px 30px rgba(15,38,11,.12);
    position: relative;
    overflow: hidden;
    border: 1px solid #DCE3DA;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    height: 100%;
    will-change: transform, box-shadow;
  }

  .product-card:hover .product-card-inner {
    box-shadow: 0 24px 50px rgba(15,38,11,.22);
    transform: translateY(-8px) scale(1.015);
    border-color: rgba(255,94,58,.72);
    background: #FFFDF9;
  }

  .product-image-wrapper {
    position: relative;
    margin-bottom: 12px;
  }

  .stock-badge {
    position: absolute;
    top: 10px; left: 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 4px 10px;
    z-index: 2;
    border-radius: 0;
  }

  .stock-badge.new  { background: #0F260B; color: #D6F0A7; border: 1px solid rgba(15,38,11,.45); }
  .stock-badge.sale { background: #0F260B; color: #E6F5C9; border: 1px solid rgba(15,38,11,.45); }
  .stock-badge.out  { background: #3B3B3B; color: #fff;    border: 1px solid rgba(0,0,0,.35); }

  .favorite-btn {
    position: absolute;
    top: -6px; right: 6px;
    width: 32px; height: 32px;
    background: rgba(255,255,255,.9);
    border: none;
    display: flex; align-items: center; justify-content: center;
    z-index: 2;
    transition: all .3s ease;
    border-radius: 0;
    box-shadow: 0 8px 18px rgba(15,38,11,.12);
  }

  .favorite-btn:hover { transform: translateY(-1px) scale(1.05); }

  .favorite-btn .material-icons-outlined { font-size: 18px; color: #9E9E9E; transition: all .3s ease; }
  .favorite-btn.active .material-icons-outlined { color: var(--secondary-color); }

  .product-image {
    width: 100%; aspect-ratio: 1/1;
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
  }

  .product-image img {
    width: 100%; height: 100%;
    object-fit: contain;
    transition: transform .5s cubic-bezier(.4,0,.2,1);
  }

  .product-card:hover .product-image img { transform: scale(1.08); }

  .product-info {
    padding: 6px 4px 0;
    display: flex; flex-direction: column; flex: 1;
  }

  .product-name {
    font-size: 16px; font-weight: 700; color: #0F260B;
    margin-bottom: 2px; letter-spacing: -.01em; line-height: 1.2;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    min-height: 2.4em;
  }

  .product-unit {
    font-size: 11px; color: #6B7280; font-weight: 500;
    margin-bottom: 8px; text-transform: uppercase; letter-spacing: .04em;
  }

  .price-row {
    display: flex; align-items: baseline;
    justify-content: space-between; gap: 8px; margin-bottom: 8px;
  }

  .price-group { display: inline-flex; align-items: baseline; gap: 8px; }

  .current-price {
    font-size: 20px; font-weight: 700; color: #0F260B; letter-spacing: -.02em;
  }

  .original-price { font-size: 13px; color: #9CA3AF; text-decoration: line-through; }

  .discount-text {
    font-size: 11px; font-weight: 700; color: #FF3B00;
    background: #FFE2D4; padding: 3px 8px; white-space: nowrap;
    text-transform: uppercase; letter-spacing: .02em;
  }

  .rating-row {
    display: inline-flex; align-items: center;
    gap: 6px; margin-bottom: 10px; color: #F4B740;
  }

  .rating-stars { display: inline-flex; gap: 2px; }
  .rating-stars .material-icons-outlined { font-size: 13px; }
  .rating-count { font-size: 11px; color: #6B7280; }

  /* ── Add to cart button ───────────────────────────────────────────────── */
  .add-to-cart-btn {
    display: flex; align-items: center; justify-content: center;
    gap: 6px; width: 100%;
    background: #0F260B; color: #fff;
    border: none; padding: 10px 12px;
    font-size: 13px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    margin-top: auto;
    transition: all .25s ease;
  }

  .add-to-cart-btn:hover:not([disabled]) {
    background: var(--cat-accent, #FF5E3A);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(255,94,58,.35);
  }

  .add-to-cart-btn[disabled] {
    background: #9CA3AF; cursor: not-allowed; opacity: .7;
  }

  .add-to-cart-btn .material-icons-outlined { font-size: 16px; }

  /* ── Empty state ──────────────────────────────────────────────────────── */
  .cat-empty {
    text-align: center; padding: 80px 20px; color: #9CA3AF;
  }

  .cat-empty .material-icons-outlined { font-size: 64px; display: block; margin-bottom: 16px; }
  .cat-empty p { font-size: 18px; font-weight: 600; }

  /* ── Cart toast ───────────────────────────────────────────────────────── */
  .cart-toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #0F260B; color: #fff;
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px;
    font-size: 14px; font-weight: 600;
    box-shadow: 0 12px 30px rgba(15,38,11,.3);
    transform: translateY(80px); opacity: 0;
    transition: all .35s cubic-bezier(.4,0,.2,1);
    z-index: 9999; pointer-events: none;
  }

  .cart-toast.show { transform: translateY(0); opacity: 1; }
  .cart-toast .material-icons-outlined { font-size: 20px; color: #A5D6A7; }

  /* ── Fade-in animation for grid items ────────────────────────────────── */
  .product-card {
    animation: catFadeUp .4s ease both;
  }

  @keyframes catFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* ── Responsive hero ──────────────────────────────────────────────────── */
  @media (max-width: 700px) {
    .cat-hero-inner { flex-direction: column; align-items: flex-start; }
    .cat-hero-stats { gap: 24px; }
    .cat-hero-title { font-size: 40px; }
  }
</style>

<!-- =========================================================================
     JAVASCRIPT
     ========================================================================= -->
<script>
(function() {
  // ── Filter by subcategory ──────────────────────────────────────────────
  const tabs    = document.querySelectorAll('.cat-tab');
  const cards   = document.querySelectorAll('#cat-products-grid .product-card');
  const empty   = document.getElementById('cat-empty');
  const countEl = document.getElementById('cat-result-count');
  const sort    = document.getElementById('cat-sort');
  const grid    = document.getElementById('cat-products-grid');

  let currentFilter = 'all';

  function applyFilter(filter) {
    currentFilter = filter;
    let visible = 0;
    cards.forEach(card => {
      const sub = card.dataset.subcategory || '';
      const show = filter === 'all' || sub.toLowerCase() === filter.toLowerCase();
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    countEl.innerHTML = `Showing <strong>${visible}</strong> product${visible !== 1 ? 's' : ''}`;
    empty.style.display = visible === 0 ? 'block' : 'none';
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      applyFilter(tab.dataset.filter);
    });
  });

  // ── Sort ──────────────────────────────────────────────────────────────
  sort.addEventListener('change', () => {
    const val   = sort.value;
    const items = [...cards].filter(c => c.style.display !== 'none');
    const all   = [...cards];

    all.sort((a, b) => {
      if (val === 'price-asc')  return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
      if (val === 'price-desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
      if (val === 'rating')     return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
      return 0; // default
    });

    all.forEach(card => grid.appendChild(card));
    applyFilter(currentFilter);
  });

  // ── Favourite toggle ──────────────────────────────────────────────────
  document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      btn.classList.toggle('active');
      btn.querySelector('.material-icons-outlined').textContent =
        btn.classList.contains('active') ? 'favorite' : 'favorite_border';
    });
  });

  // ── Card click → product page ─────────────────────────────────────────
  document.querySelectorAll('.product-card-inner[data-link]').forEach(inner => {
    inner.addEventListener('click', e => {
      if (e.target.closest('.favorite-btn') || e.target.closest('.add-to-cart-btn')) return;
      window.location.href = inner.dataset.link;
    });
  });

  // ── Add to cart ───────────────────────────────────────────────────────
  const toast    = document.getElementById('cart-toast');
  const toastMsg = document.getElementById('cart-toast-msg');
  let toastTimer;

  function showToast(name) {
    toastMsg.textContent = `"${name}" added to cart`;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
  }

  function getCart() {
    try { return JSON.parse(localStorage.getItem('hh_cart') || '[]'); } catch { return []; }
  }

  function saveCart(cart) {
    localStorage.setItem('hh_cart', JSON.stringify(cart));
    // Update any cart count badge in nav if present
    const badge = document.querySelector('.cart-count, .cart-badge, [data-cart-count]');
    if (badge) badge.textContent = cart.reduce((n, i) => n + (i.qty || 1), 0);
  }

  document.querySelectorAll('.cat-add-to-cart').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      if (btn.disabled) return;

      const name  = btn.dataset.name;
      const price = btn.dataset.price;
      const cart  = getCart();
      const idx   = cart.findIndex(i => i.name === name);

      if (idx > -1) {
        cart[idx].qty = (cart[idx].qty || 1) + 1;
      } else {
        cart.push({ name, price, qty: 1 });
      }

      saveCart(cart);
      showToast(name);

      // Pulse animation on button
      btn.classList.add('btn-pulse');
      setTimeout(() => btn.classList.remove('btn-pulse'), 400);
    });
  });
})();
</script>

<?php } // end renderCategoryPage ?>