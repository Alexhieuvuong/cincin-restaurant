<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get featured categories
$categories_query = "SELECT * FROM categories LIMIT 6";
$categories_result = $conn->query($categories_query);

// Get featured products
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.id 
                   WHERE p.is_available = 1 
                   ORDER BY p.id DESC LIMIT 8";
$products_result = $conn->query($products_query);
?>

<!-- Add carousel.css to the head -->
<link rel="stylesheet" href="assets/css/carousel.css">

<!-- HERO SECTION WITH CAROUSEL -->
<section class="banner">
    <div class="carousel-container">
        <div class="carousel-slides" id="carouselSlides">
            <!-- Slide 1 - Crème Brulée (was Slide 2) -->
            <div class="carousel-slide slide-2">
                <div class="slide-content-2">
                    <div class="banner-background-text">
                        <div>CRÈME BRULÉE</div>
                        <div>CRÈME BRULÉE</div>
                        <div>CRÈME BRULÉE</div>
                    </div>
                    <div class="banner-content">
                        <div class="creme-brulee-content">
                            <div class="text-section">
                                <h2 class="welcome-text">Bentornato<br>Crème Brulée</h2>
                            </div>
                            <div class="drinks-section">
                                <div class="drink-item">
                                    <div class="drink-glass iced-shaken">
                                        <div class="drink-layer dark-coffee"></div>
                                        <div class="drink-layer milk-swirl"></div>
                                        <div class="foam-top"></div>
                                    </div>
                                    <div class="drink-label">
                                        <h3>Crème Brulée<br>Iced Shaken<br>Espresso</h3>
                                    </div>
                                </div>
                                <div class="drink-item">
                                    <div class="drink-glass frappuccino">
                                        <div class="drink-layer frapp-base"></div>
                                        <div class="drink-layer whipped-cream"></div>
                                        <div class="foam-top caramel-drizzle"></div>
                                    </div>
                                    <div class="drink-label">
                                        <h3>Crème Brulée<br>Brown Sugar<br>Frappuccino®</h3>
                                        <p class="drink-type">blended beverage</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 2 - WOW Surprise Offer (was Slide 3) -->
            <div class="carousel-slide slide-3">
                <div class="slide-content-3 wow-slide">
                    <div class="banner-background-text wow-bg-text">
                        <div>WOW!</div>
                        <div>UNLOCK</div>
                        <div>SURPRISE</div>
                    </div>
                    <div class="banner-content wow-content">
                        <div class="wow-left">
                            <div class="wow-emoji">🎉</div>
                            <h2 class="wow-title">Surprise Offer!</h2>
                            <p class="wow-desc">For a limited time, enjoy a <span class="wow-highlight">free dessert</span> with any drink purchase.<br>Click below to reveal your code!</p>
                            <a href="#" class="cta-button wow-btn" onclick="alert('Your code: WOWHIEUVUONG25');return false;">Reveal Code</a>
                        </div>
                        <div class="wow-right">
                            <div class="wow-giftbox">
                                <div class="wow-lid"></div>
                                <div class="wow-box"></div>
                                <div class="wow-ribbon"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 3 - Summer Merch (was Slide 1) -->
            <div class="carousel-slide slide-1">
                <div class="slide-content-1">
                    <div class="banner-background-text">
                        <div>SUMMER MERCH</div>
                        <div>SUMMER MERCH</div>
                        <div>SUMMER MERCH</div>
                        <div>SUMMER MERCH</div>
                    </div>
                    <div class="banner-content">
                        <div class="products-container">
                            <div class="product-item"><div class="mug"></div></div>
                            <div class="product-item"><div class="cup-with-straw"></div></div>
                            <div class="product-item"><div class="water-bottle"></div></div>
                            <div class="product-item"><div class="tumbler"></div></div>
                            <div class="product-item"><div class="pink-mug"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation Arrows -->
    <button class="carousel-nav carousel-prev" id="prevBtn">‹</button>
    <button class="carousel-nav carousel-next" id="nextBtn">›</button>
    
    <div class="cta-section" style="margin-bottom: 2.5rem; margin-top: -4.5rem;">
        <a href="menu.php" class="cta-button">Buy Now</a>
    </div>
    
    <div class="pagination-dots">
        <div class="dot active" data-slide="0"></div>
        <div class="dot" data-slide="1"></div>
        <div class="dot" data-slide="2"></div>
    </div>
</section>

<!-- Quote Section -->
<section class="quote-section">
    <p class="quote-text">
        "Becoming the city reference point for the highest quality food, inspiring and nourishing the human spirit, a person and one neighborhood at a time."
    </p>
    <button class="find-store-button">Trova il tuo store preferito</button>
</section>

<!-- MENU/FEATURED MODERN CARDS SECTION -->
<section class="menu-modern-section">
  <div class="floating-element"></div>
  <div class="floating-element"></div>
  <div class="floating-element"></div>
  
  <div class="menu-container">
    <div class="menu-header">
      <h1 class="menu-title">IL NOSTRO MENU</h1>
      <p class="menu-subtitle">Scopri le nostre specialità</p>
    </div>

    <div class="menu-grid">
      <div class="menu-card">
        <img src="assets/images/bevande.jpg" alt="Bevande" class="card-image">
        <div class="card-content">
          <div class="card-icon">☕</div>
          <h3 class="card-title">Bevande</h3>
          <p class="card-description">Caffè, tè, drink e molto altro per ogni momento della giornata.</p>
        </div>
        <a href="menu.php?category=6" class="card-overlay">
          <span>Esplora le bevande →</span>
        </a>
      </div>

      <div class="menu-card">
        <img src="assets/images/food.jpg" alt="Food" class="card-image">
        <div class="card-content">
          <div class="card-icon">🍽️</div>
          <h3 class="card-title">Food</h3>
          <p class="card-description">Piatti caldi, panini, insalate e delizie per tutti i gusti.</p>
        </div>
        <a href="menu.php" class="card-overlay">
          <span>Scopri i piatti →</span>
        </a>
      </div>

      <div class="menu-card">
        <img src="assets/images/dessert.jpg" alt="Dessert" class="card-image">
        <div class="card-content">
          <div class="card-icon">🍰</div>
          <h3 class="card-title">Dessert</h3>
          <p class="card-description">Dolci, torte e dessert per concludere in dolcezza.</p>
        </div>
        <a href="menu.php?category=5" class="card-overlay">
          <span>Vedi i dessert →</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS SECTION -->
<section class="how-it-works">
  <h2>How It Works</h2>
  <div class="how-steps">
    <div class="how-step">
      <div class="how-step-circle">1</div>
      <div class="how-step-title">Browse Menu</div>
      <div class="how-step-desc">Explore our wide range of delicious food options from various categories.</div>
    </div>
    <div class="how-step">
      <div class="how-step-circle">2</div>
      <div class="how-step-title">Add to Cart</div>
      <div class="how-step-desc">Select your favorite meals and add them to your shopping cart.</div>
    </div>
    <div class="how-step">
      <div class="how-step-circle">3</div>
      <div class="how-step-title">Delivery Address</div>
      <div class="how-step-desc">Provide your delivery address details for a smooth delivery experience.</div>
    </div>
    <div class="how-step">
      <div class="how-step-circle">4</div>
      <div class="how-step-title">Payment</div>
      <div class="how-step-desc">Choose your preferred payment method and complete your order.</div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS SECTION -->
<section class="testimonials">
  <h2>What Our Customers Say</h2>
  <div class="testimonials-row">
    <div class="testimonial">
      <div class="testimonial-name">Sarah L.</div>
      <div class="testimonial-text">“The food always arrives hot and fresh. CinCin is my go-to for quick, delicious meals!”</div>
      <div class="testimonial-stars">★★★★★</div>
    </div>
    <div class="testimonial">
      <div class="testimonial-name">Marco R.</div>
      <div class="testimonial-text">“Un servizio impeccabile e piatti deliziosi. Consigliatissimo!”</div>
      <div class="testimonial-stars">★★★★★</div>
    </div>
    <div class="testimonial">
      <div class="testimonial-name">Giulia P.</div>
      <div class="testimonial-text">“Ampia scelta e consegna rapida. CinCin non delude mai.”</div>
      <div class="testimonial-stars">★★★★★</div>
    </div>
  </div>
</section>

<style>
.menu-modern-section {
  width: 100vw;
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  background: linear-gradient(120deg, #e8f5e9 60%, #b2dfdb 100%);
  padding: 90px 0 80px 0;
  overflow: hidden;
}
.menu-modern-content {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}
.menu-modern-title, .menu-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 900;
    color: #222;
    letter-spacing: 0.1em;
    margin-bottom: 1rem;
    position: relative;
    text-transform: uppercase;
    background: none;
    -webkit-background-clip: initial;
    -webkit-text-fill-color: initial;
    background-clip: initial;
}
.menu-modern-underline {
  width: 90px;
  height: 6px;
  background: linear-gradient(90deg, #FFD600 0%, #006241 100%);
  border-radius: 3px;
  margin: 0 auto 18px auto;
}
.menu-modern-subtitle, .menu-subtitle {
    font-size: 1.3rem;
    color: #555;
    margin-bottom: 48px;
    font-weight: 700;
    letter-spacing: 1px;
    background: none;
}
.menu-modern-cards {
  display: flex;
  justify-content: center;
  gap: 48px;
  flex-wrap: wrap;
}
.menu-modern-card {
  background: #fff;
  border-radius: 28px;
  box-shadow: 0 8px 32px rgba(0,98,65,0.13), 0 2px 8px rgba(0,0,0,0.07);
  width: 300px;
  min-height: 370px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  transition: transform 0.22s cubic-bezier(.4,2,.6,1), box-shadow 0.22s;
  position: relative;
  padding-top: 60px;
  margin-top: 40px;
}
.menu-modern-card:hover {
  transform: translateY(-10px) scale(1.04);
  box-shadow: 0 16px 48px rgba(0,98,65,0.18), 0 4px 16px rgba(0,0,0,0.10);
}
.menu-modern-img-wrap {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 120px;
  height: 120px;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 4px 18px rgba(0,98,65,0.10);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 5px solid #e8f5e9;
}
.menu-modern-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 50%;
}
.menu-modern-card-body {
  margin-top: 70px;
  padding: 0 24px 32px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.menu-modern-icon {
  font-size: 2.1rem;
  color: #FFD600;
  margin-bottom: 12px;
}
.menu-modern-card-title {
  font-size: 1.3rem;
  font-weight: 900;
  color: #006241;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 10px;
}
.menu-modern-card-desc {
  font-size: 1.05rem;
  color: #174832;
  font-weight: 500;
  margin-bottom: 0;
}
@media (max-width: 900px) {
  .menu-modern-cards { gap: 24px; }
  .menu-modern-card { width: 90vw; min-width: 0; }
}
@media (max-width: 600px) {
  .menu-modern-title { font-size: 1.5rem; }
  .menu-modern-cards { flex-direction: column; gap: 18px; }
  .menu-modern-card { width: 98vw; min-width: 0; }
  .menu-modern-img-wrap { width: 80px; height: 80px; top: -40px; }
  .menu-modern-card-body { margin-top: 50px; }
}

/* New Menu Styles */
.menu-container {
  max-width: 1200px;
  margin: 0 auto;
}

.menu-header {
  text-align: center;
  margin-bottom: 4rem;
}

.menu-title::after {
  content: '';
  width: 80px;
  height: 3px;
  background: linear-gradient(90deg, #f4c430, #e6b800);
  position: absolute;
  bottom: -0.5rem;
  left: 50%;
  transform: translateX(-50%);
  border-radius: 2px;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-top: 3rem;
}

.menu-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
}

.menu-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.card-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.menu-card:hover .card-image {
  transform: scale(1.05);
}

.card-content {
  padding: 2rem;
  text-align: center;
}

.card-icon {
  width: 50px;
  height: 50px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #2c5530, #4a8c5a);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.card-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c5530;
  margin-bottom: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.card-description {
  color: #6c757d;
  line-height: 1.6;
  font-size: 1rem;
}

.card-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, rgba(44, 85, 48, 0.9), rgba(74, 140, 90, 0.9));
  opacity: 0;
  transition: opacity 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
}

.menu-card:hover .card-overlay {
  opacity: 1;
}

.floating-element {
  position: absolute;
  width: 20px;
  height: 20px;
  background: rgba(244, 196, 48, 0.3);
  border-radius: 50%;
  animation: float 6s ease-in-out infinite;
}

.menu-modern-section .floating-element:nth-child(1) {
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.menu-modern-section .floating-element:nth-child(2) {
  top: 20%;
  right: 15%;
  animation-delay: 2s;
}

.menu-modern-section .floating-element:nth-child(3) {
  bottom: 30%;
  left: 20%;
  animation-delay: 4s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

@media (max-width: 768px) {
  .menu-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .card-content {
    padding: 1.5rem;
  }
}

.wow-slide {
    background: linear-gradient(120deg, #ffecd2 0%, #fcb69f 100%) !important;
    position: relative;
    overflow: hidden;
}
.wow-bg-text {
    color: #fff;
    opacity: 0.18;
    font-size: clamp(3rem, 10vw, 8rem);
    font-weight: 900;
    text-align: center;
    z-index: 1;
}
.wow-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100% !important;
    height: 100% !important;
    min-height: 70vh;
    max-width: none !important;
    padding: 0 4vw;
    box-sizing: border-box;
    z-index: 2;
    position: relative;
}
.wow-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1.2rem;
}
.wow-emoji {
    font-size: 3rem;
    animation: bounce 1.2s infinite alternate;
}
@keyframes bounce {
    0% { transform: translateY(0); }
    100% { transform: translateY(-18px); }
}
.wow-title {
    font-size: 2.5rem;
    font-weight: 900;
    color: #ff6f61;
    margin: 0;
}
.wow-desc {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 0.5rem;
}
.wow-highlight {
    color: #ff6f61;
    font-weight: bold;
    background: #fff3e0;
    padding: 0 0.3em;
    border-radius: 6px;
}
.wow-btn {
    background: #ff6f61;
    color: #fff;
    border: none;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 25px;
    padding: 0.9rem 2.2rem;
    margin-top: 0.7rem;
    box-shadow: 0 4px 18px rgba(255,111,97,0.13);
    transition: background 0.2s;
}
.wow-btn:hover {
    background: #ff3b1f;
}
.wow-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.wow-giftbox {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto;
    animation: shake 1.5s infinite alternate;
}
@keyframes shake {
    0% { transform: rotate(-5deg); }
    100% { transform: rotate(5deg); }
}
.wow-lid {
    position: absolute;
    top: 0;
    left: 10px;
    width: 100px;
    height: 30px;
    background: #ff6f61;
    border-radius: 10px 10px 0 0;
    z-index: 2;
}
.wow-box {
    position: absolute;
    top: 30px;
    left: 0;
    width: 120px;
    height: 80px;
    background: #fff3e0;
    border-radius: 0 0 18px 18px;
    box-shadow: 0 8px 24px rgba(255,111,97,0.13);
    z-index: 1;
}
.wow-ribbon {
    position: absolute;
    top: 30px;
    left: 55px;
    width: 10px;
    height: 80px;
    background: #ff6f61;
    border-radius: 5px;
    z-index: 3;
}
@media (max-width: 900px) {
    .wow-content { flex-direction: column; gap: 2rem; }
    .wow-left, .wow-right { align-items: center; justify-content: center; }
    .wow-title { font-size: 2rem; }
}

/* Ensure all slides fill the banner */
.carousel-slide, .slide-content-1, .slide-content-2, .slide-content-3, .wow-slide {
    width: 100% !important;
    height: 100% !important;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 0;
}
.banner-content, .wow-content {
    width: 100% !important;
    height: 100% !important;
    min-height: 70vh;
    max-width: none !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 4vw;
    box-sizing: border-box;
}
.wow-slide {
    background: linear-gradient(120deg, #ffecd2 0%, #fcb69f 100%) !important;
}
@media (max-width: 900px) {
    .banner-content, .wow-content {
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0 2vw;
    }
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 400px;
    position: relative;
    animation: slideIn 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(120deg, #ffecd2 0%, #fcb69f 100%);
    padding: 20px;
    border-radius: 15px 15px 0 0;
    text-align: center;
}

.modal-header h2 {
    margin: 0;
    color: #ff6f61;
    font-size: 1.5rem;
}

.modal-body {
    padding: 30px 20px;
    text-align: center;
}

.coupon-code {
    background: #fff3e0;
    padding: 15px;
    border-radius: 8px;
    font-size: 1.5rem;
    font-weight: bold;
    color: #ff6f61;
    margin: 20px 0;
    letter-spacing: 2px;
    border: 2px dashed #ff6f61;
}

.close-modal {
    position: absolute;
    right: 15px;
    top: 15px;
    color: #ff6f61;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close-modal:hover {
    color: #ff3b1f;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<?php require_once 'includes/footer.php'; ?>

<script>
// Carousel functionality
let currentSlide = 0;
const slides = document.getElementById('carouselSlides');
const dots = document.querySelectorAll('.dot');
const totalSlides = 3;
function goToSlide(slideIndex) {
    currentSlide = slideIndex;
    slides.style.transform = `translateX(-${slideIndex * 33.33}%)`;
    dots.forEach(dot => dot.classList.remove('active'));
    dots[slideIndex].classList.add('active');
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    goToSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    goToSlide(currentSlide);
}

// Event listeners
document.getElementById('nextBtn').addEventListener('click', nextSlide);
document.getElementById('prevBtn').addEventListener('click', prevSlide);

// Dot navigation
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        goToSlide(index);
    });
});

// Auto-slide every 5 seconds
let autoSlideInterval = setInterval(nextSlide, 5000);

// Pause auto-slide on hover
const banner = document.querySelector('.banner');
banner.addEventListener('mouseenter', () => {
    clearInterval(autoSlideInterval);
});

banner.addEventListener('mouseleave', () => {
    autoSlideInterval = setInterval(nextSlide, 5000);
});

// Touch/swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

banner.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
});

banner.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
    }
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        prevSlide();
    } else if (e.key === 'ArrowRight') {
        nextSlide();
    }
});

// Find store button functionality
document.querySelector('.find-store-button').addEventListener('click', () => {
    const locationInput = document.getElementById('nav-location');
    if (locationInput) {
        locationInput.focus();
        // Smoothly scroll to the top if needed
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
});

// Update the reveal code button click handler
document.querySelector('.wow-btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('couponModal').style.display = 'block';
});

// Close modal when clicking the X
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('couponModal').style.display = 'none';
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target == document.getElementById('couponModal')) {
        document.getElementById('couponModal').style.display = 'none';
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('couponModal').style.display === 'block') {
        document.getElementById('couponModal').style.display = 'none';
    }
});
</script> 