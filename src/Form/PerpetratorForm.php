<?php

namespace Drupal\eden\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the perpetrator entity edit forms.
 */
class PerpetratorForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->label()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New perpetrator %label has been created.', $message_arguments));
        $this->logger('eden')->notice('Created new perpetrator %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The perpetrator %label has been updated.', $message_arguments));
        $this->logger('eden')->notice('Updated perpetrator %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.eden_perpetrator.canonical', ['eden_perpetrator' => $entity->id()]);
    return $result;
  }

} 