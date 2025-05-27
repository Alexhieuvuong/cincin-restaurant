    </div><!-- end of main-content -->
    
    <footer class="footer">
      <div class="footer-content">
        <div class="footer-section">
          <div class="footer-logo">
            <i class="fas fa-utensils" style="color: #006241; font-size: 2.2rem;"></i>
            <h2>CinCin</h2>
          </div>
          <p>Enjoy quality cuisine delivered to your door. Diverse dishes from our top chefs.</p>
          <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        
        <div class="footer-section">
          <h3>ABOUT US</h3>
          <ul class="links">
            <li><a href="#">Brand Story</a></li>
            <li><a href="#">Management Team</a></li>
            <li><a href="#">Career Opportunities</a></li>
            <li><a href="#">Community Programs</a></li>
            <li><a href="#">Restaurant Partners</a></li>
            <li><a href="#">Membership Program</a></li>
          </ul>
        </div>
        
        <div class="footer-section">
          <h3>CUSTOMER SERVICE</h3>
          <ul class="links">
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">FAQs</a></li>
            <li><a href="#">Delivery Policy</a></li>
            <li><a href="#">Payment Methods</a></li>
            <li><a href="#">Complaint Procedure</a></li>
            <li><a href="#">Become a Delivery Partner</a></li>
          </ul>
        </div>
        
        <div class="footer-section">
          <h3>CONNECT WITH US</h3>
          <div class="newsletter">
            <p>Sign up to receive exciting promotions</p>
            <div class="newsletter-form">
              <input type="email" placeholder="Your email address">
              <button>Subscribe</button>
            </div>
          </div>
          <div class="app-download">
            <a href="#">
              <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" style="height:48px; width:auto; display:block;">
            </a>
            <a href="#">
              <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store" style="height:48px; width:auto; display:block;">
            </a>
          </div>
        </div>
      </div>
      
      <div class="divider"></div>
      
      <div class="contact-info" style="text-align: center; margin-bottom: 30px;">
        <p><strong>Support Hotline:</strong> 1900 1234 (7:00 - 22:00 daily)</p>
        <p><strong>Email:</strong> support@cincin.com | <strong>Address:</strong> Via Antonello Freri 12, 98124, ME, Messina</p>
      </div>
      
      <div class="footer-bottom">
        <div class="policy-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Use</a>
          <a href="#">Code of Conduct</a>
          <a href="#">Cookie Notice</a>
          <a href="#">Site Map</a>
        </div>
        <div class="copyright">
          <p>&copy; <?php echo date('Y'); ?> CinCin. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <script src="assets/js/script.js"></script>
    
    <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'SoDo Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
    
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    
    .main-content {
      flex: 1 0 auto;
    }
    
    .footer {
      background-color: #ffffff;
      padding: 40px 0 0;
      width: 100%;
      border-top: 1px solid #e5e5e5;
      flex-shrink: 0;
    }
    
    .footer-content {
      display: flex;
      flex-wrap: wrap;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .footer-section {
      flex: 1;
      min-width: 200px;
      margin-bottom: 40px;
      padding: 0 15px;
    }
    
    .footer-logo {
      display: flex;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .footer-logo h2 {
      color: #006241;
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-left: 10px;
    }
    
    .footer h3 {
      color: #000000;
      font-size: 1.2rem;
      margin-bottom: 24px;
      font-weight: 700;
      position: relative;
    }
    
    .footer p {
      color: #444444;
      line-height: 1.6;
      margin-bottom: 15px;
      font-size: 0.95rem;
    }
    
    .divider {
      width: 100%;
      height: 1px;
      background: #e5e5e5;
      margin: 0 auto 30px;
      max-width: 1140px;
    }
    
    .contact-info {
      margin-top: 20px;
    }
    
    .contact-info li {
      display: flex;
      align-items: flex-start;
      margin-bottom: 12px;
      color: #444444;
      font-size: 0.95rem;
      list-style: none;
    }
    
    .contact-info i {
      margin-right: 10px;
      color: #006241;
      font-size: 1rem;
      min-width: 20px;
      text-align: center;
    }
    
    .links li {
      list-style: none;
      margin-bottom: 16px;
    }
    
    .links a {
      color: #444444;
      text-decoration: none;
      transition: color 0.2s ease;
      font-size: 0.95rem;
      display: block;
    }
    
    .links a:hover {
      color: #006241;
      text-decoration: underline;
    }
    
    .social-links {
      display: flex;
      margin-top: 20px;
    }
    
    .social-links a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      background-color: #f7f7f7;
      margin-right: 12px;
      transition: all 0.3s ease;
      color: #444444;
      text-decoration: none;
    }
    
    .social-links a:hover {
      background-color: #e5e5e5;
      color: #006241;
    }
    
    .app-download {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-top: 20px;
      justify-content: flex-start;
      align-items: center;
    }
    
    .app-download a {
      display: inline-block;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border: 1.5px solid #e3e3e3;
      transition: box-shadow 0.2s, border 0.2s;
      background: #fff;
      padding: 0;
    }
    
    .app-download a:hover {
      box-shadow: 0 4px 16px rgba(0,98,65,0.13);
      border: 1.5px solid #006241;
      background: #f4f8f6;
    }
    
    .app-download img {
      display: block;
      height: 48px;
      width: auto;
      min-width: 140px;
      max-width: 180px;
      object-fit: contain;
      background: #fff;
      border-radius: 10px;
      padding: 0 8px;
    }
    
    .newsletter {
      margin-top: 20px;
    }
    
    .newsletter-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 15px;
    }
    
    .newsletter-form input {
      padding: 12px;
      border: 1px solid #d4d4d4;
      border-radius: 4px;
      font-size: 0.9rem;
      outline: none;
      width: 100%;
    }
    
    .newsletter-form input:focus {
      border-color: #006241;
    }
    
    .newsletter-form button {
      padding: 12px;
      background: #006241;
      color: white;
      border: none;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s ease;
      font-size: 0.9rem;
    }
    
    .newsletter-form button:hover {
      background: #004c32;
    }
    
    .footer-bottom {
      background-color: #f7f7f7;
      padding: 20px 0;
      text-align: center;
    }
    
    .policy-links {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 15px;
    }
    
    .policy-links a {
      color: #444444;
      text-decoration: none;
      font-size: 0.85rem;
      transition: color 0.2s ease;
    }
    
    .policy-links a:hover {
      color: #006241;
      text-decoration: underline;
    }
    
    .copyright {
      font-size: 0.85rem;
      color: #777777;
    }
    
    @media screen and (max-width: 768px) {
      .footer-section {
        min-width: 50%;
      }
    }
    
    @media screen and (max-width: 576px) {
      .footer-section {
        min-width: 100%;
      }
      
      .policy-links {
        flex-direction: column;
        gap: 10px;
      }
    }
    
    @media (max-width: 600px) {
      .app-download {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
      }
      .app-download img {
        height: 40px;
        min-width: 120px;
        max-width: 160px;
      }
    }
    </style>
</body>
</html> 