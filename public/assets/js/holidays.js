/**
 * Holiday Effects JavaScript
 * Lightweight particles confined to the hero section
 * Pure CSS animation — no JS recycling or reflows
 */

(function() {
    'use strict';

    if (document.body.getAttribute('data-holiday-effects') === 'disabled') return;

    var body = document.body;
    var holidays = {
        'holiday-christmas': 'christmas',
        'holiday-valentines': 'valentines',
        'holiday-stpatricks': 'stpatricks',
        'holiday-halloween': 'halloween',
        'holiday-easter': 'easter',
        'holiday-independence': 'independence',
        'holiday-newyear': 'newyear'
    };

    var active = null;
    for (var cls in holidays) {
        if (body.classList.contains(cls)) { active = holidays[cls]; break; }
    }
    if (!active) return;

    var hero = document.querySelector('.hero');
    if (!hero) return;

    hero.classList.add('holiday-effects-active');

    var cfg = {
        christmas:    { cls: 'snowflakes',       pcls: 'snowflake',      sym: ['❄','❅','❆','✻','❉'], n: 8 },
        valentines:   { cls: 'floating-hearts',   pcls: 'floating-heart', sym: ['💕','💗','💖','♥'],    n: 8 },
        stpatricks:   { cls: 'floating-clovers',  pcls: 'floating-clover',sym: ['☘️','🍀','🌿'],       n: 8 },
        halloween:    { cls: 'floating-spooky',   pcls: 'floating-bat',   sym: ['🦇','👻','🕷️'],       n: 8 },
        easter:       { cls: 'floating-eggs',     pcls: 'floating-egg',   sym: ['🥚','🐣','🌷','🌸'],  n: 8 },
        independence: { cls: 'floating-stars',    pcls: 'floating-star',  sym: ['⭐','✨','🌟','💫'],   n: 8 },
        newyear:      { cls: 'floating-confetti', pcls: 'confetti-piece', sym: null, colors: ['#f00','#0f0','#00f','#ff0','#f0f','#0ff','#fa0','#f6b'], n: 14 }
    }[active];

    if (!cfg) return;

    var container = document.createElement('div');
    container.className = cfg.cls;
    hero.appendChild(container);

    // Create all particles at once — CSS handles the infinite loop
    for (var i = 0; i < cfg.n; i++) {
        var p = document.createElement('div');
        p.className = cfg.pcls;
        p.style.left = (Math.random() * 100) + '%';
        // Spread delays evenly across the animation duration so particles are staggered
        p.style.animationDelay = (i * 1.2) + 's';
        p.style.animationDuration = (6 + Math.random() * 4) + 's';
        p.style.fontSize = (0.8 + Math.random() * 0.6) + 'rem';

        if (cfg.sym) {
            p.textContent = cfg.sym[i % cfg.sym.length];
        } else if (cfg.colors) {
            p.style.backgroundColor = cfg.colors[i % cfg.colors.length];
            p.style.width = (6 + Math.random() * 6) + 'px';
            p.style.height = (6 + Math.random() * 6) + 'px';
            p.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        }

        container.appendChild(p);
    }
})();
