document.addEventListener('DOMContentLoaded', () => {
    // --- SECTION 1: BUSINESS/PERSONAL TOGGLE ---
    const toggle = document.querySelector('.shipping-tools .toggle');
    const btnBusiness = document.querySelector('.shipping-tools .btn1');
    const btnPersonal = document.querySelector('.shipping-tools .btn2');
    const gBusiness = document.querySelector('.shipping-tools .g1');
    const gPersonal = document.querySelector('.shipping-tools .g2');

    // Safety Check: Only run if these elements exist on the current page
    if (toggle && btnBusiness && btnPersonal) {
        
        const setActiveTab = (type) => {
            if (type === 'personal') {
                toggle.style.setProperty('--after-left', '42%');
                toggle.style.setProperty('--after-width', '57%'); 
                btnPersonal.classList.add('active');
                btnBusiness.classList.remove('active');
                gPersonal.classList.add('active');
                gBusiness.classList.remove('active');
            } else {
                toggle.style.setProperty('--after-left', '4px');
                toggle.style.setProperty('--after-width', '40%');
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
    }





    // --- SECTION 2: IMPORTANT UPDATES (ACCORDION) ---
    const sectionIM = document.querySelector('.faq');
    
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