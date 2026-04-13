<?php
include 'nav-bar-logged-in.php';
?>

<main class="product-page">

	<section class="product-hero">
		<div class="page-wrap hero-wrap">
			<!-- LEFT: Product images in white rectangle -->
			<div class="hero-left">
				<div class="image-card">
					<div class="slider-wrap">
						<button class="slider-arrow slider-prev" onclick="slidePrev()">
							<span class="material-icons-outlined">chevron_left</span>
						</button>
						<div class="main-image-wrap">
							<img id="heroMainImg" src="Asstes/Item-image/green-broccoli.jpg" alt="Fresh Spinach">
						</div>
						<button class="slider-arrow slider-next" onclick="slideNext()">
							<span class="material-icons-outlined">chevron_right</span>
						</button>
					</div>
					<div class="thumb-strip">
						<button class="thumb active" onclick="goToSlide(0, this)">
							<img src="Asstes/Item-image/green-broccoli.jpg" alt="Thumb 1">
						</button>
						<button class="thumb" onclick="goToSlide(1, this)">
							<img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Thumb 2">
						</button>
						<button class="thumb" onclick="goToSlide(2, this)">
							<img src="Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg" alt="Thumb 3">
						</button>
						<button class="thumb" onclick="goToSlide(3, this)">
							<img src="Asstes/Item-image/green-broccoli.jpg" alt="Thumb 4">
						</button>
						<button class="thumb" onclick="goToSlide(4, this)">
							<img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Thumb 5">
						</button>
					</div>
				</div>
			</div>

			<!-- RIGHT: Product info cards -->
			<div class="hero-right">
				<!-- Card: Product ID + Stock + Share/Fav -->
				<div class="info-card">
					<div class="top-meta-row">
						<span class="product-id">Product ID: #1090912</span>
						<div class="stock-share-fav">
							<span class="stock-badge">In stock</span>
							<button class="icon-btn" title="Share">
								<span class="material-icons-outlined">share</span>
							</button>
							<button class="icon-btn" title="Favourite">
								<span class="material-icons-outlined">favorite_border</span>
							</button>
						</div>
					</div>
				</div>

				<!-- Card: Product Name + Rating -->
				<div class="info-card">
					<h1 class="product-title">Spinach</h1>
					<div class="rating-row">
						<div class="stars-inline">
							<span class="material-icons-outlined">star</span>
							<span class="material-icons-outlined">star</span>
							<span class="material-icons-outlined">star</span>
							<span class="material-icons-outlined">star</span>
							<span class="material-icons-outlined">star_half</span>
						</div>
						<span class="rating-text">4.5 / 5 &nbsp;·&nbsp; 128 reviews</span>
					</div>
				</div>

				<!-- Card: Category + Sold by -->
				<div class="info-card">
					<div class="category-row">
						<span class="cat-label">Category:</span>
						<span class="tag">green</span>
						<span class="tag">fresh</span>
						<span class="tag">veggies</span>
					</div>
					<p class="sold-by">Sold by: <strong>GreenGrocer</strong></p>
				</div>

				<!-- Card: Size chips -->
				<div class="info-card">
					<div class="size-row">
						<button class="size-chip active">250g</button>
						<button class="size-chip">500g</button>
						<button class="size-chip">1kg</button>
					</div>
				</div>

				<!-- Card: Quantity -->
				<div class="info-card">
					<div class="qty-row">
						<label>Quantity</label>
						<div class="qty-control">
							<button class="qty-btn">−</button>
							<span class="qty-val">1</span>
							<button class="qty-btn">+</button>
						</div>
						<span class="qty-hint">250g minimum and 5kg maximum</span>
					</div>
				</div>

				<!-- Card: Price + Buttons -->
				<div class="info-card price-card">
					<div class="price-block">
						<span class="price"><span class="pound-sign">£</span> 0.90</span>
					</div>
					<div class="btn-row">
						<button class="btn btn-add-cart">Add to cart</button>
						<button class="btn btn-buy-now">Buy now</button>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- SEPARATOR LINE -->
	<hr class="section-divider">

	<!-- PRODUCT DESCRIPTION -->
	<section class="product-description">
		<div class="page-wrap">
			<h2 class="section-title">Product description</h2>
			<p class="desc-intro">Crisp, vibrant green spinach leaves with a clean, mildly earthy flavour. Perfect for salads, smoothies, sautéing, or wilting into pasta dishes. Harvested at dawn and delivered chilled to preserve peak freshness and nutrient content.</p>

			<div class="desc-compact-grid">
				<div class="desc-sub-card">
					<h3>Appearance &amp; Aroma</h3>
					<p>Deep emerald-green leaves with a glossy sheen and tender, crinkly texture. Fresh, grassy aroma with subtle earthy undertones that signal quality and freshness.</p>
				</div>
				<div class="desc-sub-card">
					<h3>Texture &amp; Flavour</h3>
					<p>Delicate, melt-in-the-mouth leaves when young and raw. When cooked, spinach softens beautifully, developing a rich, buttery taste with a hint of natural sweetness.</p>
				</div>
				<div class="desc-sub-card">
					<h3>Culinary Versatility</h3>
					<p>Ideal for fresh salads, green smoothies, creamed spinach, sautés with garlic and olive oil, pasta sauces, omelettes, and layered in lasagne. Wilts in seconds when heated.</p>
				</div>
				<div class="desc-sub-card">
					<h3>Nutritional Highlights</h3>
					<p>Rich in iron, calcium, vitamin K, folate, and antioxidants. Low in calories and packed with dietary fibre — a nutritional powerhouse that supports bone health and immunity.</p>
				</div>
				<div class="desc-sub-card">
					<h3>Growing &amp; Sourcing</h3>
					<p>Sourced from trusted local West Yorkshire farms practising crop rotation and integrated pest management. Grown without synthetic pesticides or GMO seeds in mineral-rich soil.</p>
				</div>
				<div class="desc-sub-card">
					<h3>Allergenic Information</h3>
					<p>No known allergens. Naturally free from gluten, dairy, nuts, and soy. Please note: prepared and packed in a facility that also handles tree nuts, soy, and dairy products.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- REVIEWS -->
	<section class="reviews-section">
		<div class="page-wrap">
			<div class="reviews-top">
				<div class="reviews-summary">
					<h2 class="reviews-title">Reviews</h2>
					<div class="review-big-score">
						<span class="big-num">4.5</span>
						<span class="big-total">/5</span>
					</div>
					<div class="stars-big">
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star_half</span>
					</div>
				</div>
				<div class="review-bars-wrap">
					<div class="bar-row"><span class="bar-label">5</span><div class="bar-bg"><span class="bar-fill" style="width:70%"></span></div></div>
					<div class="bar-row"><span class="bar-label">4</span><div class="bar-bg"><span class="bar-fill" style="width:45%"></span></div></div>
					<div class="bar-row"><span class="bar-label">3</span><div class="bar-bg"><span class="bar-fill" style="width:15%"></span></div></div>
					<div class="bar-row"><span class="bar-label">2</span><div class="bar-bg"><span class="bar-fill" style="width:8%"></span></div></div>
					<div class="bar-row"><span class="bar-label">1</span><div class="bar-bg"><span class="bar-fill" style="width:4%"></span></div></div>
				</div>
			</div>

			<div class="review-sort-row">
				<span class="sort-btn active">Sort by: Date</span>
				<button class="sort-btn">Filter: All star</button>
			</div>

			<div class="review-cards">
				<div class="review-card">
					<div class="review-stars">
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star_half</span>
					</div>
					<div class="review-author">
						<div class="review-avatar">
							<img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Avatar">
						</div>
						<div class="review-text">
							<p><strong>Amelia T.</strong> — <span class="review-date">12 Mar 2026</span></p>
							<p>Very fresh and tender leaves. Great in salads and smoothies. Will definitely order again!</p>
						</div>
					</div>
				</div>

				<div class="review-card">
					<div class="review-stars">
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
					</div>
					<div class="review-author">
						<div class="review-avatar">
							<img src="Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg" alt="Avatar">
						</div>
						<div class="review-text">
							<p><strong>Marcus J.</strong> — <span class="review-date">8 Mar 2026</span></p>
							<p>Excellent quality! Crisp, clean taste and the leaves were perfectly sized. Great value for money.</p>
						</div>
					</div>
				</div>

				<div class="review-card">
					<div class="review-stars">
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star</span>
						<span class="material-icons-outlined">star_half</span>
					</div>
					<div class="review-author">
						<div class="review-avatar">
							<img src="Asstes/Item-image/green-broccoli.jpg" alt="Avatar">
						</div>
						<div class="review-text">
							<p><strong>Priya S.</strong> — <span class="review-date">2 Mar 2026</span></p>
							<p>Love the freshness! Perfect for meal prep and wilting into pasta dishes. Highly recommend.</p>
						</div>
					</div>
				</div>
			</div>

			<button class="show-more-btn">Show more</button>
		</div>
	</section>

	<!-- MORE FROM SELLER -->
	<section class="horizontal-products">
		<div class="page-wrap">
			<h2 class="section-title">More from seller</h2>
			<div class="scroll-row">
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Product"></div>
					<p class="h-card-name">Green Bell Pepper</p>
					<p class="h-card-price">£ 0.90</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg" alt="Product"></div>
					<p class="h-card-name">Chinese Broccoli</p>
					<p class="h-card-price">£ 0.90</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-broccoli.jpg" alt="Product"></div>
					<p class="h-card-name">Seasonal Greens</p>
					<p class="h-card-price">£ 1.10</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Product"></div>
					<p class="h-card-name">Garden Peas</p>
					<p class="h-card-price">£ 1.30</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-broccoli.jpg" alt="Product"></div>
					<p class="h-card-name">Spring Cabbage</p>
					<p class="h-card-price">£ 0.95</p>
				</div>
			</div>
		</div>
	</section>

	<!-- SIMILAR PRODUCTS -->
	<section class="horizontal-products">
		<div class="page-wrap">
			<h2 class="section-title">Similar products</h2>
			<div class="scroll-row">
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-broccoli.jpg" alt="Product"></div>
					<p class="h-card-name">Spring Greens</p>
					<p class="h-card-price">£ 1.20</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg" alt="Product"></div>
					<p class="h-card-name">Baby Spinach</p>
					<p class="h-card-price">£ 1.50</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-bell-pepper-isolated.jpg" alt="Product"></div>
					<p class="h-card-name">Pak Choi</p>
					<p class="h-card-price">£ 1.05</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/green-broccoli.jpg" alt="Product"></div>
					<p class="h-card-name">Kale Bunch</p>
					<p class="h-card-price">£ 1.40</p>
				</div>
				<div class="h-card">
					<div class="h-card-img"><img src="Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg" alt="Product"></div>
					<p class="h-card-name">Broccoli Florets</p>
					<p class="h-card-price">£ 1.15</p>
				</div>
			</div>
		</div>
	</section>

</main>

<style>
	@import url('https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@400;500;600;700&display=swap');

	:root {
		--brand-dark: #0F260B;
		--brand-mid: #1C3C17;
		--accent-green: #CAED95;
		--surface: #FFFFFF;
		--surface-alt: #F7F7F5;
		--text-primary: #101412;
		--text-muted: #5F6A63;
		--border: #E4E8E2;
		--shadow-sm: 0 4px 12px rgba(15, 38, 11, 0.06);
		--shadow-md: 0 8px 24px rgba(15, 38, 11, 0.10);
	}

	* { box-sizing: border-box; margin: 0; padding: 0; }

	body {
		font-family: 'Google Sans Flex', sans-serif;
		background: #FBFCFA;
		color: var(--text-primary);
	}

	.material-icons-outlined { font-size: 18px; vertical-align: middle; }

	/* ============================================================
	   HERO
	   ============================================================ */
	.product-hero {
		background: transparent;
	}

	.hero-wrap {
		display: flex;
		padding: 24px;
		gap: 24px;
		align-items: stretch;
		background: #FFFFFF;
	}

	.hero-left {
		flex: 0 0 40%;
		padding: 0;
	}

	/* Image card - no individual background */
	.image-card {
		background: transparent;
		padding: 0;
		height: 100%;
		display: flex;
		flex-direction: column;
	}

	/* Info cards - no individual backgrounds */
	.info-card {
		background: transparent;
		padding: 0;
	}

	/* Slider wrapper with arrows */
	.slider-wrap {
		position: relative;
		width: 100%;
		aspect-ratio: 1;
		flex: 1;
		min-height: 0;
	}

	.main-image-wrap {
		width: 100%;
		height: 100%;
		overflow: hidden;
	}

	.main-image-wrap img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
		transition: transform 0.4s ease;
	}

	.main-image-wrap:hover img {
		transform: scale(1.04);
	}

	.slider-arrow {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		width: 32px;
		height: 32px;
		border: none;
		background: rgba(255,255,255,0.9);
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 10;
		transition: all 0.2s ease;
	}

	.slider-arrow:hover {
		background: #fff;
		box-shadow: 0 2px 12px rgba(0,0,0,0.3);
	}

	.slider-prev { left: 0; }
	.slider-next { right: 0; }

	.slider-arrow .material-icons-outlined {
		font-size: 20px;
		color: var(--brand-dark);
	}

	/* Thumbnails */
	.thumb-strip {
		display: flex;
		gap: 6px;
		margin-top: 10px;
	}

	.thumb {
		width: 52px;
		height: 52px;
		border: 2px solid transparent;
		overflow: hidden;
		cursor: pointer;
		background: transparent;
		padding: 0;
		transition: all 0.2s ease;
	}

	.thumb:hover {
		border-color: rgba(15, 38, 11, 0.3);
		opacity: 0.85;
	}

	.thumb.active {
		border-color: var(--brand-dark);
		opacity: 1;
	}

	.thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	/* RIGHT panel */
	.hero-right {
		flex: 1;
		padding: 0;
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	/* Info cards - no individual backgrounds */
	.info-card {
		background: transparent;
		padding: 0;
		flex-shrink: 0;
		margin-bottom: 20px;
	}

	.top-meta-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.product-id {
		font-size: 14px;
		color: var(--text-muted);
		font-weight: 400;
	}

	.stock-share-fav {
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.stock-badge {
		background: var(--brand-dark);
		color: var(--accent-green);
		padding: 4px 12px;
		font-size: 12px;
		font-weight: 600;
	}

	.icon-btn {
		border: none;
		background: transparent;
		width: 32px;
		height: 32px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: all 0.2s;
	}

	.icon-btn:hover {
		opacity: 0.6;
	}

	.icon-btn .material-icons-outlined {
		font-size: 20px;
		color: var(--text-muted);
	}

	.product-title {
		font-size: 64px;
		font-weight: 700;
		color: var(--text-primary);
		line-height: 1.1;
	}

	.rating-row {
		display: flex;
		align-items: center;
		gap: 14px;
	}

	.stars-inline {
		display: inline-flex;
		gap: 2px;
		color: #F4B740;
	}

	.stars-inline .material-icons-outlined {
		font-size: 20px;
	}

	.rating-text {
		font-size: 14px;
		color: var(--text-muted);
		font-weight: 400;
	}

	.category-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 0;
		margin-bottom: 8px;
	}

	.cat-label {
		font-size: 14px;
		font-weight: 500;
		color: var(--text-primary);
	}

	.tag {
		background: #F5F5F3;
		padding: 5px 12px;
		font-size: 13px;
		font-weight: 500;
		color: var(--text-muted);
		cursor: pointer;
		transition: all 0.2s;
	}

	.tag:hover {
		background: var(--brand-dark);
		color: #fff;
	}

	.sold-by {
		font-size: 14px;
		font-weight: 500;
		color: var(--text-muted);
		user-select: text;
		margin-bottom: 0;
	}

	.sold-by strong {
		color: var(--text-primary);
		font-weight: 600;
		font-size: 15px;
	}

	.size-row {
		display: flex;
		gap: 10px;
	}

	.size-chip {
		border: 2px solid #E0E0E0;
		background: #FFFFFF;
		padding: 7px 18px;
		cursor: pointer;
		font-size: 14px;
		font-weight: 500;
		transition: all 0.2s;
		user-select: none;
	}

	.size-chip:hover {
		border-color: var(--brand-dark);
		background: #F5F5F3;
	}

	.size-chip.active {
		background: var(--brand-dark);
		color: #fff;
		border-color: var(--brand-dark);
		box-shadow: 0 2px 8px rgba(15, 38, 11, 0.15);
	}

	.qty-row {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.qty-row label {
		font-weight: 500;
		font-size: 14px;
	}

	.qty-control {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		background: #F5F5F3;
		padding: 5px 12px;
	}

	.qty-btn {
		border: none;
		background: #fff;
		width: 26px;
		height: 26px;
		cursor: pointer;
		font-size: 16px;
		font-weight: 500;
	}

	.qty-val {
		min-width: 24px;
		text-align: center;
		font-weight: 500;
		font-size: 14px;
	}

	.qty-hint {
		font-size: 11px;
		color: var(--text-muted);
	}

	.price-block {
		margin: 0;
	}

	.price {
		font-size: 56px;
		font-weight: 800;
		color: var(--brand-dark);
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.price .pound-sign {
		font-size: 42px;
		font-weight: 700;
		vertical-align: super;
	}

	.btn-row {
		display: flex;
		gap: 18px;
	}

	.btn {
		padding: 18px 44px;
		border: none;
		font-size: 20px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-add-cart {
		background: var(--brand-dark);
		color: #fff;
	}

	.btn-add-cart:hover {
		background: var(--brand-mid);
		transform: translateY(-1px);
		box-shadow: var(--shadow-sm);
	}

	.btn-buy-now {
		background: #E53935;
		color: #fff;
	}

	.btn-buy-now:hover {
		background: #C62828;
		transform: translateY(-1px);
	}

	/* ============================================================
	   SECTIONS — no borders, no backgrounds on cards
	   ============================================================ */
	.page-wrap {
		width: min(1200px, 94%);
		margin: 0 auto;
	}

	.section-title {
		font-size: 44px;
		font-weight: 700;
		margin-bottom: 12px;
	}

	/* SEPARATOR LINE */
	.section-divider {
		border: none;
		height: 2px;
		background: var(--border);
		margin: 32px auto;
		width: min(1200px, 94%);
	}

	/* DESCRIPTION */
	.product-description {
		padding: 20px 0;
	}

	.desc-intro {
		font-size: 22px;
		color: var(--text-muted);
		line-height: 1.6;
		margin-bottom: 24px;
	}

	.desc-compact-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px;
	}

	.desc-sub-card {
		padding: 20px 24px;
		background: var(--surface-alt);
		border-radius: 8px;
	}

	.desc-sub-card h3 {
		font-size: 24px;
		font-weight: 700;
		margin-bottom: 8px;
	}

	.desc-sub-card p {
		font-size: 18px;
		color: var(--text-muted);
		line-height: 1.6;
	}

	/* GROWING - hidden, merged into product description */
	.growing-section {
		display: none;
	}

	/* REVIEWS */
	.reviews-section {
		padding: 32px 0;
	}

	.reviews-top {
		display: flex;
		justify-content: space-between;
		align-items: flex-end;
		gap: 24px;
		margin-bottom: 20px;
	}

	.reviews-summary {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}

	.reviews-title {
		font-size: 44px;
		font-weight: 700;
	}

	.review-big-score {
		display: flex;
		align-items: baseline;
		gap: 4px;
	}

	.big-num {
		font-size: 48px;
		font-weight: 700;
		color: var(--brand-dark);
	}

	.big-total {
		font-size: 20px;
		color: var(--text-muted);
	}

	.stars-big {
		display: inline-flex;
		gap: 2px;
		color: #F4B740;
	}

	.stars-big .material-icons-outlined {
		font-size: 32px;
	}

	.review-bars-wrap {
		display: flex;
		flex-direction: column;
		gap: 6px;
		min-width: 260px;
	}

	.bar-row {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.bar-label {
		font-size: 14px;
		font-weight: 600;
		min-width: 14px;
	}

	.bar-bg {
		flex: 1;
		height: 10px;
		background: #E6E6E6;
		overflow: hidden;
	}

	.bar-fill {
		display: block;
		height: 100%;
		background: var(--brand-dark);
	}

	.review-sort-row {
		display: flex;
		gap: 12px;
		margin-bottom: 18px;
	}

	.sort-btn {
		border: none;
		background: transparent;
		padding: 8px 18px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
	}

	.sort-btn.active {
		background: var(--brand-dark);
		color: #fff;
	}

	.review-cards {
		display: flex;
		flex-direction: column;
		gap: 16px;
	}

	.review-card {
		padding: 20px 0;
		border-bottom: 1px solid var(--border);
	}

	.review-stars {
		display: inline-flex;
		gap: 2px;
		color: #F4B740;
		margin-bottom: 8px;
	}

	.review-stars .material-icons-outlined {
		font-size: 24px;
	}

	.review-author {
		display: flex;
		gap: 14px;
	}

	.review-avatar {
		width: 48px;
		height: 48px;
		overflow: hidden;
		flex-shrink: 0;
	}

	.review-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
		transition: transform 0.3s ease;
	}

	.review-avatar:hover img {
		transform: scale(1.1);
	}

	.review-text p {
		font-size: 16px;
		color: var(--text-muted);
		line-height: 1.6;
	}

	.review-date {
		color: var(--text-muted);
		font-size: 14px;
	}

	.show-more-btn {
		display: block;
		margin: 28px auto 0;
		border: none;
		background: transparent;
		padding: 12px 36px;
		cursor: pointer;
		font-weight: 600;
		font-size: 16px;
		color: var(--text-muted);
		transition: color 0.2s;
	}

	.show-more-btn:hover {
		color: var(--brand-dark);
	}

	/* HORIZONTAL PRODUCT ROWS */
	.horizontal-products {
		padding: 20px 0;
	}

	.scroll-row {
		display: flex;
		gap: 12px;
		overflow-x: auto;
		padding-bottom: 6px;
	}

	.scroll-row::-webkit-scrollbar {
		height: 3px;
	}

	.scroll-row::-webkit-scrollbar-thumb {
		background: var(--border);
	}

	.h-card {
		flex: 0 0 150px;
		text-align: center;
		cursor: pointer;
	}

	.h-card-img {
		width: 150px;
		height: 150px;
		overflow: hidden;
		margin-bottom: 8px;
	}

	.h-card-img img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
		transition: transform 0.35s ease;
	}

	.h-card:hover .h-card-img img {
		transform: scale(1.08);
	}

	.h-card-name {
		font-size: 12px;
		font-weight: 600;
		margin-bottom: 3px;
	}

	.h-card-price {
		font-size: 13px;
		font-weight: 700;
		color: var(--brand-dark);
	}

	/* RESPONSIVE */
	@media (max-width: 960px) {
		.hero-wrap {
			flex-direction: column;
		}
		.hero-wrap {
			max-height: none;
		}
		.hero-left {
			flex: none;
			padding: 20px 0;
		}
		.hero-right {
			padding: 0 0 20px 0;
			overflow: visible;
		}
		.image-card {
			padding: 16px;
		}
		.top-meta-row {
			flex-wrap: wrap;
			gap: 12px;
		}
		.stock-share-fav {
			flex-wrap: wrap;
		}
		.rating-row {
			flex-wrap: wrap;
		}
		.category-row {
			flex-wrap: wrap;
		}
		.qty-row {
			flex-wrap: wrap;
			align-items: flex-start;
		}
		.btn-row {
			flex-wrap: wrap;
		}
		.desc-grid {
			grid-template-columns: 1fr;
		}
		.desc-card-img,
		.desc-card-full {
			grid-column: 1;
		}
		.grow-grid {
			grid-template-columns: 1fr;
		}
		.reviews-top {
			flex-direction: column;
			align-items: flex-start;
		}
	}

	/* Tablet */
	@media (max-width: 768px) {
		.info-card {
			margin-bottom: 12px;
		}
		.product-id {
			font-size: 13px;
		}
		.stock-badge {
			font-size: 11px;
			padding: 3px 10px;
		}
		.icon-btn {
			width: 28px;
			height: 28px;
		}
		.icon-btn .material-icons-outlined {
			font-size: 18px;
		}
		.section-title {
			font-size: 36px;
		}
		.product-title {
			font-size: 48px;
		}
		.stars-inline .material-icons-outlined {
			font-size: 18px;
		}
		.rating-text {
			font-size: 13px;
		}
		.cat-label {
			font-size: 13px;
		}
		.tag {
			font-size: 12px;
			padding: 4px 10px;
		}
		.sold-by {
			font-size: 13px;
		}
		.sold-by strong {
			font-size: 14px;
		}
		.size-chip {
			font-size: 13px;
			padding: 6px 14px;
		}
		.qty-row label {
			font-size: 13px;
		}
		.qty-control {
			padding: 4px 10px;
			gap: 6px;
		}
		.qty-btn {
			width: 24px;
			height: 24px;
			font-size: 14px;
		}
		.qty-val {
			font-size: 13px;
			min-width: 20px;
		}
		.qty-hint {
			font-size: 10px;
		}
		.price {
			font-size: 44px;
		}
		.price .pound-sign {
			font-size: 32px;
		}
		.btn {
			padding: 14px 32px;
			font-size: 16px;
			flex: 1 1 160px;
		}
		.desc-compact-grid {
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
		}
		.desc-sub-card {
			padding: 16px 18px;
		}
		.desc-sub-card h3 {
			font-size: 20px;
		}
		.desc-sub-card p {
			font-size: 16px;
		}
		.desc-intro {
			font-size: 18px;
		}
		.reviews-title {
			font-size: 36px;
		}
		.big-num {
			font-size: 40px;
		}
		.big-total {
			font-size: 18px;
		}
		.stars-big .material-icons-outlined {
			font-size: 28px;
		}
		.review-text p {
			font-size: 14px;
		}
		.review-date {
			font-size: 13px;
		}
		.review-avatar {
			width: 42px;
			height: 42px;
		}
		.show-more-btn {
			font-size: 14px;
		}
	}

	/* Mobile */
	@media (max-width: 480px) {
		.hero-wrap {
			padding: 16px;
			gap: 16px;
		}
		.info-card {
			margin-bottom: 10px;
		}
		.top-meta-row {
			flex-direction: column;
			align-items: flex-start;
		}
		.product-id {
			font-size: 12px;
		}
		.stock-share-fav {
			gap: 8px;
			width: 100%;
			justify-content: flex-start;
		}
		.stock-badge {
			font-size: 10px;
			padding: 3px 8px;
		}
		.icon-btn {
			width: 26px;
			height: 26px;
		}
		.icon-btn .material-icons-outlined {
			font-size: 16px;
		}
		.section-title {
			font-size: 28px;
		}
		.product-title {
			font-size: 36px;
		}
		.stars-inline .material-icons-outlined {
			font-size: 16px;
		}
		.rating-text {
			font-size: 12px;
		}
		.category-row {
			flex-wrap: wrap;
			gap: 6px;
		}
		.cat-label {
			font-size: 12px;
		}
		.tag {
			font-size: 11px;
			padding: 4px 8px;
		}
		.sold-by {
			font-size: 12px;
		}
		.sold-by strong {
			font-size: 13px;
		}
		.size-row {
			gap: 6px;
			flex-wrap: wrap;
		}
		.size-chip {
			font-size: 12px;
			padding: 5px 12px;
		}
		.qty-row {
			flex-wrap: wrap;
			gap: 8px;
		}
		.qty-row label {
			font-size: 12px;
		}
		.qty-control {
			padding: 4px 8px;
			gap: 6px;
		}
		.qty-btn {
			width: 22px;
			height: 22px;
			font-size: 14px;
		}
		.qty-val {
			font-size: 12px;
			min-width: 18px;
		}
		.qty-hint {
			font-size: 10px;
			width: 100%;
		}
		.price {
			font-size: 36px;
			flex-wrap: wrap;
		}
		.price .pound-sign {
			font-size: 26px;
		}
		.btn-row {
			flex-direction: column;
			gap: 8px;
		}
		.btn {
			padding: 14px 28px;
			font-size: 16px;
			width: 100%;
		}
		.desc-compact-grid {
			grid-template-columns: 1fr 1fr;
			gap: 14px;
		}
		.desc-sub-card {
			padding: 14px 16px;
		}
		.desc-sub-card h3 {
			font-size: 18px;
		}
		.desc-sub-card p {
			font-size: 15px;
		}
		.desc-intro {
			font-size: 16px;
		}
		.reviews-title {
			font-size: 30px;
		}
		.big-num {
			font-size: 36px;
		}
		.big-total {
			font-size: 16px;
		}
		.stars-big .material-icons-outlined {
			font-size: 24px;
		}
		.review-stars .material-icons-outlined {
			font-size: 20px;
		}
		.review-text p {
			font-size: 14px;
		}
		.review-date {
			font-size: 12px;
		}
		.review-avatar {
			width: 40px;
			height: 40px;
		}
		.show-more-btn {
			font-size: 14px;
		}
	}

	/* Tablet and up: scale to keep the hero details visible */
	@media (min-width: 768px) {
		.hero-right {
			gap: clamp(6px, 1vh, 10px);
		}
		.info-card {
			margin-bottom: clamp(8px, 1.2vh, 16px);
		}
		.product-id {
			font-size: clamp(12px, 1.6vw, 14px);
		}
		.stock-badge {
			font-size: clamp(10px, 1.4vw, 12px);
			padding: clamp(3px, 0.6vh, 4px) clamp(8px, 1.4vw, 12px);
		}
		.icon-btn {
			width: clamp(28px, 3.2vw, 32px);
			height: clamp(28px, 3.2vw, 32px);
		}
		.icon-btn .material-icons-outlined {
			font-size: clamp(16px, 2.2vw, 20px);
		}
		.section-title {
			font-size: clamp(28px, 3.6vw, 44px);
		}
		.product-title {
			font-size: clamp(36px, 5vw, 64px);
		}
		.stars-inline .material-icons-outlined {
			font-size: clamp(16px, 2vw, 22px);
		}
		.rating-text {
			font-size: clamp(12px, 1.5vw, 15px);
		}
		.cat-label {
			font-size: clamp(12px, 1.4vw, 14px);
		}
		.tag {
			font-size: clamp(11px, 1.3vw, 13px);
			padding: clamp(4px, 0.6vh, 5px) clamp(10px, 1.4vw, 12px);
		}
		.sold-by {
			font-size: clamp(12px, 1.5vw, 14px);
		}
		.sold-by strong {
			font-size: clamp(13px, 1.6vw, 15px);
		}
		.size-chip {
			font-size: clamp(12px, 1.4vw, 14px);
			padding: clamp(5px, 0.8vh, 7px) clamp(14px, 1.8vw, 18px);
		}
		.qty-row label {
			font-size: clamp(12px, 1.5vw, 14px);
		}
		.qty-control {
			padding: clamp(4px, 0.7vh, 5px) clamp(10px, 1.4vw, 12px);
			gap: clamp(6px, 1vw, 8px);
		}
		.qty-btn {
			width: clamp(22px, 2.8vw, 26px);
			height: clamp(22px, 2.8vw, 26px);
			font-size: clamp(14px, 1.6vw, 16px);
		}
		.qty-val {
			font-size: clamp(12px, 1.5vw, 14px);
			min-width: clamp(20px, 2.4vw, 24px);
		}
		.qty-hint {
			font-size: clamp(10px, 1.2vw, 11px);
		}
		.price {
			font-size: clamp(36px, 4.5vw, 56px);
		}
		.price .pound-sign {
			font-size: clamp(26px, 3.2vw, 42px);
		}
		.btn {
			padding: clamp(14px, 2vh, 18px) clamp(28px, 3vw, 44px);
			font-size: clamp(16px, 2vw, 20px);
		}
		.desc-compact-grid {
			grid-template-columns: repeat(3, 1fr);
			gap: 18px;
		}
		.desc-sub-card h3 {
			font-size: clamp(18px, 2.2vw, 24px);
		}
		.desc-sub-card p {
			font-size: clamp(14px, 1.8vw, 18px);
		}
		.desc-intro {
			font-size: clamp(16px, 2vw, 22px);
		}
	}
</style>

<script>
	const images = [
		'Asstes/Item-image/green-broccoli.jpg',
		'Asstes/Item-image/green-bell-pepper-isolated.jpg',
		'Asstes/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
		'Asstes/Item-image/green-broccoli.jpg',
		'Asstes/Item-image/green-bell-pepper-isolated.jpg'
	];
	let current = 0;

	function updateSlider() {
		const main = document.getElementById('heroMainImg');
		main.src = images[current];
		document.querySelectorAll('.thumb').forEach((t, i) => {
			t.classList.toggle('active', i === current);
		});
	}

	function slideNext() {
		current = (current + 1) % images.length;
		updateSlider();
	}

	function slidePrev() {
		current = (current - 1 + images.length) % images.length;
		updateSlider();
	}

	function goToSlide(index, btn) {
		current = index;
		updateSlider();
	}
</script>

<?php include 'footer.php'; ?>
