<?php
/**
 * HuddersHub Homepage
 * Main landing page with header navigation
 */

// Include the header/navigation bar
include 'nav-bar.php';

// Banner images - banner-1.png (Cleckheaton) always first
$banners = [
  'Asstes/Banner/cleckheaton.png',
  'Asstes/Banner/banner-2.png',
  'Asstes/Banner/banner-3.png',
  'Asstes/Banner/banner-4.png'
];

// Demo flash sale products
$flashSaleProducts = [
  ['name' => 'Green Bell Pepper', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false],
  ['name' => 'Green Broccoli', 'price' => '£0.90', 'original_price' => '', 'image' => 'Asstes/Item-image/green-broccoli.jpg', 'stock' => 'out-of-stock', 'unit' => 'per piece', 'is_sale' => false, 'is_new' => true],
  ['name' => 'Chinese Broccoli', 'price' => '£0.90', 'original_price' => '', 'image' => 'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => false, 'is_new' => false],
  ['name' => 'Green Bell Pepper', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'out-of-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false],
  ['name' => 'Green Broccoli', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-broccoli.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false],
  ['name' => 'Green Bell Pepper', 'price' => '£0.90', 'original_price' => '', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => false, 'is_new' => true],
  ['name' => 'Green Broccoli', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-broccoli.jpg', 'stock' => 'out-of-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false],
  ['name' => 'Chinese Broccoli', 'price' => '£0.90', 'original_price' => '', 'image' => 'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => false, 'is_new' => false],
  ['name' => 'Green Bell Pepper', 'price' => '£0.90', 'original_price' => '', 'image' => 'Asstes/Item-image/green-bell-pepper-isolated.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => false, 'is_new' => false],
  ['name' => 'Green Broccoli', 'price' => '£0.90', 'original_price' => '£1.50', 'image' => 'Asstes/Item-image/green-broccoli.jpg', 'stock' => 'in-stock', 'unit' => 'per piece', 'is_sale' => true, 'is_new' => false],
];

$defaultDescription = 'Fresh, crisp, and packed with goodness.';
$defaultReviews = 216;

function format_discount_text($price, $originalPrice) {
  $priceValue = floatval(preg_replace('/[^0-9.]/', '', $price));
  $originalValue = floatval(preg_replace('/[^0-9.]/', '', $originalPrice));
  if ($priceValue <= 0 || $originalValue <= 0 || $priceValue >= $originalValue) {
    return '';
  }
  $percent = (int) round((1 - ($priceValue / $originalValue)) * 100);
  return $percent . '% off';
}

$productsJson = json_encode($flashSaleProducts, JSON_UNESCAPED_SLASHES);
?>

<!-- =========================================================================
     BANNER SLIDER SECTION
     ========================================================================= -->
<section class="banner-slider">
  <div class="slider-container">
    <div class="slider-wrapper">
      <?php foreach ($banners as $index => $banner): ?>
        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
          <img src="<?php echo htmlspecialchars($banner); ?>" alt="Banner <?php echo $index + 1; ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Dots/Indicators -->
    <div class="slider-dots">
      <?php foreach ($banners as $index => $banner): ?>
        <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index; ?>)"></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================================================================
     FLASH SALE SECTION - HORIZONTAL SLIDER
     ========================================================================= -->
<section class="flash-sale">
  <div class="page-wrap">
    <!-- Flash Sale Header with Countdown -->
    <div class="flash-sale-header">
      <div class="flash-sale-badge">
        <span class="badge-text">On sale</span>
        <span class="countdown-timer" id="countdown-timer">18:14:38</span>
      </div>
    </div>

    <!-- Horizontal Slider Container -->
    <div class="horizontal-slider overlay-nav">
      <div class="slider-viewport">
        <div class="slider-track" id="flash-sale-track">
          <?php foreach ($flashSaleProducts as $product): ?>
            <?php
              $description = $product['desc'] ?? $defaultDescription;
              $discountText = format_discount_text($product['price'], $product['original_price']);
              $reviewCount = $product['reviews'] ?? $defaultReviews;
            ?>
            <div class="slider-card <?php echo $product['stock'] === 'out-of-stock' ? 'is-out-of-stock' : ''; ?>">
              <div class="product-card-inner" data-link="product.php?name=<?php echo urlencode($product['name']); ?>">
                <div class="product-image-wrapper">
                  <?php if ($product['stock'] === 'out-of-stock'): ?>
                    <span class="stock-badge out">OUT OF STOCK</span>
                  <?php elseif (!empty($product['is_sale'])): ?>
                    <span class="stock-badge sale">ON SALE</span>
                  <?php elseif (!empty($product['is_new'])): ?>
                    <span class="stock-badge new">NEW</span>
                  <?php endif; ?>
                  <button class="favorite-btn" type="button" aria-label="Add to favorites">
                    <span class="material-icons-outlined">favorite_border</span>
                  </button>
                  <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  </div>
                </div>
                <div class="product-info">
                  <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                  <p class="product-desc"><?php echo htmlspecialchars($description); ?></p>
                  <div class="price-row">
                    <div class="price-group">
                      <span class="current-price"><?php echo $product['price']; ?></span>
                      <?php if (!empty($product['original_price'])): ?>
                        <span class="original-price"><?php echo $product['original_price']; ?></span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($discountText)): ?>
                      <span class="discount-text"><?php echo $discountText; ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="rating-row">
                    <div class="rating-stars">
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star_half</span>
                    </div>
                    <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
                  </div>
                  <button class="add-to-cart-btn" <?php echo $product['stock'] === 'out-of-stock' ? 'disabled aria-disabled="true"' : ''; ?>>
                    <span class="material-icons-outlined">shopping_cart</span>
                    <?php echo $product['stock'] === 'out-of-stock' ? 'Out of stock' : 'Add to cart'; ?>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================================
     TOP RATED SECTION - HORIZONTAL SLIDER
     ========================================================================= -->
<section class="top-rated">
  <div class="page-wrap">
    <!-- Top Rated Header -->
    <div class="section-header">
      <h2 class="section-title">Top rated</h2>
    </div>

    <!-- Horizontal Slider Container -->
    <div class="horizontal-slider overlay-nav">
      <div class="slider-viewport">
        <div class="slider-track" id="top-rated-track">
          <?php foreach ($flashSaleProducts as $product): ?>
            <?php
              $description = $product['desc'] ?? $defaultDescription;
              $discountText = format_discount_text($product['price'], $product['original_price']);
              $reviewCount = $product['reviews'] ?? $defaultReviews;
            ?>
            <div class="slider-card <?php echo $product['stock'] === 'out-of-stock' ? 'is-out-of-stock' : ''; ?>">
              <div class="product-card-inner" data-link="product.php?name=<?php echo urlencode($product['name']); ?>">
                <div class="product-image-wrapper">
                  <?php if ($product['stock'] === 'out-of-stock'): ?>
                    <span class="stock-badge out">OUT OF STOCK</span>
                  <?php elseif (!empty($product['is_sale'])): ?>
                    <span class="stock-badge sale">ON SALE</span>
                  <?php elseif (!empty($product['is_new'])): ?>
                    <span class="stock-badge new">NEW</span>
                  <?php endif; ?>
                  <button class="favorite-btn" type="button" aria-label="Add to favorites">
                    <span class="material-icons-outlined">favorite_border</span>
                  </button>
                  <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  </div>
                </div>
                <div class="product-info">
                  <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                  <p class="product-desc"><?php echo htmlspecialchars($description); ?></p>
                  <div class="price-row">
                    <div class="price-group">
                      <span class="current-price"><?php echo $product['price']; ?></span>
                      <?php if (!empty($product['original_price'])): ?>
                        <span class="original-price"><?php echo $product['original_price']; ?></span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($discountText)): ?>
                      <span class="discount-text"><?php echo $discountText; ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="rating-row">
                    <div class="rating-stars">
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star</span>
                      <span class="material-icons-outlined">star_half</span>
                    </div>
                    <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
                  </div>
                  <button class="add-to-cart-btn" <?php echo $product['stock'] === 'out-of-stock' ? 'disabled aria-disabled="true"' : ''; ?>>
                    <span class="material-icons-outlined">shopping_cart</span>
                    <?php echo $product['stock'] === 'out-of-stock' ? 'Out of stock' : 'Add to cart'; ?>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================================
     BANNER SECTION
     ========================================================================= -->
<section class="promo-banner">
  <div class="page-wrap">
    <div class="banner-container">
      <img src="Asstes/Banner/banner-2.png" alt="Promotional Banner">
    </div>
  </div>
</section>

<!-- =========================================================================
     RECOMMENDED FOR YOU SECTION - GRID LAYOUT
     ========================================================================= -->
<section class="recommended">
  <div class="page-wrap">
    <!-- Section Header -->
    <div class="section-header">
      <h2 class="section-title">For you</h2>
    </div>

    <div class="products-grid">
      <?php foreach ($flashSaleProducts as $product): ?>
        <?php
          $description = $product['desc'] ?? $defaultDescription;
          $discountText = format_discount_text($product['price'], $product['original_price']);
          $reviewCount = $product['reviews'] ?? $defaultReviews;
        ?>
        <div class="product-card <?php echo $product['stock'] === 'out-of-stock' ? 'is-out-of-stock' : ''; ?>">
          <div class="product-card-inner" data-link="product.php?name=<?php echo urlencode($product['name']); ?>">
            <div class="product-image-wrapper">
              <?php if ($product['stock'] === 'out-of-stock'): ?>
                <span class="stock-badge out">OUT OF STOCK</span>
              <?php elseif (!empty($product['is_sale'])): ?>
                <span class="stock-badge sale">ON SALE</span>
              <?php elseif (!empty($product['is_new'])): ?>
                <span class="stock-badge new">NEW</span>
              <?php endif; ?>
              <button class="favorite-btn" type="button" aria-label="Add to favorites">
                <span class="material-icons-outlined">favorite_border</span>
              </button>
              <div class="product-image">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
              </div>
            </div>
            <div class="product-info">
              <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
              <p class="product-desc"><?php echo htmlspecialchars($description); ?></p>
              <div class="price-row">
                <div class="price-group">
                  <span class="current-price"><?php echo $product['price']; ?></span>
                  <?php if (!empty($product['original_price'])): ?>
                    <span class="original-price"><?php echo $product['original_price']; ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($discountText)): ?>
                  <span class="discount-text"><?php echo $discountText; ?></span>
                <?php endif; ?>
              </div>
              <div class="rating-row">
                <div class="rating-stars">
                  <span class="material-icons-outlined">star</span>
                  <span class="material-icons-outlined">star</span>
                  <span class="material-icons-outlined">star</span>
                  <span class="material-icons-outlined">star</span>
                  <span class="material-icons-outlined">star_half</span>
                </div>
                <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
              </div>
              <button class="add-to-cart-btn" <?php echo $product['stock'] === 'out-of-stock' ? 'disabled aria-disabled="true"' : ''; ?>>
                <span class="material-icons-outlined">shopping_cart</span>
                <?php echo $product['stock'] === 'out-of-stock' ? 'Out of stock' : 'Add to cart'; ?>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="load-more-wrap">
      <button class="load-more-btn" id="load-more-btn">+ Load more</button>
    </div>
  </div>
</section>

<section class="reviews">
  <div class="page-wrap">
    <div class="section-header">
      <h2 class="section-title">What shoppers say</h2>
    </div>
    <div class="review-grid">
      <div class="review-card">
        <div class="review-meta">
          <span class="review-name">Amelia T.</span>
          <span class="review-rating">5.0</span>
        </div>
        <div class="review-stars" aria-label="5 star rating">
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
        </div>
        <p>Everything arrived fresh and packed with care. The greens were perfect.</p>
      </div>
      <div class="review-card">
        <div class="review-meta">
          <span class="review-name">Marcus J.</span>
          <span class="review-rating">4.8</span>
        </div>
        <div class="review-stars" aria-label="4.5 star rating">
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star_half</span>
        </div>
        <p>Easy to browse, fast delivery, and the butcher selection is elite.</p>
      </div>
      <div class="review-card">
        <div class="review-meta">
          <span class="review-name">Priya S.</span>
          <span class="review-rating">4.9</span>
        </div>
        <div class="review-stars" aria-label="5 star rating">
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
          <span class="material-icons-outlined">star</span>
        </div>
        <p>Loved the local producers. You can taste the quality.</p>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================================
     CSS STYLES FOR BANNER & FLASH SALE
     ========================================================================= -->
<style>
  /* ========================================================================
     GLOBAL STYLES - Professional Design System
     ======================================================================== */
  :root {
    --secondary-color: #0F260B;
    --secondary-color-soft: rgba(15, 38, 11, 0.12);
    --accent-sale: #FF5E3A;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  html {
    scroll-behavior: smooth;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-optical-sizing: auto;
    font-style: normal;
    font-size: 1rem;
    line-height: 1.6;
    color: #333333;
    background: #FFFFFF;
  }

  h1, h2, h3, h4, h5, h6 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    line-height: 1.3;
    color: #1a1a1a;
  }

  h1 { font-size: 2.5rem; }
  h2 { font-size: 2rem; }
  h3 { font-size: 1.5rem; }

  a {
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
  }

  button {
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: all 0.3s ease;
  }

  img {
    max-width: 100%;
    height: auto;
    display: block;
  }

  /* ========================================================================
     BANNER SLIDER STYLES - Professional Design
     ======================================================================== */
  .banner-slider {
    width: 100%;
    background: #FFFFFF;
    padding: 20px 0;
  }

  .slider-container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .slider-wrapper {
    display: flex;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .slide {
    min-width: 100%;
    display: none;
  }

  .slide.active {
    display: block;
  }

  .slide img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
    border-radius: 25px;
  }

  /* Slider Navigation Buttons */
  .slider-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    cursor: pointer;
    padding: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    z-index: 10;
    border-radius: 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  }

  .slider-btn:hover {
    background: #FF5E3A;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 24px rgba(255, 94, 58, 0.3);
  }

  .slider-btn:hover .material-icons-outlined {
    color: #FFFFFF;
  }

  .slider-btn:active {
    transform: translateY(-50%) scale(0.95);
  }

  .slider-btn.prev {
    left: 16px;
  }

  .slider-btn.next {
    right: 16px;
  }

  .slider-btn .material-icons-outlined {
    font-size: 24px;
    color: #333333;
    transition: color 0.3s ease;
  }

  /* Slider Dots */
  .slider-dots {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
  }

  .dot {
    width: 10px;
    height: 10px;
    border-radius: 0;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.8);
  }

  .dot:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: scale(1.2);
  }

  .dot.active {
    background: #FF5E3A;
    border-color: #FF5E3A;
    width: 28px;
    border-radius: 0;
  }

  /* ========================================================================
     SECTION STYLES
     ======================================================================== */
  .flash-sale {
    padding: 60px 0;
    background: #F3F4F6;
  }

  .top-rated {
    padding: 60px 0;
    background: #FFFFFF;
  }

  .section-header {
    margin-bottom: 28px;
  }

  .section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 54px;
    font-weight: 600;
    color: #0F260B;
    letter-spacing: -0.015em;
    line-height: 1.1;
  }

  .flash-sale-header {
    margin-bottom: 28px;
  }

  /* Flash Sale Badge */
  .flash-sale-badge {
    display: inline-flex;
    align-items: center;
    gap: 0;
    background: transparent;
    color: #000000;
    padding: 0;
    border-radius: 2px;
    font-size: 54px;
    font-weight: 600;
    box-shadow: none;
    border: none;
    transition: all 0.3s ease;
    line-height: 1.1;
  }

  .flash-sale-badge:hover {
    box-shadow: none;
    transform: none;
  }

  .badge-text {
    letter-spacing: -0.01em;
    padding-right: 16px;
    border-right: 2px solid rgba(255, 255, 255, 0.4);
    font-size: 54px;
    color: #0F260B;
  }

  .countdown-timer {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    font-weight: 700;
    letter-spacing: 1px;
    font-size: 20px;
    background: #0F260B;
    color: #FFFFFF;
    padding: 0;
    padding: 6px 12px;
    margin-left: 12px;
  }

  /* ========================================================================
     HORIZONTAL SLIDER STYLES
     ======================================================================== */
  .horizontal-slider {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .horizontal-slider.overlay-nav {
    gap: 0;
  }

  .slider-viewport {
    overflow: hidden;
    width: 100%;
  }

  .slider-track {
    display: flex;
    gap: 20px;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    align-items: stretch;
  }

  .slider-card {
    min-width: 220px;
    max-width: 220px;
    height: 100%;
  }

  .slider-arrow {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    box-shadow: none;
    backdrop-filter: none;
  }

  .horizontal-slider.overlay-nav .slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
  }

  .horizontal-slider.overlay-nav .slider-arrow.prev {
    left: clamp(-18px, -2vw, -8px);
  }

  .horizontal-slider.overlay-nav .slider-arrow.next {
    right: clamp(-18px, -2vw, -8px);
  }

  .slider-arrow:hover {
    background: transparent;
    transform: translateY(-1px) scale(1.05);
    box-shadow: none;
  }

  .slider-arrow:hover .material-icons-outlined {
    color: var(--secondary-color);
  }

  .slider-arrow .material-icons-outlined {
    font-size: 22px;
    color: var(--secondary-color);
    transition: color 0.3s ease, transform 0.3s ease;
  }

  .slider-arrow:hover .material-icons-outlined {
    transform: translateX(1px);
  }

  .slider-arrow:focus-visible {
    outline: 3px solid rgba(255, 94, 58, 0.35);
    outline-offset: 2px;
  }

  .slider-arrow:active {
    transform: translateY(0) scale(0.98);
  }

  /* ========================================================================
     PRODUCTS GRID
     ======================================================================== */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(220px, 1fr));
    gap: 20px;
    align-items: stretch;
  }

  /* Product Card */
  .product-card {
    background: transparent;
    border: none;
    cursor: pointer;
    height: 100%;
  }

  .product-card.is-out-of-stock,
  .slider-card.is-out-of-stock {
    opacity: 0.7;
  }

  .product-card.is-out-of-stock .product-image img,
  .slider-card.is-out-of-stock .product-image img {
    filter: grayscale(100%);
  }

  .product-card-inner {
    background: #FFFFFF;
    border-radius: 0;
    padding: 12px;
    transition: all 0.35s ease;
    box-shadow: 0 10px 24px rgba(15, 38, 11, 0.08);
    position: relative;
    overflow: hidden;
    border: 1px solid #E2E8E0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .product-card:hover .product-card-inner,
  .slider-card:hover .product-card-inner {
    box-shadow: 0 14px 28px rgba(15, 38, 11, 0.14);
    transform: translateY(-2px);
    border-color: #9CA3AF;
  }

  /* Product Image Wrapper */
  .product-image-wrapper {
    position: relative;
    margin-bottom: 12px;
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
  }

  /* Stock Badge */
  .stock-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 11px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 4px 10px;
    z-index: 2;
    border-radius: 0;
    background: #0F260B;
    color: #E6F5C9;
    border: 1px solid rgba(15, 38, 11, 0.35);
  }

  .stock-badge.new {
    background: #0F260B;
    color: #D6F0A7;
  }

  .stock-badge.sale {
    background: #0F260B;
    color: #E6F5C9;
    border: 1px solid rgba(15, 38, 11, 0.35);
  }

  .stock-badge.out {
    background: #3B3B3B;
    color: #FFFFFF;
    border: 1px solid rgba(0, 0, 0, 0.35);
  }

  /* Favorite Button */
  .favorite-btn {
    position: absolute;
    top: -6px;
    right: 6px;
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.3s ease;
    padding: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .favorite-btn:hover {
    transform: scale(1.1);
    box-shadow: none;
  }

  .favorite-btn .material-icons-outlined {
    font-size: 18px;
    color: #9E9E9E;
    transition: all 0.3s ease;
  }

  .favorite-btn:hover .material-icons-outlined {
    color: var(--secondary-color);
  }

  .favorite-btn.active {
    background: transparent;
  }

  .favorite-btn.active .material-icons-outlined {
    color: var(--secondary-color);
  }

  /* Product Image */
  .product-image {
    width: 100%;
    aspect-ratio: 1/1;
    border-radius: 0;
    overflow: hidden;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 0;
  }

  .product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .product-card:hover .product-image img,
  .slider-card:hover .product-image img {
    transform: scale(1.02);
  }

  /* Product Info */
  .product-info {
    padding: 8px 4px 2px 4px;
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .product-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F260B;
    margin-bottom: 6px;
    letter-spacing: -0.01em;
    line-height: 1.1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.2em;
  }

  .product-desc {
    font-size: 12px;
    color: #6B7280;
    margin-bottom: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 3em;
  }

  .price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .price-group {
    display: inline-flex;
    align-items: baseline;
    gap: 10px;
  }

  .original-price {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    color: #9CA3AF;
    text-decoration: line-through;
    font-weight: 400;
  }

  .current-price {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #0F260B;
    letter-spacing: -0.02em;
  }

  .discount-text {
    font-size: 12px;
    font-weight: 700;
    color: #FF3B00;
    background: #FFE2D4;
    padding: 3px 8px;
    border-radius: 0;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }

  .rating-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #F4B740;
  }

  .rating-stars {
    display: inline-flex;
    align-items: center;
    gap: 2px;
  }

  .rating-stars .material-icons-outlined {
    font-size: 16px;
  }

  .rating-count {
    font-size: 12px;
    color: #6B7280;
  }

  /* Quantity Selector */
  .quantity-selector {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
  }

  .qty-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0;
    transition: all 0.3s ease;
    padding: 0;
  }

  .qty-btn:hover {
    background: transparent;
  }

  .qty-btn:hover .material-icons-outlined {
    color: var(--secondary-color);
  }

  .qty-btn:active {
    transform: scale(0.95);
  }

  .qty-btn .material-icons-outlined {
    font-size: 16px;
    color: #616161;
  }

  .qty-input {
    width: 48px;
    height: 28px;
    text-align: center;
    border: 1px solid #E0E0E0;
    border-radius: 0;
    font-size: 12px;
    font-weight: 600;
    color: #333333;
    -moz-appearance: textfield;
  }

  .qty-input:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 2px rgba(15, 38, 11, 0.12);
  }

  .qty-input::-webkit-outer-spin-button,
  .qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .unit-label {
    font-size: 12px;
    color: #757575;
    font-weight: 400;
    margin-left: 4px;
  }

  /* Add to Cart Button */
  .add-to-cart-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 12px;
    background: #0B1C08;
    color: #FFFFFF;
    border: none;
    border-radius: 0;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    box-shadow: none;
    margin-top: auto;
  }

  .add-to-cart-btn[disabled] {
    background: #E9ECEF;
    color: #8A8A8A;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.9;
  }

  .add-to-cart-btn[disabled] .material-icons-outlined {
    color: #8A8A8A;
  }

  .product-card-inner .qty-btn,
  .product-card-inner .qty-input,
  .product-card-inner .favorite-btn,
  .product-card-inner .add-to-cart-btn {
    cursor: pointer;
  }

  .add-to-cart-btn:hover {
    background: #071205;
    transform: translateY(-2px);
    box-shadow: none;
  }

  .add-to-cart-btn:active {
    transform: translateY(-1px);
  }

  .add-to-cart-btn .material-icons-outlined {
    font-size: 18px;
  }

  .load-more-wrap {
    display: flex;
    justify-content: center;
    margin-top: 22px;
  }

  .load-more-btn {
    padding: 8px 14px;
    border: 1px solid var(--secondary-color);
    background: transparent;
    color: var(--secondary-color);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .load-more-btn.is-loading {
    pointer-events: none;
    opacity: 0.8;
  }

  .load-more-btn.is-loading::after {
    content: '';
    display: inline-block;
    width: 14px;
    height: 14px;
    margin-left: 8px;
    border: 2px solid rgba(15, 38, 11, 0.3);
    border-top-color: var(--secondary-color);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  .reviews {
    padding: 70px 0 90px;
    background: #FFFFFF;
  }

  .review-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
  }

  .review-card {
    border: 1px solid rgba(15, 38, 11, 0.12);
    border-radius: 0;
    padding: 20px;
    background: #FFFFFF;
    box-shadow: 0 6px 18px rgba(15, 38, 11, 0.08);
  }

  .review-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    font-weight: 700;
    color: #0F260B;
  }

  .review-stars {
    display: inline-flex;
    gap: 2px;
    margin-bottom: 12px;
    color: #F4B740;
  }

  .review-stars .material-icons-outlined {
    font-size: 16px;
  }

  .review-rating {
    background: #0F260B;
    color: #CAED95;
    padding: 4px 8px;
    border-radius: 0;
    font-size: 12px;
    font-weight: 700;
  }

  .review-card p {
    font-size: 14px;
    color: #4B534E;
    line-height: 1.6;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .load-more-btn:hover {
    background: var(--secondary-color);
    color: #FFFFFF;
  }

  /* ========================================================================
     PROMO BANNER STYLES
     ======================================================================== */
  .promo-banner {
    padding: 50px 0;
    background: #FFFFFF;
  }

  .banner-container {
    max-width: 1200px;
    margin: 0 auto;
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  }

  .banner-container img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
  }

  /* ========================================================================
     RECOMMENDED SECTION STYLES
     ======================================================================== */
  .recommended {
    padding: 60px 0;
    background: #F3F4F6;
  }

  /* ========================================================================
     RESPONSIVE DESIGN
     ======================================================================== */
  @media (max-width: 1500px) {
    .products-grid {
      grid-template-columns: repeat(4, minmax(200px, 1fr));
    }
  }

  @media (max-width: 1100px) {
    .products-grid {
      grid-template-columns: repeat(3, minmax(200px, 1fr));
    }
  }

  @media (max-width: 800px) {
    .products-grid {
      grid-template-columns: repeat(2, minmax(180px, 1fr));
    }
  }

  @media (max-width: 768px) {
    .products-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }

    .flash-sale,
    .top-rated,
    .recommended {
      padding: 40px 0;
    }

    .slider-card {
      min-width: 170px;
      max-width: 170px;
    }

    .slider-arrow {
      width: 32px;
      height: 32px;
    }

    .slider-arrow .material-icons-outlined {
      font-size: 20px;
    }

    .section-title {
      font-size: 26px;
    }

    .slider-btn {
      width: 40px;
      height: 40px;
    }

    .slider-btn .material-icons-outlined {
      font-size: 20px;
    }

    .slider-btn.prev {
      left: 12px;
    }

    .slider-btn.next {
      right: 12px;
    }

    .flash-sale-badge {
      font-size: 26px;
      padding: 10px 20px;
    }

    .badge-text {
      font-size: 26px;
      padding-right: 12px;
    }

    .countdown-timer {
      font-size: 18px;
      padding-left: 12px;
    }

    .review-grid {
      grid-template-columns: 1fr;
    }

    .favorite-btn {
      width: 30px;
      height: 30px;
    }

    .favorite-btn .material-icons-outlined {
      font-size: 16px;
    }

    .product-name {
      font-size: 16px;
    }

    .current-price {
      font-size: 18px;
    }

    .add-to-cart-btn {
      font-size: 13px;
      letter-spacing: 0.14em;
      padding: 8px 10px;
    }

    .quantity-selector {
      gap: 6px;
    }

    .qty-btn {
      width: 26px;
      height: 26px;
    }

    .qty-btn .material-icons-outlined {
      font-size: 14px;
    }

    .qty-input {
      width: 44px;
      height: 26px;
      font-size: 12px;
    }

    .unit-label {
      font-size: 11px;
    }
  }

  @media (max-width: 500px) {
    .products-grid {
      grid-template-columns: 1fr;
    }

    .flash-sale-badge {
      font-size: 22px;
      padding: 8px 14px;
    }

    .badge-text {
      font-size: 22px;
    }

    .countdown-timer {
      font-size: 17px;
      padding-left: 10px;
    }

    .product-card-inner {
      padding: 12px;
    }

    .section-title {
      font-size: 22px;
    }

    .product-name {
      font-size: 16px;
    }

    .current-price {
      font-size: 18px;
    }

    .add-to-cart-btn {
      letter-spacing: 0.1em;
    }
  }
</style>

<!-- =========================================================================
     JAVASCRIPT FOR BANNER SLIDER
     ========================================================================= -->
<script>
  const loadMoreBtn = document.getElementById('load-more-btn');
  const productsGrid = document.querySelector('.products-grid');
  const productsData = <?php echo $productsJson; ?>;

  const defaultDescription = 'Fresh, crisp, and packed with goodness.';
  const defaultReviews = 216;

  function getDiscountText(price, originalPrice) {
    const priceValue = parseFloat(String(price).replace(/[^0-9.]/g, ''));
    const originalValue = parseFloat(String(originalPrice).replace(/[^0-9.]/g, ''));
    if (!priceValue || !originalValue || priceValue >= originalValue) {
      return '';
    }
    const percent = Math.round((1 - (priceValue / originalValue)) * 100);
    return `${percent}% off`;
  }

  function buildProductCard(product) {
    const isOut = product.stock === 'out-of-stock';
    const badgeConfig = isOut
      ? { klass: 'out', text: 'OUT OF STOCK' }
      : (product.is_sale
        ? { klass: 'sale', text: 'ON SALE' }
        : (product.is_new ? { klass: 'new', text: 'NEW' } : null));
    const badgeHtml = badgeConfig ? `<span class="stock-badge ${badgeConfig.klass}">${badgeConfig.text}</span>` : '';
    const originalPriceHtml = product.original_price ? `<span class="original-price">${product.original_price}</span>` : '';
    const discountText = getDiscountText(product.price, product.original_price);
    const discountHtml = discountText ? `<span class="discount-text">${discountText}</span>` : '';
    const description = product.desc || defaultDescription;
    const reviewCount = product.reviews || defaultReviews;
    const disabledAttr = isOut ? 'disabled aria-disabled="true"' : '';
    const addToCartText = isOut ? 'Out of stock' : 'Add to cart';

    return `
      <div class="product-card ${isOut ? 'is-out-of-stock' : ''}">
        <div class="product-card-inner" data-link="product.php?name=${encodeURIComponent(product.name)}">
          <div class="product-image-wrapper">
            ${badgeHtml}
            <button class="favorite-btn" type="button" aria-label="Add to favorites">
              <span class="material-icons-outlined">favorite_border</span>
            </button>
            <div class="product-image">
              <img src="${product.image}" alt="${product.name}">
            </div>
          </div>
          <div class="product-info">
            <h3 class="product-name">${product.name}</h3>
            <p class="product-desc">${description}</p>
            <div class="price-row">
              <div class="price-group">
                <span class="current-price">${product.price}</span>
                ${originalPriceHtml}
              </div>
              ${discountHtml}
            </div>
            <div class="rating-row">
              <div class="rating-stars">
                <span class="material-icons-outlined">star</span>
                <span class="material-icons-outlined">star</span>
                <span class="material-icons-outlined">star</span>
                <span class="material-icons-outlined">star</span>
                <span class="material-icons-outlined">star_half</span>
              </div>
              <span class="rating-count">(${reviewCount})</span>
            </div>
            <button class="add-to-cart-btn" ${disabledAttr}>
              <span class="material-icons-outlined">shopping_cart</span>
              ${addToCartText}
            </button>
          </div>
        </div>
      </div>
    `;
  }

  if (loadMoreBtn && productsGrid) {
    loadMoreBtn.addEventListener('click', () => {
      loadMoreBtn.classList.add('is-loading');

      setTimeout(() => {
        const fragments = [];
        for (let i = 0; i < 22; i += 1) {
          const product = productsData[i % productsData.length];
          fragments.push(buildProductCard(product));
        }
        productsGrid.insertAdjacentHTML('beforeend', fragments.join(''));
        loadMoreBtn.classList.remove('is-loading');
      }, 600);
    });
  }

  document.querySelectorAll('.product-card-inner').forEach(card => {
    card.addEventListener('click', event => {
      if (event.target.closest('button, input, a')) {
        return;
      }
      const link = card.getAttribute('data-link');
      if (link) {
        window.location.href = link;
      }
    });
  });

  let slideIndex = 0;
  let slideInterval;
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');
  const sliderWrapper = document.querySelector('.slider-wrapper');

  // Show specific slide
  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active');
      dots[i].classList.remove('active');
    });

    slides[index].classList.add('active');
    dots[index].classList.add('active');
    slideIndex = index;
  }

  // Move to next/prev slide
  function moveSlide(direction) {
    let newIndex = slideIndex + direction;

    if (newIndex >= slides.length) {
      newIndex = 0;
    } else if (newIndex < 0) {
      newIndex = slides.length - 1;
    }

    showSlide(newIndex);
    resetInterval();
  }

  // Go to specific slide (from dots)
  function currentSlide(index) {
    showSlide(index);
    resetInterval();
  }

  // Auto-slide function
  function startSlideInterval() {
    slideInterval = setInterval(() => {
      moveSlide(1);
    }, 6000); // Change slide every 6 seconds
  }

  // Reset interval (when user interacts)
  function resetInterval() {
    clearInterval(slideInterval);
    startSlideInterval();
  }

  // Initialize slider
  showSlide(0);
  startSlideInterval();

  // ========================================================================
  // FLASH SALE COUNTDOWN TIMER
  // ========================================================================
  
  // Set countdown target (18 hours, 14 minutes, 59 seconds from now)
  const countdownDuration = 18 * 60 * 60 + 14 * 60 + 59; // in seconds
  let remainingTime = countdownDuration;
  
  const countdownTimerEl = document.getElementById('countdown-timer');
  const progressBarEl = document.getElementById('sale-progress');
  
  function formatTime(seconds) {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }
  
  function updateCountdown() {
    if (remainingTime > 0) {
      remainingTime--;
      
      // Update timer display
      if (countdownTimerEl) {
        countdownTimerEl.textContent = formatTime(remainingTime);
      }
      
      // Update progress bar
      if (progressBarEl) {
        const progressPercent = (remainingTime / countdownDuration) * 100;
        progressBarEl.style.width = progressPercent + '%';
      }
    } else {
      // Countdown finished
      if (countdownTimerEl) {
        countdownTimerEl.textContent = 'EXPIRED';
      }
      if (progressBarEl) {
        progressBarEl.style.width = '0%';
      }
    }
  }
  
  // Initialize progress bar
  if (progressBarEl) {
    const initialProgress = (remainingTime / countdownDuration) * 100;
    progressBarEl.style.width = initialProgress + '%';
  }
  
  // Update countdown every second
  setInterval(updateCountdown, 1000);

  // ========================================================================
  // HORIZONTAL SLIDER FUNCTIONS
  // ========================================================================
  
  // Flash Sale Slider
  let flashSaleScrollPos = 0;
  function slideFlashSale(direction) {
    const track = document.getElementById('flash-sale-track');
    const cardWidth = 280; // 260px card + 20px gap
    const viewportWidth = document.querySelector('.slider-viewport').offsetWidth;
    const maxScroll = track.scrollWidth - viewportWidth;
    
    flashSaleScrollPos += direction * cardWidth * 3;
    if (flashSaleScrollPos < 0) flashSaleScrollPos = 0;
    if (flashSaleScrollPos > maxScroll) flashSaleScrollPos = maxScroll;
    
    track.style.transform = `translateX(-${flashSaleScrollPos}px)`;
  }

  // Top Rated Slider
  let topRatedScrollPos = 0;
  function slideTopRated(direction) {
    const track = document.getElementById('top-rated-track');
    const cardWidth = 280;
    const viewportWidth = document.querySelector('.slider-viewport').offsetWidth;
    const maxScroll = track.scrollWidth - viewportWidth;
    
    topRatedScrollPos += direction * cardWidth * 3;
    if (topRatedScrollPos < 0) topRatedScrollPos = 0;
    if (topRatedScrollPos > maxScroll) topRatedScrollPos = maxScroll;
    
    track.style.transform = `translateX(-${topRatedScrollPos}px)`;
  }

  // ========================================================================
  // FAVORITE BUTTON TOGGLE
  // ========================================================================
  document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      this.classList.toggle('active');
      const icon = this.querySelector('.material-icons-outlined');
      if (this.classList.contains('active')) {
        icon.textContent = 'favorite';
      } else {
        icon.textContent = 'favorite_border';
      }
    });
  });

  // ========================================================================
  // QUANTITY SELECTOR
  // ========================================================================
  function updateQuantity(button, change) {
    const qtySelector = button.closest('.quantity-selector');
    const qtyInput = qtySelector.querySelector('.qty-input');
    let newValue = parseInt(qtyInput.value) + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > 99) newValue = 99;
    
    qtyInput.value = newValue;
  }
</script>

<?php include 'footer.php'; ?>





