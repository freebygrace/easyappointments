/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

(function () {

    'use strict';

    /**
     * CustomersHelper Class
     *
     * This class contains the methods that are used in the backend customers page.
     *
     * @class CustomersHelper
     */
    function CustomersHelper() {
        this.filterResults = {};
        this.filterLimit = 20;
    }

    /**
     * Binds the default event handlers of the backend customers page.
     */
    CustomersHelper.prototype.bindEventHandlers = function () {
        var instance = this;

        /**
         * Event: Filter Customers Form "Submit"
         *
         * @param {jQuery.Event} event
         */
        $('#customers').on('submit', '#filter-customers form', function (event) {
            event.preventDefault();
            var key = $('#filter-customers .key').val();
            $('#filter-customers .selected').removeClass('selected');
            instance.filterLimit = 20;
            instance.resetForm();
            instance.filter(key);
        });

        /**
         * Event: Filter Customers Clear Button "Click"
         */
        $('#customers').on('click', '#filter-customers .clear', function () {
            $('#filter-customers .key').val('');
            instance.filterLimit = 20;
            instance.filter('');
            instance.resetForm();
        });

        /**
         * Event: Filter Entry "Click"
         *
         * Display the customer data of the selected row.
         */
        $('#customers').on('click', '.customer-row', function () {
            if ($('#filter-customers .filter').prop('disabled')) {
                return; // Do nothing when user edits a customer record.
            }

            var customerId = $(this).attr('data-id');
            var customer = instance.filterResults.find(function (filterResult) {
                return Number(filterResult.id) === Number(customerId);
            });

            instance.display(customer);
            $('#filter-customers .selected').removeClass('selected');
            $(this).addClass('selected');
            $('#edit-customer, #delete-customer').prop('disabled', false);
        });

        /**
         * Event: Add Customer Button "Click"
         */
        $('#customers').on('click', '#add-customer', function () {
            instance.resetForm();
            $('#add-edit-delete-group').hide();
            $('#save-cancel-group').show();
            $('.record-details')
                .find('input, select, textarea')
                .prop('disabled', false);
			
            $('#filter-customers button').prop('disabled', true);
            $('#filter-customers .results').css('color', '#AAA');

			// MCY - added
            $('#customer-password, #customer-password-confirm').addClass('required');
			// MCY - end of added
        });

        /**
         * Event: Edit Customer Button "Click"
         */
        $('#customers').on('click', '#edit-customer', function () {
            $('.record-details')
                .find('input, select, textarea')
                .prop('disabled', false);
            $('#add-edit-delete-group').hide();
            $('#save-cancel-group').show();
			
            $('#filter-customers button').prop('disabled', true);
            $('#filter-customers .results').css('color', '#AAA');
            
			// MCY - added
            $('#customer-password, #customer-password-confirm').removeClass('required');
			// MCY - end of added
        });

        /**
         * Event: Cancel Customer Add/Edit Operation Button "Click"
         */
        $('#customers').on('click', '#cancel-customer', function () {
            var id = $('#customer-id').val();
            instance.resetForm();
            if (id) {
                instance.select(id, true);
            }
        });

        /**
         * Event: Save Add/Edit Customer Operation "Click"
         */
        $('#customers').on('click', '#save-customer', function () {
            var customer = {
                first_name: $('#first-name').val(),
                last_name: $('#last-name').val(),
                email: $('#email').val(),
                phone_number: $('#phone-number').val(),
                address: $('#address').val(),
                city: $('#city').val(),
                zip_code: $('#zip-code').val(),
                notes: $('#notes').val(),
				// MCY - added
				settings: {
                    username: $('#customer-username').val(),
                    notifications: $('#customer-notifications').prop('checked'),
                    calendar_view: $('#customer-calendar-view').val()
				},
				// MCY - end of added
				
                //timezone: $('#timezone').val(),
                timezone: $('#customer-timezone').val(),
                language: $('#language').val() || 'english'
            };

            // MCY - added - include customer locations
            customer.providers = [];
            $('#customer-providers input:checkbox').each(function () {
                if ($(this).prop('checked')) {
                    customer.providers.push($(this).attr('data-id'));
                }
            });   

			// Include password if changed.
            if ($('#customer-password').val() !== '') {
                customer.settings.password = $('#customer-password').val();
            }
			// MCY - end of added

            if ($('#customer-id').val()) {
                customer.id = $('#customer-id').val();
            }

            if (!instance.validate()) {
                return;
            }

            instance.save(customer);
        });

        /**
		
		// MCY - added
        /**
         * Event: Customer Username "Focusout"
         *
         * When the user leaves the username input field we will need to check if the username
         * is not taken by another record in the system. Usernames must be unique.
         */
        $('#customer-username').focusout(function () {
            var $input = $(this);

            if ($input.prop('readonly') == true || $input.val() == '') {
                return;
            }

            var userId = $input.parents().eq(2).find('.record-id').val();

            if (userId == undefined) {
                return;
            }

            var postUrl = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_validate_username';
            var postData = {
                csrfToken: GlobalVariables.csrfToken,
                username: $input.val(),
                user_id: userId
            };

            $.post(postUrl, postData, function (response) {
                if (!GeneralFunctions.handleAjaxExceptions(response)) {
                    return;
                }

                if (response == false) {
                    $input.closest('.form-group').addClass('has-error');
                    $input.attr('already-exists', 'true');
                    $input.parents().eq(3).find('.form-message').text(EALang.username_already_exists);
                    $input.parents().eq(3).find('.form-message').show();
                } else {
                    $input.closest('.form-group').removeClass('has-error');
                    $input.attr('already-exists', 'false');
                    if ($input.parents().eq(3).find('.form-message').text() == EALang.username_already_exists) {
                        $input.parents().eq(3).find('.form-message').hide();
                    }
                }
            }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
        });
		// MCY - end of added
    };

    /**
     * Save a customer record to the database (via ajax post).
     *
     * @param {Object} customer Contains the customer data.
     */
    CustomersHelper.prototype.save = function (customer) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_customer';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            customer: JSON.stringify(customer)
        };

        $.post(url, data)
            .done(function (response) {
                Backend.displayNotification(EALang.customer_saved);
                this.resetForm();
                $('#filter-customers .key').val('');
                this.filter('', response.id, true);
            }.bind(this));
    };

    /**
     * Delete a customer record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    CustomersHelper.prototype.delete = function (id) {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_customer';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            customer_id: id
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.customer_deleted);
                this.resetForm();
                this.filter($('#filter-customers .key').val());
            }.bind(this));
    };

    /**
     * Validate customer data before save (insert or update).
     */
    CustomersHelper.prototype.validate = function () {
        $('#form-message')
            .removeClass('alert-danger')
            .hide();
        $('.has-error').removeClass('has-error');

        try {
            // Validate required fields.
            var missingRequired = false;

            $('.required').each(function (index, requiredField) {
                if ($(requiredField).val() === '') {
                    $(requiredField).closest('.form-group').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(EALang.fields_are_required);
            }

			// MCY - added
            // Validate passwords.
            if ($('#customer-password').val() != $('#customer-password-confirm').val()) {
                $('#customer-password, #customer-password-confirm').closest('.form-group').addClass('has-error');
                throw 'Passwords mismatch!';
            }

            if ($('#customer-password').val().length < BackendCustomers.MIN_PASSWORD_LENGTH
                && $('#customer-password').val() != '') {
                $('#customer-password, #customer-password-confirm').closest('.form-group').addClass('has-error');
                throw 'Password must be at least ' + BackendCustomers.MIN_PASSWORD_LENGTH
                + ' characters long.';
            }
			// MCY - end of added

            // Validate email address.
            if (!GeneralFunctions.validateEmail($('#email').val())) {
                $('#email').closest('.form-group').addClass('has-error');
                throw new Error(EALang.invalid_email);
            }

			
			// MCY - added
            // Check if username exists
            if ($('#customer-username').attr('already-exists') == 'true') {
                $('#customer-username').closest('.form-group').addClass('has-error');
                throw 'Username already exists.';
            }			
			// MCY - end of added

            return true;
        } catch (error) {
            $('#form-message')
                .addClass('alert-danger')
                .text(error.message)
                .show();
            return false;
        }
    };

    /**
     * Bring the customer form back to its initial state.
     */
    CustomersHelper.prototype.resetForm = function () {
        $('.record-details')
            .find('input, select, textarea')
            .val('')
            .prop('disabled', true);
			
        // MCY - removed - see below
        //$('.record-details #timezone').val('UTC');
        // MCY - end of removed

        $('#language').val('english');

        $('#customer-appointments').empty();
        $('#edit-customer, #delete-customer').prop('disabled', true);	
        $('#add-edit-delete-group').show();
        $('#save-cancel-group').hide();

        $('.record-details .has-error').removeClass('has-error');
        $('.record-details #form-message').hide();

		// MCY - added
        $('.record-details #customer-calendar-view').val('default');
        $('.record-details #customer-timezone').val(GlobalVariables.defaultTimezone);
        $('#customer-notifications').prop('checked', true);
        $('#customer-providers input:checkbox').prop('checked', false);
        $('#customer-providers input:checkbox').prop('disabled', true);
		// MCY - end of added

        $('#filter-customers button').prop('disabled', false);
        $('#filter-customers .selected').removeClass('selected');
        $('#filter-customers .results').css('color', '');
    };

    /**
     * Display a customer record into the form.
     *
     * @param {Object} customer Contains the customer record data.
     */
    CustomersHelper.prototype.display = function (customer) {
        $('#customer-id').val(customer.id);
        $('#first-name').val(customer.first_name);
        $('#last-name').val(customer.last_name);
        $('#email').val(customer.email);
        $('#phone-number').val(customer.phone_number);
        $('#address').val(customer.address);
        $('#city').val(customer.city);
        $('#zip-code').val(customer.zip_code);
        $('#notes').val(customer.notes);
        // MCY - changed
//        $('#timezone').val(customer.timezone);
        $('#customer-timezone').val(customer.timezone);
        // MCY - end of changed
        $('#language').val(customer.language || 'english');

		// MCY - added
		$('#customer-username').val(customer.settings.username);
        $('#customer-calendar-view').val(customer.settings.calendar_view);
        $('#customer-notifications').prop('checked', Boolean(Number(customer.settings.notifications)));
		
        $('#customer-providers input:checkbox').prop('checked', false);
        $.each(customer.providers, function (index, providerId) {
            $('#customer-providers input:checkbox').each(function () {
                if ($(this).attr('data-id') == providerId) {
                    $(this).prop('checked', true);
                }
            });
        });
		// MCY - end of added

        $('#customer-appointments').empty();

        if (!customer.appointments.length) {
            $('<p/>', {
                'text': EALang.no_records_found
            })
                .appendTo('#customer-appointments');
        }

        customer.appointments.forEach(function (appointment) {
            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_PROVIDER && parseInt(appointment.id_users_provider) !== GlobalVariables.user.id) {
                return;
            }

            if (GlobalVariables.user.role_slug === Backend.DB_SLUG_SECRETARY && GlobalVariables.secretaryProviders.indexOf(appointment.id_users_provider) === -1) {
                return;
            }

            var start = GeneralFunctions.formatDate(Date.parse(appointment.start_datetime), GlobalVariables.dateFormat, true);
            var end = GeneralFunctions.formatDate(Date.parse(appointment.end_datetime), GlobalVariables.dateFormat, true);

            $('<div/>', {
                'class': 'appointment-row',
                'data-id': appointment.id,
                'html': [
                    // Service - Provider

                    $('<a/>', {
                        'href': GlobalVariables.baseUrl + '/index.php/backend/index/' + appointment.hash,
                        'html': [
                            $('<i/>', {
                                'class': 'fas fa-edit mr-1'
                            }),
                            $('<strong/>', {
                                'text': appointment.provider.first_name + ' ' + appointment.provider.last_name
                            }),
                            $('<br/>'),
                        ]
                    }),

                    // Start

                    $('<small/>', {
                        // MCY - changed
                        //'text': start
                        'text': ((appointment.notes) ? EALang.confirmed : EALang.pending) + ' - ' + start
                        // MCY - end of changed
                    }),
                    $('<br/>'),

                    /** MCY - removed
                    // End

                    $('<small/>', {
                        'text': end
                    }),
                    $('<br/>'),

                    // Timezone

                    $('<small/>', {
                        'text': GlobalVariables.timezones[appointment.provider.timezone]
                    })
                    MCY - end of removed */
                ]
            })
                .appendTo('#customer-appointments');
        });
    };

    /**
     * Filter customer records.
     *
     * @param {String} key This key string is used to filter the customer records.
     * @param {Number} selectId Optional, if set then after the filter operation the record with the given
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    CustomersHelper.prototype.filter = function (key, selectId, display) {
        display = display || false;

        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_customers';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            key: key,
            limit: this.filterLimit
        };

        $.post(url, data)
            .done(function (response) {
                this.filterResults = response;

                $('#filter-customers .results').empty();

                response.forEach(function (customer) {
                    $('#filter-customers .results')
                        .append(this.getFilterHtml(customer))
                        .append($('<hr/>'));
                }.bind(this));

                if (!response.length) {
                    $('#filter-customers .results').append(
                        $('<em/>', {
                            'text': EALang.no_records_found
                        })
                    );
                } else if (response.length === this.filterLimit) {
                    $('<button/>', {
                        'type': 'button',
                        'class': 'btn btn-block btn-outline-secondary load-more text-center',
                        'text': EALang.load_more,
                        'click': function () {
                            this.filterLimit += 20;
                            this.filter(key, selectId, display);
                        }.bind(this)
                    })
                        .appendTo('#filter-customers .results');
                }

                if (selectId) {
                    this.select(selectId, display);
                }

            }.bind(this));
    };

    /**
     * Get the filter results row HTML code.
     *
     * @param {Object} customer Contains the customer data.
     *
     * @return {String} Returns the record HTML code.
     */
    CustomersHelper.prototype.getFilterHtml = function (customer) {
        var name = customer.first_name + ' ' + customer.last_name;

        var info = customer.email;

        info = customer.phone_number ? info + ', ' + customer.phone_number : info;

        return $('<div/>', {
            'class': 'customer-row entry',
            'data-id': customer.id,
            'html': [
                $('<strong/>', {
                    'text': name
                }),
                $('<br/>'),
                $('<span/>', {
                    'text': info
                }),
                $('<br/>'),
            ]
        });
    };

    /**
     * Select a specific record from the current filter results.
     *
     * If the customer id does not exist in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true then the method will display the record
     * on the form.
     */
    CustomersHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-customers .selected').removeClass('selected');

        $('#filter-customers .entry[data-id="' + id + '"]').addClass('selected');

        if (display) {
            var customer = this.filterResults.find(function (filterResult) {
                return Number(filterResult.id) === Number(id);
            });

            this.display(customer);

            $('#edit-customer, #delete-customer').prop('disabled', false);
        }
    };

    window.CustomersHelper = CustomersHelper;
})();
