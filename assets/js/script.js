// BSP Ranking System — small UI enhancements

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss success alerts after a few seconds
    document.querySelectorAll('.alert-success').forEach((alertBox) => {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.4s ease';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 400);
        }, 4000);
    });

    // Clamp attendance input to 0-100 on the fly
    document.querySelectorAll('input[name="attendance"]').forEach((input) => {
        input.addEventListener('change', () => {
            let val = parseFloat(input.value);
            if (isNaN(val)) return;
            if (val < 0) input.value = 0;
            if (val > 100) input.value = 100;
        });
    });
});
