<?php include 'nav-bar.php'; ?>

<main class="contact-page">
  <section class="contact-hero">
    <div class="page-wrap">
      <p class="hero-kicker">HuddersHub Support</p>
      <h1>Contact our team</h1>
      <p class="hero-sub">Send a message for testing. We will not send emails in this demo.</p>
    </div>
  </section>

  <section class="contact-content">
    <div class="page-wrap contact-grid">
      <div class="contact-card">
        <h2>Quick help</h2>
        <p>Testing only: share your issue, order ID, and preferred reply time.</p>

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
        <div class="panel-block">
          <h3>Office</h3>
          <p>Huddersfield, West Yorkshire</p>
          <p>Mon - Fri, 9:00am - 5:30pm</p>
        </div>
        <div class="panel-block">
          <h3>Call</h3>
          <p>+44 1484 000 000</p>
          <p>Calls are recorded for quality testing.</p>
        </div>
        <div class="panel-block">
          <h3>Email</h3>
          <p>support@huddershub.test</p>
          <p>Replies within 1 business day.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
  .contact-page {
    background: linear-gradient(180deg, #f5f7fb 0%, #FFFFFF 100%);
    color: #111111;
    padding-top: 24px;
  }

  .contact-hero {
    padding: 40px 0 28px;
  }

  .contact-hero h1 {
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
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 28px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  }

  .contact-card h2 {
    font-size: 22px;
    margin-bottom: 8px;
  }

  .contact-form {
    margin-top: 18px;
    display: grid;
    gap: 14px;
  }

  .contact-form label {
    display: grid;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #222222;
  }

  .contact-form input,
  .contact-form textarea {
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 13px;
  }

  .contact-form input:focus,
  .contact-form textarea:focus {
    outline: none;
    border-color: #FF5E3A;
    box-shadow: 0 0 0 2px rgba(255, 111, 60, 0.15);
  }

  .contact-panel {
    display: grid;
    gap: 16px;
  }

  .panel-block {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  }

  .panel-block h3 {
    font-size: 16px;
    margin-bottom: 6px;
  }

  .panel-block p {
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 6px;
  }

  @media (max-width: 900px) {
    .contact-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php include 'footer.php'; ?>
