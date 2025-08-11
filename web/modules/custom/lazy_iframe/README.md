# Iframe Lazy Loading

A lightweight Drupal module that automatically adds the `loading="lazy"` attribute to all `<iframe>` elements, improving page performance by deferring offscreen iframe loading.

## Table of contents
- Features
- Requirements
- Installation
- Configuration
- How it works
- How It Works

## Features
- **Automatic lazy loading**: Adds `loading="lazy"` to all iframes without manual intervention.
- **No configuration needed**: Works out of the box after installation.
- **Smart handling**: Skips iframes that already have a `loading` attribute.
- **Performance boost**: Reduces initial page load time and bandwidth usage.


## Requirements
- Drupal 10 or 11 (core only, no dependencies)

## Installation
1. Download and extract the module into your Drupal `modules/custom` directory.
2. Enable the module via:
   - **Admin UI**: Go to `Administration > Extend`, search for "Lazy Iframe", and enable it.
   - **Drush**: Run `drush en lazy_iframe -y`.

## How It Works
The module:
1. Scans all rendered content for `<iframe>` tags.
2. Adds `loading="lazy"` to iframes missing the attribute.
3. Preserves existing `loading` attributes (e.g., `loading="eager"`).

Example:
```html
<!-- Before -->
<iframe src="https://example.com"></iframe>

<!-- After -->
<iframe src="https://example.com" loading="lazy"></iframe>
```