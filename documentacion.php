<?php
declare(strict_types=1);

// Redireccionar al dashboard principal
if (!headers_sent()) {
    header('Location: index.php');
    exit();
}
