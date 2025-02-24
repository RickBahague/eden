<?php

namespace Drupal\eden\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific selection handler for the perpetrator entity type.
 *
 * @EntityReferenceSelection(
 *   id = "eden_perpetrator",
 *   label = @Translation("Perpetrator selection"),
 *   entity_types = {"eden_perpetrator"},
 *   group = "eden_perpetrator",
 *   weight = 1
 * )
 */
class PerpetratorSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'LIKE', $limit = 0) {
    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage('eden_perpetrator')->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      // Format the perpetrator as "Group - Unit"
      $parts = [];

      if ($entity->hasField('unit') && !$entity->get('unit')->isEmpty()) {
        $parts[] = $entity->get('unit')->value;
      }

      if ($entity->hasField('group_name') && !$entity->get('group_name')->isEmpty()) {
        $parts[] = $entity->get('group_name')->value;
      }

      $label = implode(' - ', $parts);
      $options[$bundle][$entity_id] = $label;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'LIKE') {
    $query = $this->entityTypeManager->getStorage('eden_perpetrator')->getQuery();
    $query->accessCheck(TRUE);

    // Log the query for debugging
    \Drupal::logger('eden_perpetrator')->debug('Perpetrator selection query: @query', [
      '@query' => $query->__toString(),
    ]);

    if (!empty($match)) {
      // Add the conditions
      $or = $query->orConditionGroup()
        ->condition('unit', '%' . $match . '%', 'LIKE')
        ->condition('group_name', '%' . $match . '%', 'LIKE');
      
      $query->condition($or);

      // Log the query for debugging
      \Drupal::logger('eden_perpetrator')->debug('Perpetrator selection query: @query', [
        '@query' => $query->__toString(),
      ]);

      // Add unit as primary sort, then group_name
      $query->sort('unit', 'ASC')
            ->sort('group_name', 'ASC');
    }

    // Log the query for debugging
    \Drupal::logger('eden_perpetrator')->debug('Perpetrator selection query: @query', [
      '@query' => $query->__toString(),
    ]);

    return $query;
  }

} 