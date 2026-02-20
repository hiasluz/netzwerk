document.addEventListener('DOMContentLoaded', function() {
    // Accessibility: Add role="main" to the main content container
    const mainContent = document.getElementById('main-content') || document.querySelector('.et-l--body');
    if (mainContent && !mainContent.hasAttribute('role')) {
        mainContent.setAttribute('role', 'main');
    }

    const header = document.querySelector('.et-l--header');
    // Die Sektion im Header finden, deren Padding wir ändern wollen
    const headerSection = header ? header.querySelector('.et_pb_section') : null;

    if (!header || !headerSection) {
        return;
    }

    const scrollThreshold = 50; // Schwelle in Pixeln, ab wann die Klasse hinzugefügt wird
    const scrolledPadding = '1rem'; // Der gewünschte Padding-Wert beim Scrollen
    let isTicking = false;

    function updateHeaderOnScroll() {
        if (window.scrollY > scrollThreshold) {
            header.classList.add('scroll');
            // Überschreibt den Inline-Style von Divi direkt per JavaScript
            headerSection.style.setProperty('padding-top', scrolledPadding, 'important');
            headerSection.style.setProperty('padding-bottom', scrolledPadding, 'important');
        } else {
            header.classList.remove('scroll');
            // Entfernt unsere Überschreibung, damit der ursprüngliche Divi-Style wieder greift
            headerSection.style.removeProperty('padding-top');
            headerSection.style.removeProperty('padding-bottom');
        }
        isTicking = false;
    }

    window.addEventListener('scroll', function() {
        if (!isTicking) {
            window.requestAnimationFrame(updateHeaderOnScroll);
            isTicking = true;
        }
    }, { passive: true });
});
