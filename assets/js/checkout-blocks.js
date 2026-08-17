(function () {
    'use strict';

    if (typeof vnAddressBlocks === 'undefined') {
        return;
    }

    var wardsByProvince = null;
    var wardsFetchPromise = null;

    function fetchWards() {
        if (wardsFetchPromise) {
            return wardsFetchPromise;
        }
        var params = new URLSearchParams();
        params.set('action', 'vn_address_get_wards_bulk');
        params.set('nonce', vnAddressBlocks.nonce);

        wardsFetchPromise = fetch(vnAddressBlocks.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
        })
            .then(function (res) { return res.json(); })
            .then(function (response) {
                wardsByProvince = response && response.success ? response.data : {};
                return wardsByProvince;
            });
        return wardsFetchPromise;
    }

    function stripDiacritics(str) {
        return str
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D')
            .toLowerCase();
    }

    function setNativeValue(el, value) {
        var proto = el.tagName === 'SELECT' ? window.HTMLSelectElement.prototype : window.HTMLInputElement.prototype;
        var setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
        setter.call(el, value);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function fieldSelector(fieldId) {
        return fieldId.replace(/\//g, '-');
    }

    function closeDropdown(dropdown) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
    }

    function enhanceWardField(group, wardInput) {
        if (wardInput.dataset.vnAddressEnhanced) {
            return;
        }
        wardInput.dataset.vnAddressEnhanced = '1';
        wardInput.setAttribute('autocomplete', 'off');
        wardInput.setAttribute('placeholder', vnAddressBlocks.i18n.searchPlaceholder);

        var provinceId = group + '-' + fieldSelector(vnAddressBlocks.fieldIds.province);
        var wardCodeId = group + '-' + fieldSelector(vnAddressBlocks.fieldIds.wardCode);

        var wrapper = wardInput.closest('.wc-block-components-text-input') || wardInput.parentElement;
        wrapper.style.position = 'relative';

        var dropdown = document.createElement('ul');
        dropdown.className = 'vn-address-autocomplete-list';
        dropdown.style.display = 'none';
        wrapper.appendChild(dropdown);

        function currentProvinceCode() {
            var provinceEl = document.getElementById(provinceId);
            return provinceEl ? provinceEl.value : '';
        }

        function renderResults(query) {
            var provinceCode = currentProvinceCode();
            dropdown.innerHTML = '';

            if (!provinceCode) {
                var msg = document.createElement('li');
                msg.className = 'vn-address-autocomplete-empty';
                msg.textContent = vnAddressBlocks.i18n.selectProvinceFirst;
                dropdown.appendChild(msg);
                dropdown.style.display = 'block';
                return;
            }

            if (!wardsByProvince) {
                return;
            }

            var wards = wardsByProvince[provinceCode] || [];
            var needle = stripDiacritics(query || '');
            var matches = wards.filter(function (w) {
                return stripDiacritics(w.name).indexOf(needle) !== -1;
            }).slice(0, 50);

            if (matches.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'vn-address-autocomplete-empty';
                empty.textContent = vnAddressBlocks.i18n.noResults;
                dropdown.appendChild(empty);
                dropdown.style.display = 'block';
                return;
            }

            matches.forEach(function (w) {
                var item = document.createElement('li');
                item.textContent = w.type + ' ' + w.name;
                item.tabIndex = 0;
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    setNativeValue(wardInput, w.name);
                    var codeEl = document.getElementById(wardCodeId);
                    if (codeEl) {
                        setNativeValue(codeEl, w.code);
                    }
                    closeDropdown(dropdown);
                });
                dropdown.appendChild(item);
            });

            dropdown.style.display = 'block';
        }

        wardInput.addEventListener('focus', function () {
            fetchWards().then(function () { renderResults(wardInput.value); });
        });

        wardInput.addEventListener('input', function () {
            var codeEl = document.getElementById(wardCodeId);
            if (codeEl) {
                setNativeValue(codeEl, '');
            }
            fetchWards().then(function () { renderResults(wardInput.value); });
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                closeDropdown(dropdown);
            }
        });

        // Clear the ward selection whenever the province changes.
        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === provinceId) {
                setNativeValue(wardInput, '');
                var codeEl = document.getElementById(wardCodeId);
                if (codeEl) {
                    setNativeValue(codeEl, '');
                }
            }
        });
    }

    function hideWardCodeField(group) {
        var wardCodeId = group + '-' + fieldSelector(vnAddressBlocks.fieldIds.wardCode);
        var el = document.getElementById(wardCodeId);
        if (!el || el.dataset.vnAddressHidden) {
            return;
        }
        el.dataset.vnAddressHidden = '1';
        var row = el.closest('.wc-block-components-text-input') || el.parentElement;
        if (row) {
            row.style.display = 'none';
        }
    }

    function scan() {
        ['billing', 'shipping'].forEach(function (group) {
            var wardId = group + '-' + fieldSelector(vnAddressBlocks.fieldIds.ward);
            var wardInput = document.getElementById(wardId);
            if (wardInput) {
                enhanceWardField(group, wardInput);
            }
            hideWardCodeField(group);
        });
    }

    var observer = new MutationObserver(function () {
        scan();
    });

    function start() {
        scan();
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
