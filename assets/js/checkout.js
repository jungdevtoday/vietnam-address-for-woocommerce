jQuery(document).ready(function($) {
    'use strict';

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    const VNAddress = {
        init: function() {
            // Check structure selector value first, fallback to default from settings
            const $structureSelector = $('#address_structure');
            if ($structureSelector.length) {
                this.structure = $structureSelector.val() || vnAddress.structure;
            } else {
                this.structure = vnAddress.structure;
            }
            
            // Update display BEFORE loading data
            this.updateStructureDisplay();
            
            // Load provinces
            this.loadProvinces();
            
            // Bind events
            this.bindEvents();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Structure selector change
            $('body').on('change', '#address_structure', function(e) {
                e.preventDefault();
                self.structure = $(this).val();
                
                // Update display
                self.updateStructureDisplay();
                
                // Reset dependent fields
                self.resetDependentFields();
                
                // Reload provinces with new structure
                self.reloadProvinces();
            });
            
            // Billing province change
            $('body').on('change', '#billing_province', function(e) {
                const provinceCode = $(this).val();
                
                if (!provinceCode) return;
                
                // Save province text
                const provinceText = $(this).find('option:selected').text();
                $('input[name="billing_province_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'billing_province_text',
                    value: provinceText
                }).appendTo('form.checkout');
                
                // Load next level based on structure
                if (self.structure === 'old') {
                    self.loadDistricts(provinceCode, 'billing');
                } else {
                    self.loadWards(provinceCode, 'billing');
                }
            });
            
            $('body').on('change', '#billing_district', function(e) {
                const districtCode = $(this).val();
                
                if (!districtCode) return;
                
                // Save district text
                const districtText = $(this).find('option:selected').text();
                $('input[name="billing_district_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'billing_district_text',
                    value: districtText
                }).appendTo('form.checkout');
                
                self.loadWards(districtCode, 'billing');
            });
            
            $('body').on('change', '#billing_ward', function() {
                const wardText = $(this).find('option:selected').text();
                $('input[name="billing_ward_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'billing_ward_text',
                    value: wardText
                }).appendTo('form.checkout');
            });
            
            // Shipping province change
            $('body').on('change', '#shipping_province', function(e) {
                const provinceCode = $(this).val();
                
                if (!provinceCode) return;
                
                // Save province text
                const provinceText = $(this).find('option:selected').text();
                $('input[name="shipping_province_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'shipping_province_text',
                    value: provinceText
                }).appendTo('form.checkout');
                
                // Load next level based on structure
                if (self.structure === 'old') {
                    self.loadDistricts(provinceCode, 'shipping');
                } else {
                    self.loadWards(provinceCode, 'shipping');
                }
            });
            
            $('body').on('change', '#shipping_district', function(e) {
                const districtCode = $(this).val();
                
                if (!districtCode) return;
                
                // Save district text
                const districtText = $(this).find('option:selected').text();
                $('input[name="shipping_district_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'shipping_district_text',
                    value: districtText
                }).appendTo('form.checkout');
                
                self.loadWards(districtCode, 'shipping');
            });
            
            $('body').on('change', '#shipping_ward', function() {
                const wardText = $(this).find('option:selected').text();
                $('input[name="shipping_ward_text"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'shipping_ward_text',
                    value: wardText
                }).appendTo('form.checkout');
            });
        },
        
        updateStructureDisplay: function() {
            
            if (this.structure === 'old') {
                // Show district fields
                $('#billing_district_field, #shipping_district_field').show();
                $('#billing_district, #shipping_district').prop('disabled', false).prop('required', true);
                $('.vn-address-old-only').addClass('show');
                
                // Update label to show required
                $('#billing_district_field label .optional').remove();
                $('#shipping_district_field label .optional').remove();
                if (!$('#billing_district_field label .required').length) {
                    $('#billing_district_field label').append(' <abbr class="required" title="' + escapeHtml(vnAddress.i18n.required) + '">*</abbr>');
                }
                if (!$('#shipping_district_field label .required').length) {
                    $('#shipping_district_field label').append(' <abbr class="required" title="' + escapeHtml(vnAddress.i18n.required) + '">*</abbr>');
                }
                
                // OLD structure: Province full width, District + Ward same row
                $('#billing_province_field, #shipping_province_field')
                    .removeClass('form-row-first')
                    .addClass('form-row-wide')
                    .css({'width': '100%', 'float': 'none', 'clear': 'both'});
                
                $('#billing_district_field, #shipping_district_field')
                    .addClass('form-row-first')
                    .css({'width': '48%', 'float': 'left', 'clear': 'left', 'margin-right': '4%'});
                
                $('#billing_ward_field, #shipping_ward_field')
                    .addClass('form-row-last')
                    .css({'width': '48%', 'float': 'right', 'clear': 'none'});
                
            } else {
                // Hide district fields
                $('#billing_district_field, #shipping_district_field').hide();
                $('#billing_district, #shipping_district').prop('disabled', true).prop('required', false).val('');
                $('.vn-address-old-only').removeClass('show');
                
                // Remove required indicator
                $('#billing_district_field label .required').remove();
                $('#shipping_district_field label .required').remove();
                
                // NEW structure: Province + Ward same row
                $('#billing_province_field, #shipping_province_field')
                    .removeClass('form-row-wide')
                    .addClass('form-row-first')
                    .css({'width': '48%', 'float': 'left', 'clear': 'left', 'margin-right': '4%'});
                
                $('#billing_ward_field, #shipping_ward_field')
                    .addClass('form-row-last')
                    .css({'width': '48%', 'float': 'right', 'clear': 'none'});
            }
        },
        
        resetDependentFields: function() {
            
            // Reset province, district and ward
            $('#billing_province, #shipping_province').val('');
            
            $('#billing_district, #shipping_district')
                .html('<option value="">' + escapeHtml(vnAddress.i18n.select_district) + '</option>')
                .val('');

            $('#billing_ward, #shipping_ward')
                .html('<option value="">' + escapeHtml(vnAddress.i18n.select_ward) + '</option>')
                .val('');
        },
        
        reloadProvinces: function() {
            const self = this;
            const $billingProvince = $('#billing_province');
            const $shippingProvince = $('#shipping_province');
            
            $.ajax({
                url: vnAddress.ajax_url,
                type: 'POST',
                data: {
                    action: 'vn_address_get_provinces',
                    nonce: vnAddress.nonce,
                    structure: self.structure
                },
                beforeSend: function() {
                    self.setLoading($billingProvince, true);
                    self.setLoading($shippingProvince, true);
                },
                success: function(response) {
                    
                    if (response.success && response.data && response.data.length > 0) {
                        const options = '<option value="">' + escapeHtml(vnAddress.i18n.select_province) + '</option>';
                        const provinceOptions = response.data.map(function(province) {
                            return '<option value="' + escapeHtml(province.code) + '">' + escapeHtml(province.type) + ' ' + escapeHtml(province.name) + '</option>';
                        }).join('');

                        $billingProvince.html(options + provinceOptions);
                        $shippingProvince.html(options + provinceOptions);
                    } else {
                    }
                },
                error: function(xhr, status, error) {
                },
                complete: function() {
                    self.setLoading($billingProvince, false);
                    self.setLoading($shippingProvince, false);
                }
            });
        },
        
        loadProvinces: function() {
            const self = this;
            const $billingProvince = $('#billing_province');
            const $shippingProvince = $('#shipping_province');
            
            if ($billingProvince.length === 0 && $shippingProvince.length === 0) {
                return;
            }
            
            // Check if already loaded
            if ($billingProvince.find('option').length > 1) {
                return;
            }
            
            $.ajax({
                url: vnAddress.ajax_url,
                type: 'POST',
                data: {
                    action: 'vn_address_get_provinces',
                    nonce: vnAddress.nonce,
                    structure: self.structure
                },
                beforeSend: function() {
                    self.setLoading($billingProvince, true);
                    self.setLoading($shippingProvince, true);
                },
                success: function(response) {
                    
                    if (response.success && response.data && response.data.length > 0) {
                        const options = '<option value="">' + escapeHtml(vnAddress.i18n.select_province) + '</option>';
                        const provinceOptions = response.data.map(function(province) {
                            return '<option value="' + escapeHtml(province.code) + '">' + escapeHtml(province.type) + ' ' + escapeHtml(province.name) + '</option>';
                        }).join('');

                        $billingProvince.html(options + provinceOptions);
                        $shippingProvince.html(options + provinceOptions);
                    } else {
                    }
                },
                error: function(xhr, status, error) {
                },
                complete: function() {
                    self.setLoading($billingProvince, false);
                    self.setLoading($shippingProvince, false);
                }
            });
        },
        
        loadDistricts: function(provinceCode, type) {
            const self = this;
            const $district = $('#' + type + '_district');
            const $ward = $('#' + type + '_ward');
            
            $.ajax({
                url: vnAddress.ajax_url,
                type: 'POST',
                data: {
                    action: 'vn_address_get_districts',
                    nonce: vnAddress.nonce,
                    province_code: provinceCode
                },
                beforeSend: function() {
                    self.setLoading($district, true);
                    // Reset ward
                    $ward.html('<option value="">' + escapeHtml(vnAddress.i18n.select_ward) + '</option>')
                        .val('')
                        .prop('disabled', true);
                },
                success: function(response) {

                    if (response.success && response.data && response.data.length > 0) {
                        const options = '<option value="">' + escapeHtml(vnAddress.i18n.select_district) + '</option>';
                        const districtOptions = response.data.map(function(district) {
                            return '<option value="' + escapeHtml(district.code) + '">' + escapeHtml(district.type) + ' ' + escapeHtml(district.name) + '</option>';
                        }).join('');
                        
                        $district.html(options + districtOptions).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                },
                complete: function() {
                    self.setLoading($district, false);
                }
            });
        },
        
        loadWards: function(parentCode, type) {
            const self = this;
            const $ward = $('#' + type + '_ward');
            
            $.ajax({
                url: vnAddress.ajax_url,
                type: 'POST',
                data: {
                    action: 'vn_address_get_wards',
                    nonce: vnAddress.nonce,
                    parent_code: parentCode,
                    structure: self.structure
                },
                beforeSend: function() {
                    self.setLoading($ward, true);
                },
                success: function(response) {
                    
                    if (response.success && response.data && response.data.length > 0) {
                        const options = '<option value="">' + escapeHtml(vnAddress.i18n.select_ward) + '</option>';
                        const wardOptions = response.data.map(function(ward) {
                            return '<option value="' + escapeHtml(ward.code) + '">' + escapeHtml(ward.type) + ' ' + escapeHtml(ward.name) + '</option>';
                        }).join('');
                        
                        $ward.html(options + wardOptions).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                },
                complete: function() {
                    self.setLoading($ward, false);
                }
            });
        },
        
        setLoading: function($element, isLoading) {
            const $formRow = $element.closest('.form-row');
            
            if (isLoading) {
                $element.prop('disabled', true);
                $formRow.addClass('vn-address-loading');
            } else {
                $element.prop('disabled', false);
                $formRow.removeClass('vn-address-loading');
            }
        }
    };
    
    // Initialize on document ready
    VNAddress.init();
    
    // Reinitialize on checkout update
    $(document.body).on('updated_checkout', function() {
        setTimeout(function() {
            VNAddress.init();
        }, 500);
    });
});
