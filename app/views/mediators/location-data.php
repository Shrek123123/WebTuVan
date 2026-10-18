<?php
if (!function_exists('mediator_request_value')) {
    function mediator_request_value($value)
    {
        return is_string($value) || is_int($value) || is_float($value) ? trim((string) $value) : '';
    }
}

if (!function_exists('mediator_location_catalog')) {
    function mediator_location_catalog()
    {
        static $catalog = null;
        if ($catalog !== null) {
            return $catalog;
        }

        $path = __DIR__ . '/../../data/vietnam-locations.json';
        if (!is_file($path)) {
            throw new RuntimeException('The Vietnam location dataset is missing: ' . $path);
        }
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new RuntimeException('The Vietnam location dataset could not be read: ' . $path);
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The Vietnam location dataset contains invalid JSON.', 0, $exception);
        }
        if (!is_array($decoded) || !is_array($decoded['data'] ?? null)) {
            throw new RuntimeException('The Vietnam location dataset has an invalid structure.');
        }

        $catalog = [];
        foreach ($decoded['data'] as $province) {
            if (!is_array($province) || !isset($province['slug'], $province['name']) || !is_array($province['wards'] ?? null)) {
                throw new RuntimeException('The Vietnam location dataset contains an invalid province entry.');
            }
            $wards = [];
            foreach ($province['wards'] as $ward) {
                if (!is_array($ward) || !isset($ward['slug'], $ward['name'])) {
                    throw new RuntimeException('The Vietnam location dataset contains an invalid ward entry.');
                }
                $wards[(string) $ward['slug']] = (string) $ward['name'];
            }
            $catalog[(string) $province['slug']] = [
                'name' => (string) $province['name'],
                'wards' => $wards,
            ];
        }
        if (!$catalog) {
            throw new RuntimeException('The Vietnam location dataset contains no provinces.');
        }
        return $catalog;
    }
}

if (!function_exists('mediator_normalize_location_pair')) {
    function mediator_normalize_location_pair($province, $ward)
    {
        $province = mediator_request_value($province);
        $ward = mediator_request_value($ward);
        $catalog = mediator_location_catalog();
        if (!isset($catalog[$province])) {
            return ['', ''];
        }
        if ($ward !== '' && !isset($catalog[$province]['wards'][$ward])) {
            $ward = '';
        }
        return [$province, $ward];
    }
}

if (!function_exists('mediator_location_pair_is_valid')) {
    function mediator_location_pair_is_valid($province, $ward)
    {
        $province = mediator_request_value($province);
        $ward = mediator_request_value($ward);
        if ($province === '' && $ward === '') {
            return true;
        }
        if ($province === '' || !isset(mediator_location_catalog()[$province])) {
            return false;
        }
        return $ward === '' || isset(mediator_location_catalog()[$province]['wards'][$ward]);
    }
}

if (!function_exists('mediator_application_url')) {
    function mediator_application_url($path)
    {
        $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        if ($scriptDirectory === '.' || $scriptDirectory === '/') {
            $scriptDirectory = '';
        } elseif (basename($scriptDirectory) === 'admin') {
            $scriptDirectory = dirname($scriptDirectory);
        }
        return rtrim($scriptDirectory, '/') . '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('mediator_location_select_fields')) {
    function mediator_location_select_fields($idPrefix, $provinceValue, $wardValue)
    {
        [$provinceValue, $wardValue] = mediator_normalize_location_pair($provinceValue, $wardValue);
        $catalog = mediator_location_catalog();
        $provinceId = $idPrefix . '-province';
        $wardId = $idPrefix . '-ward';
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $markup = '<div class="mediator-field"><label for="' . $escape($provinceId) . '">Tỉnh/thành phố</label>';
        $markup .= '<select id="' . $escape($provinceId) . '" name="province_location" data-location-province>';
        $markup .= '<option value="">Tất cả tỉnh/thành phố</option>';
        foreach ($catalog as $slug => $province) {
            $selected = $provinceValue === $slug ? ' selected' : '';
            $markup .= '<option value="' . $escape($slug) . '"' . $selected . '>' . $escape($province['name']) . '</option>';
        }
        $markup .= '</select></div>';

        $markup .= '<div class="mediator-field"><label for="' . $escape($wardId) . '">Phường/xã</label>';
        $markup .= '<select id="' . $escape($wardId) . '" name="district_location" data-location-ward' . ($provinceValue === '' ? ' disabled' : '') . '>';
        $markup .= '<option value="">' . ($provinceValue === '' ? 'Chọn tỉnh/thành phố trước' : 'Tất cả phường/xã') . '</option>';
        if ($provinceValue !== '') {
            foreach ($catalog[$provinceValue]['wards'] as $slug => $name) {
                $selected = $wardValue === $slug ? ' selected' : '';
                $markup .= '<option value="' . $escape($slug) . '"' . $selected . '>' . $escape($name) . '</option>';
            }
        }
        $markup .= '</select></div>';
        return $markup;
    }
}
