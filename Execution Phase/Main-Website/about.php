<?php
$activePage = 'about';
include 'nav-bar.php';
?>

<main class="about-page">
  <section class="about-hero">
    <div class="page-wrap">
      <p class="hero-kicker">About HuddersHub</p>
      <h1>Local. Convenient. Delicious.</h1>
      <p class="hero-sub">
        HuddersHub connects Huddersfield shoppers with trusted local traders for
        fresh produce, artisan goods, and collection-ready convenience.
      </p>
    </div>
  </section>

  <section class="about-story">
    <div class="page-wrap story-grid">
      <div class="story-media">
        <img src="photo.png" alt="HuddersHub marketplace">
      </div>
      <div class="story-copy">
        <h2>Our story</h2>
        <p>
          HuddersHub was built to spotlight independent traders across
          Huddersfield. We bring together a fishmonger, greengrocer,
          delicatessen, butcher, and bakery so you can shop local without
          missing the moments that matter.
        </p>
        <p>
          Browse, order, and choose your pickup time. We keep it simple, fast,
          and personal so you can spend less time running errands and more time
          enjoying fresh food.
        </p>
        <div class="story-highlights">
          <div class="highlight-card">
            <h3>5 local traders</h3>
            <p>Handpicked specialists you can trust.</p>
          </div>
          <div class="highlight-card">
            <h3>Fresh daily</h3>
            <p>Seasonal stock and artisan staples.</p>
          </div>
          <div class="highlight-card">
            <h3>Click & collect</h3>
            <p>Choose a slot that fits your day.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="about-cta">
    <div class="page-wrap cta-card">
      <h2>Ready to shop local?</h2>
      <p>Explore fresh picks from Huddersfield's best traders.</p>
      <a class="cta-button" href="homepage.php">Browse marketplace</a>
    </div>
  </section>
</main>

<style>
  .about-page {
    background: linear-gradient(180deg, #F7F6F3 0%, #FFFFFF 55%, #F7F6F3 100%);
    color: #0B140A;
    padding-top: 24px;
  }

  .about-hero {
    padding: 40px 0 24px;
  }

  .about-hero h1 {
    font-size: 48px;
    letter-spacing: -0.4px;
    margin-bottom: 12px;
  }

  .hero-kicker {
    text-transform: uppercase;
    letter-spacing: 1.2px;
    font-size: 12px;
    color: #FF5E3A;
    font-weight: 700;
  }

  .hero-sub {
    color: #5E6A63;
    max-width: 620px;
  }

  .about-story {
    padding: 24px 0 40px;
  }

  .story-grid {
    display: grid;
    grid-template-columns: 1.1fr 1.2fr;
    gap: 28px;
    align-items: center;
  }

  .story-media img {
    width: 100%;
    height: auto;
    border-radius: 0;
    box-shadow: 0 16px 32px rgba(15, 38, 11, 0.16);
  }

  .story-copy h2 {
    font-size: 28px;
    margin-bottom: 12px;
  }

  .story-copy p {
    font-size: 15px;
    line-height: 1.7;
    color: #5E6A63;
    margin-bottom: 16px;
  }

  .story-highlights {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }

  .highlight-card {
    background: #FFFFFF;
    border: 1px solid #DCE3DA;
    border-radius: 0;
    padding: 14px 12px;
    box-shadow: 0 10px 22px rgba(15, 38, 11, 0.12);
  }

  .highlight-card h3 {
    font-size: 16px;
    margin-bottom: 6px;
  }

  .highlight-card p {
    font-size: 12px;
    color: #5E6A63;
  }

  .about-cta {
    padding: 10px 0 60px;
  }

  .cta-card {
    background: linear-gradient(135deg, #0F260B 0%, #143d12 55%, #1f5b1d 100%);
    color: #FFFFFF;
    border-radius: 0;
    padding: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: 0 20px 36px rgba(15, 38, 11, 0.28);
  }

  .cta-card h2 {
    font-size: 28px;
    margin-bottom: 6px;
  }

  .cta-card p {
    color: rgba(255, 255, 255, 0.7);
  }

  .cta-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    border-radius: 0;
    background: #FF5E3A;
    color: #FFFFFF;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
  }

  .cta-button:hover {
    background: #E3472C;
  }

  @media (max-width: 960px) {
    .story-grid {
      grid-template-columns: 1fr;
    }

    .story-highlights {
      grid-template-columns: 1fr;
    }

    .cta-card {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>

<?php include 'footer.php'; ?>
