document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ls-chart-bar[data-height]').forEach(function (bar) {
        var height = Number.parseFloat(bar.getAttribute('data-height'));
        if (!Number.isFinite(height)) {
            return;
        }
        height = Math.max(0, Math.min(100, height));
        bar.style.setProperty('--ls-bar-height', height + '%');
    });
});
