<?php

namespace Drupal\eden\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Form controller for the victim entity edit forms.
 */
class VictimForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    
    // Get the victim entity
    $victim = $this->entity;

    // Add victim type at the top level
    $form['victim_type_container'] = [
      '#type' => 'container',
      '#weight' => -100,
      'victim_type' => $form['victim_type'],
    ];
    unset($form['victim_type']);

    // Container for individual victim fields
    $form['individual_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Individual Information'),
      '#states' => [
        'visible' => [
          ':input[name="victim_type"]' => ['value' => 'individual'],
        ],
      ],
      '#weight' => -90,
    ];

    // Move individual-specific fields to their container
    $individual_fields = [
      'first_name',
      'middle_name',
      'last_name',
      'birthdate',
      'age',
      'gender',
      'civil_status',
      'occupation',
      'position',
      'other_affiliation',
      'number_of_children',
      'children_below_18',
    ];

    foreach ($individual_fields as $field) {
      if (isset($form[$field])) {
        $form['individual_container'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // Container for non-individual victim fields
    $form['group_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Group Information'),
      '#states' => [
        'visible' => [
          ':input[name="victim_type"]' => [
            ['value' => 'family'],
            ['value' => 'community'],
            ['value' => 'group'],
            ['value' => 'organization'],
          ],
        ],
      ],
      '#weight' => -80,
    ];

    // Move group-specific fields to their container
    if (isset($form['group_name'])) {
      $form['group_container']['group_name'] = $form['group_name'];
      unset($form['group_name']);
    }

    // Container for common fields
    $form['common_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Additional Information'),
      '#weight' => -70,
    ];

    // Move common fields to their container
    $common_fields = [
      'sectors',
      'ethnicity',
      'location',
      'residence',
      'organization_name',
      'remarks',
    ];

    foreach ($common_fields as $field) {
      if (isset($form[$field])) {
        $form['common_container'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // Add field requirements based on victim type
    $form['group_container']['group_name']['widget'][0]['value']['#states'] = [
      'required' => [
        ':input[name="victim_type"]' => [
          ['value' => 'family'],
          ['value' => 'community'],
          ['value' => 'group'],
          ['value' => 'organization'],
        ],
      ],
    ];

    // Remove the #states requirement for these fields
    $non_required_fields = [
      'first_name',
      'last_name',
      'middle_name',
      'occupation',
      'gender',
      'number_of_children',
      'position',
      'other_affiliation',
      'sectors',
      'residence',
    ];
    foreach ($individual_fields as $field) {
      if (isset($form['individual_container'][$field]) && !in_array($field, $non_required_fields)) {
        $form['individual_container'][$field]['widget'][0]['value']['#states'] = [
          'required' => [
            ':input[name="victim_type"]' => ['value' => 'individual'],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $victim_type = $form_state->getValue('victim_type')[0]['value'];
    
    // Validate required fields based on victim type
    if ($victim_type !== 'individual') {
      if (empty($form_state->getValue('group_name')[0]['value'])) {
        $form_state->setErrorByName('group_name', $this->t('Group name is required for non-individual victims.'));
      }
    } else {
      // Validate children counts for individual victims
      $total_children = $form_state->getValue('number_of_children')[0]['value'] ?? 0;
      $children_below_18 = $form_state->getValue('children_below_18')[0]['value'] ?? 0;
      
      if ($children_below_18 > $total_children) {
        $form_state->setErrorByName('children_below_18', $this->t('The number of children below 18 cannot exceed the total number of children.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $message_args = ['%label' => $entity->label()];
    $logger_args = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New victim %label has been created.', $message_args));
        $this->logger('eden')->notice('Created new victim %label', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The victim %label has been updated.', $message_args));
        $this->logger('eden')->notice('Updated victim %label.', $logger_args);
        break;
    }

    $form_state->setRedirect('entity.eden_victim.canonical', ['eden_victim' => $entity->id()]);
    return $result;
  }

} 