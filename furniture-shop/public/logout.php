<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

// Logging out is a POST with the CSRF token, so another site can't log visitors out with a link.
require_valid_post();
log_out();
redirect('login.php');
