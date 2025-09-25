// Fungsi JavaScript yang dipertingkatkan dengan animasi moden
document.addEventListener('DOMContentLoaded', function() {
    initAnimations();
    initInteractiveEffects();
    initParticleBackground();
    initStickyNavbar();
});

// Bar navigasi telus melekat
function initStickyNavbar() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

// Permulaan animasi yang dipertingkatkan
function initAnimations() {
    const cards = document.querySelectorAll('.card');
    const statCards = document.querySelectorAll('.stat-card');
    
    // Animasi kad berperingkat
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.15}s`;
        card.classList.add('fade-in');
        
        // Tambah kesan condong semasa hover
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
        });
    });
    
    // Animasi terapung kad statistik
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
        card.classList.add('slide-in-left');
        setTimeout(() => {
            card.classList.add('floating');
        }, 800 + (index * 200));
    });
}

// Kesan interaktif
function initInteractiveEffects() {
    // Kesan riak butang
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
}

// Kesan latar belakang zarah
function initParticleBackground() {
    const canvas = document.createElement('canvas');
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
        opacity: 0.3;
    `;
    document.body.appendChild(canvas);
    
    const ctx = canvas.getContext('2d');
    const particles = [];
    
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    function createParticle() {
        return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            size: Math.random() * 2 + 1,
            opacity: Math.random() * 0.5 + 0.2
        };
    }
    
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        particles.forEach((particle, index) => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;
            
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${particle.opacity})`;
            ctx.fill();
        });
        
        requestAnimationFrame(animate);
    }
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Cipta zarah
    for (let i = 0; i < 50; i++) {
        particles.push(createParticle());
    }
    
    animate();
}

// Pemeriksa kekuatan kata laluan
function checkPasswordStrength(password) {
    const strength = {
        score: 0,
        feedback: []
    };
    
    if (password.length >= 8) strength.score++;
    else strength.feedback.push('Sekurang-kurangnya 8 aksara');
    
    if (/[A-Z]/.test(password)) strength.score++;
    else strength.feedback.push('Satu huruf besar');
    
    if (/[a-z]/.test(password)) strength.score++;
    else strength.feedback.push('Satu huruf kecil');
    
    if (/\d/.test(password)) strength.score++;
    else strength.feedback.push('Satu nombor');
    
    return strength;
}

// Carian dipertingkatkan dengan animasi
function searchCandidates(query) {
    const candidates = document.querySelectorAll('.candidate-card');
    
    candidates.forEach((card, index) => {
        const name = card.querySelector('.candidate-name').textContent.toLowerCase();
        const description = card.querySelector('.candidate-description').textContent.toLowerCase();
        
        if (name.includes(query.toLowerCase()) || description.includes(query.toLowerCase())) {
            card.style.display = 'block';
            card.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s both`;
        } else {
            card.style.animation = 'fadeOut 0.3s ease-out both';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    });
}