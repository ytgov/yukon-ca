<?php

// Import all config changes.
echo "Importing configuration...\n";
passthru('drush config-import -y');
echo "Import of configuration complete.\n";
