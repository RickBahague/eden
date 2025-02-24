<?php

namespace Drupal\eden\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the eden_victim entity type.
 *
 * @EntityReferenceSelection(
 *   id = "eden_victim",
 *   label = @Translation("Victim selection"),
 *   entity_types = {"eden_victim"},
 *   group = "eden_victim",
 *   weight = 1
 * )
 */
class VictimSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->entityTypeManager->getStorage('eden_victim')->getQuery();
    $query->accessCheck(TRUE);

    if (isset($match)) {
      $or = $query->orConditionGroup()
        ->condition('first_name.value', $match, $match_operator)
        ->condition('last_name.value', $match, $match_operator);
      $query->condition($or);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $label = sprintf('%s %s (%s, %s)',
        $entity->get('first_name')->value,
        $entity->get('last_name')->value,
        $entity->get('gender')->value,
        $entity->get('age')->value ? $entity->get('age')->value . ' years old' : 'age unknown'
      );
      $options[$bundle][$entity_id] = $label;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_storage = $this->entityTypeManager->getStorage($target_type);
      $entities = $entity_storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        if ($this->validateReferenceableEntity($entity)) {
          $result[] = $entity->id();
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntity(EntityInterface $entity) {
    return $entity->access('view');
  }

} 