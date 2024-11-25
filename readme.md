# OJS Theme for TIB Open Publishing

In development. See [tib-op.org](https://www.tib-op.org).

## Usage

> This theme expects the [partnerLogos](https://github.com/NateWr/partnerLogos) to be installed and activated.

Clone this repository into the `plugins/themes` directory in OJS. Then run the following commands from the root directory of the project.

Install dependencies.

```
composer install
npm install
```

Build JS/CSS assets.

```
npm run build
```

## Contribute

This theme uses [Vite](https://vitejs.dev/) to build CSS/JS assets. Run the following commands to sync CSS/JS assets with Vite's HMR server while editing the theme.

```bash
npm run start
```
