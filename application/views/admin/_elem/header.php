<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="<?php echo $settings['site_name']; ?> - Admin panel">
        <meta name="author" content="Proxibolt">
        <meta name="keyword" content="">

        <title><?php echo $settings['site_name']; ?> - Admin Panel</title>

        <base href="<?php echo $settings['site_url'] ?>admin/">

        <link rel="shortcut icon" type="image/png" href="../<?php echo $settings['favicon_path']; ?>"/>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext">
        <link rel="stylesheet" href="https://unpkg.com/@tabler/core@1.0.0-beta3/dist/css/tabler.min.css">
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/@tabler/core@1.0.0-beta3/dist/css/tabler-flags.min.css">
        <link rel="stylesheet" href="https://unpkg.com/@tabler/core@1.0.0-beta3/dist/css/tabler-payments.min.css">
        <link rel="stylesheet" href="https://unpkg.com/@tabler/core@1.0.0-beta3/dist/css/tabler-vendors.min.css">
        <link href="../assets/admin/assets/css/dashboard.css?v=<?php echo $settings['version'] ?>" rel="stylesheet" />
        <link href="../assets/admin/assets/plugins/charts-c3/plugin.css" rel="stylesheet" />
        <link href="../assets/admin/assets/plugins/maps-google/plugin.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/spectrum-colorpicker2/dist/spectrum.min.css">

        <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/spectrum-colorpicker2/dist/spectrum.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/luxon/3.3.0/luxon.min.js" integrity="sha512-KKbQg5o92MwtJKR9sfm/HkREzfyzNMiKPIQ7i7SZOxwEdiNCm4Svayn2DBq7MKEdrqPJUOSIpy1v6PpFlCQ0YA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.4.4/js/tabulator.min.js" integrity="sha512-BGo9xGWI32ZfTMp/ueR3fZKO5nYkPbt3uwMWr+w8w3e72H8A0sTQGO+iqRADwIT0eJkFP1HDgSC82gA4sTuQ6w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.4.4/css/tabulator_bootstrap5.min.css" integrity="sha512-E3brrBS070QzILPd7448M+21NlCrA3fht9RmFvS9GIKo443nGQRF3tVOuK7YcY1gzPQsaks2cP/Ivp/yhoJCNA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <style>
            .tabulator {
                font-size: 14px;
            }
        </style>
    </head>

    <body class="antialiased <?php echo (get_cookie('admin_dark_mode') == 'true' ? 'theme-dark' : '') ?>">
        <div class="wrapper">