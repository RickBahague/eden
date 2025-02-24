<?php

namespace Drupal\eden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Form handler for managing incident-perpetrator relationships.
 */
class IncidentPerpetratorForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new IncidentPerpetratorForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eden_incident_perpetrator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eden_incident = NULL) {
    $form['#tree'] = TRUE;
    
    // Attach our CSS library
    $form['#attached']['library'][] = 'eden/perpetrators';
    
    $form['incident_id'] = [
      '#type' => 'value',
      '#value' => $eden_incident ? $eden_incident->id() : NULL,
    ];

    // Display existing perpetrators
    $existing_perpetrators = $this->getExistingPerpetrators($eden_incident->id());
    if (!empty($existing_perpetrators)) {
      $form['existing_relationships'] = [
        '#type' => 'details',
        '#title' => $this->t('Existing Perpetrators'),
        '#open' => TRUE,
      ];

      foreach ($existing_perpetrators as $perpetrator_id => $perpetrator_data) {
        $perpetrator = $this->entityTypeManager->getStorage('eden_perpetrator')->load($perpetrator_id);
        if (!$perpetrator) {
          continue;
        }

        $form['existing_relationships'][$perpetrator_id] = [
          '#type' => 'details',
          '#title' => $perpetrator->label(),
          '#open' => TRUE,
          '#attributes' => [
            'class' => ['perpetrator-details'],
          ],
        ];

        // Enhanced perpetrator information display with HTML formatting
        $form['existing_relationships'][$perpetrator_id]['info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['perpetrator-info']],
          'header' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['perpetrator-header']],
            'group' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['perpetrator-group']],
              '#value' => '<strong>' . t('Group:') . '</strong> ' . $perpetrator->get('group_name')->value,
            ],
            'unit' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['perpetrator-unit']],
              '#value' => '<strong>' . t('Unit:') . '</strong> ' . ($perpetrator->get('unit')->value ?: t('Not specified')),
            ],
          ],
          'details' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['perpetrator-details-content']],
            'co' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['perpetrator-co']],
              '#value' => '<strong>' . t('Commanding Officer:') . '</strong> ' . ($perpetrator->get('commanding_officer')->value ?: t('Not specified')),
            ],
          ],
        ];

        // Brief info if available
        if ($perpetrator->hasField('brief_info') && !$perpetrator->get('brief_info')->isEmpty()) {
          $form['existing_relationships'][$perpetrator_id]['info']['details']['brief_info'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['perpetrator-brief-info']],
            '#value' => '<strong>' . t('Brief Information:') . '</strong><br>' . $perpetrator->get('brief_info')->value,
          ];
        }

        // Location if available
        if ($perpetrator->hasField('location') && !$perpetrator->get('location')->isEmpty()) {
          $location = $perpetrator->get('location')->entity;
          if ($location) {
            $form['existing_relationships'][$perpetrator_id]['info']['details']['location'] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => ['class' => ['perpetrator-location']],
              '#value' => '<strong>' . t('Location:') . '</strong> ' . $location->label(),
            ];
          }
        }

        // Remarks if available
        if ($perpetrator->hasField('remarks') && !$perpetrator->get('remarks')->isEmpty()) {
          $form['existing_relationships'][$perpetrator_id]['info']['details']['remarks'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['perpetrator-remarks']],
            '#value' => '<strong>' . t('Remarks:') . '</strong><br>' . $perpetrator->get('remarks')->value,
          ];
        }

        // Description of involvement
        if (!empty($perpetrator_data['description'])) {
          $form['existing_relationships'][$perpetrator_id]['info']['details']['involvement'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['perpetrator-involvement']],
            '#value' => '<strong>' . t('Involvement:') . '</strong><br>' . $perpetrator_data['description'],
          ];
        }

        // Operations container
        $form['existing_relationships'][$perpetrator_id]['operations'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['perpetrator-operations']],
          'edit_link' => [
            '#type' => 'link',
            '#title' => t('Edit Perpetrator Information'),
            '#url' => \Drupal\Core\Url::fromRoute('entity.eden_perpetrator.edit_form', ['eden_perpetrator' => $perpetrator_id]),
            '#attributes' => [
              'class' => ['button', 'button--small'],
              'target' => '_blank',
            ],
          ],
          'remove_link' => [
            '#type' => 'link',
            '#title' => t('Remove from Incident'),
            '#url' => \Drupal\Core\Url::fromRoute('entity.eden_incident.remove_perpetrator', [
              'eden_incident' => $eden_incident->id(),
              'perpetrator' => $perpetrator_id,
            ]),
            '#attributes' => [
              'class' => ['button', 'button--small', 'button--danger'],
            ],
          ],
        ];
      }
    }

    // Add new perpetrator section
    $form['add_new'] = [
      '#type' => 'details',
      '#title' => $this->t('Add New Perpetrator'),
      '#open' => TRUE,
    ];

    $form['add_new']['perpetrator'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Perpetrator'),
      '#target_type' => 'eden_perpetrator',
      '#required' => FALSE,
      '#description' => $this->t('Select an existing perpetrator or <a href="@add_url">add a new one</a>.', [
        '@add_url' => '/admin/content/eden/perpetrator/add',
      ]),
      '#selection_handler' => 'eden_perpetrator',
      '#selection_settings' => [
        'match_operator' => 'LIKE',
        'match_limit' => 10,
      ],
      '#maxlength' => 1024,
      '#process_default_value' => FALSE,
      '#validate_reference' => FALSE,
    ];

    $form['add_new']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description of Involvement'),
      '#description' => $this->t('Describe how this perpetrator was involved in this incident.'),
      '#rows' => 4,
      '#states' => [
        'visible' => [
          ':input[name="add_new[perpetrator]"]' => ['filled' => TRUE],
        ],
        'required' => [
          ':input[name="add_new[perpetrator]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Gets existing perpetrators for an incident.
   *
   * @param int $incident_id
   *   The incident ID.
   *
   * @return array
   *   Array of perpetrators and their descriptions.
   */
  protected function getExistingPerpetrators($incident_id) {
    $perpetrators = [];
    
    $query = $this->database->select('eden_incident_perpetrator', 'ip');
    $query->fields('ip', ['perpetrator_id', 'description']);
    $query->condition('ip.incident_id', $incident_id);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $perpetrators[$result->perpetrator_id] = [
        'description' => $result->description,
      ];
    }

    return $perpetrators;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('incident_id')) {
      $form_state->setError($form, $this->t('No incident specified.'));
    }

    $perpetrator = $form_state->getValue(['add_new', 'perpetrator']);
    $description = $form_state->getValue(['add_new', 'description']);

    if ($perpetrator && empty($description)) {
      $form_state->setError($form['add_new']['description'], $this->t('You must provide a description of involvement when adding a perpetrator.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $incident_id = $form_state->getValue('incident_id');
    $perpetrator_value = $form_state->getValue(['add_new', 'perpetrator']);
    
    if (!$perpetrator_value) {
      $this->messenger()->addStatus($this->t('No new perpetrators were added.'));
      return;
    }

    try {
      // Start a transaction
      $transaction = $this->database->startTransaction();

      // Check if the relationship exists
      $exists = $this->database->select('eden_incident_perpetrator', 'ip')
        ->fields('ip', ['id'])
        ->condition('incident_id', $incident_id)
        ->condition('perpetrator_id', $perpetrator_value)
        ->execute()
        ->fetchField();

      if (!$exists) {
        // Insert new relationship
        $this->database->insert('eden_incident_perpetrator')
          ->fields([
            'incident_id' => $incident_id,
            'perpetrator_id' => $perpetrator_value,
            'description' => $form_state->getValue(['add_new', 'description']),
            'created' => \Drupal::time()->getRequestTime(),
            'changed' => \Drupal::time()->getRequestTime(),
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ])
          ->execute();

        $this->messenger()->addStatus($this->t('The perpetrator has been added to the incident.'));
      }
      else {
        // Update existing relationship
        $this->database->update('eden_incident_perpetrator')
          ->fields([
            'description' => $form_state->getValue(['add_new', 'description']),
            'changed' => \Drupal::time()->getRequestTime(),
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ])
          ->condition('incident_id', $incident_id)
          ->condition('perpetrator_id', $perpetrator_value)
          ->execute();

        $this->messenger()->addStatus($this->t('The perpetrator information has been updated.'));
      }

      unset($transaction);
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      $this->messenger()->addError($this->t('There was an error saving the relationship. Please try again.'));
      \Drupal::logger('eden')->error('Error saving incident-perpetrator relationship: @error', ['@error' => $e->getMessage()]);
    }

    $form_state->setRedirect('entity.eden_incident.add_perpetrator', ['eden_incident' => $incident_id]);
  }

} 