<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Eden administration pages.
 */
class EdenAdminController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EdenAdminController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the Eden administration overview page.
   *
   * @return array
   *   A render array representing the admin overview page.
   */
  public function overview() {
    $build = [
      '#theme' => 'admin_block_content',
      '#content' => [],
    ];

    // Add links to create new content.
    if ($this->currentUser()->hasPermission('create eden incident')) {
      $build['#content'][] = [
        'title' => $this->t('Add incident'),
        'description' => $this->t('Create a new incident record.'),
        'url' => \Drupal\Core\Url::fromRoute('eden.incident.add'),
      ];
    }

    if ($this->currentUser()->hasPermission('create eden victim')) {
      $build['#content'][] = [
        'title' => $this->t('Add victim'),
        'description' => $this->t('Create a new victim record.'),
        'url' => \Drupal\Core\Url::fromRoute('eden.victim.add'),
      ];
    }

    if ($this->currentUser()->hasPermission('create eden violation')) {
      $build['#content'][] = [
        'title' => $this->t('Add violation'),
        'description' => $this->t('Create a new violation record.'),
        'url' => \Drupal\Core\Url::fromRoute('eden.violation.add'),
      ];
    }

    if ($this->currentUser()->hasPermission('create eden sector')) {
      $build['#content'][] = [
        'title' => $this->t('Add sector'),
        'description' => $this->t('Create a new sector record.'),
        'url' => \Drupal\Core\Url::fromRoute('entity.eden_sector.add_form'),
      ];
    }

    if ($this->currentUser()->hasPermission('create eden perpetrator')) {
      $build['#content'][] = [
        'title' => $this->t('Add perpetrator'),
        'description' => $this->t('Create a new perpetrator record.'),
        'url' => \Drupal\Core\Url::fromRoute('entity.eden_perpetrator.add_form'),
      ];
    }

    if ($this->currentUser()->hasPermission('create eden location')) {
      $build['#content'][] = [
        'title' => $this->t('Add location'),
        'description' => $this->t('Create a new location record.'),
        'url' => \Drupal\Core\Url::fromRoute('entity.eden_location.add_form'),
      ];
    }

    return $build;
  }

} 