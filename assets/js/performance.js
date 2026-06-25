(function () {
    function optimizeMediaLoading() {
        var images = Array.prototype.slice.call(document.querySelectorAll('img'));
        var iframes = Array.prototype.slice.call(document.querySelectorAll('iframe'));

        images.forEach(function (img, index) {
            if (!img.hasAttribute('decoding')) {
                img.setAttribute('decoding', 'async');
            }

            if (img.hasAttribute('loading')) {
                return;
            }

            var rect = img.getBoundingClientRect();
            var isNearTop = rect.top < (window.innerHeight * 1.2);

            if (isNearTop && index < 2) {
                img.setAttribute('fetchpriority', 'high');
                return;
            }

            img.setAttribute('loading', 'lazy');
            if (!img.hasAttribute('fetchpriority')) {
                img.setAttribute('fetchpriority', 'low');
            }
        });

        iframes.forEach(function (iframe) {
            if (!iframe.hasAttribute('loading')) {
                iframe.setAttribute('loading', 'lazy');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', optimizeMediaLoading);
    } else {
        optimizeMediaLoading();
    }
})();
