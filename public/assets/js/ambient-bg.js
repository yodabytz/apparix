/**
 * Ambient Canvas Backgrounds for Apparix
 * Lightweight canvas-based animated backgrounds
 */
(function() {
    'use strict';

    var canvas, ctx, w, h, animId, particles = [];
    var config = window.ambientBgConfig || {};
    var effect = config.effect || 'none';
    var opacity = parseFloat(config.opacity) || 0.5;
    var color1 = config.color1 || '#ff68c5';
    var color2 = config.color2 || '#a855f7';
    var particleCount = config.particleCount || 80;

    if (effect === 'none' || window.innerWidth < 769) return;

    function init() {
        canvas = document.createElement('canvas');
        canvas.id = 'ambient-bg-canvas';
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:99;pointer-events:none;opacity:' + opacity;
        document.body.insertBefore(canvas, document.body.firstChild);
        ctx = canvas.getContext('2d');
        resize();
        window.addEventListener('resize', resize);

        if (effect === 'swirl') initSwirl();
        else if (effect === 'stars') initStars();
        else if (effect === 'aurora') initAurora();
        else if (effect === 'coalesce') initCoalesce();
        else if (effect === 'fireflies') initFireflies();

        tick();
    }

    function resize() {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
    }

    function tick() {
        ctx.clearRect(0, 0, w, h);

        if (effect === 'swirl') drawSwirl();
        else if (effect === 'stars') drawStars();
        else if (effect === 'aurora') drawAurora();
        else if (effect === 'coalesce') drawCoalesce();
        else if (effect === 'fireflies') drawFireflies();

        animId = requestAnimationFrame(tick);
    }

    // --- Helpers ---
    function hexToRgb(hex) {
        var r = parseInt(hex.slice(1,3), 16);
        var g = parseInt(hex.slice(3,5), 16);
        var b = parseInt(hex.slice(5,7), 16);
        return {r:r, g:g, b:b};
    }

    function rand(min, max) {
        return Math.random() * (max - min) + min;
    }

    // Simple 2D noise approximation
    var noiseOffset = Math.random() * 1000;
    function noise(x, y) {
        var n = Math.sin(x * 12.9898 + y * 78.233 + noiseOffset) * 43758.5453;
        return n - Math.floor(n);
    }

    // --- SWIRL ---
    var swirlTime = 0;
    function initSwirl() {
        particles = [];
        var count = Math.min(particleCount, 120);
        for (var i = 0; i < count; i++) {
            particles.push({
                x: rand(0, w),
                y: rand(0, h),
                size: rand(1.5, 3.5),
                speed: rand(0.3, 1.2),
                angle: rand(0, Math.PI * 2),
                drift: rand(-0.5, 0.5),
                color: Math.random() > 0.5 ? color1 : color2
            });
        }
    }

    function drawSwirl() {
        swirlTime += 0.003;
        var cx = w / 2, cy = h / 2;
        for (var i = 0; i < particles.length; i++) {
            var p = particles[i];
            // Calculate angle toward center
            var dx = cx - p.x;
            var dy = cy - p.y;
            var dist = Math.sqrt(dx * dx + dy * dy);
            var angleToCenter = Math.atan2(dy, dx);
            // Spiral: rotate perpendicular + slight inward pull
            var spiralAngle = angleToCenter + Math.PI / 2 + Math.sin(swirlTime + i * 0.1) * 0.3;
            var pull = Math.max(0.1, 1 - dist / (Math.max(w, h) * 0.6));

            p.x += Math.cos(spiralAngle) * p.speed + Math.cos(angleToCenter) * pull * 0.3;
            p.y += Math.sin(spiralAngle) * p.speed + Math.sin(angleToCenter) * pull * 0.3;

            // Wrap around
            if (p.x < -20) p.x = w + 20;
            if (p.x > w + 20) p.x = -20;
            if (p.y < -20) p.y = h + 20;
            if (p.y > h + 20) p.y = -20;

            // Respawn at edge if too close to center
            if (dist < 30) {
                var edge = Math.floor(rand(0, 4));
                if (edge === 0) { p.x = -10; p.y = rand(0, h); }
                else if (edge === 1) { p.x = w + 10; p.y = rand(0, h); }
                else if (edge === 2) { p.y = -10; p.x = rand(0, w); }
                else { p.y = h + 10; p.x = rand(0, w); }
            }

            var rgb = hexToRgb(p.color);
            var alpha = Math.min(0.8, 0.3 + pull * 0.5);
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
            ctx.fill();

            // Glow trail
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size * 3, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + (alpha * 0.15) + ')';
            ctx.fill();
        }
    }

    // --- STARS / FAIRY LIGHTS ---
    function initStars() {
        particles = [];
        var count = Math.min(particleCount, 150);
        for (var i = 0; i < count; i++) {
            particles.push({
                x: rand(0, w),
                y: rand(0, h),
                size: rand(1, 3),
                twinkleSpeed: rand(0.01, 0.04),
                twinkleOffset: rand(0, Math.PI * 2),
                driftX: rand(-0.15, 0.15),
                driftY: rand(-0.1, 0.1),
                color: Math.random() > 0.4 ? color1 : color2,
                brightness: rand(0.3, 1)
            });
        }
    }

    var starsTime = 0;
    function drawStars() {
        starsTime += 0.016;
        for (var i = 0; i < particles.length; i++) {
            var p = particles[i];
            p.x += p.driftX;
            p.y += p.driftY;

            // Wrap
            if (p.x < -5) p.x = w + 5;
            if (p.x > w + 5) p.x = -5;
            if (p.y < -5) p.y = h + 5;
            if (p.y > h + 5) p.y = -5;

            var twinkle = (Math.sin(starsTime * p.twinkleSpeed * 60 + p.twinkleOffset) + 1) / 2;
            var alpha = 0.1 + twinkle * p.brightness * 0.7;

            var rgb = hexToRgb(p.color);

            // Outer glow
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size * 4, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + (alpha * 0.12) + ')';
            ctx.fill();

            // Core
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size * twinkle, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
            ctx.fill();

            // Bright center
            if (twinkle > 0.7) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * 0.4, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,255,255,' + (alpha * 0.6) + ')';
                ctx.fill();
            }
        }
    }

    // --- AURORA ---
    var auroraTime = 0;
    var auroraRays = [];
    function initAurora() {
        auroraRays = [];
        var rayCount = 5;
        for (var i = 0; i < rayCount; i++) {
            auroraRays.push({
                x: w * (i / rayCount) + rand(-100, 100),
                width: rand(150, 400),
                speed: rand(0.002, 0.006),
                offset: rand(0, Math.PI * 2),
                color: i % 2 === 0 ? color1 : color2
            });
        }
    }

    function drawAurora() {
        auroraTime += 0.008;
        for (var i = 0; i < auroraRays.length; i++) {
            var ray = auroraRays[i];
            var rgb = hexToRgb(ray.color);
            var xOffset = Math.sin(auroraTime * ray.speed * 100 + ray.offset) * 100;

            var gradient = ctx.createLinearGradient(ray.x + xOffset, 0, ray.x + xOffset, h * 0.7);
            gradient.addColorStop(0, 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0.15)');
            gradient.addColorStop(0.5, 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0.05)');
            gradient.addColorStop(1, 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0)');

            ctx.beginPath();
            ctx.moveTo(ray.x + xOffset - ray.width / 2, 0);

            // Wavy shape
            var steps = 20;
            for (var j = 0; j <= steps; j++) {
                var t = j / steps;
                var yy = t * h * 0.7;
                var wave = Math.sin(t * Math.PI * 3 + auroraTime + ray.offset) * 40;
                var narrowing = 1 - t * 0.7;
                ctx.lineTo(ray.x + xOffset + wave + ray.width * narrowing / 2, yy);
            }
            for (var j = steps; j >= 0; j--) {
                var t = j / steps;
                var yy = t * h * 0.7;
                var wave = Math.sin(t * Math.PI * 3 + auroraTime + ray.offset + 1) * 40;
                var narrowing = 1 - t * 0.7;
                ctx.lineTo(ray.x + xOffset + wave - ray.width * narrowing / 2, yy);
            }

            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();
        }
    }

    // --- COALESCE ---
    function initCoalesce() {
        particles = [];
        var count = Math.min(particleCount, 100);
        for (var i = 0; i < count; i++) {
            var angle = rand(0, Math.PI * 2);
            var dist = rand(100, Math.max(w, h) * 0.7);
            particles.push({
                x: w / 2 + Math.cos(angle) * dist,
                y: h / 2 + Math.sin(angle) * dist,
                size: rand(1, 3),
                speed: rand(0.3, 1),
                life: rand(0, 1),
                color: Math.random() > 0.5 ? color1 : color2
            });
        }
    }

    var coalesceTime = 0;
    function drawCoalesce() {
        coalesceTime += 0.005;
        var cx = w / 2, cy = h / 2;
        for (var i = 0; i < particles.length; i++) {
            var p = particles[i];
            p.life += 0.003;

            var dx = cx - p.x;
            var dy = cy - p.y;
            var dist = Math.sqrt(dx * dx + dy * dy);
            var angleToCenter = Math.atan2(dy, dx);

            // Interpolate from moving toward center to spiraling
            var t = Math.min(1, p.life);
            var spiralAngle = angleToCenter + Math.PI / 2;
            var moveAngle = angleToCenter * (1 - t * 0.8) + spiralAngle * t * 0.8;

            p.x += Math.cos(moveAngle) * p.speed;
            p.y += Math.sin(moveAngle) * p.speed;

            // Reset if too close or too far
            if (dist < 20 || dist > Math.max(w, h)) {
                var edge = rand(0, Math.PI * 2);
                var eDist = rand(Math.max(w, h) * 0.4, Math.max(w, h) * 0.7);
                p.x = cx + Math.cos(edge) * eDist;
                p.y = cy + Math.sin(edge) * eDist;
                p.life = 0;
            }

            var rgb = hexToRgb(p.color);
            var alpha = Math.min(0.7, 0.2 + t * 0.5);

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
            ctx.fill();

            // Trail glow
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size * 2.5, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + (alpha * 0.15) + ')';
            ctx.fill();
        }
    }

    // --- FIREFLIES ---
    function initFireflies() {
        particles = [];
        var count = Math.min(particleCount, 60);
        for (var i = 0; i < count; i++) {
            particles.push({
                x: rand(0, w),
                y: rand(0, h),
                size: rand(2, 5),
                vx: rand(-0.3, 0.3),
                vy: rand(-0.3, 0.3),
                pulseSpeed: rand(0.01, 0.03),
                pulseOffset: rand(0, Math.PI * 2),
                color: Math.random() > 0.3 ? color1 : color2,
                wanderAngle: rand(0, Math.PI * 2),
                wanderSpeed: rand(0.005, 0.02)
            });
        }
    }

    var firefliesTime = 0;
    function drawFireflies() {
        firefliesTime += 0.016;
        for (var i = 0; i < particles.length; i++) {
            var p = particles[i];

            // Gentle wander
            p.wanderAngle += rand(-0.05, 0.05);
            p.vx += Math.cos(p.wanderAngle) * p.wanderSpeed;
            p.vy += Math.sin(p.wanderAngle) * p.wanderSpeed;

            // Damping
            p.vx *= 0.98;
            p.vy *= 0.98;

            p.x += p.vx;
            p.y += p.vy;

            // Soft boundaries
            if (p.x < 0) p.vx += 0.1;
            if (p.x > w) p.vx -= 0.1;
            if (p.y < 0) p.vy += 0.1;
            if (p.y > h) p.vy -= 0.1;

            var pulse = (Math.sin(firefliesTime * p.pulseSpeed * 60 + p.pulseOffset) + 1) / 2;
            var alpha = 0.1 + pulse * 0.6;
            var glowSize = p.size * (1 + pulse * 0.5);

            var rgb = hexToRgb(p.color);

            // Large glow
            var gradient = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, glowSize * 6);
            gradient.addColorStop(0, 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + (alpha * 0.3) + ')');
            gradient.addColorStop(1, 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0)');
            ctx.beginPath();
            ctx.arc(p.x, p.y, glowSize * 6, 0, Math.PI * 2);
            ctx.fillStyle = gradient;
            ctx.fill();

            // Core
            ctx.beginPath();
            ctx.arc(p.x, p.y, glowSize, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
            ctx.fill();

            // White center when bright
            if (pulse > 0.6) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, glowSize * 0.3, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,255,255,' + (pulse * 0.5) + ')';
                ctx.fill();
            }
        }
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Cleanup on page hide
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && animId) {
            cancelAnimationFrame(animId);
            animId = null;
        } else if (!document.hidden && !animId) {
            tick();
        }
    });
})();
