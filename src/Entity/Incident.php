<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Incident entity.
 *
 * @ContentEntityType(
 *   id = "eden_incident",
 *   label = @Translation("Incident"),
 *   label_collection = @Translation("Incidents"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eden\IncidentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\eden\Form\IncidentForm",
 *       "edit" = "Drupal\eden\Form\IncidentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "eden_incident",
 *   data_table = "eden_incident_field_data",
 *   revision_table = "eden_incident_revision",
 *   revision_data_table = "eden_incident_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer eden",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/eden/incident/add",
 *     "canonical" = "/admin/content/eden/incident/{eden_incident}",
 *     "edit-form" = "/admin/content/eden/incident/{eden_incident}/edit",
 *     "delete-form" = "/admin/content/eden/incident/{eden_incident}/delete",
 *     "collection" = "/admin/content/eden/incident"
 *   },
 *   field_ui_base_route = "entity.eden_incident.settings",
 *   persist_with_no_fields = TRUE,
 *   deletion_disabled = TRUE
 * )
 */
class Incident extends ContentEntityBase implements RevisionableInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['case_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Case Number'))
      ->setDescription(t('Auto-generated case number (EDN-YYYY-MM-<serial number>).'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 20)
      ->setDefaultValueCallback(static::class . '::getDefaultCaseNumber')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
        'settings' => [
          'disabled' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['involving_children'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Involving Children'))
      ->setDescription(t('Whether the incident involves children.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -48,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -48,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mining_related'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mining-related'))
      ->setDescription(t('Whether the incident is mining-related.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -47,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -47,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['agrarian_related'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Agrarian-related'))
      ->setDescription(t('Whether the incident is agrarian-related.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -46,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -46,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['demolition_related'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Demolition-related'))
      ->setDescription(t('Whether the incident is demolition-related.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -45,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -45,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_of_incident'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of Incident'))
      ->setDescription(t('The date when the incident occurred (YYYY-MM-DD).'))
      ->setRequired(TRUE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'custom',
          'date_format' => 'Y-m-d',
        ],
        'weight' => -44,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -44,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unspecified_date'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Unspecified Date'))
      ->setDescription(t('Whether the incident date is unspecified.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -44,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -44,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['incident_continuing'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Incident is Continuing'))
      ->setDescription(t('Whether the incident is ongoing.'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -43,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -43,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Date of incident (YYYY-MMM-DD), Name of victim/s or community victims, Violation acronym e.g. 2011-May-23 Juan dela Cruz, EJK'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -45,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -45,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['victim_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Victims Count'))
      ->setDescription(t('The number of victims involved in this incident.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -35,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['family_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Families Count'))
      ->setDescription(t('The number of families affected by this incident.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -30,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['perpetrator_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Perpetrators Count'))
      ->setDescription(t('The number of perpetrators involved in this incident.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -25,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('The location where the incident occurred.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'eden_location')
      ->setSetting('handler', 'eden_location')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'eden_location_default',
        'weight' => -15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => 'Start typing to find a location...',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['account_of_incident'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Account of Incident'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -40,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -40,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filing_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Filing Date'))
      ->setDescription(t('The date when the incident was filed (YYYY-MM-DD).'))
      ->setRequired(TRUE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValueCallback(static::class . '::getDefaultFilingDate')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'custom',
          'date_format' => 'Y-m-d',
        ],
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the incident was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the incident was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user who created the incident.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user who created the revision.'))
      ->setSetting('target_type', 'user');

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the revision was created.'));

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Generate case number if not set
    if (empty($this->get('case_number')->value)) {
      $date = new \DateTime();
      $year = $date->format('Y');
      $month = $date->format('m');

      // Get the next serial number for this month
      $query = \Drupal::database()->select('eden_incident_field_data', 'i')
        ->condition('case_number', 'EDN-' . $year . '-' . $month . '-%', 'LIKE');
      $query->addExpression('MAX(SUBSTRING(case_number, -4))', 'last_serial');
      $last_serial = $query->execute()->fetchField();

      // Start with 0001 if no previous cases this month
      $next_serial = str_pad(($last_serial ? (int)$last_serial + 1 : 1), 4, '0', STR_PAD_LEFT);

      // Set the case number
      $this->set('case_number', sprintf('EDN-%s-%s-%s', $year, $month, $next_serial));
    }

    // Ensure user tracking
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('created', \Drupal::time()->getRequestTime());
    }
    $this->set('changed', \Drupal::time()->getRequestTime());
    
    // Track revision user
    $this->set('revision_uid', \Drupal::currentUser()->id());
    $this->set('revision_timestamp', \Drupal::time()->getRequestTime());
  }

  /**
   * Default value callback for the filing_date field.
   *
   * @return array
   *   An array containing the default value.
   */
  public static function getDefaultFilingDate() {
    return [
      'value' => date('Y-m-d'),
    ];
  }

  /**
   * Default value callback for the case_number field.
   *
   * @return array
   *   An array containing the default value.
   */
  public static function getDefaultCaseNumber() {
    $date = new \DateTime();
    $year = $date->format('Y');
    $month = $date->format('m');

    // Get the next serial number for this month
    $query = \Drupal::database()->select('eden_incident_field_data', 'i')
      ->condition('case_number', 'EDN-' . $year . '-' . $month . '-%', 'LIKE');
    $query->addExpression('MAX(SUBSTRING(case_number, -4))', 'last_serial');
    $last_serial = $query->execute()->fetchField();

    // Start with 0001 if no previous cases this month
    $next_serial = str_pad(($last_serial ? (int)$last_serial + 1 : 1), 4, '0', STR_PAD_LEFT);

    // Return the case number
    return ['value' => sprintf('EDN-%s-%s-%s', $year, $month, $next_serial)];
  }

} 