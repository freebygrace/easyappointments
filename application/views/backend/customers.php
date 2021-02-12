<script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui-timepicker-addon.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_customers_helper.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_customers.js') ?>"></script>
<script>
    var GlobalVariables = {
        csrfToken: <?= json_encode($this->security->get_csrf_hash()) ?>,
        availableProviders: <?= json_encode($available_providers) ?>,
        availableServices: <?= json_encode($available_services) ?>,
        secretaryProviders: <?= json_encode($secretary_providers) ?>,
        dateFormat: <?= json_encode($date_format) ?>,
        timeFormat: <?= json_encode($time_format) ?>,
        baseUrl: <?= json_encode($base_url) ?>,
        customers: <?= json_encode($customers) ?>,
        timezones: <?= json_encode($timezones) ?>,
        // MCY - added 
        defaultTimezone: <?= json_encode($default_timezone) ?>,
        // MCY - end of added
        user: {
            id: <?= $user_id ?>,
            email: <?= json_encode($user_email) ?>,
            timezone: <?= json_encode($timezone) ?>,
            role_slug: <?= json_encode($role_slug) ?>,
            privileges: <?= json_encode($privileges) ?>
        }
    };

    $(function () {
        BackendCustomers.initialize(true);
    });
</script>

<div class="container-fluid backend-page" id="customers-page">
    <div class="row" id="customers">
        <div id="filter-customers" class="filter-records col col-12 col-md-5">
            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control">

                    <div class="input-group-addon">
                        <div>
                            <button class="filter btn btn-outline-secondary" type="submit"
                                    data-tippy-content="<?= lang('filter') ?>">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="clear btn btn-outline-secondary" type="button"
                                    data-tippy-content="<?= lang('clear') ?>">
                                <i class="fas fa-redo-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <h3><?= lang('customers') ?></h3>
            <div class="results"></div>
        </div>

        <div class="record-details col-12 col-md-7">
            <div class="btn-toolbar mb-4">
                <div id="add-edit-delete-group" class="btn-group">
                    <?php if ($privileges[PRIV_CUSTOMERS]['add'] === TRUE): ?>
                        <button id="add-customer" class="btn btn-primary">
                            <i class="fas fa-plus-square mr-2"></i>
                            <?= lang('add') ?>
                        </button>
                    <?php endif ?>

                    <?php if ($privileges[PRIV_CUSTOMERS]['edit'] === TRUE): ?>
                        <button id="edit-customer" class="btn btn-outline-secondary" disabled="disabled">
                            <i class="fas fa-edit mr-2"></i>
                            <?= lang('edit') ?>
                        </button>
                    <?php endif ?>

                    <?php if ($privileges[PRIV_CUSTOMERS]['delete'] === TRUE): ?>
                        <button id="delete-customer" class="btn btn-outline-secondary" disabled="disabled">
                            <i class="fas fa-trash-alt mr-2"></i>
                            <?= lang('delete') ?>
                        </button>
                    <?php endif ?>
                </div>

                <div id="save-cancel-group" class="btn-group" style="display:none;">
                    <button id="save-customer" class="btn btn-primary">
                        <i class="fas fa-check-square mr-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-customer" class="btn btn-outline-secondary">
                        <i class="fas fa-ban mr-2"></i>
                        <?= lang('cancel') ?>
                    </button>
                </div>
            </div>

            <h3><?= lang('details') ?></h3>

            <input id="customer-id" type="hidden">

            <div class="row">
                <div class="col-xs-12 col-sm-4" style="margin-left: 0;">
                    <div id="form-message" class="alert" style="display:none;"></div>

                    <div class="form-group">
                        <label class="control-label" for="first-name">
                            <?= lang('first_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="first-name" class="form-control required">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="last-name">
                            <?= lang('last_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="last-name" class="form-control required">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="email">
                            <?= lang('email') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input id="email" class="form-control required">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="phone-number">
                            <?= lang('phone_number') ?>
                            <?= $require_phone_number === '1' ? '<span class="text-danger">*</span>' : ''; ?></label>
                        <input id="phone-number" class="form-control
                            <?= $require_phone_number === '1' ? 'required' : '' ?>">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="address">
                            <?= lang('address') ?>
                        </label>
                        <input id="address" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="city">
                            <?= lang('city') ?>

                        </label>
                        <input id="city" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="zip-code">
                            <?= lang('zip_code') ?>
                        </label>
                        <input id="zip-code" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="language">
                            <?= lang('language') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <select id="language" class="form-control required"></select>
                    </div>

					<!-- MCY - removed - duplicated below
                    <div class="form-group">
                        <label for="timezone">
                            <?= lang('timezone') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <?= render_timezone_dropdown('id="timezone" class="form-control required"') ?>
                    </div>
					MCY - end of removed -->

                    <div class="form-group">
                        <label class="control-label" for="notes">
                            <?= lang('notes') ?>

                        </label>
                        <textarea id="notes" rows="4" class="form-control"></textarea>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-4">
					<!-- MCY - added -->
					<div class="form-group">
						<label for="customer-username">
							<?= lang('username') ?>
							<span class="text-danger">*</span>
						</label>
						<input id="customer-username" class="form-control required" maxlength="256">
					</div>								

					<div class="form-group">
						<label for="customer-password">
							<?= lang('password') ?>
							<span class="text-danger">*</span>
						</label>
						<input type="password" id="customer-password" class="form-control required"
							   maxlength="512" autocomplete="new-password">
					</div>
					
					<div class="form-group">
						<label for="customer-password-confirm">
							<?= lang('retype_password') ?>
							<span class="text-danger">*</span>
						</label>
						<input type="password" id="customer-password-confirm" class="form-control required"
							   maxlength="512" autocomplete="new-password">
					</div>

					<div class="form-group">
						<label for="customer-calendar-view">
							<?= lang('calendar') ?>
							<span class="text-danger">*</span>
						</label>
						<select id="customer-calendar-view" class="form-control required">
							<option value="default">Default</option>
							<!-- MCY - removed
							<option value="table">Table</option>
                            MCY - end of removed -->
						</select>
					</div>

					<div class="form-group">
						<label for="customer-timezone">
							<?= lang('timezone') ?>
							<span class="text-danger">*</span>
						</label>
						<?= render_timezone_dropdown('id="customer-timezone" class="form-control required"') ?>
					</div>

					<br>
					
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="customer-notifications">
						<label class="custom-control-label" for="customer-notifications">
							<?= lang('receive_notifications') ?>
						</label>
					</div>

					<br>

					<h4><?= lang('providers') ?></h4>
                    <div id="customer-providers" class="card card-body bg-light border-light"></div>
				</div>
				
				<div class="col-xs-12 col-sm-4">
				<!-- MCY - end of added -->					
                    <h4><?= lang('appointments') ?></h4>
                    <div id="customer-appointments" class="well"></div>
                    <div id="appointment-details" class="well hidden"></div>
                </div>
            </div>
        </div>
    </div>
</div>
