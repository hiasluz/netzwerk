document.addEventListener('DOMContentLoaded', function() {
    
    const sliders = document.querySelectorAll('.solawi-slider-container');
    
    // For each slider on the page (though expected to be one)
    sliders.forEach((slider, index) => {
        
        let slideIndex = 1;
        const slides = slider.querySelectorAll('.solawi-slide');
        const prevBtn = slider.querySelector('.solawi-slider-prev');
        const nextBtn = slider.querySelector('.solawi-slider-next');
        
        // Hide buttons if only one slide
        if (slides.length <= 1) {
            if(prevBtn) prevBtn.style.display = 'none';
            if(nextBtn) nextBtn.style.display = 'none';
        }

        function showSlides(n) {
            let i;
            if (n > slides.length) {slideIndex = 1}    
            if (n < 1) {slideIndex = slides.length}
            
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
                slides[i].classList.remove('active');
            }
            
            slides[slideIndex-1].style.display = "block";  
            slides[slideIndex-1].classList.add('active');
        }

        // Only add listeners if buttons exist
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
        
        // Initialize
        showSlides(slideIndex);
    });
});
