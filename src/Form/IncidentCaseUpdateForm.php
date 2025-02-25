<?php

namespace Drupal\eden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the incident case update form.
 */
class IncidentCaseUpdateForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Constructs a new IncidentCaseUpdateForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    FileUsageInterface $file_usage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eden_incident_case_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eden_incident = NULL) {
    if (!$eden_incident) {
      throw new \InvalidArgumentException('Incident parameter is required.');
    }

    $form['incident_id'] = [
      '#type' => 'value',
      '#value' => $eden_incident->id(),
    ];

    $form['documents'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Documents'),
      '#tree' => TRUE,
    ];

    // Container for multiple documents
    $form['documents']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('File'),
        $this->t('Description'),
        $this->t('Date'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No documents added yet.'),
      '#prefix' => '<div id="documents-wrapper">',
      '#suffix' => '</div>',
    ];

    // Get the number of documents
    $document_count = $form_state->get('document_count');
    if ($document_count === NULL) {
      $document_count = 1;
      $form_state->set('document_count', $document_count);
    }

    // Add document fields
    for ($i = 0; $i < $document_count; $i++) {
      $form['documents']['items'][$i]['file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Document'),
        '#title_display' => 'invisible',
        '#upload_location' => 'public://incident_documents/',
        '#multiple' => FALSE,
        '#required' => $i === 0,
      ];

      $form['documents']['items'][$i]['description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#title_display' => 'invisible',
        '#rows' => 2,
        '#required' => $i === 0,
      ];

      $form['documents']['items'][$i]['date'] = [
        '#type' => 'date',
        '#title' => $this->t('Date'),
        '#title_display' => 'invisible',
        '#required' => $i === 0,
        '#date_date_format' => 'Y-m-d',
      ];

      $form['documents']['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_document_' . $i,
        '#submit' => ['::removeDocument'],
        '#ajax' => [
          'callback' => '::updateDocumentsCallback',
          'wrapper' => 'documents-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#access' => $i > 0,
      ];
    }

    $form['documents']['add_document'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another document'),
      '#submit' => ['::addDocument'],
      '#ajax' => [
        'callback' => '::updateDocumentsCallback',
        'wrapper' => 'documents-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save case update'),
    ];

    return $form;
  }

  /**
   * Ajax callback for updating the documents fieldset.
   */
  public function updateDocumentsCallback(array &$form, FormStateInterface $form_state) {
    return $form['documents']['items'];
  }

  /**
   * Submit handler for adding a new document.
   */
  public function addDocument(array &$form, FormStateInterface $form_state) {
    $document_count = $form_state->get('document_count');
    $form_state->set('document_count', $document_count + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a document.
   */
  public function removeDocument(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = substr($trigger['#name'], strlen('remove_document_'));
    
    $document_count = $form_state->get('document_count');
    if ($document_count > 1) {
      $form_state->set('document_count', $document_count - 1);
    }
    
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate that at least one document is provided
    $documents = $form_state->getValue('documents')['items'];
    $has_document = FALSE;
    foreach ($documents as $document) {
      if (!empty($document['file'])) {
        $has_document = TRUE;
        break;
      }
    }

    if (!$has_document) {
      $form_state->setError($form['documents'], $this->t('At least one document must be provided.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $incident_id = $form_state->getValue('incident_id');
    $documents = $form_state->getValue('documents')['items'];

    try {
      // Get database connection and start transaction
      $connection = \Drupal::database();
      $transaction = $connection->startTransaction();

      // Create the case update record
      $connection->insert('eden_incident_case_update')
        ->fields([
          'incident_id' => $incident_id,
          'created' => \Drupal::time()->getRequestTime(),
          'changed' => \Drupal::time()->getRequestTime(),
          'uid' => \Drupal::currentUser()->id(),
          'status' => 1,
          'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
          'uuid' => \Drupal::service('uuid')->generate(),
        ])
        ->execute();

      $case_update_id = $connection->lastInsertId();

      // Process each document
      foreach ($documents as $document) {
        if (empty($document['file'])) {
          continue;
        }

        // Save the document record
        $connection->insert('eden_incident_case_update_document')
          ->fields([
            'case_update_id' => $case_update_id,
            'file_id' => reset($document['file']),
            'description' => $document['description'],
            'file_date' => $document['date'],
            'created' => \Drupal::time()->getRequestTime(),
            'changed' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();

        // Update file usage
        $file = $this->entityTypeManager->getStorage('file')->load(reset($document['file']));
        if ($file) {
          $file->setPermanent();
          $file->save();
          $this->fileUsage->add($file, 'eden', 'eden_incident_case_update', $case_update_id);
        }
      }

      $this->messenger()->addMessage($this->t('Case update has been saved.'));
      $form_state->setRedirect('entity.eden_incident.canonical', ['eden_incident' => $incident_id]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while saving the case update.'));
      \Drupal::logger('eden')->error($e->getMessage());
      throw $e;
    }
  }
} 