(function () {
    const initFreshReleases = () => {
        const slider = document.querySelector('.fresh-releases-swiper');

        if (!slider || typeof window.Swiper === 'undefined') {
            return;
        }

        new window.Swiper(slider, {
            slidesPerView: 1.75,
            spaceBetween: 10,
            speed: 680,
            grabCursor: true,
            watchOverflow: true,
            navigation: {
                nextEl: '.fresh-releases-next',
                prevEl: '.fresh-releases-prev',
            },
            pagination: {
                el: '.fresh-releases-pagination',
                clickable: true,
            },
            breakpoints: {
                560: {
                    slidesPerView: 2.8,
                    spaceBetween: 12,
                },
                760: {
                    slidesPerView: 4.05,
                    spaceBetween: 12,
                },
                1120: {
                    slidesPerView: 5.05,
                    spaceBetween: 12,
                },
            },
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFreshReleases);
    } else {
        initFreshReleases();
    }
}());
