<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Location entity.
 *
 * @ingroup eden
 *
 * @ContentEntityType(
 *   id = "eden_location",
 *   label = @Translation("Location"),
 *   label_collection = @Translation("Locations"),
 *   bundle_label = @Translation("Location type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eden\LocationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\Core\Entity\EntityPermissionProvider",
 *     "form" = {
 *       "add" = "Drupal\eden\Form\LocationForm",
 *       "edit" = "Drupal\eden\Form\LocationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "eden_location",
 *   data_table = "eden_location_field_data",
 *   revision_table = "eden_location_revision",
 *   revision_data_table = "eden_location_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer eden",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "town",
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
 *     "canonical" = "/admin/content/eden/location/{eden_location}",
 *     "add-form" = "/admin/content/eden/location/add",
 *     "edit-form" = "/admin/content/eden/location/{eden_location}/edit",
 *     "delete-form" = "/admin/content/eden/location/{eden_location}/delete",
 *     "collection" = "/admin/content/eden/location"
 *   },
 *   field_ui_base_route = "eden.location_settings",
 *   permission_granularity = "entity_type"
 * )
 */
class Location extends EditorialContentEntityBase implements RevisionableInterface {

  use EntityOwnerTrait;

  /**
   * Gets the formatted label with town, province, and region.
   *
   * @return string
   *   The formatted location string.
   */
  public function getFormattedLabel() {
    $parts = [];
    if ($this->hasField('town') && !$this->get('town')->isEmpty()) {
      $parts[] = $this->get('town')->value;
    }
    if ($this->hasField('province') && !$this->get('province')->isEmpty()) {
      $parts[] = $this->get('province')->value;
    }
    if ($this->hasField('region') && !$this->get('region')->isEmpty()) {
      $parts[] = $this->get('region')->value;
    }
    return implode(', ', $parts);
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

    $fields['town'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Town'))
      ->setDescription(t('The name of the town.'))
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

    $fields['province'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Province'))
      ->setDescription(t('The name of the province.'))
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
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['region'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Region'))
      ->setDescription(t('The name of the region.'))
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
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']
      ->setDescription(t('A boolean indicating whether the Location is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the location was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the location was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the location author.'))
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