<?php

namespace Drupal\eden\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Form controller for the incident entity edit forms.
 */
class IncidentForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New incident %label has been created.', $message_args));
        $this->logger('eden')->notice('Created new incident %label', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The incident %label has been updated.', $message_args));
        $this->logger('eden')->notice('Updated incident %label.', $logger_args);
        break;
    }

    $form_state->setRedirect('entity.eden_incident.canonical', ['eden_incident' => $entity->id()]);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Make the case number field read-only as it's auto-generated
    if (isset($form['case_number']['widget'][0]['value'])) {
      $form['case_number']['widget'][0]['value']['#attributes']['readonly'] = 'readonly';
    }

    // Add JavaScript to handle the unspecified date checkbox
    $form['#attached']['library'][] = 'eden/incident_form';

    // Add states to handle the date field visibility based on unspecified_date
    if (isset($form['date_of_incident']['widget'][0]['value'])) {
      $form['date_of_incident']['widget'][0]['value']['#states'] = [
        'disabled' => [
          ':input[name="unspecified_date[value]"]' => ['checked' => TRUE],
        ],
      ];
    }

    // Add states to handle the incident continuing checkbox
    if (isset($form['incident_continuing']['widget'][0]['value'])) {
      $form['incident_continuing']['widget'][0]['value']['#states'] = [
        'visible' => [
          ':input[name="unspecified_date[value]"]' => ['checked' => FALSE],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate date_of_incident is required unless unspecified_date is checked
    if (!$form_state->getValue(['unspecified_date', 0, 'value']) 
        && empty($form_state->getValue(['date_of_incident', 0, 'value']))) {
      $form_state->setError($form['date_of_incident'], $this->t('Date of Incident is required unless Unspecified Date is checked.'));
    }

    // Validate counts are non-negative
    $counts = ['victim_count', 'family_count', 'perpetrator_count'];
    foreach ($counts as $count_field) {
      $value = $form_state->getValue([$count_field, 0, 'value']);
      if ($value < 0) {
        $form_state->setError($form[$count_field], $this->t('@field cannot be negative.', 
          ['@field' => $form[$count_field]['widget']['#title']]));
      }
    }
  }

} 