<?php

namespace Drupal\eden\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the sector add and edit forms.
 */
class SectorForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\eden\Entity\Sector */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['#title'] = $this->t('Edit sector %label', [
        '%label' => $this->entity->label(),
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->label()];
    $message = ($status == SAVED_NEW) 
      ? $this->t('Created new sector %label.', $message_args)
      : $this->t('Updated sector %label.', $message_args);
    
    $this->messenger()->addStatus($message);
    $form_state->setRedirect('entity.eden_sector.collection');
    
    return $status;
  }

} 