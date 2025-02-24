<?php

namespace Drupal\eden\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * View builder handler for victims.
 */
class VictimViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    // Don't call parent::view() to avoid duplicate field rendering
    $build = [];
    
    if ($view_mode == 'full') {
      // Add a custom theme wrapper
      $build['#theme_wrappers'][] = 'container';
      $build['#attributes']['class'][] = 'victim-details';

      // Personal Information Section with inline edit button
      $build['personal_info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['victim-personal-info', 'details-section'],
        ],
      ];

      $build['personal_info']['header_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['section-header-wrapper']],
        'header' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => t('Personal Information'),
          '#attributes' => ['class' => ['section-header']],
        ],
        'operations' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['header-operations']],
          'edit' => [
            '#type' => 'link',
            '#title' => t('Edit'),
            '#url' => Url::fromRoute('entity.eden_victim.edit_form', ['eden_victim' => $entity->id()]),
            '#attributes' => [
              'class' => ['button', 'button--primary'],
            ],
          ],
        ],
      ];

      // Add CSS
      $build['#attached']['library'][] = 'eden/victims';

      // Group fields by sections
      $this->buildPersonalInfoSection($build, $entity);
      $this->buildAffiliationSection($build, $entity);
      $this->buildLocationSection($build, $entity);
      $this->buildIncidentSection($build, $entity);

      // Add entity-level metadata
      $build['#entity_type'] = 'eden_victim';
      $build['#' . $entity->getEntityTypeId()] = $entity;
      $build['#cache']['tags'] = $entity->getCacheTags();
      $build['#cache']['contexts'] = $entity->getCacheContexts();
      $build['#cache']['max-age'] = $entity->getCacheMaxAge();
    }

    return $build;
  }

  /**
   * Builds the personal information section.
   */
  protected function buildPersonalInfoSection(array &$build, EntityInterface $entity) {
    $fields = [
      'victim_type' => t('Victim Type'),
      'first_name' => t('First Name'),
      'middle_name' => t('Middle Name'),
      'last_name' => t('Last Name'),
      'group_name' => t('Group Name'),
      'occupation' => t('Occupation'),
      'birthdate' => t('Birthdate'),
      'age' => t('Age'),
      'gender' => t('Gender'),
      'civil_status' => t('Civil Status'),
      'ethnicity' => t('Ethnicity'),
      'number_of_children' => t('Number of Children'),
      'sectors' => t('Sectors'),
    ];

    foreach ($fields as $field_name => $label) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $build['personal_info'][$field_name] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-item']],
          'label' => [
            '#type' => 'html_tag',
            '#tag' => 'strong',
            '#value' => $label . ':',
          ],
          'value' => $entity->get($field_name)->view([
            'label' => 'hidden',
            'type' => 'default',
          ]),
        ];
      }
    }
  }

  /**
   * Builds the affiliation section.
   */
  protected function buildAffiliationSection(array &$build, EntityInterface $entity) {
    $fields = [
      'organization_name' => t('Organization Name'),
      'position' => t('Position'),
      'other_affiliation' => t('Other Affiliation'),
    ];

    $has_affiliation = FALSE;
    foreach ($fields as $field_name => $label) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $has_affiliation = TRUE;
        break;
      }
    }

    if ($has_affiliation) {
      $build['affiliation'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['victim-affiliation', 'details-section']],
        'header' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => t('Affiliation'),
          '#attributes' => ['class' => ['section-header']],
        ],
      ];

      foreach ($fields as $field_name => $label) {
        if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
          $build['affiliation'][$field_name] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['field-item']],
            'label' => [
              '#type' => 'html_tag',
              '#tag' => 'strong',
              '#value' => $label . ':',
            ],
            'value' => $entity->get($field_name)->view([
              'label' => 'hidden',
              'type' => 'default',
            ]),
          ];
        }
      }
    }
  }

  /**
   * Builds the location section.
   */
  protected function buildLocationSection(array &$build, EntityInterface $entity) {
    $fields = [
      'location' => t('Town/City'),
      'residence' => t('Complete Address'),
    ];

    $has_location = FALSE;
    foreach ($fields as $field_name => $label) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $has_location = TRUE;
        break;
      }
    }

    if ($has_location) {
      $build['location'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['victim-location', 'details-section']],
        'header' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => t('Location Information'),
          '#attributes' => ['class' => ['section-header']],
        ],
      ];

      foreach ($fields as $field_name => $label) {
        if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
          $build['location'][$field_name] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['field-item']],
            'label' => [
              '#type' => 'html_tag',
              '#tag' => 'strong',
              '#value' => $label . ':',
            ],
            'value' => $entity->get($field_name)->view([
              'label' => 'hidden',
              'type' => 'default',
            ]),
          ];
        }
      }
    }
  }

  /**
   * Builds the incident section.
   */
  protected function buildIncidentSection(array &$build, EntityInterface $entity) {
    // Add related incidents if any
    if ($entity->hasField('incidents') && !$entity->get('incidents')->isEmpty()) {
      $build['incidents'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['victim-incidents', 'details-section']],
        'header' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => t('Related Incidents'),
          '#attributes' => ['class' => ['section-header']],
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => [],
        ],
      ];

      foreach ($entity->get('incidents') as $incident) {
        if ($incident->entity) {
          $build['incidents']['list']['#items'][] = [
            '#type' => 'markup',
            '#markup' => $incident->entity->label(),
          ];
        }
      }
    }
  }

} 