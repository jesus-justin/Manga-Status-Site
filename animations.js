// JavaScript Animation Library for Manga Library
class ButtonAnimations {
    constructor() {
        this.init();
    }

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.setupRippleEffects();
        this.setupHoverAnimations();
        this.setupClickAnimations();
        this.setupLoadingStates();
        this.setupCardKeyboardAccess();
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

    setupCardKeyboardAccess() {
        const cards = document.querySelectorAll('.manga-card');
        cards.forEach((card) => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'article');

            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    const editLink = card.querySelector('a[href*="edit.php"]');
                    if (editLink) {
                        window.location.href = editLink.getAttribute('href');
                    }
                }
            });
        });
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

    // Enhanced loading spinner overlay
    showFullScreenLoading(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        `;
        
        const spinner = document.createElement('div');
        spinner.style.cssText = `
            text-align: center;
            color: white;
        `;
        spinner.innerHTML = `
            <div class="loading-spinner-enhanced" style="margin: 0 auto 20px;"></div>
            <p style="font-size: 18px; margin: 0;">${message}</p>
        `;
        
        overlay.appendChild(spinner);
        document.body.appendChild(overlay);
        return overlay;
    }

    hideFullScreenLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => overlay.remove(), 300);
        }
    }

    // Toast notification
    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${this.getToastColor(type)};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideInUp 0.3s ease;
            font-weight: 500;
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    getToastColor(type) {
        const colors = {
            'success': '#4CAF50',
            'error': '#f44336',
            'warning': '#FF9800',
            'info': '#2196F3'
        };
        return colors[type] || colors['info'];
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
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        element.scrollIntoView({ block: 'center' });
        return;
    }

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
