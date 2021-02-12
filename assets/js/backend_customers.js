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

window.BackendCustomers = window.BackendCustomers || {};

/**
 * Backend Customers
 *
 * Backend Customers javascript namespace. Contains the main functionality of the backend customers
 * page. If you need to use this namespace in a different page, do not bind the default event handlers
 * during initialization.
 *
 * @module BackendCustomers
 */
(function (exports) {

    'use strict';

	// MCY - added
    /**
     * Minimum Password Length
     *
     * @type {Number}
     */
    exports.MIN_PASSWORD_LENGTH = 7;
	// MCY - end of added

    /**
     * The page helper contains methods that implement each record type functionality
     * (for now there is only the CustomersHelper).
     *
     * @type {Object}
     */
    var helper = {};

    /**
     * This method initializes the backend customers page. If you use this namespace
     * in a different page do not use this method.
     *
     * @param {Boolean} defaultEventHandlers Optional (false), whether to bind the default
     * event handlers or not.
     */
    exports.initialize = function (defaultEventHandlers) {
        defaultEventHandlers = defaultEventHandlers || false;

        // Add the available languages to the language dropdown.
        availableLanguages.forEach(function (language) {
            $('#language').append(new Option(language, language));
        });

        helper = new CustomersHelper();

		// MCY - added
		// Update the list with the all the available providers.
		var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_providers';

		var data = {
			csrfToken: GlobalVariables.csrfToken,
			key: ''
		};

		$.post(url, data)
			.done(function (response) {
				GlobalVariables.providers = response;

				$('#customer-providers').empty();

				GlobalVariables.providers.forEach(function (provider) {
					$('<div/>', {
						'class': 'checkbox',
						'html': [
							$('<div/>', {
								'class': 'checkbox form-check',
								'html': [
									$('<input/>', {
										'class': 'form-check-input',
										'type': 'checkbox',
										'data-id': provider.id,
										'prop': {
											'disabled': true
										}
									}),
									$('<label/>', {
										'class': 'form-check-label',
										'text': provider.first_name + ' ' + provider.last_name,
										'for': provider.id
									}),
								]
							})
						]
					})
						.appendTo('#customer-providers');
				});
			});
		// MCY - end of added
		
        helper.resetForm();
        helper.filter('');
        helper.bindEventHandlers();

        if (defaultEventHandlers) {
            bindEventHandlers();
        }
    };

    /**
     * Default event handlers declaration for backend customers page.
     */
    function bindEventHandlers() {
        //
    }

})(window.BackendCustomers);
