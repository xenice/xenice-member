// assets/js/wc-free-download.js
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.download-popup-close').forEach(button => {
        button.addEventListener('click', function () {
            const popup = this.closest('.download-popup');
            if (popup) popup.classList.remove('active');
        });
    });


    document.querySelectorAll('.download-popup').forEach(popup => {
        popup.addEventListener('click', function (e) {
            const inner = this.querySelector('.download-popup-inner');
            if (!inner.contains(e.target)) {
                this.classList.remove('active');
            }
        });
    });
});