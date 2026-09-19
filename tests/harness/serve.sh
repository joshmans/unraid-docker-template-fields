#!/bin/sh
# Serve the mock Add Container page on http://127.0.0.1:8766 (open /?selftest=1 to run the checks in the page).
cd "$(dirname "$0")" || exit 1
exec php -S 127.0.0.1:8766 router.php
