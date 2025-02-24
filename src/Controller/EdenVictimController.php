<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

/**
 * Controller for Eden Victim entity.
 */
class EdenVictimController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The autocomplete matcher service.
   *
   * @var \Drupal\Core\Entity\EntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * Constructs a new EdenVictimController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $matcher
   *   The autocomplete matcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityAutocompleteMatcher $matcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->matcher = $matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.autocomplete_matcher')
    );
  }

  /**
   * Handles the autocomplete request for victim search.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the matching victim suggestions.
   */
  public function autocomplete(Request $request) {
    $matches = [];
    $string = $request->query->get('q');

    if ($string) {
      $victim_storage = $this->entityTypeManager->getStorage('eden_victim');
      $query = $victim_storage->getQuery()
        ->condition('status', 1)
        ->accessCheck(TRUE)
        ->range(0, 10);

      $group = $query->orConditionGroup()
        ->condition('first_name.value', $string, 'CONTAINS')
        ->condition('last_name.value', $string, 'CONTAINS');
      
      $query->condition($group);

      $ids = $query->execute();
      $victims = $victim_storage->loadMultiple($ids);

      foreach ($victims as $victim) {
        $label = sprintf('%s %s', 
          $victim->get('first_name')->value,
          $victim->get('last_name')->value
        );
        
        $matches[] = [
          'value' => sprintf('%s (%s)', $label, $victim->id()),
          'label' => sprintf('%s (%s, %s)', 
            $label,
            $victim->get('gender')->value,
            $victim->get('age')->value ? $victim->get('age')->value . ' years old' : 'age unknown'
          )
        ];
      }
    }

    return new JsonResponse($matches);
  }
} 