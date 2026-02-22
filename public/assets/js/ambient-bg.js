/**
 * Ambient Canvas Backgrounds for Apparix
 * Based on crnacura/AmbientCanvasBackgrounds (Codrops)
 * Renders inside the navbar header area
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
var target = config.target || 'navbar'; // 'navbar' or 'fullpage'

if (effect === 'none' || window.innerWidth < 769) return;

// --- Color helpers ---
function hexToHsl(hex) {
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

var hsl1 = hexToHsl(color1);
var hsl2 = hexToHsl(color2);

// --- Canvas state ---
var container, canvas, ctx, center, tick, simplex, particleProps;
var w, h, animId;

// --- Swirl config ---
var swirlConfig = {
    particleCount: 500,
    particlePropCount: 9,
    rangeY: 50,
    baseTTL: 50,
    rangeTTL: 150,
    baseSpeed: 0.1,
    rangeSpeed: 1.5,
    baseRadius: 1,
    rangeRadius: 3,
    noiseSteps: 8,
    xOff: 0.00125,
    yOff: 0.00125,
    zOff: 0.0005
};

// --- Stars config ---
var starsConfig = {
    particleCount: 300,
    particlePropCount: 10,
    baseTTL: 80,
    rangeTTL: 200,
    baseSpeed: 0.05,
    rangeSpeed: 0.3,
    baseRadius: 1,
    rangeRadius: 3
};

// --- Aurora config ---
var auroraConfig = {
    rayCount: 6,
    rays: []
};

// --- Fireflies config ---
var firefliesConfig = {
    particleCount: 60,
    particlePropCount: 11
};

function setup() {
    createCanvas();
    resize();

    if (effect === 'swirl') initSwirl();
    else if (effect === 'stars') initStars();
    else if (effect === 'aurora') initAurora();
    else if (effect === 'coalesce') initCoalesce();
    else if (effect === 'fireflies') initFireflies();

    draw();
}

function createCanvas() {
    container = document.querySelector('.navbar');
    if (!container) return;

    // Make navbar position relative for canvas positioning
    container.style.position = container.style.position || 'sticky';
    container.style.overflow = 'hidden';

    canvas = {
        a: document.createElement('canvas'),
        b: document.createElement('canvas')
    };
    canvas.b.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;opacity:' + opacity + ';z-index:0;';
    canvas.b.setAttribute('aria-hidden', 'true');

    // Insert canvas as first child of navbar
    container.insertBefore(canvas.b, container.firstChild);

    ctx = {
        a: canvas.a.getContext('2d'),
        b: canvas.b.getContext('2d')
    };
    center = [];

    // Make navbar children above canvas
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

function draw() {
    if (!canvas) return;
    tick++;

    ctx.a.clearRect(0, 0, w, h);
    ctx.b.clearRect(0, 0, w, h);

    if (effect === 'swirl') drawSwirl();
    else if (effect === 'stars') drawStars();
    else if (effect === 'aurora') drawAurora();
    else if (effect === 'coalesce') drawCoalesce();
    else if (effect === 'fireflies') drawFireflies();

    renderGlow();
    renderToScreen();

    animId = requestAnimationFrame(draw);
}

// ========== SWIRL ==========
function initSwirl() {
    tick = 0;
    simplex = new SimplexNoise();
    var total = swirlConfig.particleCount * swirlConfig.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += swirlConfig.particlePropCount) {
        initSwirlParticle(i);
    }
}

function initSwirlParticle(i) {
    var x = rand(w);
    var y = center[1] + randRange(swirlConfig.rangeY);
    var vx = 0, vy = 0, life = 0;
    var ttl = swirlConfig.baseTTL + rand(swirlConfig.rangeTTL);
    var speed = swirlConfig.baseSpeed + rand(swirlConfig.rangeSpeed);
    var radius = swirlConfig.baseRadius + rand(swirlConfig.rangeRadius);
    // Blend between color1 hue and color2 hue
    var hue = random() > 0.5 ? hsl1.h + rand(30) - 15 : hsl2.h + rand(30) - 15;
    particleProps.set([x, y, vx, vy, life, ttl, speed, radius, hue], i);
}

function drawSwirl() {
    var c = swirlConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        updateSwirlParticle(i);
    }
}

function updateSwirlParticle(i) {
    var c = swirlConfig;
    var x = particleProps[i];
    var y = particleProps[i+1];
    var n = simplex.noise3D(x * c.xOff, y * c.yOff, tick * c.zOff) * c.noiseSteps * TAU;
    var vx = lerp(particleProps[i+2], cos(n), 0.5);
    var vy = lerp(particleProps[i+3], sin(n), 0.5);
    var life = particleProps[i+4];
    var ttl = particleProps[i+5];
    var speed = particleProps[i+6];
    var x2 = x + vx * speed;
    var y2 = y + vy * speed;
    var radius = particleProps[i+7];
    var hue = particleProps[i+8];

    // Draw as line segment with glow
    ctx.a.save();
    ctx.a.lineCap = 'round';
    ctx.a.lineWidth = radius;
    var sat = hue === hsl2.h ? hsl2.s : hsl1.s;
    var lit = hue === hsl2.h ? Math.min(hsl2.l + 20, 95) : Math.min(hsl1.l + 30, 90);
    ctx.a.strokeStyle = 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + fadeInOut(life, ttl) + ')';
    ctx.a.beginPath();
    ctx.a.moveTo(x, y);
    ctx.a.lineTo(x2, y2);
    ctx.a.stroke();
    ctx.a.closePath();
    ctx.a.restore();

    life++;
    particleProps[i] = x2;
    particleProps[i+1] = y2;
    particleProps[i+2] = vx;
    particleProps[i+3] = vy;
    particleProps[i+4] = life;

    if (x2 > w || x2 < 0 || y2 > h || y2 < 0 || life > ttl) {
        initSwirlParticle(i);
    }
}

// ========== STARS / FAIRY LIGHTS ==========
function initStars() {
    tick = 0;
    simplex = new SimplexNoise();
    var c = starsConfig;
    var total = c.particleCount * c.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += c.particlePropCount) {
        initStarParticle(i);
    }
}

function initStarParticle(i) {
    var c = starsConfig;
    var x = rand(w), y = rand(h);
    var vx = 0, vy = 0, life = 0;
    var ttl = c.baseTTL + rand(c.rangeTTL);
    var speed = c.baseSpeed + rand(c.rangeSpeed);
    var radius = c.baseRadius + rand(c.rangeRadius);
    var hue = random() > 0.4 ? hsl1.h : hsl2.h;
    var twinklePhase = rand(TAU);
    particleProps.set([x, y, vx, vy, life, ttl, speed, radius, hue, twinklePhase], i);
}

function drawStars() {
    var c = starsConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var life = particleProps[i+4], ttl = particleProps[i+5];
        var speed = particleProps[i+6], radius = particleProps[i+7];
        var hue = particleProps[i+8], phase = particleProps[i+9];

        // Gentle noise drift
        var n = simplex.noise3D(x * 0.002, y * 0.002, tick * 0.0003);
        var vx = lerp(particleProps[i+2], cos(n * TAU) * 0.3, 0.1);
        var vy = lerp(particleProps[i+3], sin(n * TAU) * 0.3, 0.1);
        var x2 = x + vx * speed;
        var y2 = y + vy * speed;

        // Twinkle
        var twinkle = (sin(tick * 0.02 + phase) + 1) * 0.5;
        var alpha = fadeInOut(life, ttl) * (0.3 + twinkle * 0.7);
        var r = radius * (0.5 + twinkle * 0.5);

        var sat = hue === hsl2.h ? hsl2.s : hsl1.s;
        var lit = hue === hsl2.h ? Math.min(hsl2.l + 20, 95) : Math.min(hsl1.l + 30, 90);

        // Draw point
        ctx.a.beginPath();
        ctx.a.arc(x2, y2, r, 0, TAU);
        ctx.a.fillStyle = 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + alpha + ')';
        ctx.a.fill();

        life++;
        particleProps[i] = x2;
        particleProps[i+1] = y2;
        particleProps[i+2] = vx;
        particleProps[i+3] = vy;
        particleProps[i+4] = life;

        if (x2 > w || x2 < 0 || y2 > h || y2 < 0 || life > ttl) {
            initStarParticle(i);
        }
    }
}

// ========== AURORA ==========
function initAurora() {
    tick = 0;
    simplex = new SimplexNoise();
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
}

function drawAurora() {
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
            var t = j / steps;
            var yy = t * h;
            var wave = simplex.noise2D(t * 3 + ray.offset, tick * 0.003) * 30;
            var narrowing = 1 - t * 0.6;
            ctx.a.lineTo(ray.x + xOff + wave + ray.width * narrowing / 2, yy);
        }
        for (var j = steps; j >= 0; j--) {
            var t = j / steps;
            var yy = t * h;
            var wave = simplex.noise2D(t * 3 + ray.offset + 5, tick * 0.003) * 30;
            var narrowing = 1 - t * 0.6;
            ctx.a.lineTo(ray.x + xOff + wave - ray.width * narrowing / 2, yy);
        }
        ctx.a.closePath();
        ctx.a.fillStyle = gradient;
        ctx.a.fill();
    }
}

// ========== COALESCE ==========
function initCoalesce() {
    tick = 0;
    simplex = new SimplexNoise();
    var count = 400, propCount = 9;
    swirlConfig.particleCount = count;
    swirlConfig.particlePropCount = propCount;
    particleProps = new Float32Array(count * propCount);
    for (var i = 0; i < count * propCount; i += propCount) {
        initCoalesceParticle(i);
    }
}

function initCoalesceParticle(i) {
    var angle = rand(TAU);
    var dist = rand(Math.max(w, h) * 0.8);
    var x = center[0] + cos(angle) * dist;
    var y = center[1] + sin(angle) * dist;
    var life = 0, ttl = 60 + rand(120);
    var speed = 0.2 + rand(1.2);
    var radius = 1 + rand(3);
    var hue = random() > 0.5 ? hsl1.h : hsl2.h;
    particleProps.set([x, y, 0, 0, life, ttl, speed, radius, hue], i);
}

function drawCoalesce() {
    var count = swirlConfig.particleCount, propCount = swirlConfig.particlePropCount;
    for (var i = 0; i < count * propCount; i += propCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var life = particleProps[i+4], ttl = particleProps[i+5];
        var speed = particleProps[i+6], radius = particleProps[i+7], hue = particleProps[i+8];

        var dx = center[0] - x, dy = center[1] - y;
        var dist = sqrt(dx * dx + dy * dy);
        var angleToCenter = Math.atan2(dy, dx);
        var t = Math.min(1, life / ttl);
        // Interpolate from center-directed to spiral
        var spiralAngle = angleToCenter + PI / 2;
        var moveAngle = lerp(angleToCenter, spiralAngle, t * 0.7);

        var x2 = x + cos(moveAngle) * speed;
        var y2 = y + sin(moveAngle) * speed;

        var sat = hue === hsl2.h ? hsl2.s : hsl1.s;
        var lit = hue === hsl2.h ? Math.min(hsl2.l + 20, 95) : Math.min(hsl1.l + 30, 90);

        ctx.a.save();
        ctx.a.lineCap = 'round';
        ctx.a.lineWidth = radius;
        ctx.a.strokeStyle = 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + fadeInOut(life, ttl) + ')';
        ctx.a.beginPath();
        ctx.a.moveTo(x, y);
        ctx.a.lineTo(x2, y2);
        ctx.a.stroke();
        ctx.a.restore();

        life++;
        particleProps[i] = x2;
        particleProps[i+1] = y2;
        particleProps[i+4] = life;

        if (dist < 15 || life > ttl || x2 < 0 || x2 > w || y2 < 0 || y2 > h) {
            initCoalesceParticle(i);
        }
    }
}

// ========== FIREFLIES ==========
function initFireflies() {
    tick = 0;
    simplex = new SimplexNoise();
    var c = firefliesConfig;
    var total = c.particleCount * c.particlePropCount;
    particleProps = new Float32Array(total);
    for (var i = 0; i < total; i += c.particlePropCount) {
        initFireflyParticle(i);
    }
}

function initFireflyParticle(i) {
    var x = rand(w), y = rand(h);
    var vx = randRange(0.3), vy = randRange(0.3);
    var radius = 2 + rand(4);
    var hue = random() > 0.3 ? hsl1.h : hsl2.h;
    var sat = hue === hsl2.h ? hsl2.s : hsl1.s;
    var lit = hue === hsl2.h ? Math.min(hsl2.l + 20, 95) : Math.min(hsl1.l + 30, 90);
    var pulsePhase = rand(TAU);
    var pulseSpeed = 0.01 + rand(0.03);
    var wanderAngle = rand(TAU);
    particleProps.set([x, y, vx, vy, radius, hue, sat, lit, pulsePhase, pulseSpeed, wanderAngle], i);
}

function drawFireflies() {
    var c = firefliesConfig;
    for (var i = 0; i < c.particleCount * c.particlePropCount; i += c.particlePropCount) {
        var x = particleProps[i], y = particleProps[i+1];
        var vx = particleProps[i+2], vy = particleProps[i+3];
        var radius = particleProps[i+4];
        var hue = particleProps[i+5], sat = particleProps[i+6], lit = particleProps[i+7];
        var phase = particleProps[i+8], pSpeed = particleProps[i+9];
        var wanderAngle = particleProps[i+10];

        // Wander
        wanderAngle += randRange(0.05);
        vx += cos(wanderAngle) * 0.01;
        vy += sin(wanderAngle) * 0.01;
        vx *= 0.98;
        vy *= 0.98;
        x += vx;
        y += vy;

        // Soft bounds
        if (x < 0) vx += 0.1;
        if (x > w) vx -= 0.1;
        if (y < 0) vy += 0.1;
        if (y > h) vy -= 0.1;

        var pulse = (sin(tick * pSpeed + phase) + 1) * 0.5;
        var alpha = 0.1 + pulse * 0.6;
        var r = radius * (0.5 + pulse * 0.5);

        ctx.a.beginPath();
        ctx.a.arc(x, y, r, 0, TAU);
        ctx.a.fillStyle = 'hsla(' + hue + ',' + sat + '%,' + lit + '%,' + alpha + ')';
        ctx.a.fill();

        particleProps[i] = x;
        particleProps[i+1] = y;
        particleProps[i+2] = vx;
        particleProps[i+3] = vy;
        particleProps[i+10] = wanderAngle;
    }
}

// --- Start ---
function init() {
    if (!document.querySelector('.navbar')) return;
    setup();
    window.addEventListener('resize', function() {
        resize();
        // Reinitialize particles on resize
        if (effect === 'swirl') initSwirl();
        else if (effect === 'stars') initStars();
        else if (effect === 'aurora') initAurora();
        else if (effect === 'coalesce') initCoalesce();
        else if (effect === 'fireflies') initFireflies();
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
