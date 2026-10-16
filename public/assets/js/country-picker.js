/**
 * Sports Junction - Searchable Country Dropdown with Flags and Auto Dialing Code
 * Works seamlessly with both Tailwind and Bootstrap forms.
 */
(function() {
    'use strict';

    // Inject self-contained styles once
    if (!document.getElementById('sj-country-picker-styles')) {
        const style = document.createElement('style');
        style.id = 'sj-country-picker-styles';
        style.textContent = `
            .sj-country-picker-wrapper {
                position: relative;
                width: 100%;
                font-family: inherit;
            }
            .sj-country-picker-trigger {
                display: flex;
                align-items: center;
                gap: 8px;
                width: 100%;
                padding: 10px 14px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                font-size: 14px;
                color: #0f172a;
                cursor: pointer;
                user-select: none;
                transition: all 0.15s ease-in-out;
                box-sizing: border-box;
            }
            .sj-country-picker-trigger:hover {
                background: #ffffff;
                border-color: #94a3b8;
            }
            .sj-country-picker-trigger.active {
                background: #ffffff;
                border-color: #1e40af;
                box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.15);
            }
            .sj-country-picker-dropdown {
                position: absolute;
                top: calc(100% + 4px);
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                z-index: 99999;
                display: none;
                overflow: hidden;
                box-sizing: border-box;
            }
            .sj-country-picker-dropdown.open {
                display: block;
            }
            .sj-country-search-wrap {
                padding: 8px 10px;
                border-bottom: 1px solid #f1f5f9;
                background: #f8fafc;
            }
            .sj-country-search-input {
                width: 100%;
                padding: 8px 12px;
                font-size: 13px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                outline: none;
                background: #ffffff;
                box-sizing: border-box;
            }
            .sj-country-search-input:focus {
                border-color: #1e40af;
            }
            .sj-country-list {
                max-height: 240px;
                overflow-y: auto;
                padding: 4px 0;
                margin: 0;
                list-style: none;
            }
            .sj-country-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                font-size: 13px;
                color: #1e293b;
                cursor: pointer;
                transition: background 0.1s;
            }
            .sj-country-item:hover, .sj-country-item.highlighted {
                background: #eff6ff;
                color: #1e40af;
            }
            .sj-country-item.selected {
                background: #e0e7ff;
                font-weight: 600;
                color: #1e3a8a;
            }
            .sj-country-flag {
                font-size: 18px;
                line-height: 1;
                display: inline-block;
            }
            .sj-country-name {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .sj-country-code {
                font-size: 11px;
                color: #64748b;
                font-weight: 600;
            }
            .sj-country-no-results {
                padding: 16px;
                text-align: center;
                font-size: 12px;
                color: #94a3b8;
            }
            /* Bootstrap admin adjustments */
            .form-control-sm + .sj-country-picker-wrapper .sj-country-picker-trigger,
            .form-select-sm + .sj-country-picker-wrapper .sj-country-picker-trigger {
                padding: 6px 10px;
                font-size: 12px;
                border-radius: 6px;
            }
        `;
        document.head.appendChild(style);
    }

    function initCountryPicker(selectEl) {
        if (!selectEl || selectEl.dataset.countryPickerInitialized === 'true') return;
        selectEl.dataset.countryPickerInitialized = 'true';

        // Read options from select
        const options = [];
        Array.from(selectEl.options).forEach(opt => {
            if (!opt.value) return; // skip placeholder if empty
            const flag = opt.dataset.flag || (opt.text.match(/^(\p{Extended_Pictographic}+)/u)?.[1] || '🌐');
            const phone = opt.dataset.phone || opt.dataset.phoneCode || (opt.text.match(/\((\+\d+)\)/)?.[1] || '');
            let name = opt.text.replace(/^(\p{Extended_Pictographic}+\s*)/u, '').replace(/\s*\(\+\d+\)\s*$/, '').trim();
            options.push({
                value: opt.value,
                name: name || opt.text,
                flag: flag,
                phone: phone,
                selected: opt.selected
            });
        });

        // Hide select element but keep for form submission
        selectEl.style.display = 'none';

        // Create container wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'sj-country-picker-wrapper';
        selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);

        // Current selection
        let currentOption = options.find(o => String(o.value) === String(selectEl.value)) || options[0] || null;

        // Trigger button
        const trigger = document.createElement('div');
        trigger.className = 'sj-country-picker-trigger';
        trigger.setAttribute('tabindex', '0');

        function updateTriggerDisplay() {
            if (currentOption) {
                trigger.innerHTML = `
                    <span class="sj-country-flag">${currentOption.flag}</span>
                    <span class="sj-country-name">${currentOption.name}</span>
                    ${currentOption.phone ? `<span class="sj-country-code">${currentOption.phone}</span>` : ''}
                    <span style="margin-left:auto;font-size:11px;opacity:0.6;">▼</span>
                `;
            } else {
                trigger.innerHTML = `<span class="sj-country-name text-slate-400">Select country...</span><span style="margin-left:auto;font-size:11px;opacity:0.6;">▼</span>`;
            }
        }
        updateTriggerDisplay();
        wrapper.appendChild(trigger);

        // Dropdown container
        const dropdown = document.createElement('div');
        dropdown.className = 'sj-country-picker-dropdown';
        wrapper.appendChild(dropdown);

        // Search input wrap
        const searchWrap = document.createElement('div');
        searchWrap.className = 'sj-country-search-wrap';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'sj-country-search-input';
        searchInput.placeholder = 'Search country name or code...';
        searchWrap.appendChild(searchInput);
        dropdown.appendChild(searchWrap);

        // Options List container
        const list = document.createElement('ul');
        list.className = 'sj-country-list';
        dropdown.appendChild(list);

        function renderOptions(query = '') {
            const q = query.trim().toLowerCase();
            list.innerHTML = '';
            const filtered = options.filter(opt => {
                if (!q) return true;
                return opt.name.toLowerCase().includes(q) || opt.phone.toLowerCase().includes(q) || opt.value.toString() === q;
            });

            if (filtered.length === 0) {
                list.innerHTML = '<li class="sj-country-no-results">No countries found</li>';
                return;
            }

            filtered.forEach(opt => {
                const li = document.createElement('li');
                li.className = 'sj-country-item' + (currentOption && String(currentOption.value) === String(opt.value) ? ' selected' : '');
                li.innerHTML = `
                    <span class="sj-country-flag">${opt.flag}</span>
                    <span class="sj-country-name">${opt.name}</span>
                    ${opt.phone ? `<span class="sj-country-code">${opt.phone}</span>` : ''}
                `;
                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectCountry(opt);
                    closeDropdown();
                });
                list.appendChild(li);
            });
        }

        function selectCountry(opt) {
            currentOption = opt;
            selectEl.value = opt.value;
            updateTriggerDisplay();

            // Auto-populate dialing code if target exists
            autoUpdateDialCode(selectEl, opt.phone);

            // Trigger change event on native select
            const changeEvent = new Event('change', { bubbles: true });
            selectEl.dispatchEvent(changeEvent);
        }

        function openDropdown() {
            dropdown.classList.add('open');
            trigger.classList.add('active');
            searchInput.value = '';
            renderOptions();
            setTimeout(() => searchInput.focus(), 50);
        }

        function closeDropdown() {
            dropdown.classList.remove('open');
            trigger.classList.remove('active');
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdown.classList.contains('open')) {
                closeDropdown();
            } else {
                // Close any other open country pickers
                document.querySelectorAll('.sj-country-picker-dropdown.open').forEach(d => d.classList.remove('open'));
                document.querySelectorAll('.sj-country-picker-trigger.active').forEach(t => t.classList.remove('active'));
                openDropdown();
            }
        });

        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });

        searchInput.addEventListener('input', () => {
            renderOptions(searchInput.value);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeDropdown();
                trigger.focus();
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                closeDropdown();
            }
        });

        // Sync when native select changes programmatically
        selectEl.addEventListener('change', () => {
            const found = options.find(o => String(o.value) === String(selectEl.value));
            if (found && found !== currentOption) {
                currentOption = found;
                updateTriggerDisplay();
                autoUpdateDialCode(selectEl, found.phone);
            }
        });

        // Auto update dial code initially if already selected
        if (currentOption && currentOption.phone) {
            autoUpdateDialCode(selectEl, currentOption.phone, false);
        }
    }

    function autoUpdateDialCode(selectEl, phoneCode, triggerChange = true) {
        if (!phoneCode) return;
        const form = selectEl.closest('form') || document;
        
        // Find country code element: select[name="country_code"], input[name="country_code"], #inpCountryCode, #ebCountryCode
        let codeEl = form.querySelector('[name="country_code"]') 
                  || form.querySelector('#inpCountryCode')
                  || form.querySelector('#ebCountryCode');

        if (codeEl) {
            if (codeEl.tagName === 'SELECT') {
                // Check if exact phone code exists in select options
                let matched = false;
                for (let i = 0; i < codeEl.options.length; i++) {
                    if (codeEl.options[i].value === phoneCode || codeEl.options[i].text.includes(phoneCode)) {
                        codeEl.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
                // If not in select options, add it dynamically
                if (!matched) {
                    const newOpt = new Option(phoneCode, phoneCode, true, true);
                    codeEl.add(newOpt);
                }
            } else if (codeEl.tagName === 'INPUT') {
                codeEl.value = phoneCode;
            }
            if (triggerChange) {
                codeEl.dispatchEvent(new Event('change', { bubbles: true }));
                codeEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }

    // Initialize all country pickers on page
    function initAll() {
        document.querySelectorAll('select.searchable-country-select, select[data-country-picker]').forEach(initCountryPicker);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.SJCountryPicker = {
        init: initCountryPicker,
        initAll: initAll
    };
})();
