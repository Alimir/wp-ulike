Translations are managed on GlotPress:
https://translate.wordpress.org/projects/wp-plugins/wp-ulike

Block editor (JavaScript) strings
---------------------------------
WordPress loads Jed JSON files (not .mo) for block scripts. After building blocks:

1. Update the template catalog:
   npm run i18n:pot

2. Build blocks and generate JSON (requires .po files in this folder, or copy them here):
   npm run build:blocks:i18n

Or, if language packs live in wp-content/languages/plugins/ (typical on a local site):

   wp i18n make-json ../../../languages/plugins --no-purge

JSON files are named wp-ulike-{locale}-{hash}.json. WordPress.org language packs
include them automatically. For local development, run make-json after npm run build
so references point at includes/blocks/*/build/index.js.
