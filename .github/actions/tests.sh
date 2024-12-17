#!/bin/bash

set -e

npm install --prefix plugins/themes/tibTheme
npm run build --prefix plugins/themes/tibTheme
npx cypress run --headless --browser chrome --config '{"specPattern":["plugins/themes/tibTheme/cypress/tests/functional/*.cy.{js,jsx,ts,tsx}"]}'
