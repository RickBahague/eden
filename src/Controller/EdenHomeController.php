<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Eden homepage.
 */
class EdenHomeController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a EdenHomeController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Builds the homepage.
   *
   * @return array
   *   A render array for the homepage.
   */
  public function content() {
    $build = [];

    // Header section
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['eden-header', 'text-align-center'],
      ],
    ];

    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Document, track, and monitor human rights violations.'),
    ];

    $build['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Eden Human Rights Violation Monitoring System.'),
    ];

    // Quick Stats
    $build['stats'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['eden-stats', 'clearfix'],
      ],
    ];

    // Get counts
    $incident_count = $this->entityTypeManager->getStorage('eden_incident')
      ->getQuery()
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    $victim_count = $this->entityTypeManager->getStorage('eden_victim')
      ->getQuery()
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    $violation_count = $this->entityTypeManager->getStorage('eden_violation')
      ->getQuery()
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    $stats_items = [
      [
        'count' => $incident_count,
        'label' => $this->t('Incidents'),
        'link' => 'entity.eden_incident.collection',
      ],
      [
        'count' => $victim_count,
        'label' => $this->t('Victims'),
        'link' => 'entity.eden_victim.collection',
      ],
      [
        'count' => $violation_count,
        'label' => $this->t('Violations'),
        'link' => 'entity.eden_violation.collection',
      ],
    ];

    foreach ($stats_items as $item) {
      $build['stats'][] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['eden-stat-item'],
        ],
        'count' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $item['count'],
          '#attributes' => [
            'class' => ['eden-stat-count'],
          ],
        ],
        'label' => [
          '#type' => 'link',
          '#title' => $item['label'],
          '#url' => \Drupal\Core\Url::fromRoute($item['link']),
          '#attributes' => [
            'class' => ['eden-stat-label'],
          ],
        ],
      ];
    }

    // Recent Incidents
    $build['recent'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['eden-recent-incidents'],
      ],
    ];

    $build['recent']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Recent Incidents'),
    ];

    $incidents = $this->entityTypeManager->getStorage('eden_incident')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->execute();

    if (!empty($incidents)) {
      $incidents = $this->entityTypeManager->getStorage('eden_incident')->loadMultiple($incidents);
      $items = [];

      foreach ($incidents as $incident) {
        $items[] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['eden-incident-item'],
          ],
          'title' => [
            '#type' => 'link',
            '#title' => $incident->label(),
            '#url' => $incident->toUrl(),
          ],
          'date' => [
            '#markup' => ' - ' . $this->dateFormatter->format($incident->get('created')->value, 'medium'),
          ],
        ];
      }

      $build['recent']['list'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }
    else {
      $build['recent']['empty'] = [
        '#markup' => $this->t('No incidents recorded yet.'),
      ];
    }

    // Quick Links
    $build['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['eden-actions'],
      ],
    ];

    $build['actions']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Quick Actions'),
    ];

    $links = [
      [
        'title' => $this->t('Add New Incident'),
        'route' => 'eden.incident.add',
        'class' => ['button', 'button--primary'],
      ],
      [
        'title' => $this->t('Add New Victim'),
        'route' => 'eden.victim.add',
        'class' => ['button'],
      ],
      [
        'title' => $this->t('Add New Perpetrator'),
        'route' => 'eden.perpetrator.add',
        'class' => ['button'],
      ],
    ];

    foreach ($links as $link) {
      $build['actions'][] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => \Drupal\Core\Url::fromRoute($link['route']),
        '#attributes' => [
          'class' => $link['class'],
        ],
      ];
    }

    // Attach custom CSS
    $build['#attached']['library'][] = 'eden/eden-home';

    return $build;
  }

} 