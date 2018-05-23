<?php

$module = new UIOWA\QuickProjects\QuickProjects();
$module->generateProject();

if ($_POST['redirect']) {
    $redirect = $_POST['redirect'];
    header("Location: $redirect");
}