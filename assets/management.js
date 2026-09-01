document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-yll-print').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });
});
