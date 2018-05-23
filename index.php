<?php

require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

$quickProjects = new \UIOWA\QuickProjects\QuickProjects();
$quickProjects->displayRequestBuilderPage();