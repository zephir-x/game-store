// filters and sorting logic

export function toggleDropdown(dropdownId) {
    const target = document.getElementById(dropdownId);
    if (!target) return;

    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== dropdownId && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
            menu.classList.remove('fade-in-card');
        }
    });

    target.classList.toggle('hidden');
    if (!target.classList.contains('hidden')) {
        target.classList.add('fade-in-card');
    }
}

export function closeDropdownsOnClickOutside(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
            menu.classList.remove('fade-in-card');
        });
    }
}

export function clearFilters(tableType) {
    const url = new URL(window.location.href);
    window.location.href = url.pathname + '?tab=' + tableType;
}

export function handleVisualSort(buttonElement, tableType, sortColumn) {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab') || 'users';
    const defaultSort = tableType === 'games' ? 'game_id' : 'id';
    
    let actualCurrentSort;
    let actualCurrentDir;
    
    // determine the true state of the table before click, including default load scenarios
    if (activeTab === tableType && params.has('sort')) {
        actualCurrentSort = params.get('sort');
        actualCurrentDir = params.get('dir') || 'asc';
    } else {
        actualCurrentSort = defaultSort;
        actualCurrentDir = 'asc';
    }

    let newDir = 'asc';
    // correctly reverse direction if clicking the active column
    if (actualCurrentSort === sortColumn) {
        newDir = actualCurrentDir === 'asc' ? 'desc' : 'asc';
    }

    params.set('sort', sortColumn);
    params.set('dir', newDir);
    params.set('tab', tableType);

    window.location.search = params.toString();
}

export function applyFilters(formElement, tableType) {
    const formData = new FormData(formElement);
    const params = new URLSearchParams();
    const oldParams = new URLSearchParams(window.location.search);

    // preserve sorting state
    if (oldParams.has('sort')) params.set('sort', oldParams.get('sort'));
    if (oldParams.has('dir')) params.set('dir', oldParams.get('dir'));

    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            params.set(key, value);
        }
    }

    params.set('tab', tableType);
    params.set('filtered', '1'); 
    
    window.location.search = params.toString();
}

export function restoreState() {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab') || 'users';
    
    // restore inputs
    document.querySelectorAll('input.form-input').forEach(input => {
        if (params.has(input.name)) {
            input.value = params.get(input.name);
        }
    });

    // restore checkboxes
    if (activeTab === 'users') {
        const roleUser = document.querySelector('input[name="role_user"]');
        const roleAdmin = document.querySelector('input[name="role_admin"]');
        const isFiltered = params.has('filtered');
        
        if (roleUser) roleUser.checked = !isFiltered || params.has('role_user');
        if (roleAdmin) roleAdmin.checked = !isFiltered || params.has('role_admin');
    }

    // restore sort buttons highlighting securely via regex extraction
    document.querySelectorAll('.btn-sort-item').forEach(btn => {
        btn.classList.remove('active');
        const i = btn.querySelector('i');
        if (i) i.className = 'fa-solid fa-sort'; 
        
        const onclickAttr = btn.getAttribute('onclick') || '';
        const args = onclickAttr.match(/handleVisualSort\([^,]+,\s*'([^']+)',\s*'([^']+)'\)/);
        if (!args) return;
        
        const btnTableType = args[1];
        const btnSortCol = args[2];
        const defaultSort = btnTableType === 'games' ? 'game_id' : 'id';
        
        let targetSort, targetDir;
        if (activeTab === btnTableType) {
            targetSort = params.get('sort') || defaultSort;
            targetDir = params.get('dir') || 'asc';
        } else {
            targetSort = defaultSort;
            targetDir = 'asc';
        }

        if (btnSortCol === targetSort) {
            btn.classList.add('active');
            if (i) i.className = targetDir === 'asc' ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down';
        }
    });
}