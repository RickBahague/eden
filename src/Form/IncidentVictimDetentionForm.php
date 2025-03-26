<?php

namespace Drupal\eden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Url;

/**
 * Form for editing detention details of a victim in an incident.
 */
class IncidentVictimDetentionForm extends FormBase {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new IncidentVictimDetentionForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eden_incident_victim_detention_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eden_incident = NULL, $victim = NULL) {
    $form['#tree'] = TRUE;
    
    // Add form attributes for styling
    $form['#attributes']['class'][] = 'detention-edit-form';
    $form['#attached']['library'][] = 'eden/victims';

    // Get the numeric ID if an entity object was passed
    $incident_id = is_object($eden_incident) ? $eden_incident->id() : $eden_incident;
    $victim_id = is_object($victim) ? $victim->id() : $victim;

    // Store incident and victim IDs
    $form['incident_id'] = [
      '#type' => 'value',
      '#value' => $incident_id,
    ];
    
    $form['victim_id'] = [
      '#type' => 'value',
      '#value' => $victim_id,
    ];

    // Get existing detention data
    $detention_data = $this->database->select('eden_incident_victim', 'iv')
      ->fields('iv', [
        'date_of_detention',
        'place_of_arrest',
        'place_of_detention',
        'charges',
        'already_released',
        'remarks_on_release',
      ])
      ->condition('incident_id', $incident_id)
      ->condition('victim_id', $victim_id)
      ->execute()
      ->fetchAssoc();

    // Load the victim entity to display the name
    $victim_entity = $this->entityTypeManager->getStorage('eden_victim')->load($victim_id);
    $victim_name = $victim_entity ? $victim_entity->label() : $this->t('Unknown Victim');

    $form['victim_name'] = [
      '#type' => 'markup',
      '#markup' => '<div class="victim-name">' . $this->t('Detention Details for @name', ['@name' => $victim_name]) . '</div>',
    ];

    // Create a fieldset for detention details
    $form['detention_details'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['detention-details-container']],
    ];

    $form['detention_details']['date_of_detention'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Detention'),
      '#description' => $this->t('The date when the victim was detained.'),
      '#default_value' => $detention_data['date_of_detention'] ?? '',
      '#required' => FALSE,
    ];

    $form['detention_details']['place_of_arrest'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Place of Arrest'),
      '#description' => $this->t('The place where the victim was arrested.'),
      '#default_value' => $detention_data['place_of_arrest'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
      '#attributes' => ['class' => ['detention-input']],
    ];

    $form['detention_details']['place_of_detention'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Place of Detention'),
      '#description' => $this->t('The place where the victim is/was detained.'),
      '#default_value' => $detention_data['place_of_detention'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
      '#attributes' => ['class' => ['detention-input']],
    ];

    $form['detention_details']['charges'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Charges'),
      '#description' => $this->t('The charges filed against the victim.'),
      '#default_value' => $detention_data['charges'] ?? '',
      '#rows' => 3,
      '#required' => FALSE,
      '#attributes' => ['class' => ['detention-textarea']],
    ];

    $form['detention_details']['already_released'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Already Released'),
      '#description' => $this->t('Check if the victim has been released from detention.'),
      '#default_value' => $detention_data['already_released'] ?? 0,
      '#attributes' => ['class' => ['detention-checkbox']],
    ];

    $form['detention_details']['remarks_on_release'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remarks on Release'),
      '#description' => $this->t('Additional remarks regarding the release of the victim.'),
      '#default_value' => $detention_data['remarks_on_release'] ?? '',
      '#rows' => 3,
      '#states' => [
        'visible' => [
          ':input[name="detention_details[already_released]"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => ['class' => ['detention-textarea']],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['form-actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#ajax' => [
        'callback' => '::submitAjaxForm',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Saving detention details...'),
        ],
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'button--cancel']],
      '#ajax' => [
        'callback' => '::closeDialog',
      ],
    ];

    // Add dialog settings
    $form['#prefix'] = '<div id="detention-edit-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    
    // Add custom dialog settings
    $form['#attached']['drupalSettings']['dialog']['detention_edit'] = [
      'dialogClass' => 'detention-edit-dialog',
      'width' => '800',
      'title' => $this->t('Edit Detention Details'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $incident = $form_state->getValue('incident_id');
    $incident_id = is_object($incident) ? $incident->id() : $incident;
    $victim_id = $form_state->getValue('victim_id');

    // Update the detention information
    try {
      // Get the date value and convert empty string to NULL
      $date_of_detention = $form_state->getValue(['detention_details', 'date_of_detention']);
      $date_of_detention = !empty($date_of_detention) ? $date_of_detention : NULL;

      $this->database->update('eden_incident_victim')
        ->fields([
          'date_of_detention' => $date_of_detention,
          'place_of_arrest' => $form_state->getValue(['detention_details', 'place_of_arrest']),
          'place_of_detention' => $form_state->getValue(['detention_details', 'place_of_detention']),
          'charges' => $form_state->getValue(['detention_details', 'charges']),
          'already_released' => (int) $form_state->getValue(['detention_details', 'already_released']),
          'remarks_on_release' => $form_state->getValue(['detention_details', 'remarks_on_release']),
          'changed' => \Drupal::time()->getRequestTime(),
        ])
        ->condition('incident_id', $incident_id)
        ->condition('victim_id', $victim_id)
        ->execute();

      $this->messenger()->addStatus($this->t('Detention details have been updated successfully.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while saving the detention details.'));
      \Drupal::logger('eden')->error('Error updating detention details: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Ajax callback for form submission.
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    if (!$form_state->hasAnyErrors()) {
      // Remove any existing messages
      $response->addCommand(new RemoveCommand('.messages'));
      
      // Add success message
      $response->addCommand(new MessageCommand(
        $this->t('Detention details have been updated successfully.'),
        NULL,
        ['type' => 'status', 'announce' => TRUE]
      ));
      
      // Close the modal
      $response->addCommand(new CloseModalDialogCommand());
      
      // Get the numeric ID if an entity object was passed
      $incident = $form_state->getValue('incident_id');
      $incident_id = is_object($incident) ? $incident->id() : $incident;
      
      // Add a small delay before redirect to show the message
      $url = Url::fromRoute('entity.eden_incident.add_victim', [
        'eden_incident' => $incident_id,
      ])->toString();
      
      $response->addCommand(new RedirectCommand($url));
    }
    else {
      // If there are form errors, show error message
      $response->addCommand(new MessageCommand(
        $this->t('Please correct the errors and try again.'),
        NULL,
        ['type' => 'error', 'announce' => TRUE]
      ));
    }
    
    return $response;
  }

  /**
   * Ajax callback to close the dialog.
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }
} 