---
raw: true
title: "Assets Overview"
---

# Assets Overview

Any file placed into the configured `assets` folder will be directly copied into the `dist` folder. The files at the top level will be copied into the top level of the dist folder.

The assets folder is a perfect place to store all of your site assets.

* images
* JavaScript
* CSS
* webmanifest
* .htaccess
* favicons
* So much more...

### Cache Busting

Proton provides a `proton.build_time` variable containing a Unix timestamp that changes on every build. Use it to append cache-busting query strings to your asset URLs so browsers always load the latest version after a deploy:

```html
<link rel="stylesheet" href="/css/main.css?v={{ proton.build_time }}">
<script src="/js/app.js?v={{ proton.build_time }}"></script>
```
