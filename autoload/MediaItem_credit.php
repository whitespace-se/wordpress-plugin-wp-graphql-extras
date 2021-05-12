<?php

namespace WhitespaceWPGraphQLExtras;

function get_default_acf_field_key() {
  return "media_item_credit";
}

function get_acf_field_key() {
  $acf_field_key = get_default_acf_field_key();
  $acf_field_key = apply_filters(
    "WhitespaceWPGraphQLExtras/MediaItem/credit/acf_field_key",
    $acf_field_key,
  );
  return $acf_field_key;
}

add_action("acf/init", function () {
  $acf_field_key = get_acf_field_key();
  $register_acf_fields = $acf_field_key == get_default_acf_field_key();
  $register_acf_fields = apply_filters(
    "WhitespaceWPGraphQLExtras/MediaItem/credit/register_acf_fields",
    $register_acf_fields,
  );
  if (!$register_acf_fields) {
    return;
  }
  acf_add_local_field_group([
    "key" => "group_" . $acf_field_key,
    "title" => __("Photographer", "whitespace-wp-graphql-extras"),
    "fields" => [
      [
        "key" => $acf_field_key,
        "name" => $acf_field_key,
        "label" => __("Photographer", "whitespace-wp-graphql-extras"),
        "type" => "text",
        "show_in_graphql" => 0,
      ],
    ],
    "location" => [
      [
        [
          "param" => "attachment",
          "operator" => "==",
          "value" => "image",
        ],
      ],
    ],
    // 'menu_order' => 0,
    // 'position' => 'normal',
    // 'style' => 'default',
    // 'label_placement' => 'top',
    // 'instruction_placement' => 'label',
    // 'hide_on_screen' => '',
    "show_in_graphql" => 0,
  ]);
});

/**
 * Add `credit` field to `MediaItem` type
 */
add_action("graphql_register_types", function () {
  register_graphql_field("MediaItem", "credit", [
    "type" => "String",
    "description" => "Photo credit",
    "resolve" => function ($mediaItem) {
      $acf_field_key = get_acf_field_key();
      $value = get_field($acf_field_key, $mediaItem->ID);
      return $value;
    },
  ]);
});
