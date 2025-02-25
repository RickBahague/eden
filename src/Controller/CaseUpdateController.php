<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Controller for case updates.
 */
class CaseUpdateController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a CaseUpdateController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Displays case updates for an incident.
   *
   * @param mixed $eden_incident
   *   The incident entity.
   *
   * @return array
   *   A render array.
   */
  public function overview($eden_incident) {
    $build = [];

    // Add button to create new case update
    $build['add_case_update'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Case Update'),
      '#url' => Url::fromRoute('entity.eden_incident.add_case_update', ['eden_incident' => $eden_incident->id()]),
      '#attributes' => [
        'class' => ['button', 'button--action', 'button--primary'],
      ],
    ];

    // Get case updates for this incident
    $query = \Drupal::database()->select('eden_incident_case_update', 'cu')
      ->fields('cu', ['id', 'created', 'uid'])
      ->condition('incident_id', $eden_incident->id())
      ->orderBy('created', 'DESC');
    $case_updates = $query->execute()->fetchAll();

    if (empty($case_updates)) {
      $build['no_updates'] = [
        '#markup' => $this->t('No case updates available.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      return $build;
    }

    $rows = [];
    foreach ($case_updates as $update) {
      // Get documents for this update
      $documents = \Drupal::database()->select('eden_incident_case_update_document', 'd')
        ->fields('d', ['file_id', 'description', 'file_date'])
        ->condition('case_update_id', $update->id)
        ->execute()
        ->fetchAll();

      $document_list = [];
      foreach ($documents as $doc) {
        $file = $this->entityTypeManager->getStorage('file')->load($doc->file_id);
        if ($file instanceof FileInterface) {
          $document_list[] = [
            '#theme' => 'item_list',
            '#items' => [
              [
                '#type' => 'link',
                '#title' => $file->getFilename(),
                '#url' => Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri())),
                '#suffix' => ' - ' . $doc->description . ' (' . $doc->file_date . ')',
              ],
            ],
          ];
        }
      }

      $user = $this->entityTypeManager->getStorage('user')->load($update->uid);
      $username = $user ? $user->getDisplayName() : $this->t('Unknown user');

      $rows[] = [
        'date' => $this->dateFormatter->format($update->created, 'medium'),
        'user' => $username,
        'documents' => [
          'data' => $document_list,
        ],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Date'),
        $this->t('Added by'),
        $this->t('Documents'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No case updates available.'),
    ];

    return $build;
  }
} 