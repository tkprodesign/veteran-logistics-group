/**
 * index.js - Developer Global Logic
 * Updated for Developer Responsive Footer (Details/Summary Version)
 */

// --- HEADER SWIPER ---
if (document.querySelector('.swiper') && typeof Swiper === 'function') {
    const swiper = new Swiper(".swiper", {
        loop: true,
        slidesPerView: 1,
        autoplay: {
            delay: 5000, 
            disableOnInteraction: false,
        },
    });
}

// --- GLOBAL SELECTORS ---
const body = document.querySelector('body');
const nav = document.querySelector('header .container .left nav');
const menuToggleBtn = document.querySelector('header #menuToggleBtn');

// --- FOOTER ACCORDION LOGIC ---
const footerSections = document.querySelectorAll('footer .footer-section');

/**
 * Handle the accordion toggle
 * Manages mutual exclusion on mobile and prevents closing on desktop
 */
const handleFooterAccordion = (e) => {
    const section = e.currentTarget.closest('.footer-section');
    
    // 1. Desktop Check (1140px / 960px threshold)
    // If we are on desktop, we prevent the "close" action so columns stay visible
    if (window.innerWidth >= 960) {
        if (section.hasAttribute('open')) {
            e.preventDefault(); // Art stays static on desktop
        }
        return;
    }

    // 2. Mobile Logic: Mutual Exclusion
    // When one opens, we close the others for a "Pro" feel
    if (!section.hasAttribute('open')) {
        footerSections.forEach(otherSection => {
            if (otherSection !== section) {
                otherSection.removeAttribute('open');
            }
        });
    }
};

/**
 * Function to ensure all sections are OPEN on desktop resize
 */
const resetFooterState = () => {
    if (window.innerWidth >= 960) {
        footerSections.forEach(s => s.setAttribute('open', ''));
    }
};

if (footerSections.length > 0) {
    footerSections.forEach(section => {
        const summary = section.querySelector('summary');
        if (summary) {
            // We listen to the summary click to intercept the toggle
            summary.addEventListener('click', handleFooterAccordion);
        }
    });

    // Run once on load to ensure desktop is expanded
    resetFooterState();
    
    // Watch for window resize
    window.addEventListener('resize', resetFooterState);
}

// --- MOBILE NAV TOGGLE ---
if (menuToggleBtn && nav && body) {
    window.__headerNavBound = true;

    menuToggleBtn.addEventListener('click', () => {
        menuToggleBtn.classList.toggle('active');
        body.classList.toggle('active-nav');
        nav.classList.toggle('active');
    });

    nav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menuToggleBtn.classList.remove('active');
            body.classList.remove('active-nav');
            nav.classList.remove('active');
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 960) {
            menuToggleBtn.classList.remove('active');
            body.classList.remove('active-nav');
            nav.classList.remove('active');
        }
    });
}

// --- GLOBAL CHAT WIDGET HELPERS ---
window.openChatWidget = function () {
    if (typeof window.smartsupp === 'function') {
        window.smartsupp('chat:open');
        return true;
    }

    if (window._smartsupp && window._smartsupp.api && typeof window._smartsupp.api.open === 'function') {
        window._smartsupp.api.open();
        return true;
    }

    return false;
};

const chatLinks = document.querySelectorAll('.js-open-live-chat');
if (chatLinks.length > 0) {
    chatLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (!window.openChatWidget()) {
                window.location.href = '/support/';
            }
        });
    });
}

// --- BOX OUTLINE FUNCTIONS ---
window.outlineBox = function(id) {
    const outlineDoc = document.querySelector(id);
    if (outlineDoc) outlineDoc.classList.add("outline-input-field");
};

window.removeOutlineBox = function(id) {
    const outlineDoc = document.querySelector(id);
    if (outlineDoc) outlineDoc.classList.remove("outline-input-field");
};

// --- PASSWORD VISIBILITY FUNCTION ---
window.togglePassVisibility = function(cid, oid, piid, acid = null, aoid = null, cpiid = null) {
    const closedEye = document.getElementById(cid);
    const openEye = document.getElementById(oid);
    const passwordInput = document.getElementById(piid);

    if (closedEye && openEye && passwordInput) {
        closedEye.classList.toggle("display-none");
        openEye.classList.toggle("display-none");
        passwordInput.type = (passwordInput.type === "password") ? "text" : "password";
    }

    if (acid && aoid && cpiid) {
        const altClosedEye = document.getElementById(acid);
        const altOpenEye = document.getElementById(aoid);
        const confirmInput = document.getElementById(cpiid);

        if (altClosedEye && altOpenEye && confirmInput) {
            altClosedEye.classList.toggle("display-none");
            altOpenEye.classList.toggle("display-none");
            confirmInput.type = (confirmInput.type === "password") ? "text" : "password";
        }
    }
};






// --- SECTION EDITING LOGIC ---
/**
 * Hides all <section> elements unless they have the '.editing' class.
 * This ensures the focus remains on the "art piece" currently in progress.
 */
// const manageSectionVisibility = () => {
//     const sections = document.querySelectorAll('section');
    
//     sections.forEach(section => {
//         if (!section.classList.contains('editing')) {
//             section.style.display = 'none';
//         } else {
//             section.style.display = ''; // Restores default (block, flex, etc.)
//         }
//     });
// };

// Execute the visibility check
// manageSectionVisibility();
