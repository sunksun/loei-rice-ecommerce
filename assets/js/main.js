/**
 * Loei Rice E-commerce Main JavaScript
 * Main functionality for the rice e-commerce website
 */

// Global Variables
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let userMenuHideTimeout = null;

// Cart Management Functions
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

function addToCart(productId) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> เพิ่มแล้ว';
    button.disabled = true;

    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            quantity: 1
        });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();

    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 1500);

    showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว! 🛒', 'success');
}

function toggleFavorite(button) {
    if (button.classList.contains('active')) {
        button.classList.remove('active');
        button.innerHTML = '♡';
        showNotification('ลบออกจากรายการโปรดแล้ว', 'info');
    } else {
        button.classList.add('active');
        button.innerHTML = '♥';
        // ไม่แสดงข้อความแจ้งเตือนเมื่อเพิ่มลงรายการโปรด
    }
}

function toggleCart() {
    window.location.href = 'cart.php';
}

// Product Functions
function viewProduct(productId) {
    window.location.href = `product-detail.php?id=${productId}`;
}

// Notification Functions
function showNotifications() {
    showNotification('ยังไม่มีการแจ้งเตือนใหม่', 'info');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const colors = {
        success: '#27ae60',
        info: '#3498db',
        warning: '#f39c12',
        error: '#e74c3c'
    };

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        z-index: 9999;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// User Menu Functions
function showUserMenu() {
    if (userMenuHideTimeout) {
        clearTimeout(userMenuHideTimeout);
        userMenuHideTimeout = null;
    }
    const menu = document.getElementById('userMenuPopup');
    if (menu) menu.style.display = 'block';
}

function hideUserMenu() {
    if (userMenuHideTimeout) clearTimeout(userMenuHideTimeout);
    userMenuHideTimeout = setTimeout(function() {
        const menu = document.getElementById('userMenuPopup');
        if (menu) menu.style.display = 'none';
    }, 250);
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenuPopup');
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

// Search Functions
function performSearch() {
    const query = document.getElementById('searchInput').value.trim();
    if (query) {
        window.location.href = `products.php?search=${encodeURIComponent(query)}`;
    }
}

// Animation Functions
function observeElements() {
    const elements = document.querySelectorAll('.fade-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });

    elements.forEach(element => {
        observer.observe(element);
    });
}

// Carousel Functions
function initPromoCarousel() {
    const dots = document.querySelectorAll('.nav-dot');
    let currentSlide = 0;
    if (!dots.length) return; // ป้องกัน error ถ้าไม่มี nav-dot
    
    setInterval(() => {
        if (!dots.length) return; // ป้องกัน error ระหว่าง runtime
        if (dots[currentSlide]) dots[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % dots.length;
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }, 4000);
}

// Filter Functions
function initFilterPills() {
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active class from all pills
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            // Add active class to clicked pill
            if (this) this.classList.add('active');
            // Filter products based on the selected category
            showNotification(`เลือกหมวดหมู่: ${this.textContent}`, 'info');
        });
    });
}

// CSS Animation Setup
function setupAnimations() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Event Listeners Setup
function setupEventListeners() {
    // Search input event listener
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    // User menu hover events
    const userProfile = document.querySelector('.user-profile');
    const userMenu = document.getElementById('userMenuPopup');
    if (userProfile && userMenu) {
        userProfile.addEventListener('mouseenter', showUserMenu);
        userProfile.addEventListener('mouseleave', hideUserMenu);
        userMenu.addEventListener('mouseenter', showUserMenu);
        userMenu.addEventListener('mouseleave', hideUserMenu);
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.focus();
        }
    });
}

// Animation Delay Setup
function setupAnimationDelays() {
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
    });
}

// Main Initialization Function
function initializeApp() {
    // Update cart count on load
    updateCartCount();
    
    // Hide user menu initially
    hideUserMenu();
    
    // Setup animations
    setupAnimations();
    observeElements();
    setupAnimationDelays();
    
    // Initialize components
    initPromoCarousel();
    initFilterPills();
    
    // Setup event listeners
    setupEventListeners();
    
    console.log('🌾 Loei Rice E-commerce initialized successfully!');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeApp);

// Export functions for global access (if needed)
window.LoeiRice = {
    cart: {
        add: addToCart,
        update: updateCartCount,
        toggle: toggleCart
    },
    product: {
        view: viewProduct,
        toggleFavorite: toggleFavorite
    },
    ui: {
        showNotification: showNotification,
        showNotifications: showNotifications
    },
    user: {
        showMenu: showUserMenu,
        hideMenu: hideUserMenu,
        toggleMenu: toggleUserMenu
    },
    search: {
        perform: performSearch
    }
};