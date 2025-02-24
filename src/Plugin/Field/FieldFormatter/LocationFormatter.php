<?php

namespace Drupal\eden\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'eden_location' formatter.
 *
 * @FieldFormatter(
 *   id = "eden_location_default",
 *   label = @Translation("Location (Town, Province, Region)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class LocationFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (!$entity) {
        continue;
      }

      $elements[$delta] = [
        '#plain_text' => $entity->label(),
        '#cache' => [
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

} 