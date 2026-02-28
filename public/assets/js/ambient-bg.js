/**
 * Ambient Canvas Backgrounds for Apparix
 *
 * Based on "Ambient Canvas Backgrounds" by crnacura
 * Original: https://github.com/crnacura/AmbientCanvasBackgrounds
 * License: MIT
 *
 * Adapted for Apparix: renders inside navbar or hero, uses theme colors
 * Effects: swirl, stars, aurora, coalesce, fireflies, constellation, matrix, bokeh, wavemesh, fireworks
 */
(function() {
'use strict';

// --- SimplexNoise (inline) ---
!function(){var r=.5*(Math.sqrt(3)-1),e=(3-Math.sqrt(3))/6,t=1/6,a=(Math.sqrt(5)-1)/4,o=(5-Math.sqrt(5))/20;function i(r){var e;e="function"==typeof r?r:r?function(){var r=0,e=0,t=0,a=1,o=(i=4022871197,function(r){r=r.toString();for(var e=0;e<r.length;e++){var t=.02519603282416938*(i+=r.charCodeAt(e));t-=i=t>>>0,i=(t*=i)>>>0,i+=4294967296*(t-=i)}return 2.3283064365386963e-10*(i>>>0)});var i;r=o(" "),e=o(" "),t=o(" ");for(var n=0;n<arguments.length;n++)(r-=o(arguments[n]))<0&&(r+=1),(e-=o(arguments[n]))<0&&(e+=1),(t-=o(arguments[n]))<0&&(t+=1);return o=null,function(){var o=2091639*r+2.3283064365386963e-10*a;return r=e,e=t,t=o-(a=0|o)}}(r):Math.random,this.p=n(e),this.perm=new Uint8Array(512),this.permMod12=new Uint8Array(512);for(var t=0;t<512;t++)this.perm[t]=this.p[255&t],this.permMod12[t]=this.perm[t]%12}function n(r){var e,t=new Uint8Array(256);for(e=0;e<256;e++)t[e]=e;for(e=0;e<255;e++){var a=e+~~(r()*(256-e)),o=t[e];t[e]=t[a],t[a]=o}return t}i.prototype={grad3:new Float32Array([1,1,0,-1,1,0,1,-1,0,-1,-1,0,1,0,1,-1,0,1,1,0,-1,-1,0,-1,0,1,1,0,-1,1,0,1,-1,0,-1,-1]),grad4:new Float32Array([0,1,1,1,0,1,1,-1,0,1,-1,1,0,1,-1,-1,0,-1,1,1,0,-1,1,-1,0,-1,-1,1,0,-1,-1,-1,1,0,1,1,1,0,1,-1,1,0,-1,1,1,0,-1,-1,-1,0,1,1,-1,0,1,-1,-1,0,-1,1,-1,0,-1,-1,1,1,0,1,1,1,0,-1,1,-1,0,1,1,-1,0,-1,-1,1,0,1,-1,1,0,-1,-1,-1,0,1,-1,-1,0,-1,1,1,1,0,1,1,-1,0,1,-1,1,0,1,-1,-1,0,-1,1,1,0,-1,1,-1,0,-1,-1,1,0,-1,-1,-1,0]),noise2D:function(t,a){var o,i,n=this.permMod12,f=this.perm,s=this.grad3,v=0,h=0,l=0,u=(t+a)*r,d=Math.floor(t+u),p=Math.floor(a+u),M=(d+p)*e,m=t-(d-M),c=a-(p-M);m>c?(o=1,i=0):(o=0,i=1);var y=m-o+e,w=c-i+e,g=m-1+2*e,A=c-1+2*e,x=255&d,q=255&p,D=.5-m*m-c*c;if(D>=0){var S=3*n[x+f[q]];v=(D*=D)*D*(s[S]*m+s[S+1]*c)}var U=.5-y*y-w*w;if(U>=0){var b=3*n[x+o+f[q+i]];h=(U*=U)*U*(s[b]*y+s[b+1]*w)}var F=.5-g*g-A*A;if(F>=0){var N=3*n[x+1+f[q+1]];l=(F*=F)*F*(s[N]*g+s[N+1]*A)}return 70*(v+h+l)},noise3D:function(r,e,a){var o,i,n,f,s,v,h,l,u,d,p=this.permMod12,M=this.perm,m=this.grad3,c=(r+e+a)*(1/3),y=Math.floor(r+c),w=Math.floor(e+c),g=Math.floor(a+c),A=(y+w+g)*t,x=r-(y-A),q=e-(w-A),D=a-(g-A);x>=q?q>=D?(s=1,v=0,h=0,l=1,u=1,d=0):x>=D?(s=1,v=0,h=0,l=1,u=0,d=1):(s=0,v=0,h=1,l=1,u=0,d=1):q<D?(s=0,v=0,h=1,l=0,u=1,d=1):x<D?(s=0,v=1,h=0,l=0,u=1,d=1):(s=0,v=1,h=0,l=1,u=1,d=0);var S=x-s+t,U=q-v+t,b=D-h+t,F=x-l+2*t,N=q-u+2*t,C=D-d+2*t,P=x-1+.5,T=q-1+.5,_=D-1+.5,j=255&y,k=255&w,z=255&g,B=.6-x*x-q*q-D*D;if(B<0)o=0;else{var E=3*p[j+M[k+M[z]]];o=(B*=B)*B*(m[E]*x+m[E+1]*q+m[E+2]*D)}var G=.6-S*S-U*U-b*b;if(G<0)i=0;else{var H=3*p[j+s+M[k+v+M[z+h]]];i=(G*=G)*G*(m[H]*S+m[H+1]*U+m[H+2]*b)}var I=.6-F*F-N*N-C*C;if(I<0)n=0;else{var J=3*p[j+l+M[k+u+M[z+d]]];n=(I*=I)*I*(m[J]*F+m[J+1]*N+m[J+2]*C)}var K=.6-P*P-T*T-_*_;if(K<0)f=0;else{var L=3*p[j+1+M[k+1+M[z+1]]];f=(K*=K)*K*(m[L]*P+m[L+1]*T+m[L+2]*_)}return 32*(o+i+n+f)}},i._buildPermutationTable=n,"undefined"!=typeof window&&(window.SimplexNoise=i)}();

// --- Utility functions ---
var PI = Math.PI, cos = Math.cos, sin = Math.sin, abs = Math.abs, sqrt = Math.sqrt, random = Math.random;
var TAU = 2 * PI;
var rand = function(n) { return n * random(); };
var randRange = function(n) { return n - rand(2 * n); };
var fadeInOut = function(t, m) { var hm = 0.5 * m; return abs((t + hm) % m - hm) / hm; };
var lerp = function(n1, n2, speed) { return (1 - speed) * n1 + speed * n2; };

// --- Config ---
var config = window.ambientBgConfig || {};
var effect = config.effect || 'none';
var opacity = parseFloat(config.opacity) || 0.5;
var color1 = config.color1 || '#c9a84c';
var color2 = config.color2 || '#ffffff';
var effectTarget = config.location || 'navbar';

if (effect === 'none' || window.innerWidth < 769) return;

// --- Color helpers ---
function hexToHsl(hex) {
    if (!hex || hex.length < 7) return { h: 0, s: 0, l: 50 };
    var r = parseInt(hex.slice(1,3), 16) / 255;
    var g = parseInt(hex.slice(3,5), 16) / 255;
    var b = parseInt(hex.slice(5,7), 16) / 255;
    var max = Math.max(r,g,b), min = Math.min(r,g,b);
    var h, s, l = (max + min) / 2;
    if (max === min) { h = s = 0; }
    else {
        var d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
        else if (max === g) h = ((b - r) / d + 2) / 6;
        else h = ((r - g) / d + 4) / 6;
    }
    return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
}

function hexToRgb(hex) {
    if (!hex || hex.length < 7) return { r: 200, g: 200, b: 200 };
    return {
        r: parseInt(hex.slice(1,3), 16),
        g: parseInt(hex.slice(3,5), 16),
        b: parseInt(hex.slice(5,7), 16)
    };
}

var hsl1 = hexToHsl(color1);
var hsl2 = hexToHsl(color2);
var rgb1 = hexToRgb(color1);
var rgb2 = hexToRgb(color2);

// Color helper: get sat/lit for a particle based on which color it uses (0=color1, 1=color2)
function getParticleColor(colorIdx, hue, alpha) {
    var sat, lit;
    if (colorIdx === 1) { sat = hsl2.s; lit = Math.min(hsl2.l + 20, 95); }
    else { sat = hsl1.s; lit = Math.min(hsl1.l + 30, 90); }
    return 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + alpha + ')';
}

// --- Canvas state ---
var container, canvas, ctx, center, tick, simplex, particleProps;
var w, h, animId;

// --- Effect configs ---
var swirlConfig = { particleCount: 500, particlePropCount: 10, rangeY: 50, baseTTL: 50, rangeTTL: 150, baseSpeed: 0.1, rangeSpeed: 1.5, baseRadius: 1, rangeRadius: 3, noiseSteps: 8, xOff: 0.00125, yOff: 0.00125, zOff: 0.0005 };
var starsConfig = { fairyCount: 25, trailLength: 18, fairies: null };
var auroraConfig = { rayCount: 6, rays: [] };
var coalesceConfig = { particleCount: 400, particlePropCount: 10 };
var firefliesConfig = { particleCount: 60, particlePropCount: 11 };
var constellationConfig = { nodeCount: 100, nodePropCount: 6, linkDistance: 120, nodes: null };
var matrixConfig = { columns: null, fontSize: 14, chars: 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン0123456789ABCDEF' };
var bokehConfig = { circleCount: 40, circlePropCount: 8, circles: null };
var wavemeshConfig = { cols: 0, rows: 0, spacing: 30, dots: null };
var fireworksConfig = { rockets: [], sparks: [], spawnTimer: 0, spawnInterval: 45 };
var snowfallConfig = { flakes: null, count: 120 };
var hauntedConfig = { wisps: null, flashTimer: 0, flashAlpha: 0 };

var effectSetupFns = {};
var effectDrawFns = {};

function setup() {
    createCanvas();
    if (!container || !canvas) return;
    resize();
    tick = 0;
    simplex = new SimplexNoise();

    if (effectSetupFns[effect]) effectSetupFns[effect]();
    draw();
}

function createCanvas() {
    if (effectTarget === 'hero') {
        container = document.querySelector('.hero');
        if (!container) return; // Hero-only effects don't fall back to navbar
    } else {
        container = document.querySelector('.navbar');
    }
    if (!container) return;

    container.style.overflow = 'hidden';
    if (effectTarget === 'hero') {
        container.style.position = 'relative';
    } else {
        container.style.position = container.style.position || 'sticky';
    }

    canvas = {
        a: document.createElement('canvas'),
        b: document.createElement('canvas')
    };
    canvas.b.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;opacity:' + opacity + ';z-index:0;';
    canvas.b.setAttribute('aria-hidden', 'true');

    container.insertBefore(canvas.b, container.firstChild);

    ctx = {
        a: canvas.a.getContext('2d'),
        b: canvas.b.getContext('2d')
    };
    center = [];

    var children = container.children;
    for (var i = 0; i < children.length; i++) {
        if (children[i] !== canvas.b) {
            var el = children[i];
            if (getComputedStyle(el).position === 'static') {
                el.style.position = 'relative';
            }
            if (!el.style.zIndex) el.style.zIndex = '1';
        }
    }
}

function resize() {
    if (!container || !canvas) return;
    var rect = container.getBoundingClientRect();
    w = canvas.a.width = canvas.b.width = Math.round(rect.width);
    h = canvas.a.height = canvas.b.height = Math.round(rect.height);
    center[0] = 0.5 * w;
    center[1] = 0.5 * h;
}

function renderGlow() {
    ctx.b.save();
    ctx.b.filter = 'blur(8px) brightness(200%)';
    ctx.b.globalCompositeOperation = 'lighter';
    ctx.b.drawImage(canvas.a, 0, 0);
    ctx.b.restore();

    ctx.b.save();
    ctx.b.filter = 'blur(4px) brightness(200%)';
    ctx.b.globalCompositeOperation = 'lighter';
    ctx.b.drawImage(canvas.a, 0, 0);
    ctx.b.restore();
}

function renderToScreen() {
    ctx.b.save();
    ctx.b.globalCompositeOperation = 'lighter';
    ctx.b.drawImage(canvas.a, 0, 0);
    ctx.b.restore();
}

// Effects that look better without the bloom/glow post-processing
var noGlowEffects = { stars: 1, constellation: 1, matrix: 1, bokeh: 1, wavemesh: 1, fireworks: 1, snowfall: 1, haunted: 1 };

// Effects that manage their own canvas clearing (e.g. fade trails)
var selfRenderEffects = { fireworks: 1 };

function draw() {
    if (!canvas) return;
    tick++;

    if (!selfRenderEffects[effect]) {
        ctx.a.clearRect(0, 0, w, h);
        ctx.b.clearRect(0, 0, w, h);
    }

    if (effectDrawFns[effect]) effectDrawFns[effect]();

    if (!selfRenderEffects[effect]) {
        if (!noGlowEffects[effect]) {
            renderGlow();
            renderToScreen();
        } else {
            ctx.b.drawImage(canvas.a, 0, 0);
        }
    }

    animId = requestAnimationFrame(draw);
}

// ========== SWIRL ==========
effectSetupFns.swirl = function() {
    var c = swirlConfig;
    var total = c.particleCount * c.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += c.particlePropCount) initSwirlParticle(i);
};

function initSwirlParticle(i) {
    var c = swirlConfig;
    var colorIdx = random() > 0.5 ? 0 : 1;
    var hue = colorIdx === 0 ? hsl1.h + rand(30) - 15 : hsl2.h + rand(30) - 15;
    particleProps.set([rand(w), center[1] + randRange(c.rangeY), 0, 0, 0, c.baseTTL + rand(c.rangeTTL), c.baseSpeed + rand(c.rangeSpeed), c.baseRadius + rand(c.rangeRadius), hue, colorIdx], i);
}

effectDrawFns.swirl = function() {
    var c = swirlConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var n = simplex.noise3D(x * c.xOff, y * c.yOff, tick * c.zOff) * c.noiseSteps * TAU;
        var vx = lerp(particleProps[i+2], cos(n), 0.5);
        var vy = lerp(particleProps[i+3], sin(n), 0.5);
        var life = particleProps[i+4], ttl = particleProps[i+5];
        var speed = particleProps[i+6], radius = particleProps[i+7];
        var hue = particleProps[i+8], colorIdx = particleProps[i+9];
        var x2 = x + vx * speed, y2 = y + vy * speed;

        ctx.a.save();
        ctx.a.lineCap = 'round';
        ctx.a.lineWidth = radius;
        ctx.a.strokeStyle = getParticleColor(colorIdx, hue, fadeInOut(life, ttl));
        ctx.a.beginPath();
        ctx.a.moveTo(x, y);
        ctx.a.lineTo(x2, y2);
        ctx.a.stroke();
        ctx.a.restore();

        life++;
        particleProps[i] = x2; particleProps[i+1] = y2;
        particleProps[i+2] = vx; particleProps[i+3] = vy;
        particleProps[i+4] = life;

        if (x2 > w || x2 < 0 || y2 > h || y2 < 0 || life > ttl) initSwirlParticle(i);
    }
};

// ========== FAIRY LIGHTS ==========
effectSetupFns.stars = function() {
    var c = starsConfig;
    c.fairyCount = Math.max(15, Math.round(25 * (w * h) / (1200 * 600)));
    c.fairyCount = Math.min(c.fairyCount, 40);
    c.fairies = [];
    for (var i = 0; i < c.fairyCount; i++) {
        c.fairies.push(createFairy());
    }
};

function createFairy() {
    var colorIdx = random() > 0.4 ? 0 : 1;
    var c1 = colorIdx === 0 ? rgb1 : rgb2;
    return {
        x: rand(w), y: rand(h),
        vx: randRange(1.5), vy: randRange(1.5),
        wanderAngle: rand(TAU),
        wanderSpeed: 0.03 + rand(0.05),
        speed: 0.8 + rand(1.2),
        radius: 2 + rand(2),
        colorIdx: colorIdx,
        r: c1.r, g: c1.g, b: c1.b,
        phase: rand(TAU),
        trail: [],
        dustTimer: 0,
        dustInterval: 2 + Math.floor(rand(3))
    };
}

effectDrawFns.stars = function() {
    var c = starsConfig;
    var fairies = c.fairies;
    var maxTrail = c.trailLength;

    for (var i = 0; i < fairies.length; i++) {
        var f = fairies[i];

        // Flutter movement: wander angle shifts smoothly with occasional darting
        f.wanderAngle += simplex.noise3D(f.x * 0.005, f.y * 0.005, tick * 0.003 + i) * 0.15;
        if (random() < 0.008) f.wanderAngle += randRange(PI * 0.5); // occasional dart

        f.vx += cos(f.wanderAngle) * f.wanderSpeed;
        f.vy += sin(f.wanderAngle) * f.wanderSpeed;
        // Gentle upward drift (fairies float)
        f.vy -= 0.005;
        // Damping
        f.vx *= 0.96; f.vy *= 0.96;

        f.x += f.vx * f.speed;
        f.y += f.vy * f.speed;

        // Soft boundary bounce
        if (f.x < 10) { f.vx += 0.15; f.wanderAngle = rand(PI) - PI * 0.5; }
        if (f.x > w - 10) { f.vx -= 0.15; f.wanderAngle = PI + rand(PI) - PI * 0.5; }
        if (f.y < 10) { f.vy += 0.15; }
        if (f.y > h - 10) { f.vy -= 0.15; f.wanderAngle = -PI * 0.5 + randRange(PI * 0.3); }

        // Spawn dust sparkle at intervals
        f.dustTimer++;
        if (f.dustTimer >= f.dustInterval) {
            f.dustTimer = 0;
            f.trail.push({
                x: f.x + randRange(3),
                y: f.y + randRange(3),
                radius: 0.5 + rand(1.5),
                life: 0,
                maxLife: 25 + Math.floor(rand(20)),
                drift: randRange(0.15),
                fall: 0.08 + rand(0.12),
                twinklePhase: rand(TAU)
            });
            if (f.trail.length > maxTrail) f.trail.shift();
        }

        // Draw dust trail (pixie dust sparkles)
        for (var j = 0; j < f.trail.length; j++) {
            var d = f.trail[j];
            d.x += d.drift;
            d.y += d.fall;
            d.life++;
            var progress = d.life / d.maxLife;
            if (progress >= 1) { f.trail.splice(j, 1); j--; continue; }
            var twinkle = (sin(tick * 0.08 + d.twinklePhase) + 1) * 0.5;
            var dustAlpha = (1 - progress) * (0.2 + twinkle * 0.5);
            var dustR = d.radius * (1 - progress * 0.5) * (0.6 + twinkle * 0.4);
            ctx.a.beginPath();
            ctx.a.arc(d.x, d.y, dustR, 0, TAU);
            ctx.a.fillStyle = 'rgba(' + f.r + ',' + f.g + ',' + f.b + ',' + dustAlpha + ')';
            ctx.a.fill();
        }

        // Draw the fairy (bright core with glow)
        var pulse = (sin(tick * 0.04 + f.phase) + 1) * 0.5;
        var coreAlpha = 0.7 + pulse * 0.3;
        var glowR = f.radius * (2.5 + pulse);

        // Outer glow
        var grd = ctx.a.createRadialGradient(f.x, f.y, 0, f.x, f.y, glowR);
        grd.addColorStop(0, 'rgba(' + Math.min(f.r + 60, 255) + ',' + Math.min(f.g + 60, 255) + ',' + Math.min(f.b + 60, 255) + ',' + (coreAlpha * 0.5) + ')');
        grd.addColorStop(0.4, 'rgba(' + f.r + ',' + f.g + ',' + f.b + ',' + (coreAlpha * 0.2) + ')');
        grd.addColorStop(1, 'rgba(' + f.r + ',' + f.g + ',' + f.b + ',0)');
        ctx.a.beginPath();
        ctx.a.arc(f.x, f.y, glowR, 0, TAU);
        ctx.a.fillStyle = grd;
        ctx.a.fill();

        // Bright core
        ctx.a.beginPath();
        ctx.a.arc(f.x, f.y, f.radius * (0.8 + pulse * 0.2), 0, TAU);
        ctx.a.fillStyle = 'rgba(' + Math.min(f.r + 80, 255) + ',' + Math.min(f.g + 80, 255) + ',' + Math.min(f.b + 80, 255) + ',' + coreAlpha + ')';
        ctx.a.fill();
    }
};

// ========== AURORA ==========
effectSetupFns.aurora = function() {
    auroraConfig.rays = [];
    for (var i = 0; i < auroraConfig.rayCount; i++) {
        auroraConfig.rays.push({
            x: w * (i / auroraConfig.rayCount) + randRange(80),
            width: 80 + rand(200),
            offset: rand(TAU),
            hue: i % 2 === 0 ? hsl1.h : hsl2.h,
            sat: i % 2 === 0 ? hsl1.s : hsl2.s,
            lit: i % 2 === 0 ? Math.min(hsl1.l + 30, 90) : Math.min(hsl2.l + 20, 95)
        });
    }
};

effectDrawFns.aurora = function() {
    for (var i = 0; i < auroraConfig.rays.length; i++) {
        var ray = auroraConfig.rays[i];
        var xOff = simplex.noise2D(i * 0.5, tick * 0.002) * 80;

        var gradient = ctx.a.createLinearGradient(ray.x + xOff, 0, ray.x + xOff, h);
        gradient.addColorStop(0, 'hsla(' + ray.hue + ',' + ray.sat + '%,' + ray.lit + '%,0.2)');
        gradient.addColorStop(0.5, 'hsla(' + ray.hue + ',' + ray.sat + '%,' + ray.lit + '%,0.08)');
        gradient.addColorStop(1, 'hsla(' + ray.hue + ',' + ray.sat + '%,' + ray.lit + '%,0)');

        ctx.a.beginPath();
        ctx.a.moveTo(ray.x + xOff - ray.width / 2, 0);
        var steps = 12;
        for (var j = 0; j <= steps; j++) {
            var t = j / steps, yy = t * h;
            var wave = simplex.noise2D(t * 3 + ray.offset, tick * 0.003) * 30;
            ctx.a.lineTo(ray.x + xOff + wave + ray.width * (1 - t * 0.6) / 2, yy);
        }
        for (var j = steps; j >= 0; j--) {
            var t = j / steps, yy = t * h;
            var wave = simplex.noise2D(t * 3 + ray.offset + 5, tick * 0.003) * 30;
            ctx.a.lineTo(ray.x + xOff + wave - ray.width * (1 - t * 0.6) / 2, yy);
        }
        ctx.a.closePath();
        ctx.a.fillStyle = gradient;
        ctx.a.fill();
    }
};

// ========== COALESCE ==========
effectSetupFns.coalesce = function() {
    var c = coalesceConfig;
    var total = c.particleCount * c.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += c.particlePropCount) initCoalesceParticle(i);
};

function initCoalesceParticle(i) {
    var angle = rand(TAU);
    var dist = rand(Math.max(w, h) * 0.8);
    var colorIdx = random() > 0.5 ? 0 : 1;
    particleProps.set([center[0] + cos(angle) * dist, center[1] + sin(angle) * dist, 0, 0, 0, 60 + rand(120), 0.2 + rand(1.2), 1 + rand(3), colorIdx === 0 ? hsl1.h : hsl2.h, colorIdx], i);
}

effectDrawFns.coalesce = function() {
    var c = coalesceConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var life = particleProps[i+4], ttl = particleProps[i+5];
        var speed = particleProps[i+6], radius = particleProps[i+7];
        var hue = particleProps[i+8], colorIdx = particleProps[i+9];

        var dx = center[0] - x, dy = center[1] - y;
        var dist = sqrt(dx * dx + dy * dy);
        var angleToCenter = Math.atan2(dy, dx);
        var t = Math.min(1, life / ttl);
        var moveAngle = lerp(angleToCenter, angleToCenter + PI / 2, t * 0.7);
        var x2 = x + cos(moveAngle) * speed, y2 = y + sin(moveAngle) * speed;

        ctx.a.save();
        ctx.a.lineCap = 'round';
        ctx.a.lineWidth = radius;
        ctx.a.strokeStyle = getParticleColor(colorIdx, hue, fadeInOut(life, ttl));
        ctx.a.beginPath();
        ctx.a.moveTo(x, y);
        ctx.a.lineTo(x2, y2);
        ctx.a.stroke();
        ctx.a.restore();

        life++;
        particleProps[i] = x2; particleProps[i+1] = y2; particleProps[i+4] = life;

        if (dist < 15 || life > ttl || x2 < 0 || x2 > w || y2 < 0 || y2 > h) initCoalesceParticle(i);
    }
};

// ========== FIREFLIES ==========
effectSetupFns.fireflies = function() {
    var c = firefliesConfig;
    var total = c.particleCount * c.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += c.particlePropCount) initFireflyParticle(i);
};

function initFireflyParticle(i) {
    var colorIdx = random() > 0.3 ? 0 : 1;
    var hue = colorIdx === 0 ? hsl1.h : hsl2.h;
    var sat = colorIdx === 1 ? hsl2.s : hsl1.s;
    var lit = colorIdx === 1 ? Math.min(hsl2.l + 20, 95) : Math.min(hsl1.l + 30, 90);
    particleProps.set([rand(w), rand(h), randRange(0.3), randRange(0.3), 2 + rand(4), hue, sat, lit, rand(TAU), 0.01 + rand(0.03), rand(TAU)], i);
}

effectDrawFns.fireflies = function() {
    var c = firefliesConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var vx = particleProps[i+2], vy = particleProps[i+3];
        var radius = particleProps[i+4];
        var hue = particleProps[i+5], sat = particleProps[i+6], lit = particleProps[i+7];
        var phase = particleProps[i+8], pSpeed = particleProps[i+9];
        var wanderAngle = particleProps[i+10];

        wanderAngle += randRange(0.05);
        vx += cos(wanderAngle) * 0.01; vy += sin(wanderAngle) * 0.01;
        vx *= 0.98; vy *= 0.98;
        x += vx; y += vy;

        if (x < 0) vx += 0.1; if (x > w) vx -= 0.1;
        if (y < 0) vy += 0.1; if (y > h) vy -= 0.1;

        var pulse = (sin(tick * pSpeed + phase) + 1) * 0.5;
        ctx.a.beginPath();
        ctx.a.arc(x, y, radius * (0.5 + pulse * 0.5), 0, TAU);
        ctx.a.fillStyle = 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + (0.1 + pulse * 0.6) + ')';
        ctx.a.fill();

        particleProps[i] = x; particleProps[i+1] = y;
        particleProps[i+2] = vx; particleProps[i+3] = vy;
        particleProps[i+10] = wanderAngle;
    }
};

// ========== CONSTELLATION ==========
effectSetupFns.constellation = function() {
    var c = constellationConfig;
    c.nodeCount = Math.round(100 * (w * h) / (1200 * 600));
    c.nodeCount = Math.max(40, Math.min(c.nodeCount, 150));
    c.nodes = [];
    for (var i = 0; i < c.nodeCount; i++) {
        c.nodes.push({
            x: rand(w), y: rand(h),
            vx: randRange(0.3), vy: randRange(0.3),
            radius: 1.5 + rand(2),
            colorIdx: random() > 0.5 ? 0 : 1
        });
    }
};

effectDrawFns.constellation = function() {
    var c = constellationConfig;
    var nodes = c.nodes;
    var linkDist = c.linkDistance;

    for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];
        node.x += node.vx; node.y += node.vy;

        // Wrap around edges
        if (node.x < -10) node.x = w + 10;
        if (node.x > w + 10) node.x = -10;
        if (node.y < -10) node.y = h + 10;
        if (node.y > h + 10) node.y = -10;

        // Draw node
        ctx.a.beginPath();
        ctx.a.arc(node.x, node.y, node.radius, 0, TAU);
        var c1 = node.colorIdx === 0 ? rgb1 : rgb2;
        ctx.a.fillStyle = 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',0.8)';
        ctx.a.fill();

        // Draw connections to nearby nodes
        for (var j = i + 1; j < nodes.length; j++) {
            var other = nodes[j];
            var dx = node.x - other.x, dy = node.y - other.y;
            var dist = sqrt(dx * dx + dy * dy);
            if (dist < linkDist) {
                var alpha = (1 - dist / linkDist) * 0.4;
                var c2 = node.colorIdx === 0 ? rgb1 : rgb2;
                ctx.a.beginPath();
                ctx.a.moveTo(node.x, node.y);
                ctx.a.lineTo(other.x, other.y);
                ctx.a.strokeStyle = 'rgba(' + c2.r + ',' + c2.g + ',' + c2.b + ',' + alpha + ')';
                ctx.a.lineWidth = 0.8;
                ctx.a.stroke();
            }
        }
    }
};

// ========== MATRIX RAIN ==========
effectSetupFns.matrix = function() {
    var c = matrixConfig;
    var colCount = Math.ceil(w / c.fontSize);
    c.columns = [];
    for (var i = 0; i < colCount; i++) {
        c.columns.push({
            x: i * c.fontSize,
            y: -rand(h * 2),
            speed: 1 + rand(3),
            chars: [],
            length: 8 + Math.floor(rand(20)),
            hue: random() > 0.5 ? hsl1.h : hsl2.h,
            colorIdx: random() > 0.5 ? 0 : 1
        });
        // Pre-fill chars
        var col = c.columns[i];
        for (var j = 0; j < col.length; j++) {
            col.chars.push(c.chars[Math.floor(rand(c.chars.length))]);
        }
    }
};

effectDrawFns.matrix = function() {
    var c = matrixConfig;
    ctx.a.font = c.fontSize + 'px monospace';
    ctx.a.textAlign = 'center';

    for (var i = 0; i < c.columns.length; i++) {
        var col = c.columns[i];
        col.y += col.speed;

        for (var j = 0; j < col.length; j++) {
            var charY = col.y - j * c.fontSize;
            if (charY < -c.fontSize || charY > h + c.fontSize) continue;

            // Randomly change characters
            if (random() < 0.02) {
                col.chars[j] = c.chars[Math.floor(rand(c.chars.length))];
            }

            var alpha;
            if (j === 0) {
                // Head: bright
                alpha = 0.95;
                var c1 = col.colorIdx === 0 ? rgb1 : rgb2;
                ctx.a.fillStyle = 'rgba(' + Math.min(c1.r + 80, 255) + ',' + Math.min(c1.g + 80, 255) + ',' + Math.min(c1.b + 80, 255) + ',' + alpha + ')';
            } else {
                // Trail: fading
                alpha = (1 - j / col.length) * 0.7;
                var c1 = col.colorIdx === 0 ? rgb1 : rgb2;
                ctx.a.fillStyle = 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha + ')';
            }

            ctx.a.fillText(col.chars[j], col.x + c.fontSize / 2, charY);
        }

        // Reset when fully off screen
        if (col.y - col.length * c.fontSize > h) {
            col.y = -rand(h);
            col.speed = 1 + rand(3);
            col.length = 8 + Math.floor(rand(20));
            col.colorIdx = random() > 0.5 ? 0 : 1;
            col.chars = [];
            for (var j = 0; j < col.length; j++) {
                col.chars.push(c.chars[Math.floor(rand(c.chars.length))]);
            }
        }
    }
};

// ========== BOKEH ==========
effectSetupFns.bokeh = function() {
    var c = bokehConfig;
    c.circles = [];
    for (var i = 0; i < c.circleCount; i++) {
        var layer = random(); // 0=far, 1=near
        var colorIdx = random() > 0.5 ? 0 : 1;
        c.circles.push({
            x: rand(w), y: rand(h),
            vx: randRange(0.15) * (0.3 + layer * 0.7),
            vy: randRange(0.1) * (0.3 + layer * 0.7),
            radius: 15 + rand(45) * (0.4 + layer * 0.6),
            alpha: 0.03 + rand(0.12) * (0.5 + layer * 0.5),
            colorIdx: colorIdx,
            phase: rand(TAU),
            breathSpeed: 0.003 + rand(0.008)
        });
    }
};

effectDrawFns.bokeh = function() {
    var c = bokehConfig;
    for (var i = 0; i < c.circles.length; i++) {
        var circle = c.circles[i];
        circle.x += circle.vx; circle.y += circle.vy;

        // Wrap
        if (circle.x < -circle.radius * 2) circle.x = w + circle.radius;
        if (circle.x > w + circle.radius * 2) circle.x = -circle.radius;
        if (circle.y < -circle.radius * 2) circle.y = h + circle.radius;
        if (circle.y > h + circle.radius * 2) circle.y = -circle.radius;

        var breath = (sin(tick * circle.breathSpeed + circle.phase) + 1) * 0.5;
        var r = circle.radius * (0.85 + breath * 0.15);
        var alpha = circle.alpha * (0.7 + breath * 0.3);

        var c1 = circle.colorIdx === 0 ? rgb1 : rgb2;
        var grad = ctx.a.createRadialGradient(circle.x, circle.y, 0, circle.x, circle.y, r);
        grad.addColorStop(0, 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha + ')');
        grad.addColorStop(0.5, 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha * 0.4 + ')');
        grad.addColorStop(1, 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',0)');

        ctx.a.beginPath();
        ctx.a.arc(circle.x, circle.y, r, 0, TAU);
        ctx.a.fillStyle = grad;
        ctx.a.fill();
    }
};

// ========== WAVE MESH ==========
effectSetupFns.wavemesh = function() {
    var c = wavemeshConfig;
    c.spacing = Math.max(20, Math.round(30 * (w / 1200)));
    c.cols = Math.ceil(w / c.spacing) + 2;
    c.rows = Math.ceil(h / c.spacing) + 2;
    c.dots = [];
    for (var row = 0; row < c.rows; row++) {
        for (var col = 0; col < c.cols; col++) {
            c.dots.push({
                baseX: col * c.spacing,
                baseY: row * c.spacing,
                x: col * c.spacing,
                y: row * c.spacing,
                row: row, col: col
            });
        }
    }
};

effectDrawFns.wavemesh = function() {
    var c = wavemeshConfig;
    var amp = h * 0.12;
    var freq = 0.04;
    var timeFreq = 0.015;

    // Update positions
    for (var i = 0; i < c.dots.length; i++) {
        var dot = c.dots[i];
        var wave = sin(dot.baseX * freq + tick * timeFreq) * cos(dot.baseY * freq * 0.7 + tick * timeFreq * 0.8);
        dot.x = dot.baseX;
        dot.y = dot.baseY + wave * amp;
    }

    // Draw connections and dots
    for (var i = 0; i < c.dots.length; i++) {
        var dot = c.dots[i];
        var row = dot.row, col = dot.col;

        // Determine color based on wave displacement
        var displacement = abs(dot.y - dot.baseY) / amp;
        var c1, alpha;
        if (displacement > 0.5) {
            c1 = rgb1;
            alpha = 0.15 + displacement * 0.2;
        } else {
            c1 = rgb2;
            alpha = 0.08 + displacement * 0.15;
        }

        // Draw horizontal line to right neighbor
        if (col < c.cols - 1) {
            var right = c.dots[i + 1];
            ctx.a.beginPath();
            ctx.a.moveTo(dot.x, dot.y);
            ctx.a.lineTo(right.x, right.y);
            ctx.a.strokeStyle = 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha * 0.6 + ')';
            ctx.a.lineWidth = 0.6;
            ctx.a.stroke();
        }

        // Draw vertical line to bottom neighbor
        if (row < c.rows - 1) {
            var below = c.dots[i + c.cols];
            ctx.a.beginPath();
            ctx.a.moveTo(dot.x, dot.y);
            ctx.a.lineTo(below.x, below.y);
            ctx.a.strokeStyle = 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha * 0.6 + ')';
            ctx.a.lineWidth = 0.6;
            ctx.a.stroke();
        }

        // Draw dot
        ctx.a.beginPath();
        ctx.a.arc(dot.x, dot.y, 1.2, 0, TAU);
        ctx.a.fillStyle = 'rgba(' + c1.r + ',' + c1.g + ',' + c1.b + ',' + alpha + ')';
        ctx.a.fill();
    }
};

// --- Haunted (Halloween) ---
effectSetupFns.haunted = function() {
    var c = hauntedConfig;
    c.wisps = [];
    c.flashTimer = 0;
    c.flashAlpha = 0;

    // Large slow fog wisps
    for (var i = 0; i < 15; i++) {
        c.wisps.push({
            x: random() * w,
            y: random() * h,
            r: 40 + random() * 80,
            vx: randRange(0.3),
            vy: randRange(0.2),
            phase: random() * TAU,
            phaseSpeed: 0.005 + random() * 0.01,
            opacity: 0.03 + random() * 0.06,
            type: 'fog',
            cr: 100, cg: 60, cb: 180
        });
    }

    // Floating ghost orbs
    for (var i = 0; i < 20; i++) {
        var isOrange = random() < 0.4;
        c.wisps.push({
            x: random() * w,
            y: random() * h,
            r: 3 + random() * 6,
            vx: randRange(0.5),
            vy: -0.1 - random() * 0.4,
            phase: random() * TAU,
            phaseSpeed: 0.02 + random() * 0.03,
            opacity: 0.2 + random() * 0.5,
            type: 'orb',
            cr: isOrange ? 255 : 160,
            cg: isOrange ? 120 : 80,
            cb: isOrange ? 30 : 220,
            trail: []
        });
    }

    // Eerie drifting specters
    for (var i = 0; i < 5; i++) {
        c.wisps.push({
            x: random() * w,
            y: h * 0.3 + random() * h * 0.5,
            r: 15 + random() * 25,
            vx: 0.2 + random() * 0.4,
            vy: randRange(0.15),
            phase: random() * TAU,
            phaseSpeed: 0.008 + random() * 0.012,
            opacity: 0.04 + random() * 0.06,
            type: 'specter',
            cr: 200, cg: 200, cb: 220
        });
    }
};

effectDrawFns.haunted = function() {
    var c = hauntedConfig;
    if (!c.wisps) return;

    // Lightning flash
    c.flashTimer++;
    if (c.flashTimer > 200 + random() * 400) {
        c.flashAlpha = 0.15 + random() * 0.1;
        c.flashTimer = 0;
        // Double flash
        if (random() < 0.4) {
            setTimeout(function() { c.flashAlpha = 0.1; }, 100);
        }
    }
    if (c.flashAlpha > 0) {
        ctx.a.fillStyle = 'rgba(180, 160, 220, ' + c.flashAlpha + ')';
        ctx.a.fillRect(0, 0, w, h);
        c.flashAlpha *= 0.85;
        if (c.flashAlpha < 0.005) c.flashAlpha = 0;
    }

    for (var i = 0; i < c.wisps.length; i++) {
        var p = c.wisps[i];
        p.phase += p.phaseSpeed;
        var pulse = 0.6 + 0.4 * sin(p.phase);

        if (p.type === 'fog') {
            p.x += p.vx + sin(p.phase * 0.5) * 0.2;
            p.y += p.vy + cos(p.phase * 0.3) * 0.15;

            // Wrap
            if (p.x > w + p.r) p.x = -p.r;
            if (p.x < -p.r) p.x = w + p.r;
            if (p.y > h + p.r) p.y = -p.r;
            if (p.y < -p.r) p.y = h + p.r;

            var g = ctx.a.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r);
            g.addColorStop(0, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + (p.opacity * pulse) + ')');
            g.addColorStop(1, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',0)');
            ctx.a.beginPath();
            ctx.a.arc(p.x, p.y, p.r, 0, TAU);
            ctx.a.fillStyle = g;
            ctx.a.fill();

        } else if (p.type === 'orb') {
            p.x += p.vx + sin(p.phase) * 0.8;
            p.y += p.vy + cos(p.phase * 1.3) * 0.3;

            // Store trail
            p.trail.push({ x: p.x, y: p.y });
            if (p.trail.length > 10) p.trail.shift();

            // Wrap
            if (p.y < -10) { p.y = h + 10; p.x = random() * w; p.trail = []; }
            if (p.x > w + 10) p.x = -10;
            if (p.x < -10) p.x = w + 10;

            // Draw trail
            for (var t = 0; t < p.trail.length; t++) {
                var ta = (t / p.trail.length) * p.opacity * pulse * 0.3;
                ctx.a.beginPath();
                ctx.a.arc(p.trail[t].x, p.trail[t].y, p.r * 0.4, 0, TAU);
                ctx.a.fillStyle = 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + ta + ')';
                ctx.a.fill();
            }

            // Draw orb with glow
            var g = ctx.a.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 3);
            g.addColorStop(0, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + (p.opacity * pulse) + ')');
            g.addColorStop(0.4, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + (p.opacity * pulse * 0.3) + ')');
            g.addColorStop(1, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',0)');
            ctx.a.beginPath();
            ctx.a.arc(p.x, p.y, p.r * 3, 0, TAU);
            ctx.a.fillStyle = g;
            ctx.a.fill();

        } else if (p.type === 'specter') {
            p.x += p.vx;
            p.y += p.vy + sin(p.phase) * 0.5;

            // Wrap
            if (p.x > w + p.r * 2) { p.x = -p.r * 2; p.y = h * 0.2 + random() * h * 0.6; }

            // Draw elongated ghostly shape
            ctx.a.save();
            ctx.a.translate(p.x, p.y);
            var g = ctx.a.createRadialGradient(0, 0, 0, 0, 0, p.r);
            g.addColorStop(0, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + (p.opacity * pulse) + ')');
            g.addColorStop(0.6, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',' + (p.opacity * pulse * 0.3) + ')');
            g.addColorStop(1, 'rgba(' + p.cr + ',' + p.cg + ',' + p.cb + ',0)');
            ctx.a.scale(2.5, 1);
            ctx.a.beginPath();
            ctx.a.arc(0, 0, p.r, 0, TAU);
            ctx.a.fillStyle = g;
            ctx.a.fill();
            ctx.a.restore();
        }
    }
};

// --- Snowfall ---
effectSetupFns.snowfall = function() {
    var c = snowfallConfig;
    c.flakes = [];
    for (var i = 0; i < c.count; i++) {
        c.flakes.push({
            x: random() * w,
            y: random() * h,
            r: 1 + random() * 3,
            vx: randRange(0.3),
            vy: 0.3 + random() * 1.2,
            wobble: random() * TAU,
            wobbleSpeed: 0.01 + random() * 0.02,
            opacity: 0.3 + random() * 0.7
        });
    }
};

effectDrawFns.snowfall = function() {
    var c = snowfallConfig;
    if (!c.flakes) return;

    for (var i = 0; i < c.flakes.length; i++) {
        var f = c.flakes[i];
        f.wobble += f.wobbleSpeed;
        f.x += f.vx + sin(f.wobble) * 0.3;
        f.y += f.vy;

        // Wrap around
        if (f.y > h + 5) { f.y = -5; f.x = random() * w; }
        if (f.x > w + 5) f.x = -5;
        if (f.x < -5) f.x = w + 5;

        // Draw snowflake with soft glow
        var gradient = ctx.a.createRadialGradient(f.x, f.y, 0, f.x, f.y, f.r * 2);
        gradient.addColorStop(0, 'rgba(255, 255, 255, ' + f.opacity + ')');
        gradient.addColorStop(0.5, 'rgba(220, 235, 255, ' + (f.opacity * 0.4) + ')');
        gradient.addColorStop(1, 'rgba(200, 220, 255, 0)');
        ctx.a.beginPath();
        ctx.a.arc(f.x, f.y, f.r * 2, 0, TAU);
        ctx.a.fillStyle = gradient;
        ctx.a.fill();

        // Bright core
        ctx.a.beginPath();
        ctx.a.arc(f.x, f.y, f.r * 0.5, 0, TAU);
        ctx.a.fillStyle = 'rgba(255, 255, 255, ' + f.opacity + ')';
        ctx.a.fill();
    }
};

// --- Fireworks ---
var fwColors = [
    [255, 60, 60],    // red
    [255, 255, 255],  // white
    [80, 120, 255],   // blue
    [255, 200, 50],   // gold
    [255, 100, 100],  // light red
    [150, 180, 255]   // light blue
];

function fwSpawnRocket() {
    var fw = fireworksConfig;
    var x = w * 0.15 + random() * w * 0.7;
    var targetY = h * 0.1 + random() * h * 0.35;
    var colorSet = random() < 0.6
        ? [fwColors[0], fwColors[1], fwColors[2]] // red white blue
        : [fwColors[Math.floor(random() * fwColors.length)], fwColors[Math.floor(random() * fwColors.length)]];
    fw.rockets.push({
        x: x, y: h + 5,
        targetY: targetY,
        speed: 2.5 + random() * 2,
        trail: [],
        colors: colorSet,
        size: random() < 0.3 ? 'big' : 'normal'
    });
}

function fwExplode(rocket) {
    var fw = fireworksConfig;
    var count = rocket.size === 'big' ? 80 + Math.floor(random() * 40) : 40 + Math.floor(random() * 25);
    var hasRing = random() < 0.4;

    for (var i = 0; i < count; i++) {
        var angle = TAU * (i / count) + randRange(0.15);
        var speed = 1.5 + random() * 3.5;
        if (rocket.size === 'big') speed *= 1.4;
        var c = rocket.colors[Math.floor(random() * rocket.colors.length)];
        fw.sparks.push({
            x: rocket.x, y: rocket.y,
            vx: cos(angle) * speed,
            vy: sin(angle) * speed,
            r: c[0], g: c[1], b: c[2],
            life: 1,
            decay: 0.008 + random() * 0.012,
            radius: 1.5 + random() * 1.5,
            gravity: 0.02 + random() * 0.02,
            sparkle: random() < 0.3
        });
    }

    // Optional ring burst
    if (hasRing) {
        var ringColor = rocket.colors[0];
        var ringCount = 30 + Math.floor(random() * 20);
        var ringSpeed = 3 + random() * 2;
        for (var j = 0; j < ringCount; j++) {
            var a = TAU * (j / ringCount);
            fw.sparks.push({
                x: rocket.x, y: rocket.y,
                vx: cos(a) * ringSpeed,
                vy: sin(a) * ringSpeed,
                r: ringColor[0], g: ringColor[1], b: ringColor[2],
                life: 1,
                decay: 0.015 + random() * 0.01,
                radius: 1,
                gravity: 0.01,
                sparkle: false
            });
        }
    }
}

effectSetupFns.fireworks = function() {
    var fw = fireworksConfig;
    fw.rockets = [];
    fw.sparks = [];
    fw.spawnTimer = 0;
    fw.spawnInterval = 35 + Math.floor(random() * 30);
};

effectDrawFns.fireworks = function() {
    var fw = fireworksConfig;

    // Fade existing content by reducing alpha (keeps canvas transparent, no black fill)
    ctx.a.save();
    ctx.a.globalCompositeOperation = 'destination-out';
    ctx.a.fillStyle = 'rgba(0, 0, 0, 0.15)';
    ctx.a.fillRect(0, 0, w, h);
    ctx.a.restore();

    // Spawn new rockets
    fw.spawnTimer++;
    if (fw.spawnTimer >= fw.spawnInterval) {
        fwSpawnRocket();
        // Occasionally launch 2-3 at once
        if (random() < 0.3) fwSpawnRocket();
        if (random() < 0.1) fwSpawnRocket();
        fw.spawnTimer = 0;
        fw.spawnInterval = 35 + Math.floor(random() * 40);
    }

    // Update and draw rockets
    for (var i = fw.rockets.length - 1; i >= 0; i--) {
        var r = fw.rockets[i];
        r.trail.push({ x: r.x, y: r.y });
        if (r.trail.length > 12) r.trail.shift();
        r.y -= r.speed;
        r.x += randRange(0.3);

        // Draw rocket trail
        for (var t = 0; t < r.trail.length; t++) {
            var ta = (t / r.trail.length) * 0.8;
            ctx.a.beginPath();
            ctx.a.arc(r.trail[t].x, r.trail[t].y, 1.2, 0, TAU);
            ctx.a.fillStyle = 'rgba(255, 220, 150, ' + ta + ')';
            ctx.a.fill();
        }

        // Draw rocket head
        ctx.a.beginPath();
        ctx.a.arc(r.x, r.y, 2, 0, TAU);
        ctx.a.fillStyle = 'rgba(255, 255, 220, 1)';
        ctx.a.fill();

        // Explode when reaching target
        if (r.y <= r.targetY) {
            fwExplode(r);
            fw.rockets.splice(i, 1);
        }
    }

    // Update and draw sparks
    for (var i = fw.sparks.length - 1; i >= 0; i--) {
        var s = fw.sparks[i];
        s.x += s.vx;
        s.y += s.vy;
        s.vy += s.gravity;
        s.vx *= 0.985;
        s.vy *= 0.985;
        s.life -= s.decay;

        if (s.life <= 0) {
            fw.sparks.splice(i, 1);
            continue;
        }

        var a = s.life;
        // Sparkle twinkle
        if (s.sparkle && random() < 0.3) a *= 0.3 + random() * 0.7;

        ctx.a.beginPath();
        ctx.a.arc(s.x, s.y, s.radius * s.life, 0, TAU);
        ctx.a.fillStyle = 'rgba(' + s.r + ',' + s.g + ',' + s.b + ',' + a + ')';
        ctx.a.fill();
    }

    // Copy to display canvas (no glow post-process, just direct copy)
    ctx.b.clearRect(0, 0, w, h);
    ctx.b.drawImage(canvas.a, 0, 0);
};

// --- Start ---
function init() {
    var target = effectTarget === 'hero' ? document.querySelector('.hero') : document.querySelector('.navbar');
    if (!target) {
        // If hero not found, fall back to navbar
        if (effectTarget === 'hero') {
            target = document.querySelector('.navbar');
        }
        if (!target) return;
    }
    setup();
    window.addEventListener('resize', function() {
        resize();
        // Only reinitialize effects that need it (skip effects with persistent state)
        if (effect === 'fireflies' || effect === 'fireworks' || effect === 'haunted') return;
        if (effectSetupFns[effect]) effectSetupFns[effect]();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Pause when hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden && animId) {
        cancelAnimationFrame(animId);
        animId = null;
    } else if (!document.hidden && !animId && canvas) {
        draw();
    }
});

})();
