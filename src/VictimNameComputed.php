<?php

namespace Drupal\eden;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computes the full name of a victim from first, middle, and last names or group name.
 */
class VictimNameComputed extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $victim_type = $entity->get('victim_type')->value;

    if ($victim_type === 'individual') {
      // Get the individual name components
      $first_name = $entity->get('first_name')->value;
      $middle_name = $entity->get('middle_name')->value;
      $last_name = $entity->get('last_name')->value;

      // Build the full name
      $full_name = $first_name;
      if (!empty($middle_name)) {
        $full_name .= ' ' . $middle_name;
      }
      if (!empty($last_name)) {
        $full_name .= ' ' . $last_name;
      }
    } else {
      // For non-individual victims, use the group name
      $full_name = $entity->get('group_name')->value;
    }

    // Set the computed value
    $this->list[0] = $this->createItem(0, trim($full_name));
  }

} 