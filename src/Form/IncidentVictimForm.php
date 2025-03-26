<?php

namespace Drupal\eden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Form handler for managing incident-victim relationships.
 */
class IncidentVictimForm extends FormBase {

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
   * Constructs a new IncidentVictimForm.
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
    return 'eden_incident_victim_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eden_incident = NULL) {
    $form['#tree'] = TRUE;
    
    // Attach our CSS library
    $form['#attached']['library'][] = 'eden/victims';
    
    $form['incident_id'] = [
      '#type' => 'value',
      '#value' => $eden_incident ? $eden_incident->id() : NULL,
    ];

    // Display existing victims and their violations
    $existing_victims = $this->getExistingVictims($eden_incident->id());
    if (!empty($existing_victims)) {
      $form['existing_relationships'] = [
        '#type' => 'details',
        '#title' => $this->t('Existing Victims and Violations'),
        '#open' => TRUE,
      ];

      foreach ($existing_victims as $victim_id => $victim_data) {
        $victim = $this->entityTypeManager->getStorage('eden_victim')->load($victim_id);
        if (!$victim) {
          continue;
        }

        $form['existing_relationships'][$victim_id] = [
          '#type' => 'details',
          '#title' => $victim->label(),
          '#open' => TRUE,
        ];

        // Create a header wrapper for name and edit button
        $form['existing_relationships'][$victim_id]['info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['victim-info']],
        ];

        // Create a header wrapper for name and edit button
        $form['existing_relationships'][$victim_id]['info']['header'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['victim-header']],
          'name' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('@first @middle @last', [
              '@first' => $victim->get('first_name')->value ?? '',
              '@middle' => $victim->get('middle_name')->value ?? '',
              '@last' => $victim->get('last_name')->value ?? '',
            ]),
            '#attributes' => ['class' => ['victim-name']],
          ],
          'operations' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['victim-operations']],
            'edit_link' => [
              '#type' => 'link',
              '#title' => $this->t('View Victim Information'),
              '#url' => \Drupal\Core\Url::fromRoute('entity.eden_victim.canonical', ['eden_victim' => $victim_id]),
              '#attributes' => [
                'class' => ['button', 'button--primary', 'button--small'],
                'target' => '_blank',
              ],
            ],
            'delete_link' => [
              '#type' => 'link',
              '#title' => $this->t('Remove Victim'),
              '#url' => \Drupal\Core\Url::fromRoute('entity.eden_incident.remove_victim', [
                'eden_incident' => $eden_incident->id(),
                'victim' => $victim_id,
              ]),
              '#attributes' => [
                'class' => ['button', 'button--danger', 'button--small'],
              ],
              // Disable the button if there are violations
              '#access' => empty($victim_data['violations']),
            ],
          ],
        ];

        // Add the rest of the info items with proper HTML escaping
        $info_items = [
          'personal' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Age: @age | Gender: @gender | Civil Status: @civil_status', [
              '@age' => $victim->get('age')->value ?? $this->t('Not specified'),
              '@gender' => $victim->get('gender')->value ?? $this->t('Not specified'),
              '@civil_status' => $victim->get('civil_status')->value ?? $this->t('Not specified'),
            ]),
          ],
          'residence' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Residence: @residence', [
              '@residence' => $victim->get('residence')->value ?? $this->t('Not specified'),
            ]),
          ],
          'occupation' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Occupation: @occupation | Position: @position', [
              '@occupation' => $victim->get('occupation')->value ?? $this->t('Not specified'),
              '@position' => $victim->get('position')->value ?? $this->t('Not specified'),
            ]),
          ],
          'organization' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Organization: @org', [
              '@org' => $victim->get('organization_name')->value ?? $this->t('Not specified'),
            ]),
          ],
        ];

        // Add detention information if available
        if (!empty($victim_data['date_of_detention']) || !empty($victim_data['place_of_arrest']) || 
            !empty($victim_data['place_of_detention']) || !empty($victim_data['charges'])) {
          
          $detention_info = [];
          
          if (!empty($victim_data['date_of_detention'])) {
            $detention_info[] = $this->t('Date of Detention: @date', [
              '@date' => $victim_data['date_of_detention'],
            ]);
          }
          
          if (!empty($victim_data['place_of_arrest'])) {
            $detention_info[] = $this->t('Place of Arrest: @place', [
              '@place' => $victim_data['place_of_arrest'],
            ]);
          }
          
          if (!empty($victim_data['place_of_detention'])) {
            $detention_info[] = $this->t('Place of Detention: @place', [
              '@place' => $victim_data['place_of_detention'],
            ]);
          }
          
          if (!empty($victim_data['charges'])) {
            $detention_info[] = $this->t('Charges: @charges', [
              '@charges' => $victim_data['charges'],
            ]);
          }
          
          if (!empty($victim_data['already_released'])) {
            $detention_info[] = $this->t('Status: Released');
            if (!empty($victim_data['remarks_on_release'])) {
              $detention_info[] = $this->t('Remarks on Release: @remarks', [
                '@remarks' => $victim_data['remarks_on_release'],
              ]);
            }
          } else {
            $detention_info[] = $this->t('Status: In Detention');
          }

          $info_items['detention'] = [
            '#type' => 'details',
            '#title' => $this->t('Details of Detention'),
            '#open' => TRUE,
            'content' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['detention-details']],
              'info' => [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#value' => implode('<br>', $detention_info),
              ],
              'operations' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['detention-operations']],
                'edit' => [
                  '#type' => 'link',
                  '#title' => $this->t('Edit Detention Details'),
                  '#url' => \Drupal\Core\Url::fromRoute('eden.incident_victim_detention_edit', [
                    'eden_incident' => $eden_incident->id(),
                    'victim' => $victim_id,
                  ]),
                  '#attributes' => [
                    'class' => ['button', 'button--small'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => json_encode([
                      'width' => '800',
                    ]),
                  ],
                ],
              ],
            ],
          ];
        }

        // Only add remarks if they exist and are not empty
        $remarks = $victim->get('remarks')->value;
        if (!empty($remarks)) {
          $info_items['remarks'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('Remarks: @remarks', [
              '@remarks' => $remarks,
            ]),
          ];
        }

        $form['existing_relationships'][$victim_id]['info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['victim-info']],
        ] + $info_items;

        // Enhanced violations table with proper HTML escaping
        $form['existing_relationships'][$victim_id]['violations'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Violation'),
            $this->t('Category'),
            $this->t('Description'),
            $this->t('Operations'),
          ],
          '#empty' => $this->t('No violations recorded.'),
          '#attributes' => ['class' => ['violations-table']],
        ];

        foreach ($victim_data['violations'] as $violation) {
          $violation_entity = $this->entityTypeManager->getStorage('eden_violation')->load($violation['violation_id']);
          if (!$violation_entity) {
            continue;
          }

          $row = [];
          $row['violation'] = [
            '#type' => 'container',
            'title' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $violation_entity->label() ?? $this->t('Untitled'),
            ],
            'edit_link' => [
              '#type' => 'link',
              '#title' => $this->t('Edit'),
              '#url' => \Drupal\Core\Url::fromRoute('entity.eden_violation.edit_form', ['eden_violation' => $violation['violation_id']]),
              '#attributes' => [
                'class' => ['button', 'button--small', 'button--link'],
                'target' => '_blank',
              ],
            ],
          ];
          
          $row['category'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $violation_entity->get('category')->value ?? $this->t('Not categorized'),
          ];
          
          $row['description'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => !empty($violation['description']) ? $violation['description'] : $this->t('No description provided'),
          ];
          
          $row['operations'] = [
            '#type' => 'operations',
            '#links' => [
              'delete' => [
                'title' => $this->t('Remove'),
                'url' => \Drupal\Core\Url::fromRoute('entity.eden_incident.remove_violation', [
                  'eden_incident' => $eden_incident->id(),
                  'victim' => $victim_id,
                  'violation' => $violation['violation_id'],
                ]),
              ],
            ],
          ];

          $form['existing_relationships'][$victim_id]['violations'][] = $row;
        }
      }
    }

    // Add new victim section
    $form['add_new'] = [
      '#type' => 'details',
      '#title' => $this->t('Add New Victim'),
      '#open' => TRUE,
    ];

    $form['add_new']['victim'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Victim'),
      '#target_type' => 'eden_victim',
      '#required' => FALSE,
      '#description' => $this->t('Select an existing victim or <a href="@add_url">add a new one</a>.', [
        '@add_url' => '/admin/content/eden/victim/add',
      ]),
      '#selection_handler' => 'eden_victim',
      '#selection_settings' => [
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
      ],
      '#maxlength' => 1024,
      '#process_default_value' => FALSE,
      '#validate_reference' => FALSE,
    ];

    $form['add_new']['violations_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="add_new[victim]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['add_new']['violations_container']['violations'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Human Rights Violations'),
      '#target_type' => 'eden_violation',
      '#tags' => TRUE,
      '#required' => FALSE,
      '#description' => $this->t('Select one or more violations. Separate multiple entries with commas.'),
      '#ajax' => [
        'callback' => '::updateViolationDescriptions',
        'wrapper' => 'violation-descriptions',
        'event' => 'change',
      ],
    ];

    // Add detention details container
    $form['add_new']['detention_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Details of Detention'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="add_new[victim]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['add_new']['detention_container']['date_of_detention'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Detention'),
      '#description' => $this->t('The date when the victim was detained.'),
      '#required' => FALSE,
    ];

    $form['add_new']['detention_container']['place_of_arrest'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Place of Arrest'),
      '#description' => $this->t('The place where the victim was arrested.'),
      '#maxlength' => 255,
      '#required' => FALSE,
    ];

    $form['add_new']['detention_container']['place_of_detention'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Place of Detention'),
      '#description' => $this->t('The place where the victim is/was detained.'),
      '#maxlength' => 255,
      '#required' => FALSE,
    ];

    $form['add_new']['detention_container']['charges'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Charges'),
      '#description' => $this->t('The charges filed against the victim.'),
      '#rows' => 3,
      '#required' => FALSE,
    ];

    $form['add_new']['detention_container']['already_released'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Already Released'),
      '#description' => $this->t('Check if the victim has been released from detention.'),
      '#default_value' => FALSE,
    ];

    $form['add_new']['detention_container']['remarks_on_release'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remarks on Release'),
      '#description' => $this->t('Additional remarks regarding the release of the victim.'),
      '#rows' => 3,
      '#states' => [
        'visible' => [
          ':input[name="add_new[detention_container][already_released]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['add_new']['violations_container']['descriptions'] = [
      '#type' => 'container',
      '#prefix' => '<div id="violation-descriptions">',
      '#suffix' => '</div>',
    ];

    if ($violations = $form_state->getValue(['add_new', 'violations_container', 'violations'])) {
      foreach ($violations as $delta => $violation) {
        $violation_entity = $this->entityTypeManager->getStorage('eden_violation')->load($violation['target_id']);
        if ($violation_entity) {
          $form['add_new']['violations_container']['descriptions'][$violation['target_id']] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description for @violation', ['@violation' => $violation_entity->label()]),
            '#description' => $this->t('Describe how this violation applies to this victim in this incident.'),
            '#rows' => 3,
            '#required' => TRUE,
          ];
        }
      }
    }

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
   * Ajax callback to update violation descriptions.
   */
  public function updateViolationDescriptions(array $form, FormStateInterface $form_state) {
    return $form['add_new']['violations_container']['descriptions'];
  }

  /**
   * Gets existing victims and their violations for an incident.
   *
   * @param int $incident_id
   *   The incident ID.
   *
   * @return array
   *   Array of victims and their violations.
   */
  protected function getExistingVictims($incident_id) {
    $victims = [];
    
    // Get all victims for this incident
    $query = $this->database->select('eden_incident_victim', 'iv');
    
    // Always get the victim_id
    $fields = ['victim_id'];
    
    // Check if the new fields exist before adding them to the query
    $schema = $this->database->schema();
    $detention_fields = [
      'date_of_detention',
      'place_of_arrest',
      'place_of_detention',
      'charges',
      'already_released',
      'remarks_on_release'
    ];
    
    foreach ($detention_fields as $field) {
      if ($schema->fieldExists('eden_incident_victim', $field)) {
        $fields[] = $field;
      }
    }
    
    $query->fields('iv', $fields);
    $query->condition('iv.incident_id', $incident_id);
    $victim_results = $query->execute()->fetchAll();

    foreach ($victim_results as $victim_data) {
      $victim_id = $victim_data->victim_id;
      
      // Get violations for each victim
      $query = $this->database->select('eden_incident_victim_violation', 'ivv');
      $query->fields('ivv', ['violation_id', 'description']);
      $query->condition('ivv.incident_id', $incident_id);
      $query->condition('ivv.victim_id', $victim_id);
      $violation_results = $query->execute()->fetchAll();

      $violations = [];
      foreach ($violation_results as $violation) {
        $violations[] = [
          'violation_id' => $violation->violation_id,
          'description' => $violation->description,
        ];
      }

      $victim_info = [
        'violations' => $violations,
      ];

      // Add detention fields if they exist
      foreach ($detention_fields as $field) {
        if (isset($victim_data->$field)) {
          $victim_info[$field] = $victim_data->$field;
        }
      }

      $victims[$victim_id] = $victim_info;
    }

    return $victims;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('incident_id')) {
      $form_state->setError($form, $this->t('No incident specified.'));
    }

    $victim = $form_state->getValue(['add_new', 'victim']);
    $violations = $form_state->getValue(['add_new', 'violations_container', 'violations']);

    // If either victim or violations are specified, both must be specified
    if ($victim || $violations) {
      if (!$victim) {
        $form_state->setError($form['add_new']['victim'], $this->t('You must select a victim when adding violations.'));
      }
      if (!$violations) {
        $form_state->setError($form['add_new']['violations_container']['violations'], $this->t('You must select at least one violation when adding a victim.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $incident_id = $form_state->getValue('incident_id');
    $victim_value = $form_state->getValue(['add_new', 'victim']);
    
    // Get victim ID directly from the entity reference value
    $victim_id = !empty($victim_value) ? $victim_value : NULL;
    
    $violations_data = $form_state->getValue(['add_new', 'violations_container']);
    
    if (!$victim_id || empty($violations_data['violations'])) {
      $this->messenger()->addStatus($this->t('No new victims or violations were added.'));
      return;
    }

    try {
      // Start a transaction
      $transaction = $this->database->startTransaction();

      // First check if the relationship exists
      $incident_victim_id = $this->database->select('eden_incident_victim', 'iv')
        ->fields('iv', ['id'])
        ->condition('incident_id', $incident_id)
        ->condition('victim_id', $victim_id)
        ->execute()
        ->fetchField();

      if (!$incident_victim_id) {
        // Get the date value and convert empty string to NULL
        $date_of_detention = $form_state->getValue(['add_new', 'detention_container', 'date_of_detention']);
        $date_of_detention = !empty($date_of_detention) ? $date_of_detention : NULL;

        // Insert new relationship
        $incident_victim_id = $this->database->insert('eden_incident_victim')
          ->fields([
            'incident_id' => $incident_id,
            'victim_id' => $victim_id,
            'date_of_detention' => $date_of_detention,
            'place_of_arrest' => $form_state->getValue(['add_new', 'detention_container', 'place_of_arrest']),
            'place_of_detention' => $form_state->getValue(['add_new', 'detention_container', 'place_of_detention']),
            'charges' => $form_state->getValue(['add_new', 'detention_container', 'charges']),
            'already_released' => (int) $form_state->getValue(['add_new', 'detention_container', 'already_released']),
            'remarks_on_release' => $form_state->getValue(['add_new', 'detention_container', 'remarks_on_release']),
            'created' => \Drupal::time()->getRequestTime(),
            'changed' => \Drupal::time()->getRequestTime(),
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ])
          ->execute();
      }
      else {
        // Get the date value and convert empty string to NULL
        $date_of_detention = $form_state->getValue(['add_new', 'detention_container', 'date_of_detention']);
        $date_of_detention = !empty($date_of_detention) ? $date_of_detention : NULL;

        // Update existing relationship
        $this->database->update('eden_incident_victim')
          ->fields([
            'date_of_detention' => $date_of_detention,
            'place_of_arrest' => $form_state->getValue(['add_new', 'detention_container', 'place_of_arrest']),
            'place_of_detention' => $form_state->getValue(['add_new', 'detention_container', 'place_of_detention']),
            'charges' => $form_state->getValue(['add_new', 'detention_container', 'charges']),
            'already_released' => (int) $form_state->getValue(['add_new', 'detention_container', 'already_released']),
            'remarks_on_release' => $form_state->getValue(['add_new', 'detention_container', 'remarks_on_release']),
            'changed' => \Drupal::time()->getRequestTime(),
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ])
          ->condition('id', $incident_victim_id)
          ->execute();
      }

      // Add the violations
      foreach ($violations_data['violations'] as $violation) {
        $description = isset($violations_data['descriptions'][$violation['target_id']]) 
          ? $violations_data['descriptions'][$violation['target_id']] 
          : '';

        // Check if this violation relationship exists
        $exists = $this->database->select('eden_incident_victim_violation', 'ivv')
          ->fields('ivv', ['id'])
          ->condition('incident_victim_id', $incident_victim_id)
          ->condition('incident_id', $incident_id)
          ->condition('victim_id', $victim_id)
          ->condition('violation_id', $violation['target_id'])
          ->execute()
          ->fetchField();

        if (!$exists) {
          // Insert new violation relationship
          $this->database->insert('eden_incident_victim_violation')
            ->fields([
              'incident_victim_id' => $incident_victim_id,
              'incident_id' => $incident_id,
              'victim_id' => $victim_id,
              'violation_id' => $violation['target_id'],
              'description' => $description,
              'created' => \Drupal::time()->getRequestTime(),
              'changed' => \Drupal::time()->getRequestTime(),
              'uid' => \Drupal::currentUser()->id(),
              'status' => 1,
            ])
            ->execute();
        }
        else {
          // Update existing violation relationship
          $this->database->update('eden_incident_victim_violation')
            ->fields([
              'description' => $description,
              'changed' => \Drupal::time()->getRequestTime(),
              'uid' => \Drupal::currentUser()->id(),
              'status' => 1,
            ])
            ->condition('incident_victim_id', $incident_victim_id)
            ->condition('incident_id', $incident_id)
            ->condition('victim_id', $victim_id)
            ->condition('violation_id', $violation['target_id'])
            ->execute();
        }
      }

      unset($transaction);
      $this->messenger()->addStatus($this->t('The victim and violations have been added to the incident.'));
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      $this->messenger()->addError($this->t('There was an error saving the relationships. Please try again.'));
      \Drupal::logger('eden')->error('Error saving incident-victim relationships: @error', ['@error' => $e->getMessage()]);
    }

    $form_state->setRedirect('entity.eden_incident.add_victim', ['eden_incident' => $incident_id]);
  }
} 