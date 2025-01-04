# Free Backlink Generator WordPress Plugin

A WordPress plugin that helps generate and verify backlinks for any given website URL.

## Features

- Generate backlinks from customizable URL templates
- Real-time link status checking
- Client-side URL verification (no server load)
- Modern, responsive interface
- Copy all generated links with one click
- Rate limiting to prevent abuse
- Line-numbered template editor in admin

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and choose the downloaded zip file
4. Click "Install Now" and then "Activate"

## Usage

1. Go to Settings > Backlink Generator in your WordPress admin panel
2. Add your URL templates in the admin settings
3. Visit the plugin page and enter your website URL
4. Click "Generate Links" to create and verify backlinks

### Example URL Templates

Here are some example templates you can use. Add these to the URL templates section in the admin settings (one per line):

```
https://securityheaders.com/?q={website}
https://europa.eu/europass/eportfolio/screen/redirect-external?url=https:%2F%2F{website}&lang=en
https://bytecheck.com/results?resource={website}
https://whoisology.com/{website}
https://builtwith.com/{website}
https://www.woorank.com/en/teaser-review/{website}
https://www.deviantart.com/users/outgoing?{website}
https://proza.ru/go/{website}
https://dnswhois.info/dnsip/{website}
https://rbls.org/dns-lookup/{website}
https://www.statscrop.com/www/{website}
https://website.informer.com/{website}
```

Replace `{website}` with your target website URL when using the plugin.

## Configuration

You can customize URL templates in the WordPress admin panel under Settings > Backlink Generator. Each template should be on a new line and use `{website}` as a placeholder for the target URL.

## Author

Saadulla D.
- GitHub: [saadulla](https://github.com/saadulla)

## License

This project is licensed under the GPL v2 or later
