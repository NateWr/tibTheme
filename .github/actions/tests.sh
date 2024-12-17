#!/bin/bash

set -e

sudo n 18.17.1
npm install --prefix plugins/themes/tibTheme
npm run build --prefix plugins/themes/tibTheme
npx cypress run  --headless --browser chrome  --config integrationFolder=plugins/themes/tibTheme/cypress/tests
