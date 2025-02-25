<?php

namespace Drupal\eden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Form handler for case updates.
 */
class CaseUpdateForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * Constructs a new CaseUpdateForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eden_case_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eden_incident = NULL) {
    $form['#tree'] = TRUE;

    // Store the incident ID
    $form['incident_id'] = [
      '#type' => 'value',
      '#value' => $eden_incident->id(),
    ];

    // Display the incident case number
    $form['case_number'] = [
      '#type' => 'item',
      '#title' => $this->t('Case Number'),
      '#markup' => $eden_incident->get('case_number')->value,
    ];

    // Container for document attachments
    $form['documents'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Document Attachments'),
      '#prefix' => '<div id="documents-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Get the number of document fields to show
    $num_documents = $form_state->get('num_documents');
    if ($num_documents === NULL) {
      $num_documents = 1;
      $form_state->set('num_documents', $num_documents);
    }

    // Add document fields
    for ($i = 0; $i < $num_documents; $i++) {
      $form['documents'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Document @num', ['@num' => $i + 1]),
        '#attributes' => ['class' => ['document-fieldset']],
      ];

      $form['documents'][$i]['file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload Document'),
        '#upload_location' => 'public://eden/case_updates',
        '#upload_validators' => [
          'file_validate_extensions' => ['pdf doc docx txt jpg jpeg png'],
          'file_validate_size' => [25 * 1024 * 1024], // 25MB limit
        ],
        '#description' => $this->t('Allowed file types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG. Maximum size: 25MB'),
        '#required' => TRUE,
      ];

      $form['documents'][$i]['description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Document Description'),
        '#rows' => 3,
        '#required' => TRUE,
      ];

      $form['documents'][$i]['file_date'] = [
        '#type' => 'date',
        '#title' => $this->t('File Date'),
        '#required' => TRUE,
        '#default_value' => date('Y-m-d'),
      ];
    }

    // Add more documents button
    $form['documents']['add_document'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Another Document'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'documents-fieldset-wrapper',
      ],
    ];

    // If there is more than one document, add the remove button
    if ($num_documents > 1) {
      $form['documents']['remove_document'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Document'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'documents-fieldset-wrapper',
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Case Update'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['documents'];
  }

  /**
   * Submit handler for the "Add Another Document" button.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_documents = $form_state->get('num_documents');
    $form_state->set('num_documents', $num_documents + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove Last Document" button.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $num_documents = $form_state->get('num_documents');
    if ($num_documents > 1) {
      $form_state->set('num_documents', $num_documents - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate file dates are not in the future
    $today = strtotime('today');
    foreach ($form_state->getValue('documents') as $delta => $document) {
      if (is_array($document) && isset($document['file_date'])) {
        $file_date = strtotime($document['file_date']);
        if ($file_date > $today) {
          $form_state->setError($form['documents'][$delta]['file_date'], $this->t('File date cannot be in the future.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      // Start a transaction
      $transaction = $this->database->startTransaction();

      // Insert the case update
      $case_update_id = $this->database->insert('eden_case_update')
        ->fields([
          'incident_id' => $form_state->getValue('incident_id'),
          'uuid' => \Drupal::service('uuid')->generate(),
          'langcode' => 'en',
          'status' => 1,
          'uid' => \Drupal::currentUser()->id(),
          'created' => \Drupal::time()->getRequestTime(),
          'changed' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();

      // Process each document
      foreach ($form_state->getValue('documents') as $document) {
        if (!is_array($document) || empty($document['file'])) {
          continue;
        }

        // Load and make the file permanent
        $fid = reset($document['file']);
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();

          // Insert the document record
          $this->database->insert('eden_case_update_document')
            ->fields([
              'case_update_id' => $case_update_id,
              'fid' => $fid,
              'description' => $document['description'],
              'file_date' => $document['file_date'],
              'created' => \Drupal::time()->getRequestTime(),
            ])
            ->execute();
        }
      }

      $this->messenger()->addStatus($this->t('Case update has been saved successfully.'));
      $form_state->setRedirect('entity.eden_incident.canonical', ['eden_incident' => $form_state->getValue('incident_id')]);
    }
    catch (\Exception $e) {
      // Roll back the transaction
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      $this->messenger()->addError($this->t('An error occurred while saving the case update. Please try again.'));
      \Drupal::logger('eden')->error('Error saving case update: @error', ['@error' => $e->getMessage()]);
    }
  }
} 