<?php

namespace Drupal\eden\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the location entity type.
 *
 * @EntityReferenceSelection(
 *   id = "eden_location",
 *   label = @Translation("Location selection"),
 *   entity_types = {"eden_location"},
 *   group = "eden_location",
 *   weight = 1
 * )
 */
class LocationSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage('eden_location')->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      // Format the location as "Town, Province, Region"
      $label = sprintf(
        '%s, %s, %s',
        $entity->get('town')->value,
        $entity->get('province')->value,
        $entity->get('region')->value
      );
      $options[$bundle][$entity_id] = $label;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // If there's a search string, search in town, province, and region fields
    if (isset($match)) {
      $or = $query->orConditionGroup()
        ->condition('town', $match, $match_operator)
        ->condition('province', $match, $match_operator)
        ->condition('region', $match, $match_operator);
      $query->condition($or);
    }

    // Sort by region, province, then town
    $query->sort('region', 'ASC')
      ->sort('province', 'ASC')
      ->sort('town', 'ASC');

    return $query;
  }

} 