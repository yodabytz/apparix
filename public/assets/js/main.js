/**
 * Apparix - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize functionality
    console.log('Lily\'s Pad Studio loaded');

    // Initialize image zoom on product pages
    initImageZoom();

    // Initialize video hover play on product cards
    initVideoHover();
});

/**
 * Initialize video hover play functionality for product cards
 */
function initVideoHover() {
    const productCards = document.querySelectorAll('.product-card[data-has-video="true"]');

    productCards.forEach(card => {
        const video = card.querySelector('video.product-video');
        if (!video) return;

        card.addEventListener('mouseenter', function() {
            video.play().catch(() => {
                // Autoplay may be blocked, ignore
            });
        });

        card.addEventListener('mouseleave', function() {
            video.pause();
            video.currentTime = 0;
        });
    });
}

/**
 * Initialize image zoom functionality
 */
function initImageZoom() {
    const mainImage = document.querySelector('.main-image');
    if (!mainImage) return;

    const img = mainImage.querySelector('img');
    if (!img) return;

    const isMobile = window.innerWidth <= 768 || 'ontouchstart' in window;

    // Add zoom hint if not exists
    if (!mainImage.querySelector('.zoom-hint')) {
        const hint = document.createElement('span');
        hint.className = 'zoom-hint';
        // Create SVG element safely
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '16');
        svg.setAttribute('height', '16');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '2');
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', '11');
        circle.setAttribute('cy', '11');
        circle.setAttribute('r', '8');
        const path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path1.setAttribute('d', 'M21 21l-4.35-4.35');
        const path2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path2.setAttribute('d', 'M11 8v6');
        const path3 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path3.setAttribute('d', 'M8 11h6');
        svg.appendChild(circle);
        svg.appendChild(path1);
        svg.appendChild(path2);
        svg.appendChild(path3);
        hint.appendChild(svg);
        hint.appendChild(document.createTextNode(' Tap to zoom'));
        mainImage.appendChild(hint);
    }

    // Create mobile lightbox if it doesn't exist
    if (!document.getElementById('imageLightbox')) {
        createImageLightbox();
    }

    // Update zoom hint visibility based on whether video or image is shown
    updateZoomHintVisibility();

    // Click/tap handler
    mainImage.addEventListener('click', function(e) {
        // Don't zoom if clicking on navigation arrows or any button
        if (e.target.closest('.gallery-nav') ||
            e.target.closest('button') ||
            e.target.tagName === 'BUTTON') {
            e.stopPropagation();
            return;
        }
        // Only zoom if clicking directly on the image (not video)
        if (e.target.tagName !== 'IMG') {
            return;
        }
        // Don't zoom if video is currently displayed
        const video = mainImage.querySelector('video');
        if (video && video.style.display !== 'none') {
            return;
        }

        // On mobile, open lightbox. On desktop, use zoom.
        if (isMobile) {
            openImageLightbox(img.src);
        } else {
            this.classList.toggle('zoomed');
            if (this.classList.contains('zoomed')) {
                updateZoomPosition(e, mainImage, img);
            }
        }
    });

    // Mouse move for pan when zoomed (desktop only)
    if (!isMobile) {
        mainImage.addEventListener('mousemove', function(e) {
            if (this.classList.contains('zoomed')) {
                updateZoomPosition(e, mainImage, img);
            }
        });

        // Reset zoom when mouse leaves (desktop)
        mainImage.addEventListener('mouseleave', function() {
            if (this.classList.contains('zoomed')) {
                this.classList.remove('zoomed');
                img.style.transformOrigin = 'center center';
            }
        });
    }
}

/**
 * Create image lightbox for mobile with pinch-to-zoom
 */
function createImageLightbox() {
    const lightbox = document.createElement('div');
    lightbox.id = 'imageLightbox';
    lightbox.className = 'image-lightbox';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'lightbox-close';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.textContent = '\u00D7';

    const content = document.createElement('div');
    content.className = 'lightbox-content';

    const img = document.createElement('img');
    img.src = '';
    img.alt = 'Zoomed image';
    img.style.transformOrigin = 'center center';

    const hint = document.createElement('p');
    hint.className = 'lightbox-hint';
    hint.textContent = 'Pinch to zoom \u2022 Tap X to close';

    content.appendChild(img);
    lightbox.appendChild(closeBtn);
    lightbox.appendChild(content);
    lightbox.appendChild(hint);
    document.body.appendChild(lightbox);

    // Pinch-to-zoom variables
    let scale = 1;
    let lastScale = 1;
    let posX = 0;
    let posY = 0;
    let lastPosX = 0;
    let lastPosY = 0;
    let startDistance = 0;
    let startX = 0;
    let startY = 0;

    // Touch handlers for pinch-to-zoom on lightbox
    lightbox.addEventListener('touchstart', function(e) {
        hint.textContent = 'Touches: ' + e.touches.length;
        if (e.touches.length === 2) {
            e.preventDefault();
            e.stopPropagation();
            startDistance = getDistance(e.touches[0], e.touches[1]);
            lastScale = scale;
        }
    }, { passive: false, capture: true });

    lightbox.addEventListener('touchmove', function(e) {
        if (e.touches.length === 2) {
            e.preventDefault();
            e.stopPropagation();
            const currentDistance = getDistance(e.touches[0], e.touches[1]);
            scale = Math.min(Math.max(lastScale * (currentDistance / startDistance), 1), 4);
            img.style.setProperty('transform', 'scale(' + scale + ')', 'important');
            hint.textContent = 'Scale: ' + scale.toFixed(2);
        }
    }, { passive: false, capture: true });

    lightbox.addEventListener('touchend', function(e) {
        if (e.touches.length < 2 && scale !== 1) {
            scale = 1;
            lastScale = 1;
            img.style.setProperty('transform', 'scale(1)', 'important');
            hint.textContent = 'Pinch to zoom';
        }
    }, { passive: false });

    function resetLightboxZoom() {
        scale = 1;
        lastScale = 1;
        posX = 0;
        posY = 0;
        img.style.transform = 'scale(1)';
    }

    function getDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    // Close on X button
    closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        resetLightboxZoom();
        closeImageLightbox();
    });

    // Close on overlay background (not on image)
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox || e.target === content) {
            if (scale <= 1) {
                closeImageLightbox();
            }
        }
    });

    // Close on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            resetLightboxZoom();
            closeImageLightbox();
        }
    });

}

/**
 * Open image lightbox (for mobile)
 */
function openImageLightbox(src) {
    const lightbox = document.getElementById('imageLightbox');
    if (!lightbox) return;

    const img = lightbox.querySelector('img');
    img.src = src;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Close image lightbox
 */
function closeImageLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    if (!lightbox) return;

    lightbox.classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * Reset image zoom state
 */
function resetImageZoom() {
    const mainImage = document.querySelector('.main-image');
    if (mainImage && mainImage.classList.contains('zoomed')) {
        mainImage.classList.remove('zoomed');
        const img = mainImage.querySelector('img');
        if (img) {
            img.style.transformOrigin = 'center center';
        }
    }
}

/**
 * Update zoom hint visibility - hide when video is displayed
 */
function updateZoomHintVisibility() {
    const mainImage = document.querySelector('.main-image');
    if (!mainImage) return;

    const hint = mainImage.querySelector('.zoom-hint');
    if (!hint) return;

    const video = mainImage.querySelector('video');
    const img = mainImage.querySelector('img');

    // Hide zoom hint if video is visible, show if image is visible
    if (video && video.style.display !== 'none') {
        hint.style.display = 'none';
        // Also remove zoomed class if switching to video
        mainImage.classList.remove('zoomed');
    } else if (img && img.style.display !== 'none') {
        hint.style.display = '';
    }
}

/**
 * Update zoom position based on cursor/touch position
 */
function updateZoomPosition(e, container, img) {
    const rect = container.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    img.style.transformOrigin = `${x}% ${y}%`;
}

/**
 * Update cart badge count
 */
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
            // Trigger animation
            badge.style.animation = 'none';
            badge.offsetHeight; // Force reflow
            badge.style.animation = 'cartBadgePop 0.3s ease';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Add to cart with validation
 */
function addToCart(productId, quantity = 1) {
    const form = new FormData();
    form.append('product_id', productId);
    form.append('quantity', quantity);

    fetch('/cart/add', {
        method: 'POST',
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart badge
            if (data.cartCount !== undefined) {
                updateCartBadge(data.cartCount);
            }
            alert('Product added to cart!');
        } else {
            alert(data.error || 'Error adding product to cart');
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Format price for display
 */
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}
