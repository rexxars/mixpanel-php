<?php
header('Content-Type: application/json');
if (stripos($_SERVER['REQUEST_URI'], '/track') === 0) {
    echo '1';
} else {
    echo '0';
}