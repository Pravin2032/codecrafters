<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>eLearning</title>
    <link rel="stylesheet" href="styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css"
    />
    <script
      src="https://code.jquery.com/jquery-3.6.3.min.js"
      integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU="
      crossorigin="anonymous"
    ></script>
    <style>
      /* Adding styles for the login/signup buttons */
      .auth-buttons {
        display: flex;
        align-items: center;
        margin-right: 20px;
      }
      
      .auth-buttons a {
        padding: 8px 15px;
        margin-left: 10px;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
      }
      
      .login-btn {
        color: #6c63ff;
        border: 1px solid #6c63ff;
      }
      
      .login-btn:hover {
        background-color: #f5f5f7;
      }
      
      .signup-btn {
        background-color: #6c63ff;
        color: white;
        border: 1px solid #6c63ff;
      }
      
      .signup-btn:hover {
        background-color: #5a52e0;
      }

      /* Adjusting navigation for mobile responsiveness */
      @media screen and (max-width: 769px) {
        .auth-buttons {
          margin-right: 60px;
        }
      }

      @media screen and (max-width: 475px) {
        .auth-buttons {
          display: none;
        }
        
        /* Adding login/signup to mobile menu */
        nav .navigation ul li.auth-link {
          margin-top: 15px;
        }
        
        nav .navigation ul li.auth-link a {
          color: #6c63ff;
          font-weight: 600;
        }
      }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav>
      <img src="assets/eLearning_logo.png" alt="Website logo" />
      <div class="navigation">
        <ul>
          <i id="menu-close" class="fa-sharp fa-regular fa-circle-xmark"></i>
          <li><a href="#">Home</a></li>
          <li><a href="#features">About us</a></li>
          <li><a href="#course">Courses</a></li>
          <li><a href="#community">Community</a></li>
          <li><a href="#contact">Contact Us</a></li>
          <!-- Adding login/signup for mobile menu -->
          <li class="auth-link"><a href="admin login.php">Login</a></li>
          <li class="auth-link"><a href="register.php">Sign Up</a></li>
        </ul>
  
        <img
          id="menu-btn"
          src="https://cdn-icons-png.flaticon.com/512/56/56763.png"
          alt="menu button"
        />
      </div>
    </nav>

    <!-- Rest of the HTML remains unchanged -->
    <!-- Home -->
    <section id="home">
      <h2>Enhance Your Future With eLearning</h2>
      <p>
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Aliquam
        consequatur tempore esse quasi! Tempora in expedita ipsum eaque alias
        nesciunt. Eveniet debitis adipisci optio neque enim, iusto ipsam atque
        facere?
      </p>
      <div class="btn">
        <a class="btn-one" href="#course">Learn more</a>
        <a class="btn-two" href="#course">Visit Courses</a>
      </div>
    </section>

    <!-- Features -->
    <section id="features">
      <h1>Awesome Features</h1>
      <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Est, nisi!</p>
      <div class="feature-base">
        <div class="feature-box">
          <i class="fa-solid fa-graduation-cap"></i>
          <h3>Scholorship Facility</h3>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Excepturi
            quam sunt nam neque aperiam tenetur!
          </p>
        </div>

        <div class="feature-box">
          <i class="fa-solid fa-laptop-code"></i>
          <h3>Online Courses</h3>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Excepturi
            quam sunt nam neque aperiam tenetur!
          </p>
        </div>

        <div class="feature-box">
          <i class="fa-solid fa-award"></i>
          <h3>Global Certificatiion</h3>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Excepturi
            quam sunt nam neque aperiam tenetur!
          </p>
        </div>
      </div>
    </section>

    <!-- Courses -->
    <section id="course">
      <h1>Our Popular Courses</h1>
      <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Est, nisi!</p>
      <div class="course-box">
        <div class="courses">
          <img src="assets/webdev.png" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>Web Development for beginners</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹1200</div>
        </div>

        <div class="courses">
          <img src="assets/app_development.jpg" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>App Development for beginners</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹1500</div>
        </div>

        <div class="courses">
          <img src="assets/Full_Stack.png" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>Full Stack Development</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹3000</div>
        </div>

        <div class="courses">
          <img src="assets/data_science.jpg" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>Data Science for beginners</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹2000</div>
        </div>

        <div class="courses">
          <img src="assets/SQL.png" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>SQL</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹2000</div>
        </div>

        <div class="courses">
          <img src="assets/al.png" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>AI and ML using Python</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹2500</div>
        </div>

        <div class="courses">
          <img src="assets/c.webp" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>C and C++</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹5000</div>
        </div>

        <div class="courses">
          <img src="assets/cloud.jpg" alt="Web Development" />
          <div class="details">
            <span>Updated 21/3/25</span>
            <h6>Cloud Computing - AWS</h6>
            <div class="star">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <span>(239)</span>
            </div>
          </div>
          <div class="cost" onclick="location.href='register.php'">₹3500</div>
        </div>
      </div>
    </section>

    <!-- community -->
    <section id="community">
      <h1>Community Experts</h1>
      <p class="community-para">
        Lorem ipsum dolor, sit amet consectetur adipisicing elit. Totam, enim.
      </p>
      <div class="expert-box">
        <div class="profile">
          <img
            src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
            alt="profile image"
          />
          <h6>Anonymous One</h6>
          <p>Data Science Expert</p>
          <div class="social">
            <i class="fa-brands fa-facebook-f"></i>
            <i class="fa-brands fa-instagram"></i>
            <i class="fa-brands fa-linkedin-in"></i>
          </div>
        </div>

        <div class="profile">
          <img
            src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
            alt="profile image"
          />
          <h6>Anonymous Two</h6>
          <p>App Development Expert</p>
          <div class="social">
            <i class="fa-brands fa-facebook-f"></i>
            <i class="fa-brands fa-instagram"></i>
            <i class="fa-brands fa-linkedin-in"></i>
          </div>
        </div>

        <div class="profile">
          <img
            src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
            alt="profile image"
          />
          <h6>Anonymous Three</h6>
          <p>Web Development Expert</p>
          <div class="social">
            <i class="fa-brands fa-facebook-f"></i>
            <i class="fa-brands fa-instagram"></i>
            <i class="fa-brands fa-linkedin-in"></i>
          </div>
        </div>

        <div class="profile">
          <img
            src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
            alt="profile image"
          />
          <h6>Anonymous Four</h6>
          <p>Cloud Computing Expert</p>
          <div class="social">
            <i class="fa-brands fa-facebook-f"></i>
            <i class="fa-brands fa-instagram"></i>
            <i class="fa-brands fa-linkedin-in"></i>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact us -->
    <section id="contact">
      <h1>Contact Us</h1>
      <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Est, nisi!</p>
      <div class="contact-box">
        <div class="contact-image">
          <img
            src="https://img.freepik.com/free-vector/flat-design-illustration-customer-support_23-2148887720.jpg?w=996&t=st=1678294756~exp=1678295356~hmac=fdf1cb1d0bedd5c8095ebce03993e1c685a8b8d25c8db48bd278c26f0a1f46fd"
            alt="contact us"
          />
        </div>

        <div class="form">
          <h3>Let us help</h3>
          <span>Name</span>
          <input type="text" placeholder="Enter your name" />
          <span>Email</span>
          <input type="text" placeholder="Enter your Email" />
          <span>Message</span>
          <input type="text" placeholder="Enter your message" />
          <div class="send-btn">
            <a href="">Send Message</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer>
      <div class="footer-col">
        <h3>eLearning</h3>
        <li><a href="#">Home</a></li>
        <li><a href="#features">About us</a></li>
        <li><a href="#course">Courses</a></li>
        <li><a href="#community">Community</a></li>
        <li><a href="#contact">Contact Us</a></li>
      </div>

      <div class="footer-col">
        <h3>Resources</h3>
        <li><a href="#course">Web Development</a></li>
        <li><a href="#course">App Development</a></li>
        <li><a href="#course">Data Science</a></li>
        <li><a href="#course">Clound Computing</a></li>
      </div>

      <div class="footer-col">
        <h3>Follow us</h3>
        <li>Instagram</li>
        <li>Facebook</li>
        <li>Linkedin</li>
        <li>Github</li>
      </div>

      <div class="footer-col">
        
        <p>        </p>
        <div class="subscribe">
         
        </div>
      </div>

      <div class="copyright">
        <p>
          Copyright ©2025 All rights reserved
        </p>
        <div class="social">
          <i class="fa-brands fa-facebook-f"></i>
          <i class="fa-brands fa-instagram"></i>
          <i class="fa-brands fa-linkedin-in"></i>
        </div>
      </div>
    </footer>

    <script>
     // Wait for document to be fully loaded
$(document).ready(function() {
  // Existing functionality for mobile menu toggle
  $("#menu-btn").click(function() {
    $("nav .navigation ul").addClass("active");
  });
  
  $("#menu-close").click(function() {
    $("nav .navigation ul").removeClass("active");
  });
  
  // Close mobile menu when clicking on a menu item
  $("nav .navigation ul li a").click(function() {
    if($("nav .navigation ul").hasClass("active")) {
      $("nav .navigation ul").removeClass("active");
    }
  });
  
  // Close mobile menu when clicking outside
  $(document).click(function(event) {
    const navigation = $("nav .navigation ul");
    const menuBtn = $("#menu-btn");
    
    if(navigation.hasClass("active") && 
       !navigation.is(event.target) && 
       navigation.has(event.target).length === 0 &&
       !menuBtn.is(event.target)) {
      navigation.removeClass("active");
    }
  });
  
  // Smooth scrolling for anchor links
  $('a[href^="#"]').on('click', function(event) {
    event.preventDefault();
    
    $('html, body').animate({
      scrollTop: $($.attr(this, 'href')).offset().top - 80
    }, 800);
  });
  
  // Responsive behavior for window resize
  $(window).resize(function() {
    if($(window).width() > 769) {
      $("nav .navigation ul").removeClass("active");
    }
  });
  
  // Highlight active menu item based on scroll position
  $(window).scroll(function() {
    const scrollDistance = $(window).scrollTop();
    
    // Check each section for visibility
    $('section').each(function() {
      const sectionTop = $(this).offset().top - 100;
      const sectionHeight = $(this).height();
      const sectionId = $(this).attr('id');
      
      if(scrollDistance >= sectionTop && scrollDistance < sectionTop + sectionHeight) {
        $('nav .navigation ul li a').removeClass('active');
        $('nav .navigation ul li a[href="#' + sectionId + '"]').addClass('active');
      }
    });
  });
  
  // Form validation for contact form
  $('.form input').on('blur', function() {
    if($(this).val() === '') {
      $(this).addClass('error');
    } else {
      $(this).removeClass('error');
    }
  });
  
  // Handle form submission
  $('.send-btn a').click(function(event) {
    event.preventDefault();
    
    let valid = true;
    $('.form input').each(function() {
      if($(this).val() === '') {
        $(this).addClass('error');
        valid = false;
      }
    });
    
    if(valid) {
      // Here you would normally submit the form data
      // For now, just show a success message
      $('.form').html('<h3>Thank you for your message!</h3><p>We will get back to you soon.</p>');
    }
  });
});
    </script>
  </body>
</html>