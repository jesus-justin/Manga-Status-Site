// JavaScript Animation Library for Manga Library
class ButtonAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupRippleEffects();
        this.setupHoverAnimations();
        this.setupClickAnimations();
        this.setupLoadingStates();
    }

    // Ripple effect for buttons
    setupRippleEffects() {
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.btn, .pagination-btn, .floating-btn, .auth-btn, .filter-pill, #randomMangaBtn, #darkModeToggle');
            if (button && !button.classList.contains('no-ripple')) {
                this.createRippleEffect(e, button);
            }
        });
    }

    createRippleEffect(event, button) {
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
        circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
        circle.classList.add('ripple');

        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
        
        // Remove ripple after animation completes
        setTimeout(() => {
            if (circle.parentNode === button) {
                circle.remove();
            }
        }, 600);
    }

    // Enhanced hover animations
    setupHoverAnimations() {
        const buttons = document.querySelectorAll('.btn, .pagination-btn, .floating-btn, .auth-btn, .filter-pill');
        
        buttons.forEach(button => {
            button.addEventListener('mouseenter', () => {
                this.animateHover(button, true);
            });
            
            button.addEventListener('mouseleave', () => {
                this.animateHover(button, false);
            });
        });
    }

    animateHover(button, isHovering) {
        const accentGlow = this.getAccentGlow();
        if (isHovering) {
            button.style.transform = 'scale(1.05) translateZ(10px)';
            button.style.boxShadow = `0 8px 25px ${accentGlow}`;
        } else {
            button.style.transform = '';
            button.style.boxShadow = '';
        }
    }

    getAccentGlow() {
        const root = document.documentElement;
        const value = getComputedStyle(root).getPropertyValue('--accent-glow').trim();
        return value || 'rgba(215, 38, 61, 0.35)';
    }

    // Click animations with bounce effect
    setupClickAnimations() {
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.btn, .pagination-btn, .floating-btn');
            if (button) {
                this.animateClick(button);
            }
        });
    }

    animateClick(button) {
        button.style.transform = 'scale(0.95)';
        setTimeout(() => {
            button.style.transform = '';
        }, 150);
    }

    // Loading state animations
    setupLoadingStates() {
        // Enhanced random manga button loading
        const randomBtn = document.getElementById('randomMangaBtn');
        if (randomBtn) {
            randomBtn.addEventListener('click', () => {
                this.showLoadingState(randomBtn, 'Finding...');
            });
        }
    }

    showLoadingState(button, text) {
        const originalText = button.innerHTML;
        button.innerHTML = `<span class="loading-spinner-enhanced"></span> ${text}`;
        button.disabled = true;

        // Re-enable after a delay (simulating async operation)
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1500);
    }

    // Success animation
    showSuccessAnimation(element) {
        element.classList.add('success-animation');
        setTimeout(() => {
            element.classList.remove('success-animation');
        }, 1000);
    }

    // Error animation
    showErrorAnimation(element) {
        element.classList.add('error-animation');
        setTimeout(() => {
            element.classList.remove('error-animation');
        }, 1000);
    }

    // Page transition animation
    animatePageTransition(url) {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            window.location.href = url;
        }, 300);
    }
}

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.buttonAnimations = new ButtonAnimations();
});

// Utility function for smooth scrolling
function smoothScrollTo(element, duration = 1000) {
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const run = easeInOutQuad(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    }

    function easeInOutQuad(t, b, c, d) {
        t /= d/2;
        if (t < 1) return c/2*t*t + b;
        t--;
        return -c/2 * (t*(t-2) - 1) + b;
    }

    requestAnimationFrame(animation);
}

// Enhanced random manga function with better animations
window.enhancedRandomManga = function () {
    const randomBtn = document.getElementById('randomMangaBtn');
    const resultDiv = document.getElementById('randomMangaResult');
    
    if (!randomBtn) return;

    // Show loading animation
    randomBtn.innerHTML = '<span class="loading-spinner-enhanced"></span> Finding...';
    randomBtn.disabled = true;

    setTimeout(() => {
        const mangaCards = Array.from(document.querySelectorAll('.manga-card'));
        if (!mangaCards.length) {
            randomBtn.textContent = '🎲 Random Manga';
            randomBtn.disabled = false;
            return;
        }

        // Remove previous highlights
        mangaCards.forEach(card => card.classList.remove('highlight-manga'));

        // Select random manga with animation
        const randomIndex = Math.floor(Math.random() * mangaCards.length);
        const randomCard = mangaCards[randomIndex];

        // Add highlight animation
        randomCard.classList.add('highlight-manga');
        
        // Smooth scroll to the card
        smoothScrollTo(randomCard);

        // Show result animation
        resultDiv.innerHTML = `<div class="result-bubble">🎯 Found: ${randomCard.querySelector('h3').textContent}</div>`;
        resultDiv.style.opacity = '0';
        resultDiv.style.transform = 'scale(0.8) translateY(20px)';
        
        setTimeout(() => {
            resultDiv.style.opacity = '1';
            resultDiv.style.transform = 'scale(1) translateY(0)';
            randomBtn.textContent = '🎲 Random Manga';
            randomBtn.disabled = false;
        }, 400);
    }, 1000);
};
