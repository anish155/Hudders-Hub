<?php
$activePage = 'contact';
include 'nav-bar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<main class="contact-page">
  <section class="contact-hero">
    <div class="page-wrap">
      <p class="hero-kicker">HuddersHub Support</p>
      <h1>Contact us</h1>
    </div>
  </section>

  <section class="contact-content">
    <div class="page-wrap contact-grid">
      <div class="contact-card">
        <h2>Quick help</h2>

        <form class="contact-form">
          <label>
            Full name
            <input type="text" placeholder="Your name">
          </label>
          <label>
            Email
            <input type="email" placeholder="name@example.com">
          </label>
          <label>
            Subject
            <input type="text" placeholder="Order issue">
          </label>
          <label>
            Message
            <textarea rows="5" placeholder="Tell us what went wrong"></textarea>
          </label>
          <button type="button" class="btn btn-primary">Send message</button>
        </form>
      </div>

      <div class="contact-panel">
        <div class="contact-panel-card">
          <div class="map-card">
            <h3>Huddersfield, UK</h3>
            <iframe
              title="Huddersfield map"
              src="https://www.google.com/maps?q=Huddersfield%2C%20UK&output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>

          <div class="panel-section">
            <h3>Office</h3>
            <p>Huddersfield, West Yorkshire</p>
            <p>Mon - Fri, 9:00am - 5:30pm</p>
          </div>
          <div class="panel-section">
            <h3>Call</h3>
            <p>+44 1484 000 000</p>
            <p>Calls are recorded for quality testing.</p>
          </div>
          <div class="panel-section">
            <h3>Email</h3>
            <p>support@huddershub.test</p>
            <p>Replies within 1 business day.</p>
          </div>

          <div class="panel-section socials-block">
            <h3>Follow us</h3>
            <div class="socials">
              <a href="#" aria-label="Instagram" class="social-pill">
                <i class="bi bi-instagram"></i>
              </a>
              <a href="#" aria-label="Facebook" class="social-pill">
                <i class="bi bi-facebook"></i>
              </a>
              <a href="#" aria-label="TikTok" class="social-pill">
                <i class="bi bi-tiktok"></i>
              </a>
              <a href="#" aria-label="X" class="social-pill">
                <i class="bi bi-twitter-x"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
  .contact-page {
    background: linear-gradient(180deg, #F7F6F3 0%, #FFFFFF 55%, #F7F6F3 100%);
    color: #0B140A;
    padding-top: 24px;
  }

  .contact-hero {
    padding: 40px 0 28px;
  }

  .contact-hero h1 {
    font-size: 48px;
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

  .contact-content {
    padding: 22px 0 60px;
  }

  .contact-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 28px;
  }

  .contact-card {
    background: #FFFFFF;
    border: 1px solid #DCE3DA;
    border-radius: 0;
    padding: 28px;
    box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
  }

  .contact-card h2 {
    font-size: 26px;
    margin-bottom: 8px;
  }

  .contact-card p {
    font-size: 15px;
    color: #5E6A63;
  }

  .contact-form {
    margin-top: 18px;
    display: grid;
    gap: 14px;
  }

  .contact-form label {
    display: grid;
    gap: 6px;
    font-size: 14px;
    font-weight: 600;
    color: #1E2A1C;
  }

  .contact-form input,
  .contact-form textarea {
    border: 1px solid #DCE3DA;
    border-radius: 0;
    padding: 12px 14px;
    font-size: 14px;
  }

  .contact-form input:focus,
  .contact-form textarea:focus {
    outline: none;
    border-color: #FF5E3A;
    box-shadow: 0 0 0 3px rgba(255, 94, 58, 0.22);
  }

  .contact-panel {
    display: grid;
  }

  .contact-panel-card {
    background: #FFFFFF;
    border: 1px solid #DCE3DA;
    border-radius: 0;
    padding: 18px;
    box-shadow: 0 14px 28px rgba(15, 38, 11, 0.12);
    display: grid;
    gap: 16px;
  }

  .panel-section h3 {
    font-size: 16px;
    margin-bottom: 6px;
  }

  .panel-section p {
    font-size: 13px;
    color: #5E6A63;
    margin-bottom: 6px;
  }

  .map-card iframe {
    width: 100%;
    height: 240px;
    border: 0;
    border-radius: 0;
    margin-top: 10px;
  }

  .socials {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
  }

  .social-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    height: auto;
    background: transparent;
    color: #0F260B;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    letter-spacing: 0.4px;
    transition: all 0.2s ease;
    border: none;
    box-shadow: none;
    padding: 2px 6px;
  }

  .social-pill i {
    font-size: 18px;
  }

  .social-pill:hover {
    color: #FF5E3A;
    transform: translateY(-1px);
  }

  @media (max-width: 900px) {
    .contact-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php include 'footer.php'; ?>
