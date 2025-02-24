<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the Sector entity.
 *
 * @ContentEntityType(
 *   id = "eden_sector",
 *   label = @Translation("Sector"),
 *   label_collection = @Translation("Sectors"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eden\SectorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\eden\Form\SectorForm",
 *       "edit" = "Drupal\eden\Form\SectorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "eden_sector",
 *   data_table = "eden_sector_field_data",
 *   revision_table = "eden_sector_revision",
 *   revision_data_table = "eden_sector_field_revision",
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
 *     "add-form" = "/admin/content/eden/sector/add",
 *     "canonical" = "/admin/content/eden/sector/{eden_sector}",
 *     "edit-form" = "/admin/content/eden/sector/{eden_sector}/edit",
 *     "delete-form" = "/admin/content/eden/sector/{eden_sector}/delete",
 *     "collection" = "/admin/content/eden/sector"
 *   },
 *   field_ui_base_route = "entity.eden_sector.settings",
 *   persist_with_no_fields = TRUE,
 *   deletion_disabled = TRUE
 * )
 */
class Sector extends RevisionableContentEntityBase implements RevisionLogInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Gets the created time of the sector.
   *
   * @return int
   *   Created time of the sector.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Sets the created time of the sector.
   *
   * @param int $timestamp
   *   The sector creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Gets the changed time of the sector.
   *
   * @return int
   *   Changed time of the sector.
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }

    // Initialize revision metadata if this is a new revision or new entity.
    if ($this->isNew()) {
      $this->setNewRevision();
    }

    if ($this->isNewRevision()) {
      $this->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $this->setRevisionUserId(\Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector Code'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setRevisionable(TRUE)
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
      ->setDescription(t('The time that the sector was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the sector was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

} 