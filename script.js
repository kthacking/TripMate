/* Mobile Navigation & Interactivity */

document.addEventListener('DOMContentLoaded', () => {
    
    // Mobile Menu Toggle
    const navbar = document.querySelector('.navbar');
    const navContainer = document.querySelector('.nav-container');
    
    // Create Hamburger
    const hamburger = document.createElement('div');
    hamburger.className = 'hamburger';
    hamburger.innerHTML = '<i class="ri-menu-3-line"></i>';
    hamburger.style.cssText = `
        display: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--secondary-color);
    `;
    
    // Check screen size
    const checkMobile = () => {
        const navLinks = document.querySelector('.nav-links');
        if (window.innerWidth <= 768) {
            hamburger.style.display = 'block';
            if(navLinks) {
                navLinks.style.display = 'none'; 
                navLinks.classList.add('mobile-menu');
            }
        } else {
            hamburger.style.display = 'none';
            if(navLinks) {
                navLinks.style.display = 'flex';
                navLinks.classList.remove('mobile-menu');
                navLinks.style.position = 'static';
                navLinks.style.backgroundColor = 'transparent';
                navLinks.style.padding = '0';
                navLinks.style.boxShadow = 'none';
            }
        }
    };
    
    // Insert Hamburger
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.after(hamburger);
    }
    
    window.addEventListener('resize', checkMobile);
    checkMobile(); // Info init
    
    // Toggle Logic
    let isMenuOpen = false;
    hamburger.addEventListener('click', () => {
        isMenuOpen = !isMenuOpen;
        const navLinks = document.querySelector('.nav-links');
        if (isMenuOpen) {
            navLinks.style.display = 'flex';
            navLinks.style.flexDirection = 'column';
            navLinks.style.position = 'absolute';
            navLinks.style.top = '70px';
            navLinks.style.left = '0';
            navLinks.style.width = '100%';
            navLinks.style.background = 'white';
            navLinks.style.padding = '20px';
            navLinks.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            hamburger.innerHTML = '<i class="ri-close-line"></i>';
        } else {
            navLinks.style.display = 'none';
            hamburger.innerHTML = '<i class="ri-menu-3-line"></i>';
        }
    });

    // Scroll Effect for Navbar
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 4px 6px rgba(0,0,0,0.05)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.85)';
            navbar.style.boxShadow = 'none';
        }
    });
});
