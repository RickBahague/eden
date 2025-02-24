<?php

namespace Drupal\eden\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Form controller for the violation entity edit forms.
 */
class ViolationForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New violation %label has been created.', $message_args));
        $this->logger('eden')->notice('Created new violation %label', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The violation %label has been updated.', $message_args));
        $this->logger('eden')->notice('Updated violation %label.', $logger_args);
        break;
    }

    $form_state->setRedirect('entity.eden_violation.canonical', ['eden_violation' => $entity->id()]);
    return $result;
  }

} 