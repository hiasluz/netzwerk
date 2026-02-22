document.addEventListener('DOMContentLoaded', function() {
    
    const sliders = document.querySelectorAll('.solawi-slider-container');
    
    sliders.forEach((slider) => {
        let slideIndex = 1;
        const slides = slider.querySelectorAll('.solawi-slide');
        const prevBtn = slider.querySelector('.solawi-slider-prev');
        const nextBtn = slider.querySelector('.solawi-slider-next');
        
        if (slides.length <= 1) {
            if(prevBtn) prevBtn.style.display = 'none';
            if(nextBtn) nextBtn.style.display = 'none';
        }

        function updateHeight() {
            const activeSlide = slider.querySelector('.solawi-slide.active');
            if (activeSlide) {
                const img = activeSlide.querySelector('img');
                if (img) {
                    if (img.complete) {
                        slider.style.height = img.offsetHeight + 'px';
                    } else {
                        img.onload = () => {
                            slider.style.height = img.offsetHeight + 'px';
                        };
                    }
                }
            }
        }

        function findMinHeight() {
            let minH = 9999;
            let allLoaded = true;
            
            slides.forEach(slide => {
                const img = slide.querySelector('img');
                if (img) {
                    if (img.complete && img.offsetHeight > 0) {
                        if (img.offsetHeight < minH) minH = img.offsetHeight;
                    } else {
                        allLoaded = false;
                        img.onload = findMinHeight; // Erneut versuchen, wenn geladen
                    }
                }
            });

            if (allLoaded && minH < 9999) {
                slider.style.setProperty('--min-height', minH + 'px');
            }
        }

        function showSlides(n) {
            if (n > slides.length) {slideIndex = 1}    
            if (n < 1) {slideIndex = slides.length}
            
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            slides[slideIndex-1].classList.add('active');
            
            // Höhe anpassen
            updateHeight();
        }

        if(prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showSlides(slideIndex -= 1);
            });
        }

        if(nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showSlides(slideIndex += 1);
            });
        }
        
        // Initialisierung
        showSlides(slideIndex);
        findMinHeight();

        // Bei Fenster-Resize Höhe und min-Höhe neu berechnen
        window.addEventListener('resize', () => {
            updateHeight();
            findMinHeight();
        });
    });
});
