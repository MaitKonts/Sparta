document.addEventListener('DOMContentLoaded', function() {
    const filterButton = document.getElementById('filter-button');
    const filterSelect = document.getElementById('item-filter');
    const tableRows = document.querySelectorAll('.market-table .market-item');


    filterButton.addEventListener('click', function() {
        const selectedType = filterSelect.value;
        console.log("Filter button clicked");

        tableRows.forEach(row => {
            const itemType = row.getAttribute('data-type');
            if (selectedType === 'all' || itemType === selectedType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
