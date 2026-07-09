(function() {
    const headlinerSelect = document.querySelector('[data-artist-headliner-select]');
    const headlinerInput = document.querySelector('[data-artist-headliner-input]');
    const performersSelect = document.querySelector('[data-artist-performers-select]');
    const performersTextarea = document.querySelector('[data-artist-performers-textarea]');

    if (headlinerSelect && headlinerInput) {
        headlinerSelect.addEventListener('change', () => {
            const names = Array.from(headlinerSelect.selectedOptions)
                .map((option) => option.dataset.artistName)
                .filter(Boolean);

            if (names.length) {
                headlinerInput.value = names.join(', ');
            }
        });
    }

    if (performersSelect && performersTextarea) {
        performersSelect.addEventListener('change', () => {
            const names = Array.from(performersSelect.selectedOptions)
                .map((option) => option.dataset.artistName)
                .filter(Boolean);

            if (names.length) {
                performersTextarea.value = names.join(', ');
            }
        });
    }
})();
