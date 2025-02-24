<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Perpetrator entity.
 *
 * @ingroup eden
 *
 * @ContentEntityType(
 *   id = "eden_perpetrator",
 *   label = @Translation("Perpetrator"),
 *   label_collection = @Translation("Perpetrators"),
 *   bundle_label = @Translation("Perpetrator type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eden\PerpetratorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\Core\Entity\EntityPermissionProvider",
 *     "form" = {
 *       "add" = "Drupal\eden\Form\PerpetratorForm",
 *       "edit" = "Drupal\eden\Form\PerpetratorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "selection" = "Drupal\eden\Entity\Handler\PerpetratorSelection",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "eden_perpetrator",
 *   data_table = "eden_perpetrator_field_data",
 *   revision_table = "eden_perpetrator_revision",
 *   revision_data_table = "eden_perpetrator_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer eden",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "group_name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "owner" = "uid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/eden/perpetrator/{eden_perpetrator}",
 *     "add-form" = "/admin/content/eden/perpetrator/add",
 *     "edit-form" = "/admin/content/eden/perpetrator/{eden_perpetrator}/edit",
 *     "delete-form" = "/admin/content/eden/perpetrator/{eden_perpetrator}/delete",
 *     "collection" = "/admin/content/eden/perpetrator"
 *   },
 *   field_ui_base_route = "eden.perpetrator_settings",
 *   permission_granularity = "entity_type"
 * )
 */
class Perpetrator extends EditorialContentEntityBase implements RevisionableInterface {

  use EntityOwnerTrait;

  /**
   * Gets the formatted label with group name and unit.
   *
   * @return string
   *   The formatted perpetrator string.
   */
  public function getFormattedLabel() {
    $parts = [];
    
    if ($this->hasField('group_name') && !$this->get('group_name')->isEmpty()) {
      $parts[] = $this->get('group_name')->value;
    }

    if ($this->hasField('unit') && !$this->get('unit')->isEmpty()) {
      $parts[] = $this->get('unit')->value;
    }
    
    return implode(' - ', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getFormattedLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(\Drupal\Core\Entity\EntityStorageInterface $storage) {
    parent::preSave($storage);
    
    // Ensure the revision user is set.
    if ($this->isNewRevision() && !$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unit'))
      ->setDescription(t('The unit of the perpetrator group.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_name'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Group'))
      ->setDescription(t('The type of perpetrator group.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'allowed_values' => [
          'AFP' => 'AFP',
          'AFP-ARMY' => 'AFP-ARMY',
          'AFP-NAVY' => 'AFP-NAVY',
          'AFP-AIRFORCE' => 'AFP-AIRFORCE',
          'PNP' => 'PNP',
          'CAFGU' => 'CAFGU',
          'LGU' => 'LGU',
          'BPSO' => 'BPSO',
          'CVO' => 'CVO',
          'CAA' => 'CAA',
          'NGU' => 'NGU',
          'PRIVATE' => 'PRIVATE',
          'PARAMILITARY' => 'PARAMILITARY',
          'OTHER' => 'OTHER',
        ],
      ])
      ->setDefaultValue('OTHER')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['brief_info'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Brief Info'))
      ->setDescription(t('Brief information about the perpetrator group.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['commanding_officer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CO'))
      ->setDescription(t('The commanding officer of the unit.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('The location of the perpetrator group.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'eden_location')
      ->setSetting('handler', 'eden_location')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'eden_location_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remarks'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Remarks'))
      ->setDescription(t('Additional remarks about the perpetrator.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']
      ->setDescription(t('A boolean indicating whether the Perpetrator is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the perpetrator was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the perpetrator was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the perpetrator author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 15,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

} 