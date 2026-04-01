---
raw: true
title: "Generating a Sitemap"
nav_group: "Reference"
nav_order: 1
---

# Generating a Sitemap

By default, Proton will generate a `sitemap.xml` file for SEO. You can turn off this feature by setting the `sitemap` option in the config file to `false`.

One important setting that you will need to make sure that you set is the `domain` setting. This setting should contain the root URL of your website. For example: `https://www.example.com`.

## Last Modified Dates

Proton automatically includes `<lastmod>` dates in your sitemap entries based on the file modification time of each output file. This helps search engines prioritize crawling pages that have actually changed.
