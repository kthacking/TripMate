<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Show cursor if user is not logged in OR is logged in as a student
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'student')):
?>
<style>
    body, * {
        cursor: none !important;
    }
    #cursor-canvas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999;
    }
</style>
<canvas id="cursor-canvas"></canvas>

<script>
    const canvas = document.getElementById('cursor-canvas');
    const ctx = canvas.getContext('2d');

    let width, height;
    let mouse = { x: window.innerWidth/2, y: window.innerHeight/2 };
    let lastMouse = { x: window.innerWidth/2, y: window.innerHeight/2 };
    let mouseVel = { x: 0, y: 0 };
    
    const points = [];
    const maxPoints = 38; 
    const particles = []; 
    
    const butterflies = [];
    const backgroundLeaves = [];

    function init() {
        resize();
        for(let i=0; i<maxPoints; i++) {
            points.push({x: mouse.x, y: mouse.y});
        }
        window.addEventListener('resize', resize);
        window.addEventListener('mousemove', updateMouse);
        window.addEventListener('touchmove', updateTouch, { passive: false });
        requestAnimationFrame(animate);
    }

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    }

    function updateMouse(e) {
        mouseVel.x = e.clientX - mouse.x;
        mouseVel.y = e.clientY - mouse.y;
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        
        const speed = Math.sqrt(mouseVel.x**2 + mouseVel.y**2);
        
        if (Math.random() < 0.05 * speed) {
            spawnParticle(mouse.x, mouse.y, mouseVel);
        }

        if (speed > 25 && Math.random() < 0.15) {
            spawnButterfly(mouse.x, mouse.y);
        }
    }

    function updateTouch(e) {
        e.preventDefault();
        const touch = e.touches[0];
        mouseVel.x = touch.clientX - mouse.x;
        mouseVel.y = touch.clientY - mouse.y;
        mouse.x = touch.clientX;
        mouse.y = touch.clientY;
        spawnParticle(mouse.x, mouse.y, mouseVel);
        
        const speed = Math.sqrt(mouseVel.x**2 + mouseVel.y**2);
        if (speed > 20) spawnButterfly(mouse.x, mouse.y);
    }

    function spawnParticle(x, y, vel) {
        const rand = Math.random();
        let type = 'leaf-yellow';
        if (rand > 0.6) type = 'petal';
        else if (rand > 0.3) type = 'leaf-orange';

        particles.push({
            x: x, 
            y: y,
            vx: vel.x * 0.2 + (Math.random() - 0.5) * 2,
            vy: vel.y * 0.2 + 1 + Math.random() * 2,
            rotation: Math.random() * Math.PI * 2,
            rSpeed: (Math.random() - 0.5) * 0.15,
            scale: 0.5 + Math.random() * 0.6,
            type: type,
            life: 1.0
        });
    }

    function spawnButterfly(x, y) {
        butterflies.push({
            x: x,
            y: y,
            vx: (Math.random() - 0.5) * 6,
            vy: (Math.random() - 0.5) * 6 - 2,
            size: 5 + Math.random() * 5,
            phase: Math.random() * Math.PI * 2,
            life: 1.0,
            color: `rgba(255, ${180 + Math.random() * 40}, ${200 + Math.random() * 30},`
        });
    }

    function spawnBackgroundLeaf() {
        const isOrange = Math.random() > 0.5;
        backgroundLeaves.push({
            x: Math.random() * width,
            y: -20,
            vx: (Math.random() - 0.5) * 1.5,
            vy: 0.8 + Math.random() * 1.2,
            rotation: Math.random() * Math.PI * 2,
            rSpeed: (Math.random() - 0.5) * 0.05,
            scale: 0.6 + Math.random() * 0.4,
            life: 1.0,
            drift: Math.random() * Math.PI * 2,
            type: isOrange ? 'orange' : 'yellow'
        });
    }

    function drawButterfly(b, time) {
        ctx.save();
        ctx.translate(b.x, b.y);
        ctx.rotate(Math.atan2(b.vy, b.vx) + Math.PI/2);
        ctx.globalAlpha = b.life * 0.7;

        const flap = Math.sin(time * 0.015 + b.phase);
        const wingWidth = b.size * flap;

        ctx.fillStyle = b.color + ' 0.6)';
        ctx.beginPath();
        ctx.ellipse(-wingWidth/2 - 1, 0, Math.abs(wingWidth), b.size * 1.2, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.ellipse(wingWidth/2 + 1, 0, Math.abs(wingWidth), b.size * 1.2, 0, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = 'rgba(100, 50, 50, 0.3)';
        ctx.beginPath();
        ctx.ellipse(0, 0, 1, b.size * 0.8, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }

    function drawFlower(x, y, scale, rotation = 0, opacity = 1) {
        ctx.save();
        ctx.translate(x, y);
        ctx.scale(scale * 1.8, scale * 1.8);
        ctx.rotate(rotation); 
        ctx.globalAlpha = opacity;

        const petals = 5;
        for (let i = 0; i < petals; i++) {
            ctx.rotate((Math.PI * 2) / petals);
            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.bezierCurveTo(-10, -15, 10, -15, 0, 0);
            
            const petalGrad = ctx.createRadialGradient(0, -5, 0, 0, -5, 12);
            petalGrad.addColorStop(0, '#ffffff');
            petalGrad.addColorStop(1, '#f9faf9');
            ctx.fillStyle = petalGrad;
            ctx.fill();
        }
        ctx.beginPath();
        ctx.arc(0, 0, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#fdd835';
        ctx.fill();
        
        ctx.fillStyle = '#f9a825';
        for(let j=0; j<5; j++) {
            ctx.beginPath();
            ctx.arc(Math.cos(j)*1.5, Math.sin(j)*1.5, 0.6, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.restore();
    }

    function drawLeaf(x, y, angle, scale, flip = 1, opacity = 1, colorType = 'yellow') {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.scale(scale * flip * 1.6, scale * 1.6);
        ctx.globalAlpha = opacity;

        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.bezierCurveTo(8, -15, 20, -18, 30, 0);
        ctx.bezierCurveTo(20, 18, 8, 15, 0, 0);
        
        const grad = ctx.createLinearGradient(0, 0, 30, 0);
        if (colorType === 'orange') {
            grad.addColorStop(0, '#e65100'); 
            grad.addColorStop(1, '#ffb74d'); 
        } else {
            grad.addColorStop(0, '#fbc02d'); 
            grad.addColorStop(1, '#fff176'); 
        }
        ctx.fillStyle = grad;
        ctx.fill();
        
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(25, 0);
        ctx.strokeStyle = 'rgba(0,0,0,0.1)'; 
        ctx.lineWidth = 1;
        ctx.stroke();
        
        ctx.restore();
    }

    function animate(time) {
        ctx.clearRect(0, 0, width, height);

        if (Math.random() < 0.008) { 
            spawnBackgroundLeaf();
        }

        for (let i = backgroundLeaves.length - 1; i >= 0; i--) {
            const l = backgroundLeaves[i];
            l.y += l.vy;
            l.x += l.vx + Math.sin(time * 0.001 + l.drift) * 1.0;
            l.rotation += l.rSpeed;
            if (l.y > height * 0.7) l.life -= 0.005;
            
            if (l.life <= 0 || l.y > height + 20) {
                backgroundLeaves.splice(i, 1);
                continue;
            }
            drawLeaf(l.x, l.y, l.rotation, l.scale, 1, l.life * 0.6, l.type);
        }

        lastMouse.x += (mouse.x - lastMouse.x) * 0.15;
        lastMouse.y += (mouse.y - lastMouse.y) * 0.15;

        points[0].x = lastMouse.x;
        points[0].y = lastMouse.y;

        for (let i = 1; i < points.length; i++) {
            const p = points[i];
            const prev = points[i-1];
            
            const dx = p.x - prev.x;
            const dy = p.y - prev.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const minDistance = 14; 

            if (distance > minDistance) {
                const angle = Math.atan2(dy, dx);
                p.x = prev.x + Math.cos(angle) * minDistance;
                p.y = prev.y + Math.sin(angle) * minDistance;
            }
            
            p.y += Math.sin(time * 0.0015 + i * 0.3) * 0.4;
            p.x += Math.cos(time * 0.001 + i * 0.3) * 0.2;
        }

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.x += p.vx + Math.sin(time * 0.002 + i) * 1.5;
            p.y += p.vy;
            p.rotation += p.rSpeed;
            p.life -= 0.005;

            if (p.life <= 0 || p.y > height + 50) {
                particles.splice(i, 1);
                continue;
            }

            if (p.type === 'petal') {
                drawFlower(p.x, p.y, p.scale, p.rotation, p.life);
            } else {
                drawLeaf(p.x, p.y, p.rotation, p.scale, 1, p.life, p.type === 'leaf-orange' ? 'orange' : 'yellow');
            }
        }

        for (let i = butterflies.length - 1; i >= 0; i--) {
            const b = butterflies[i];
            b.x += b.vx; b.y += b.vy;
            b.vx += (Math.random() - 0.5) * 0.5;
            b.vy += (Math.random() - 0.5) * 0.5;
            b.life -= 0.01;
            if (b.life <= 0) { butterflies.splice(i, 1); continue; }
            drawButterfly(b, time);
        }

        if (points.length > 1) {
            for (let i = points.length - 2; i >= 0; i--) {
                const p1 = points[i];
                const p2 = points[i+1];
                const size = (maxPoints - i) / maxPoints;
                const thickness = Math.max(2, size * 14);

                ctx.lineCap = 'round'; ctx.lineJoin = 'round';
                ctx.beginPath();
                ctx.strokeStyle = '#2b1d1a'; 
                ctx.lineWidth = thickness;
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.stroke();

                if (i % 2 === 0) {
                    ctx.beginPath();
                    ctx.strokeStyle = '#3e2723';
                    ctx.lineWidth = thickness * 0.5;
                    ctx.moveTo(p1.x, p1.y + 1);
                    ctx.lineTo(p2.x, p2.y + 1);
                    ctx.stroke();
                }
            }

            points.forEach((p, i) => {
                if (i > 2 && i % 4 === 0 && i < points.length - 3) {
                    const next = points[i+1];
                    const angle = Math.atan2(p.y - next.y, p.x - next.x);
                    const scale = (maxPoints - i) / maxPoints;
                    const sway = Math.sin(time * 0.002 + i) * 0.2;

                    if (i % 8 === 0) {
                        const stemX = p.x + Math.cos(angle + 1.5) * 8;
                        const stemY = p.y + Math.sin(angle + 1.5) * 8;
                        ctx.beginPath();
                        ctx.strokeStyle = '#1a110f';
                        ctx.lineWidth = 1.5;
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(stemX, stemY);
                        ctx.stroke();
                        drawFlower(stemX, stemY, scale * 0.8, sway);
                    } else {
                        const isOrangeLeaf = i % 3 === 0;
                        drawLeaf(p.x, p.y, angle + 0.8 + sway, scale * 0.9, 1, 1, isOrangeLeaf ? 'orange' : 'yellow');
                        drawLeaf(p.x, p.y, angle - 0.8 - sway, scale * 0.6, -1, 1, isOrangeLeaf ? 'orange' : 'yellow');
                    }
                }
            });
        }

        ctx.beginPath();
        ctx.arc(lastMouse.x, lastMouse.y, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#fbc02d'; 
        ctx.fill();

        requestAnimationFrame(animate);
    }

    window.onload = init;
</script>
<?php endif; ?>
