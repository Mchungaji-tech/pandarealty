/**
 * Panda Realty - Main Front-End JavaScript Engine
 * Designed & Developed by TekTrend
 */

document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();
    initCardSliders();
    initCurrencySwitcher();
    initFilters();
    initLightbox();
    initLiveToasts();
    initPromoModal();
    initInstallmentCalculator();
    initScrollAnimations();
    initNavbarScroll();
});

/* =======================================================
   1. NAVBAR SCROLL EFFECT
   ======================================================= */
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

/* =======================================================
   2. DYNAMIC HERO SLIDER
   ======================================================= */
let currentHeroIndex = 0;
let heroSliderTimer = null;

function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');
    if (!slides.length) return;

    function showSlide(index) {
        slides.forEach((s, i) => {
            s.classList.toggle('active', i === index);
        });
        dots.forEach((d, i) => {
            d.classList.toggle('active', i === index);
        });
        currentHeroIndex = index;
    }

    window.nextHeroSlide = function() {
        const next = (currentHeroIndex + 1) % slides.length;
        showSlide(next);
        resetHeroTimer();
    };

    window.prevHeroSlide = function() {
        const prev = (currentHeroIndex - 1 + slides.length) % slides.length;
        showSlide(prev);
        resetHeroTimer();
    };

    window.goToHeroSlide = function(index) {
        showSlide(index);
        resetHeroTimer();
    };

    function startHeroTimer() {
        heroSliderTimer = setInterval(window.nextHeroSlide, 6000);
    }

    function resetHeroTimer() {
        if (heroSliderTimer) clearInterval(heroSliderTimer);
        startHeroTimer();
    }

    startHeroTimer();
}

/* =======================================================
   3. PROPERTY CARDS MINI-IMAGE CAROUSELS
   ======================================================= */
function initCardSliders() {
    document.querySelectorAll('.property-card').forEach(card => {
        const slides = card.querySelectorAll('.card-slide-img');
        const dots = card.querySelectorAll('.card-dot');
        const prevBtn = card.querySelector('.card-slider-prev');
        const nextBtn = card.querySelector('.card-slider-next');

        if (slides.length <= 1) return;

        let activeIdx = 0;

        function updateSlide(idx) {
            slides.forEach((s, i) => s.classList.toggle('active', i === idx));
            dots.forEach((d, i) => d.classList.toggle('active', i === idx));
            activeIdx = idx;
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                updateSlide((activeIdx + 1) % slides.length);
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                updateSlide((activeIdx - 1 + slides.length) % slides.length);
            });
        }
    });
}

/* =======================================================
   4. CURRENCY SWITCHER (KSH / USD)
   ======================================================= */
function initCurrencySwitcher() {
    const exchangeRate = parseFloat(window.USD_EXCHANGE_RATE || 130.00);

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return 'KES';
    }

    function setCookie(name, val, days = 30) {
        const d = new Date();
        d.setTime(d.getTime() + (days*24*60*60*1000));
        document.cookie = `${name}=${val};path=/;expires=${d.toUTCString()}`;
    }

    let activeCurrency = getCookie('panda_currency') || 'KES';

    window.switchCurrency = function(curr) {
        activeCurrency = curr.toUpperCase();
        setCookie('panda_currency', activeCurrency);

        document.querySelectorAll('.currency-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.curr === activeCurrency);
        });

        // Recalculate and update all price display elements
        document.querySelectorAll('[data-price-kes]').forEach(el => {
            const kes = parseFloat(el.getAttribute('data-price-kes')) || 0;
            const period = el.getAttribute('data-price-period') || '';

            if (activeCurrency === 'USD') {
                const usd = Math.round(kes / exchangeRate);
                el.innerHTML = `$${usd.toLocaleString()}${period ? `<span class="period">${period}</span>` : ''}`;
            } else {
                el.innerHTML = `KSh ${Math.round(kes).toLocaleString()}${period ? `<span class="period">${period}</span>` : ''}`;
            }
        });

        // Trigger Installment Calculator update if available
        if (typeof window.recalcInstallments === 'function') {
            window.recalcInstallments();
        }
    };

    // Apply active currency on load
    window.switchCurrency(activeCurrency);
}

/* =======================================================
   5. PROPERTY FILTER ENGINE (INCLUDING STUDIO APARTMENTS)
   ======================================================= */
function initFilters() {
    const filterTags = document.querySelectorAll('.filter-tag');
    const propertyCards = document.querySelectorAll('.property-card');

    if (!filterTags.length || !propertyCards.length) return;

    filterTags.forEach(tag => {
        tag.addEventListener('click', () => {
            filterTags.forEach(t => t.classList.remove('active'));
            tag.classList.add('active');

            const filter = tag.dataset.filter;

            propertyCards.forEach(card => {
                const type = card.dataset.type || '';
                const category = card.dataset.category || '';
                const status = card.dataset.status || '';

                let show = false;

                if (filter === 'all') {
                    show = true;
                } else if (filter === 'sale') {
                    show = (category === 'sale');
                } else if (filter === 'rent') {
                    show = (category === 'rent');
                } else if (filter === 'studio') {
                    show = (type === 'studio');
                } else if (filter === 'land') {
                    show = (type === 'land');
                } else if (filter === 'construction') {
                    show = (status === 'under_construction');
                } else if (filter === 'completed') {
                    show = (status === 'available' || status === 'completed');
                }

                card.style.display = show ? 'flex' : 'none';
            });
        });
    });
}

/* =======================================================
   6. FULLSCREEN PROPERTY LIGHTBOX GALLERY
   ======================================================= */
let currentLightboxImages = [];
let currentLightboxIdx = 0;

function initLightbox() {
    const modal = document.getElementById('lightboxModal');
    if (!modal) return;

    const img = document.getElementById('lightboxImg');
    const thumbsContainer = document.getElementById('lightboxThumbnails');

    window.openLightbox = function(images, initialIdx = 0) {
        if (!images || !images.length) return;
        currentLightboxImages = images;
        currentLightboxIdx = initialIdx;

        renderLightboxView();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    };

    window.nextLightboxImg = function() {
        currentLightboxIdx = (currentLightboxIdx + 1) % currentLightboxImages.length;
        renderLightboxView();
    };

    window.prevLightboxImg = function() {
        currentLightboxIdx = (currentLightboxIdx - 1 + currentLightboxImages.length) % currentLightboxImages.length;
        renderLightboxView();
    };

    function renderLightboxView() {
        if (!img) return;
        img.src = currentLightboxImages[currentLightboxIdx];

        if (thumbsContainer) {
            thumbsContainer.innerHTML = '';
            currentLightboxImages.forEach((src, idx) => {
                const thumb = document.createElement('img');
                thumb.src = src;
                thumb.className = `lightbox-thumb ${idx === currentLightboxIdx ? 'active' : ''}`;
                thumb.onclick = () => {
                    currentLightboxIdx = idx;
                    renderLightboxView();
                };
                thumbsContainer.appendChild(thumb);
            });
        }
    }

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('active')) return;
        if (e.key === 'Escape') window.closeLightbox();
        if (e.key === 'ArrowRight') window.nextLightboxImg();
        if (e.key === 'ArrowLeft') window.prevLightboxImg();
    });
}

/* =======================================================
   7. LIVE PROPERTY SOLD & ACTIVITY TOAST NOTIFICATIONS
   ======================================================= */
function initLiveToasts() {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    let events = [];
    try {
        events = JSON.parse(container.getAttribute('data-events') || '[]');
    } catch (err) {
        events = [];
    }

    if (!Array.isArray(events) || !events.length) {
        events = [
        { icon: 'fas fa-check-circle', title: 'Plot Sold in Annex!', desc: 'Dr. Kipchumba just acquired a 50x100 prime plot.' },
        { icon: 'fas fa-key', title: 'Studio Apartment Booked', desc: 'A new tenant reserved a Pioneer Studio unit.' },
        { icon: 'fas fa-calendar-check', title: 'Site Tour Scheduled', desc: 'VIP viewing booked for Elgon View Royal Manor.' },
        { icon: 'fas fa-fire', title: 'High Demand in Annex', desc: 'Only 3 plots remaining in Annex Oasis Phase 2.' },
        { icon: 'fas fa-handshake', title: 'Installment Plan Activated', desc: 'Client signed 24-month payment scheme for West Indies Villa.' }
        ];
    }

    let eventIdx = 0;

    function triggerToast() {
        const item = events[eventIdx];
        eventIdx = (eventIdx + 1) % events.length;

        const toast = document.createElement('div');
        toast.className = 'live-toast';
        toast.innerHTML = `
            <div class="toast-icon"><i class="${item.icon}"></i></div>
            <div class="toast-body">
                <h5>${item.title}</h5>
                <p>${item.desc}</p>
                ${item.cta_url && item.cta_label ? `<a href="${item.cta_url}" class="toast-link" style="display:inline-block;margin-top:8px;font-size:12px;font-weight:600;color:#c5a059;">${item.cta_label}</a>` : ''}
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 600);
        }, 5000);
    }

    // Show first toast after 4s, then every 18s
    setTimeout(triggerToast, 4000);
    setInterval(triggerToast, 18000);
}

/* =======================================================
   8. PROMOTIONAL AD MODAL
   ======================================================= */
function initPromoModal() {
    const modal = document.getElementById('promoModal');
    if (!modal) return;

    const dismissed = sessionStorage.getItem('panda_promo_dismissed');
    const thresholdPercent = Math.min(95, Math.max(5, parseInt(modal.dataset.scrollTrigger || '35', 10)));
    let shown = false;

    function maybeShowModal() {
        if (dismissed || shown) return;

        const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (scrollableHeight <= 0) return;

        const currentPercent = (window.scrollY / scrollableHeight) * 100;
        if (currentPercent >= thresholdPercent) {
            shown = true;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            window.removeEventListener('scroll', maybeShowModal);
        }
    }

    if (!dismissed) {
        window.addEventListener('scroll', maybeShowModal, { passive: true });
        maybeShowModal();
    }

    window.closePromoModal = function() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
        sessionStorage.setItem('panda_promo_dismissed', '1');
    };
}

/* =======================================================
   9. INSTALLMENT CALCULATOR
   ======================================================= */
function initInstallmentCalculator() {
    const depositSlider = document.getElementById('calcDepositSlider');
    const monthsSelect = document.getElementById('calcMonthsSelect');
    const priceInput = document.getElementById('calcPropertyPrice');

    if (!depositSlider || !monthsSelect || !priceInput) return;

    window.recalcInstallments = function() {
        const priceKES = parseFloat(priceInput.value) || 0;
        const depositPercent = parseInt(depositSlider.value) || 10;
        const months = parseInt(monthsSelect.value) || 12;

        const isUSD = (getCookie('panda_currency') === 'USD');
        const rate = parseFloat(window.USD_EXCHANGE_RATE || 130.00);

        const depositValKES = priceKES * (depositPercent / 100);
        const balanceKES = priceKES - depositValKES;
        const monthlyKES = months > 0 ? (balanceKES / months) : 0;

        document.getElementById('calcDepositPercentText').textContent = depositPercent + '%';

        if (isUSD) {
            const depUSD = Math.round(depositValKES / rate);
            const balUSD = Math.round(balanceKES / rate);
            const monthUSD = Math.round(monthlyKES / rate);

            document.getElementById('calcDepositVal').textContent = `$${depUSD.toLocaleString()}`;
            document.getElementById('calcBalanceVal').textContent = `$${balUSD.toLocaleString()}`;
            document.getElementById('calcMonthlyVal').textContent = `$${monthUSD.toLocaleString()} / mo`;
        } else {
            document.getElementById('calcDepositVal').textContent = `KSh ${Math.round(depositValKES).toLocaleString()}`;
            document.getElementById('calcBalanceVal').textContent = `KSh ${Math.round(balanceKES).toLocaleString()}`;
            document.getElementById('calcMonthlyVal').textContent = `KSh ${Math.round(monthlyKES).toLocaleString()} / mo`;
        }
    };

    depositSlider.addEventListener('input', window.recalcInstallments);
    monthsSelect.addEventListener('change', window.recalcInstallments);
    window.recalcInstallments();
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return 'KES';
}

/* =======================================================
   10. SCROLL REVEAL INTERSECTION OBSERVER
   ======================================================= */
function initScrollAnimations() {
    const reveals = document.querySelectorAll('.reveal-fade, .reveal-left, .reveal-right');
    if (!reveals.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    reveals.forEach(el => observer.observe(el));
}

/* =======================================================
   11. GENERIC MODAL CONTROLS & FAVORITES
   ======================================================= */
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.querySelectorAll('iframe[data-src]').forEach((frame) => {
            if (!frame.getAttribute('src')) {
                frame.setAttribute('src', frame.getAttribute('data-src'));
            }
        });
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function(idOrElem) {
    let modal = null;
    if (typeof idOrElem === 'string') {
        modal = document.getElementById(idOrElem);
    } else if (idOrElem instanceof HTMLElement) {
        modal = idOrElem.closest('.modal') || idOrElem.closest('.lightbox-modal') || idOrElem;
    }

    if (modal) {
        modal.querySelectorAll('iframe').forEach((frame) => {
            const currentSrc = frame.getAttribute('src') || '';
            if (currentSrc && !currentSrc.startsWith('javascript:')) {
                frame.setAttribute('data-src', currentSrc);
                frame.setAttribute('src', '');
            }
        });
        modal.classList.remove('active');
        modal.style.display = 'none';
        
        // Restore body scroll if no other active modals
        if (!document.querySelector('.modal.active') && !document.querySelector('.lightbox-modal.active')) {
            document.body.style.overflow = 'auto';
        }
    }
};

// Global click listener for backdrop closing and close buttons
document.addEventListener('click', (e) => {
    // Click outside modal content (on the overlay backdrop)
    if (e.target.classList.contains('modal') || e.target.classList.contains('lightbox-modal')) {
        window.closeModal(e.target);
    }

    // Click on any close button
    const closeBtn = e.target.closest('.modal-close, [data-close-modal], .lightbox-close');
    if (closeBtn) {
        const modal = closeBtn.closest('.modal') || closeBtn.closest('.lightbox-modal') || document.querySelector('.modal.active');
        if (modal) {
            window.closeModal(modal);
        }
    }
});

// Global Escape Key Listener to Close All Modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.keyCode === 27) {
        document.querySelectorAll('.modal.active, .modal[style*="display: flex"], .modal[style*="display:flex"], .lightbox-modal.active').forEach((m) => {
            window.closeModal(m);
        });
        if (typeof window.closeLightbox === 'function') {
            window.closeLightbox();
        }
    }
});

window.toggleFavorite = function(e, btn) {
    e.stopPropagation();
    btn.classList.toggle('active');
    const icon = btn.querySelector('i');
    if (btn.classList.contains('active')) {
        icon.className = 'fas fa-heart';
    } else {
        icon.className = 'far fa-heart';
    }
};

