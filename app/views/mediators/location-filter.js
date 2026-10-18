document.querySelectorAll('form[data-location-source]').forEach((form) => {
    const provinceSelect = form.querySelector('[data-location-province]');
    const wardSelect = form.querySelector('[data-location-ward]');
    const status = form.querySelector('.mediator-location-status')
        || form.parentElement.querySelector('.mediator-location-status');

    if (!provinceSelect || !wardSelect) {
        return;
    }

    const existingWard = wardSelect.value;
    wardSelect.disabled = true;

    const setWardOptions = (locations, preserveSelection) => {
        const province = locations.find((item) => item.slug === provinceSelect.value);
        const selectedWard = preserveSelection ? wardSelect.value : '';
        wardSelect.replaceChildren(new Option(
            province ? 'Tất cả phường/xã' : 'Chọn tỉnh/thành phố trước',
            ''
        ));

        if (!province) {
            wardSelect.disabled = true;
            return;
        }

        province.wards.forEach((ward) => {
            wardSelect.add(new Option(ward.name, ward.slug));
        });
        wardSelect.disabled = false;
        if (selectedWard && province.wards.some((ward) => ward.slug === selectedWard)) {
            wardSelect.value = selectedWard;
        }
    };

    fetch(form.dataset.locationSource, { headers: { Accept: 'application/json' } })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Location data request failed');
            }
            return response.json();
        })
        .then((payload) => {
            if (!payload || !Array.isArray(payload.data)) {
                throw new Error('Location data is invalid');
            }
            setWardOptions(payload.data, true);
            if (existingWard && wardSelect.value === '') {
                wardSelect.value = existingWard;
            }
            provinceSelect.addEventListener('change', () => setWardOptions(payload.data, false));
            if (status) {
                status.textContent = '';
            }
        })
        .catch(() => {
            wardSelect.replaceChildren(new Option('Không tải được danh sách phường/xã', ''));
            wardSelect.disabled = true;
            if (status) {
                status.textContent = 'Không thể tải danh sách địa điểm. Vui lòng tải lại trang.';
            }
        });
});
