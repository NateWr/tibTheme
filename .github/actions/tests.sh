#!/bin/bash

set -e

npx cypress run  --headless --browser chrome  --config integrationFolder=plugins/themes/tibTheme/cypress/tests
