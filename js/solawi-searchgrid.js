document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('solawi-filter-form');
    // Check if the form and localized data exist
    if (!form || typeof solawiGridData === 'undefined') {
        return;
    }

    const gridContainer = document.getElementById('solawi-grid-container');
    const loader = document.getElementById('solawi-loader');

    function triggerFilter() {
        const searchSelect = document.getElementById('filter-search');
        const depotSelect = document.getElementById('filter-depot');
        
        const searchVal = searchSelect ? searchSelect.value : '';
        const depotVal  = depotSelect ? depotSelect.value : '';
        const termIds   = [];
        
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        checkboxes.forEach(function(checkbox) {
            termIds.push(checkbox.value);
        });

        if (gridContainer) gridContainer.style.opacity = '0.5';
        if (loader) loader.style.display = 'block';

        // Dispatch Event to filter Map
        const event = new CustomEvent('solawi-filter-update', {
            detail: {
                search: searchVal,
                terms: termIds,
                depot: depotVal
            }
        });
        document.dispatchEvent(event);

        // Prepare data for Fetch API
        const formData = new FormData();
        formData.append('action', 'filter_solawis');
        formData.append('nonce', solawiGridData.nonce); // Add nonce for security
        formData.append('search', searchVal);
        formData.append('depot', depotVal);
        // PHP expects an array for 'terms', so we append each item with []
        termIds.forEach(id => formData.append('terms[]', id));

        fetch(solawiGridData.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (gridContainer) gridContainer.innerHTML = data.data;
            } else {
                if (gridContainer) gridContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;"><h4>Fehler</h4><p>Beim Laden der Daten ist ein Fehler aufgetreten.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (gridContainer) gridContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;"><h4>Fehler</h4><p>Beim Laden der Daten ist ein Fehler aufgetreten.</p></div>';
        })
        .finally(() => {
            if (gridContainer) gridContainer.style.opacity = '1';
            if (loader) loader.style.display = 'none';
        });
    }

    // Event Listeners
    // Use delegation or direct attachment. Since elements are static in formulation:
    
    // 1. Checkboxes
    const allCheckboxes = form.querySelectorAll('input[type="checkbox"]');
    allCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', triggerFilter);
    });

    // 2. Selects
    const selectDepot = document.getElementById('filter-depot');
    if (selectDepot) selectDepot.addEventListener('change', triggerFilter);

    const selectSearch = document.getElementById('filter-search');
    if (selectSearch) selectSearch.addEventListener('change', triggerFilter);
    
    // Prevent form submission
    form.addEventListener('submit', function(e) { e.preventDefault(); });
});