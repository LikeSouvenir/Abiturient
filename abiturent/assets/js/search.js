document.addEventListener('DOMContentLoaded', () => {
    const programGridContainer = document.getElementById('programGridContainer');
    const searchInput = document.getElementById('programSearchInput');

    function filterPrograms(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const items = programGridContainer.querySelectorAll('.program-card');
        let visibleItemsCount = 0;

        items.forEach(item => {
            const text = item.dataset.searchText;
            const matches = !term || text.includes(term);
            item.style.display = matches ? 'flex' : 'none';
            if (matches) {
                visibleItemsCount++;
            }
        });

        let jsNoResultsEl = programGridContainer.querySelector('.js-no-results-message');
        const phpMessageExists = !!programGridContainer.querySelector('.php-no-results-message');

        if (term && visibleItemsCount === 0 && !phpMessageExists) {
            if (!jsNoResultsEl) {
                jsNoResultsEl = document.createElement('p');
                jsNoResultsEl.className = 'no-results js-no-results-message';
                programGridContainer.appendChild(jsNoResultsEl);
            }
            jsNoResultsEl.textContent = 'По вашему запросу программы не найдены.';
            jsNoResultsEl.style.display = 'block';
        } else if (jsNoResultsEl) {
            jsNoResultsEl.style.display = 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterPrograms(e.target.value);
        });
        if (searchInput.value) {
            filterPrograms(searchInput.value);
        }
    }

    const backButton = document.getElementById('backButton');
    if (backButton) {
        backButton.addEventListener('click', () => {
            history.back();
        });
    }

    const goHomeButton = document.getElementById('goHomeButton');
    if (goHomeButton) {
        goHomeButton.addEventListener('click', () => {
            window.location.href = 'index.php';
        });
    }
});
