<?php

namespace Drupal\eden\Plugin\Search;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\search\Annotation\SearchPlugin;

/**
 * Handles searching for Eden entities.
 *
 * @SearchPlugin(
 *   id = "eden_search",
 *   title = @Translation("Eden Search")
 * )
 */
class EdenSearch extends SearchPluginBase {

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Array of entity types to search.
   *
   * @var array
   */
  protected $searchableEntityTypes = [
    'eden_incident' => [
      'fields' => [
        'case_number',
        'title',
        'account_of_incident',
        'description_value',
        'date_of_incident',
        'filing_date'
      ],
      'label' => 'Incident',
    ],
    'eden_victim' => [
      'fields' => ['first_name', 'last_name', 'middle_name', 'name', 'occupation', 'group_name'],
      'label' => 'Victim',
    ],
    'eden_violation' => [
      'fields' => ['violation', 'description_value', 'category'],
      'label' => 'Violation',
    ],
    'eden_location' => [
      'fields' => ['town', 'province', 'region'],
      'label' => 'Location',
    ],
    'eden_perpetrator' => [
      'fields' => ['group_name', 'unit'],
      'label' => 'Perpetrator',
    ],
    'eden_sector' => [
      'fields' => ['name', 'sector_code', 'description_value'],
      'label' => 'Sector',
    ],
  ];

  /**
   * Constructs an EdenSearch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (empty($this->keywords)) {
      return [];
    }

    $results = [];
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Search each entity type.
    foreach ($this->searchableEntityTypes as $entity_type_id => $info) {
      try {
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $data_table = $entity_type->getDataTable();
        $base_table = $entity_type->getBaseTable();
        
        // Start with the base query
        $query = $this->database->select($data_table ?: $base_table, 'e')
          ->fields('e', ['id', 'langcode'])
          ->condition('e.langcode', $langcode)
          ->condition('e.status', 1)
          ->range(0, 10);

        // Add conditions for each searchable field
        $or = $query->orConditionGroup();
        foreach ($info['fields'] as $field) {
          $field_storage = \Drupal::service('entity_field.manager')
            ->getFieldStorageDefinitions($entity_type_id)[$field] ?? NULL;
            
          if ($field_storage) {
            // Handle fields in separate tables
            if ($field_storage->getCardinality() != 1) {
              $table_name = $entity_type_id . '__' . $field;
              if ($this->database->schema()->tableExists($table_name)) {
                $query->leftJoin($table_name, $field, "[e].[id] = [$field].[entity_id] AND [e].[langcode] = [$field].[langcode]");
                $or->condition("$field.{$field}_value", '%' . $this->database->escapeLike($this->keywords) . '%', 'LIKE');
              }
            }
            // Handle fields in data table
            elseif ($this->database->schema()->fieldExists($data_table ?: $base_table, $field)) {
              $or->condition("e.$field", '%' . $this->database->escapeLike($this->keywords) . '%', 'LIKE');
            }
          }
        }
        
        // Only add the OR conditions if there are any valid fields
        if (count($or->conditions())) {
          $query->condition($or);
          
          $results_query = $query->execute();
          foreach ($results_query as $row) {
            $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($row->id);
            if ($entity && $entity->access('view')) {
              $results[] = [
                'title' => $entity->label(),
                'link' => $entity->toUrl()->toString(),
                'snippet' => $this->buildSnippet($entity, $info['fields']),
                'type' => $info['label'],
              ];
            }
          }
        }
      }
      catch (\Exception $e) {
        // Log the error but continue with other entity types
        \Drupal::logger('eden_search')->error('Error searching @type: @message', [
          '@type' => $entity_type_id,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    return $results;
  }

  /**
   * Builds a text snippet for search results.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build a snippet for.
   * @param array $fields
   *   Array of fields to include in the snippet.
   *
   * @return string
   *   The snippet text.
   */
  protected function buildSnippet($entity, array $fields) {
    $snippet_parts = [];
    foreach ($fields as $field) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        $field_item = $entity->get($field)->first();
        
        // Handle different field types
        if ($field_item->getFieldDefinition()->getType() === 'datetime') {
          $value = $field_item->date->format('Y-m-d');
        }
        elseif ($field_item->getFieldDefinition()->getType() === 'entity_reference') {
          $value = $field_item->entity ? $field_item->entity->label() : '';
        }
        else {
          // Default to main property value
          $value = $field_item->value ?? $field_item->getValue()['value'] ?? '';
        }
        
        if ($value && strlen($value) > 50) {
          $value = substr($value, 0, 50) . '...';
        }
        if ($value) {
          $snippet_parts[] = $value;
        }
      }
    }
    return implode(' | ', array_filter($snippet_parts));
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced search'),
      '#open' => FALSE,
    ];

    $form['advanced']['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Search in'),
      '#options' => array_map(function ($info) {
        return $info['label'];
      }, $this->searchableEntityTypes),
      '#default_value' => array_keys($this->searchableEntityTypes),
    ];

    $form['#submit'][] = [$this, 'searchFormSubmit'];
  }

  /**
   * Form submission handler for the search form.
   */
  public function searchFormSubmit(array &$form, FormStateInterface $form_state) {
    $keys = trim($form_state->getValue('keys'));
    $entity_types = array_filter($form_state->getValue('entity_types'));

    if (!empty($keys)) {
      $query = [
        'keys' => $keys,
      ];
      if (!empty($entity_types)) {
        $query['types'] = implode(',', array_keys($entity_types));
      }
      $form_state->setRedirect('search.view_eden_search', [], ['query' => $query]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();

    $output = [];
    foreach ($results as $result) {
      $output[] = [
        '#theme' => 'search_result',
        '#result' => $result,
        '#plugin_id' => $this->getPluginId(),
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $account ? $account : $this->currentUser;
    return $access->hasPermission('access eden content');
  }

} 