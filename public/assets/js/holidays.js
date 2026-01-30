/**
 * Holiday Effects JavaScript
 * Generates animated particles for various holidays
 */

(function() {
    'use strict';

    // Get the active holiday from body class
    const body = document.body;
    const holidayClasses = {
        'holiday-christmas': 'christmas',
        'holiday-valentines': 'valentines',
        'holiday-stpatricks': 'stpatricks',
        'holiday-halloween': 'halloween',
        'holiday-easter': 'easter',
        'holiday-independence': 'independence',
        'holiday-newyear': 'newyear'
    };

    let activeHoliday = null;
    for (const [className, holiday] of Object.entries(holidayClasses)) {
        if (body.classList.contains(className)) {
            activeHoliday = holiday;
            break;
        }
    }

    if (!activeHoliday) return;

    // Check for reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    // Configuration for each holiday
    const holidayConfig = {
        christmas: {
            containerClass: 'snowflakes',
            particleClass: 'snowflake',
            symbols: ['❄', '❅', '❆', '✻', '✼', '❉'],
            count: 18,
            minDuration: 10,
            maxDuration: 18,
            minSize: 0.8,
            maxSize: 1.6
        },
        valentines: {
            containerClass: 'floating-hearts',
            particleClass: 'floating-heart',
            symbols: ['💕', '💗', '💖', '💝', '♥', '❤️'],
            count: 20,
            minDuration: 10,
            maxDuration: 18,
            minSize: 0.8,
            maxSize: 1.5
        },
        stpatricks: {
            containerClass: 'floating-clovers',
            particleClass: 'floating-clover',
            symbols: ['☘️', '🍀', '🌿'],
            count: 25,
            minDuration: 10,
            maxDuration: 16,
            minSize: 0.8,
            maxSize: 1.4
        },
        halloween: {
            containerClass: 'floating-spooky',
            particleClass: 'floating-bat',
            symbols: ['🦇', '🕷️', '👻', '🕸️'],
            count: 20,
            minDuration: 8,
            maxDuration: 14,
            minSize: 0.9,
            maxSize: 1.5
        },
        easter: {
            containerClass: 'floating-eggs',
            particleClass: 'floating-egg',
            symbols: ['🥚', '🐣', '🌷', '🐰', '🌸'],
            count: 20,
            minDuration: 10,
            maxDuration: 16,
            minSize: 0.8,
            maxSize: 1.4
        },
        independence: {
            containerClass: 'floating-stars',
            particleClass: 'floating-star',
            symbols: ['⭐', '✨', '🌟', '💫', '🎆'],
            count: 25,
            minDuration: 8,
            maxDuration: 14,
            minSize: 0.8,
            maxSize: 1.5
        },
        newyear: {
            containerClass: 'floating-confetti',
            particleClass: 'confetti-piece',
            symbols: null, // Uses colored squares
            colors: ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500', '#ff69b4'],
            count: 40,
            minDuration: 6,
            maxDuration: 12,
            minSize: 0.6,
            maxSize: 1.2
        }
    };

    const config = holidayConfig[activeHoliday];
    if (!config) return;

    // Create container
    const container = document.createElement('div');
    container.className = config.containerClass;
    document.body.appendChild(container);

    // Create particles
    function createParticle() {
        const particle = document.createElement('div');
        particle.className = config.particleClass;

        // Random position
        particle.style.left = Math.random() * 100 + '%';

        // Random animation duration
        const duration = config.minDuration + Math.random() * (config.maxDuration - config.minDuration);
        particle.style.animationDuration = duration + 's';

        // Random delay
        particle.style.animationDelay = Math.random() * duration + 's';

        // Random size
        const size = config.minSize + Math.random() * (config.maxSize - config.minSize);
        particle.style.fontSize = size + 'rem';

        // Set content
        if (config.symbols) {
            particle.textContent = config.symbols[Math.floor(Math.random() * config.symbols.length)];
        } else if (config.colors) {
            // For confetti, use colored squares
            particle.style.backgroundColor = config.colors[Math.floor(Math.random() * config.colors.length)];
            particle.style.width = (8 + Math.random() * 8) + 'px';
            particle.style.height = (8 + Math.random() * 8) + 'px';
            particle.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        }

        return particle;
    }

    // Add initial particles
    for (let i = 0; i < config.count; i++) {
        container.appendChild(createParticle());
    }

    // Recycle particles when they complete animation
    // This keeps the effect going without creating too many DOM elements
    container.addEventListener('animationend', function(e) {
        if (e.target.classList.contains(config.particleClass)) {
            // Reset the particle
            e.target.style.left = Math.random() * 100 + '%';
            const duration = config.minDuration + Math.random() * (config.maxDuration - config.minDuration);
            e.target.style.animationDuration = duration + 's';
            e.target.style.animationDelay = '0s';

            if (config.symbols) {
                e.target.textContent = config.symbols[Math.floor(Math.random() * config.symbols.length)];
            } else if (config.colors) {
                e.target.style.backgroundColor = config.colors[Math.floor(Math.random() * config.colors.length)];
            }

            // Restart animation
            e.target.style.animation = 'none';
            e.target.offsetHeight; // Trigger reflow
            e.target.style.animation = '';
        }
    });

    // Performance: pause animations when tab is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            container.style.animationPlayState = 'paused';
            container.querySelectorAll('.' + config.particleClass).forEach(p => {
                p.style.animationPlayState = 'paused';
            });
        } else {
            container.style.animationPlayState = 'running';
            container.querySelectorAll('.' + config.particleClass).forEach(p => {
                p.style.animationPlayState = 'running';
            });
        }
    });

})();
