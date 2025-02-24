<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the Victim entity.
 *
 * @ContentEntityType(
 *   id = "eden_victim",
 *   label = @Translation("Victim"),
 *   label_collection = @Translation("Victims"),
 *   handlers = {
 *     "view_builder" = "Drupal\eden\Entity\VictimViewBuilder",
 *     "list_builder" = "Drupal\eden\VictimListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\eden\Form\VictimForm",
 *       "edit" = "Drupal\eden\Form\VictimForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "eden_victim",
 *   data_table = "eden_victim_field_data",
 *   revision_table = "eden_victim_revision",
 *   revision_data_table = "eden_victim_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer eden",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "name",
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
 *     "add-form" = "/admin/content/eden/victim/add",
 *     "canonical" = "/admin/content/eden/victim/{eden_victim}",
 *     "edit-form" = "/admin/content/eden/victim/{eden_victim}/edit",
 *     "delete-form" = "/admin/content/eden/victim/{eden_victim}/delete",
 *     "collection" = "/admin/content/eden/victim"
 *   },
 *   field_ui_base_route = "entity.eden_victim.settings",
 *   persist_with_no_fields = TRUE,
 *   deletion_disabled = TRUE
 * )
 */
class Victim extends ContentEntityBase implements RevisionableInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Add victim type field first as it controls the visibility of other fields
    $fields['victim_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Victim Type'))
      ->setDescription(t('The type of victim.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'individual' => 'Individual',
          'family' => 'Family',
          'community' => 'Community',
          'group' => 'Group',
          'organization' => 'Organization',
        ],
      ])
      ->setDefaultValue('individual')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -60,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -60,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Group name field for non-individual victims
    $fields['group_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group Name'))
      ->setDescription(t('Name of the family/community/group/organization.'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -55,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -55,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Individual victim fields
    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Name'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -49,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -49,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['middle_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Middle Name'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -48,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -48,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Computed field for the full name (used as label)
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full Name'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\eden\VictimNameComputed')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -47,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['occupation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Occupation'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -46,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -46,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sectors'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sectors'))
      ->setDescription(t('The sectors this victim belongs to.'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'eden_sector')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -45,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -45,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['birthdate'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Birthdate'))
      ->setRequired(FALSE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'html_date',
        ],
        'weight' => -44,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -44,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Age'))
      ->setRequired(FALSE)
      ->setSettings([
        'min' => 0,
        'max' => 150,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -43,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -43,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['gender'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Gender'))
      ->setRequired(FALSE)
      ->setSettings([
        'allowed_values' => [
          'male' => 'Male',
          'female' => 'Female',
          'other' => 'Other',
          'na' => 'Not Applicable'
        ],
      ])
      ->setDefaultValue('na')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -42,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -42,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['civil_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Civil Status'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'single' => 'Single',
          'married' => 'Married',
          'divorced' => 'Divorced',
          'widowed' => 'Widowed',
          'na' => 'Not Applicable'
        ],
      ])
      ->setDefaultValue('na')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -41,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -41,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ethnicity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ethnicity'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -40,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -40,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_children'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Children'))
      ->setDescription(t('The total number of children.'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -39,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -39,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['children_below_18'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Children Below 18 y.o.'))
      ->setDescription(t('The number of children below 18 years old.'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => -38,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -38,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Town/City'))
      ->setDescription(t('The town/city where the victim is from.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'eden_location')
      ->setSetting('handler', 'eden_location')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'eden_location_default',
        'weight' => -37,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -37,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => 'Start typing to find a location...',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['residence'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Complete Address'))
      ->setDescription(t('The complete address/residence details of the victim (street, barangay, etc.).'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'basic_string',
        'weight' => -36,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -36,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['organization_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Organization Name'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -35,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Position'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -34,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -34,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['other_affiliation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Other Affiliation'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'basic_string',
        'weight' => -33,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -33,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remarks'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Remarks'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -31,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -31,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
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
      ->setDescription(t('The time that the victim was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the victim was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

} 