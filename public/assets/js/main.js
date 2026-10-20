/**
 * Template Name: MediCare
 * Description: Dedicated Healthcare & Medical Services Website
 */

(function() {
    "use strict";

    /**
     * Apply .scrolled class to the body as the page is scrolled down
     */
    function toggleScrolled() {
        const selectBody = document.querySelector('body');
        const selectHeader = document.querySelector('#header');
        if (!selectHeader) return;
        if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
        window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
    }

    document.addEventListener('scroll', toggleScrolled);
    window.addEventListener('load', toggleScrolled);

    /**
     * Mobile nav toggle
     */
    const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

    function mobileNavToogle() {
        document.querySelector('body').classList.toggle('mobile-nav-active');
        if (mobileNavToggleBtn) {
            mobileNavToggleBtn.classList.toggle('bi-list');
            mobileNavToggleBtn.classList.toggle('bi-x');
        }
    }
    if (mobileNavToggleBtn) {
        mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
    }

    /**
     * Hide mobile nav on same-page/hash links
     */
    document.querySelectorAll('#navmenu a').forEach(navmenu => {
        navmenu.addEventListener('click', () => {
            if (document.querySelector('.mobile-nav-active') && !navmenu.classList.contains('toggle-dropdown')) {
                mobileNavToogle();
            }
        });
    });

    /**
     * Toggle mobile nav dropdowns
     */
    document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
        navmenu.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentNode.classList.toggle('active');
            if (this.parentNode.nextElementSibling) {
                this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
            }
            e.stopImmediatePropagation();
        });
    });

    /**
     * Scroll top button
     */
    let scrollTop = document.querySelector('.scroll-top');

    function toggleScrollTop() {
        if (scrollTop) {
            window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
        }
    }
    if (scrollTop) {
        scrollTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    window.addEventListener('load', toggleScrollTop);
    document.addEventListener('scroll', toggleScrollTop);

    /**
     * Animation on scroll function and init
     */
    function aosInit() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 600,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
        }
    }
    window.addEventListener('load', aosInit);

    /**
     * Init swiper sliders
     */
    function initSwiper() {
        if (typeof Swiper === 'undefined') return;
        document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
            let configElement = swiperElement.querySelector(".swiper-config");
            if (!configElement) return;
            try {
                let config = JSON.parse(configElement.innerHTML.trim());
                new Swiper(swiperElement, config);
            } catch (err) {
                console.error("Swiper config parse error:", err);
            }
        });
    }

    window.addEventListener("load", initSwiper);

    /**
     * Initiate glightbox
     */
    if (typeof GLightbox !== 'undefined') {
        GLightbox({
            selector: '.glightbox'
        });
    }

    /**
     * Initiate Pure Counter
     */
    if (typeof PureCounter !== 'undefined') {
        new PureCounter();
    }

    /**
     * Frequently Asked Questions Toggle
     */
    document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle, .faq-item .faq-header').forEach((faqItem) => {
        faqItem.addEventListener('click', () => {
            faqItem.parentNode.classList.toggle('faq-active');
        });
    });

    /**
     * Navmenu Active State on Scroll (Scrollspy)
     */
    let navmenulinks = document.querySelectorAll('.navmenu a[href^="#"]');

    function navmenuScrollspy() {
        navmenulinks.forEach(navmenulink => {
            if (!navmenulink.hash) return;
            let section = document.querySelector(navmenulink.hash);
            if (!section) return;
            let position = window.scrollY + 200;
            if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
                document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
                navmenulink.classList.add('active');
            }
        });
    }
    window.addEventListener('load', navmenuScrollspy);
    document.addEventListener('scroll', navmenuScrollspy);

    /**
     * Doctor Directory Search & Filtering
     */
    const doctorSearchForm = document.querySelector('#doctor-search-form');
    const doctorCards = document.querySelectorAll('.directory-grid .clinician-row');
    const resultsCountEl = document.querySelector('.results-count .count-number');
    const tagFilters = document.querySelectorAll('.sort-tags .tag');
    const emptyStateEl = document.querySelector('#doctor-empty-state');

    function filterDoctors() {
        if (!doctorCards.length) return;

        const nameInput = document.querySelector('#doctor_name');
        const specialtySelect = document.querySelector('#doctor_specialty');
        const locationSelect = document.querySelector('#doctor_location');
        const activeTag = document.querySelector('.sort-tags .tag.active');

        const nameQuery = nameInput ? nameInput.value.trim().toLowerCase() : '';
        const specialtyQuery = specialtySelect ? specialtySelect.value.trim().toLowerCase() : '';
        const locationQuery = locationSelect ? locationSelect.value.trim().toLowerCase() : '';
        const tagQuery = activeTag ? activeTag.getAttribute('data-filter') || activeTag.textContent.trim().toLowerCase() : 'all';

        let visibleCount = 0;

        doctorCards.forEach(card => {
            const docName = (card.getAttribute('data-name') || card.querySelector('h3')?.textContent || '').toLowerCase();
            const docSpecialty = (card.getAttribute('data-specialty') || card.querySelector('.role')?.textContent || '').toLowerCase();
            const docLocation = (card.getAttribute('data-location') || '').toLowerCase();
            const docStatus = (card.getAttribute('data-status') || '').toLowerCase();
            const docScore = parseFloat(card.getAttribute('data-score') || '0');

            const matchesName = !nameQuery || docName.includes(nameQuery) || docSpecialty.includes(nameQuery);
            const matchesSpecialty = !specialtyQuery || docSpecialty.includes(specialtyQuery);
            const matchesLocation = !locationQuery || docLocation.includes(locationQuery);

            let matchesTag = true;
            if (tagQuery === 'top rated' || tagQuery === 'top-rated') {
                matchesTag = docScore >= 4.8;
            } else if (tagQuery === 'available today' || tagQuery === 'available') {
                matchesTag = docStatus === 'active' || docStatus === 'available';
            } else if (tagQuery === 'most reviewed') {
                const reviews = parseInt(card.getAttribute('data-reviews') || '0', 10);
                matchesTag = reviews >= 130;
            }

            const colWrapper = card.closest('.col-12');
            if (matchesName && matchesSpecialty && matchesLocation && matchesTag) {
                if (colWrapper) colWrapper.style.display = '';
                visibleCount++;
            } else {
                if (colWrapper) colWrapper.style.display = 'none';
            }
        });

        if (resultsCountEl) {
            resultsCountEl.textContent = visibleCount;
        }

        if (emptyStateEl) {
            emptyStateEl.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (doctorSearchForm) {
        doctorSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            filterDoctors();
        });

        const nameInput = document.querySelector('#doctor_name');
        if (nameInput) {
            nameInput.addEventListener('input', filterDoctors);
        }

        const specialtySelect = document.querySelector('#doctor_specialty');
        if (specialtySelect) {
            specialtySelect.addEventListener('change', filterDoctors);
        }

        const locationSelect = document.querySelector('#doctor_location');
        if (locationSelect) {
            locationSelect.addEventListener('change', filterDoctors);
        }
    }

    tagFilters.forEach(tag => {
        tag.addEventListener('click', function() {
            tagFilters.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            filterDoctors();
        });
    });

    // Reset filters button
    const resetFiltersBtn = document.querySelector('#btn-reset-filters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (doctorSearchForm) doctorSearchForm.reset();
            tagFilters.forEach(t => t.classList.remove('active'));
            const firstTag = document.querySelector('.sort-tags .tag');
            if (firstTag) firstTag.classList.add('active');
            filterDoctors();
        });
    }

    /**
     * Quick Doctor Selection from Cards into Appointment Form
     */
    document.querySelectorAll('.book-consult-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const doctorName = this.getAttribute('data-doctor');
            const department = this.getAttribute('data-department');

            const doctorSelect = document.querySelector('#appointment_doctor');
            const deptSelect = document.querySelector('#appointment_department');

            if (deptSelect && department) {
                deptSelect.value = department;
            }
            if (doctorSelect && doctorName) {
                doctorSelect.value = doctorName;
            }

            const appointmentSection = document.querySelector('#appointment');
            if (appointmentSection) {
                appointmentSection.scrollIntoView({ behavior: 'smooth' });
                // Highlight form
                const formCard = appointmentSection.querySelector('.form-card');
                if (formCard) {
                    formCard.style.boxShadow = '0 0 0 4px rgba(14, 165, 233, 0.4)';
                    setTimeout(() => {
                        formCard.style.boxShadow = '';
                    }, 2000);
                }
            }
        });
    });

    // Native backend submission handled by Laravel AppointmentController
                    appointmentForm.reset();
                }, 1000);
            }
        });
    }

    // Native backend submission handled by Laravel ContactController

})();