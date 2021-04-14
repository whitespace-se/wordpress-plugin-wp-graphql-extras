# Whitespace WP GraphQL Extras plugin for Wordpress

Wordpress plugin that adds additional types and fields to the GraphQL API.

## How to install

If you want to use this plugin as an MU-plugin, first add this to your
composer.json:

```json
{
  "extra": {
    "installer-paths": {
      "path/to/your/mu-plugins/{$name}/": [
        "whitespace-se/wordpress-plugin-wp-graphql-extras"
      ]
    }
  }
}
```

Where `path/to/your/mu-plugins` is something like `wp-content/mu-plugins` or
`web/app/mu-plugins`.

Then get the plugin via composer:

```bash
composer require whitespace-se/wordpress-plugin-wp-graphql-extras
```

## Features

- Adds `archiveDates` and `archiveDatesGmt` fields to `ContentNode`
- Allows public querying of `labels` and `hasArchive` on `ContentType`
- Adds `contentMedia` field to `NodeWithContentEditor`
- Adds `width`, `height`, `fileContent` and `base64Uri` fields to `MediaItem`
- Adds `slug` field to `ContentType`
- Overrides response headers, allowed request headers and `max_query_amount`
