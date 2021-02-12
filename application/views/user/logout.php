<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35A768">

    <title><?= lang('log_out') ?> | CWALC Scheduler</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/logout.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">

    <script>
        var EALang = <?= json_encode($this->lang->language) ?>;
    </script>

    <script src="<?= asset_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/fontawesome/js/fontawesome.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/fontawesome/js/solid.min.js') ?>"></script>
</head>
<body>
<div id="logout-frame" class="frame-container">
	<div class="container-fluid">
        <div class="row">
			<div class="col-md-4">
				<img src="<?= base_url('assets/img/logo.png') ?>">
            </div>
            <div class="col-md-8">
				<h2><?= lang('application_name') ?></h2>
				<h5><?= lang('logout_success') ?></h5>
			</div>
        </div>
        <div class="row">
			<div class="col-md-12">
				<hr>

				<a href="<?= site_url('backend') ?>" class="btn btn-outline-secondary btn-large">
					<i class="fas fa-wrench mr-2"></i>
					<?= lang('login') ?>
				</a>

				<div class="mt-4">
					<small>
						Powered by
						<a href="https://easyappointments.org">Easy!Appointments</a>
					</small>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
