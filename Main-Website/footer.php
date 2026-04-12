<footer class="site-footer">
  <div class="page-wrap footer-grid">
    <div class="footer-brand">
      <div class="brand-row">
        <img src="Asstes/logo.png" alt="HuddersHub logo">
        <span class="brand-name">HuddersHub</span>
      </div>
      <p class="footer-tagline">
        Local food, trusted traders, and fresh picks curated for Huddersfield.
      </p>
    </div>

    <div class="footer-col">
      <h4>Explore</h4>
      <a href="homepage.php">Homepage</a>
      <a href="invoice.php">Invoice (Test)</a>
      <a href="contact.php">Contact</a>
    </div>

    <div class="footer-col">
      <h4>Support</h4>
      <a href="#">Help Center</a>
      <a href="#">Order Tracking</a>
      <a href="#">Returns</a>
    </div>

    <div class="footer-col">
      <h4>Contact</h4>
      <p>Huddersfield, West Yorkshire</p>
      <p>support@huddershub.test</p>
      <p>+44 1484 000 000</p>
    </div>
  </div>

  <div class="page-wrap footer-bottom">
    <span>Copyright 2026 HuddersHub. All rights reserved.</span>
    <span>Built for testing only.</span>
  </div>
</footer>

<style>
  .site-footer {
    margin-top: 60px;
    background: #0F260B;
    color: #FFFFFF;
    padding: 48px 0 22px;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1fr;
    gap: 28px;
    align-items: start;
  }

  .footer-brand img {
    width: 48px;
    height: 48px;
    object-fit: contain;
  }

  .brand-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
  }

  .brand-name {
    font-weight: 700;
    font-style: italic;
    font-size: 24px;
  }

  .footer-tagline {
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
    line-height: 1.6;
  }

  .footer-col h4 {
    font-size: 14px;
    margin-bottom: 12px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
  }

  .footer-col a,
  .footer-col p {
    display: block;
    color: rgba(255, 255, 255, 0.72);
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 8px;
  }

  .footer-col a:hover {
    color: #FF8A6A;
  }

  .footer-bottom {
    margin-top: 26px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 16px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
  }

  @media (max-width: 900px) {
    .footer-grid {
      grid-template-columns: 1fr 1fr;
    }

    .footer-bottom {
      flex-direction: column;
      gap: 6px;
      align-items: flex-start;
    }
  }

  @media (max-width: 640px) {
    .footer-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

</body>
</html>
