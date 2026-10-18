#!/bin/sh
# Wraps the paste-in fragment in a minimal document so it can be opened directly in a browser.
cd "$(dirname "$0")" || exit 1
{
  printf '<!doctype html>\n<html lang="en">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n<title>Fred Fitness promo preview</title>\n<style>html,body{margin:0;padding:0}</style>\n</head>\n<body>\n'
  cat fred-promo-landing.html
  printf '\n</body>\n</html>\n'
} > preview.html
