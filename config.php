<?php

set_error_handler(function() {
    header("HTTP/1.1 500 Internal Server Error");
    include('erreur-500.php');
    exit();
});

set_error_handler(function() {
    header("HTTP/1.1 404 Internal Server Error");
    include('erreur-404.php');
    exit();
});

?>