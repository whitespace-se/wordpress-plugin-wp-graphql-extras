<?php
/**
 * Plugin Name: Whitespace WP GraphQL Extras
 * Plugin URI: -
 * Description: Adds additional types and fields to the GraphQL API
 * Version: 0.1.1
 * Author: Whitespace
 * Author URI: https://www.whitespace.se/
 */

namespace WhitespaceWPGraphQLExtras;

use WPGraphQL\Model\Post;

/**
 * Adds `archiveDates` and `archiveDatesGmt` fields to `ContentNode`
 */
add_action(
  "graphql_register_types",
  function ($type_registry) {
    $type_registry->register_field("ContentNode", "archiveDates", [
      "type" => ["list_of" => "String"],
      "resolve" => function ($post) {
        $value = [$post->date];
        $value = apply_filters(
          "wp-graphql-extras/ContentNode/archiveDates/value",
          $value,
          $post,
        );
        return $value;
      },
    ]);
    $type_registry->register_field("ContentNode", "archiveDatesGmt", [
      "type" => ["list_of" => "String"],

      "resolve" => function ($post) {
        $value = [$post->dateGmt];
        $value = apply_filters(
          "wp-graphql-extras/ContentNode/archiveDatesGmt/value",
          $value,
          $post,
        );
        return $value;
      },
    ]);
  },
  10,
  1,
);

/**
 * Adds `width`, `height` and `fileContent` fields to `MediaItem`
 */
add_action("graphql_register_types", function ($type_registry) {
  $type_registry->register_field("MediaItem", "width", [
    "type" => "Integer",
    "args" => [
      "size" => [
        "type" => "MediaItemSizeEnum",
        "description" => __(
          "Size of the MediaItem to calculate sizes with",
          "wp-graphql",
        ),
      ],
    ],
    "description" => __(
      "The width attribute value for an image.",
      "wp-graphql",
    ),
    "resolve" => function ($source, $args) {
      $size = "medium";
      if (!empty($args["size"])) {
        $size = $args["size"];
      }
      $src = wp_get_attachment_image_src($source->ID, $size);
      return $src[1];
    },
  ]);
  $type_registry->register_field("MediaItem", "height", [
    "type" => "Integer",
    "args" => [
      "size" => [
        "type" => "MediaItemSizeEnum",
        "description" => __(
          "Size of the MediaItem to calculate sizes with",
          "wp-graphql",
        ),
      ],
    ],
    "description" => __(
      "The height attribute value for an image.",
      "wp-graphql",
    ),
    "resolve" => function ($source, $args) {
      $size = "medium";
      if (!empty($args["size"])) {
        $size = $args["size"];
      }
      $src = wp_get_attachment_image_src($source->ID, $size);
      return $src[2];
    },
  ]);
  $type_registry->register_field("MediaItem", "fileContent", [
    "type" => "String",
    "resolve" => function ($media_item) {
      return file_get_contents($media_item->mediaItemUrl);
    },
  ]);
});

/**
 * Allows querying of `labels` and `hasArchive` on `ContentType`
 */
add_filter(
  "graphql_allowed_fields_on_restricted_type",
  function ($allowed_restricted_fields, $model_name) {
    switch ($model_name) {
      case "PostTypeObject":
        $allowed_restricted_fields[] = "labels";
        $allowed_restricted_fields[] = "hasArchive";
        break;
    }
    return $allowed_restricted_fields;
  },
  10,
  6,
);

/**
 * Adds `contentMedia` field to `NodeWithContentEditor`
 */
add_action(
  "graphql_register_types",
  function ($type_registry) {
    $type_registry->register_field("NodeWithContentEditor", "contentMedia", [
      "type" => ["list_of" => "MediaItem"],
      "resolve" => function ($post) {
        $post = get_post($post->ID);
        $content = $post->post_content;
        $content = apply_filters("the_content", $content);

        preg_match_all("/wp-(?:image|caption)-(\d+)/", $content, $matches);
        $posts = array_map(function ($id) {
          $post = get_post($id);
          return new Post($post);
        }, array_unique($matches[1]));
        return $posts;
      },
    ]);
  },
  10,
  1,
);

/**
 * Adds `slug` field to `ContentType`
 */
add_action("graphql_register_types", function ($type_registry) {
  $type_registry->register_field("ContentType", "slug", [
    "type" => "String",
    "resolve" => function ($post_type) {
      if (!isset($post_type->labels->menu_name)) {
        return null;
      }
      return sanitize_title($post_type->labels->menu_name);
      return $post_type->labels->menu_name ?? null;
    },
  ]);
});

/**
 * Adds `base64Uri` field to `MediaItem`
 */
add_action("init", function () {
  add_image_size("base64_blur", 40, 40);
});
add_action("graphql_register_types", function ($type_registry) {
  $type_registry->register_field("MediaItem", "base64Uri", [
    "type" => "String",
    "resolve" => function ($attachment) {
      $post = get_post($attachment->ID);
      if (!wp_attachment_is_image($post)) {
        return null;
      }
      $uploads_dir = wp_get_upload_dir();
      $info = image_get_intermediate_size($post->ID, "base64_blur");
      $path = realpath($uploads_dir["basedir"] . "/" . $info["path"]);
      if (empty($path)) {
        return null;
      }
      $data = base64_encode(file_get_contents($path));
      $uri = "data:" . mime_content_type($path) . ";base64," . $data;
      return $uri;
    },
  ]);
});

/**
 * Setting overrides
 */
add_filter("graphql_connection_max_query_amount", function () {
  return 100000;
});

add_filter("graphql_access_control_allow_headers", function ($headers) {
  return array_merge($headers, ["x-wp-nonce"]);
});

add_filter("graphql_response_headers_to_send", function ($headers) {
  $headers["Access-Control-Allow-Credentials"] = "true";
  $headers["Access-Control-Allow-Origin"] = get_http_origin();
  return $headers;
});

define("WHITESPACE_WP_GRAPHQL_EXTRAS_PATH", dirname(__FILE__));
define(
  "WHITESPACE_WP_GRAPHQL_EXTRAS_AUTOLOAD_PATH",
  WHITESPACE_WP_GRAPHQL_EXTRAS_PATH . "/autoload",
);

array_map(static function () {
  include_once func_get_args()[0];
}, glob(WHITESPACE_WP_GRAPHQL_EXTRAS_AUTOLOAD_PATH . "/*.php"));
