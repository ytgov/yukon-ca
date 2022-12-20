<?php

// Import all config changes.
echo "Apply database updates...\n";
passthru('drush updb -y');
echo "Database updates complete.\n";
