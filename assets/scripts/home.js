/**
 * home.js - Developer Homepage Specific Logic
 * Handles Service Tabs and Important Updates Accordion
 */
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.swiper') && typeof Swiper === 'function') {
        new Swiper('.swiper', {
            loop: true,
            slidesPerView: 1,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
        });
    }

    if (!window.__headerNavBound) {
        const body = document.body;
        const nav = document.querySelector('header .container .left nav');
        const menuToggleBtn = document.querySelector('header #menuToggleBtn');
        if (menuToggleBtn && nav && body) {
            menuToggleBtn.addEventListener('click', () => {
                menuToggleBtn.classList.toggle('active');
                body.classList.toggle('active-nav');
                nav.classList.toggle('active');
            });
        }
    }
    
    // --- SECTION 1: BUSINESS/PERSONAL TOGGLE ---
    const toggle = document.querySelector('.services-alt .toggle');
    const btnBusiness = document.querySelector('.services-alt .btn1');
    const btnPersonal = document.querySelector('.services-alt .btn2');
    const gBusiness = document.querySelector('.services-alt .g1');
    const gPersonal = document.querySelector('.services-alt .g2');

    // Safety Check: Only run if these elements exist on the current page
    if (toggle && btnBusiness && btnPersonal) {
        
        const setActiveTab = (type) => {
            if (type === 'personal') {
                toggle.style.setProperty('--after-left', 'calc(50% - 1px)');
                btnPersonal.classList.add('active');
                btnBusiness.classList.remove('active');
                gPersonal.classList.add('active');
                gBusiness.classList.remove('active');
            } else {
                toggle.style.setProperty('--after-left', '3px');
                btnBusiness.classList.add('active');
                btnPersonal.classList.remove('active');
                gBusiness.classList.add('active');
                gPersonal.classList.remove('active');
            }
        };

        btnPersonal.addEventListener('click', (e) => {
            e.preventDefault(); 
            setActiveTab('personal');
        });

        btnBusiness.addEventListener('click', (e) => {
            e.preventDefault(); 
            setActiveTab('business');
        });

        setActiveTab(btnPersonal.classList.contains('active') ? 'personal' : 'business');
    }

    // --- SECTION 2: IMPORTANT UPDATES (ACCORDION) ---
    const sectionIM = document.querySelector('.important-updates');
    
    if (sectionIM) {
        const allDetails = sectionIM.querySelectorAll('details');

        allDetails.forEach((target) => {
            // The 'toggle' event fires when the 'open' attribute changes
            target.addEventListener('toggle', () => {
                const icon = target.querySelector('.accordion-icon');
                
                if (target.open) {
                    // UI State: OPEN
                    if (icon) {
                        icon.textContent = 'keyboard_arrow_down';
                        icon.classList.add('active');
                    }
                    target.classList.add('is-active');

                    // Logic: Exclusive Toggle (Close all other open accordions)
                    allDetails.forEach((other) => {
                        if (other !== target && other.open) {
                            other.open = false; 
                            // This trigger's the 'other' element's toggle event, 
                            // ensuring its icon and classes are also reset.
                        }
                    });
                } else {
                    // UI State: CLOSED
                    if (icon) {
                        icon.textContent = 'chevron_right';
                        icon.classList.remove('active');
                    }
                    target.classList.remove('is-active');
                }
            });
        });
    }
});


