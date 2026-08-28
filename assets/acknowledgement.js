document.addEventListener('DOMContentLoaded', function () {
    var button = document.getElementById('yll-save-pdf');
    if (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    }
});
