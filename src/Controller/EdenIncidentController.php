<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eden\Entity\Incident;
use Drupal\eden\Entity\Victim;
use Drupal\eden\Entity\Violation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\eden\Entity\Perpetrator;

/**
 * Controller for Eden Incident entity.
 */
class EdenIncidentController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EdenIncidentController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Removes a violation from an incident-victim relationship.
   *
   * @param \Drupal\eden\Entity\Incident $eden_incident
   *   The incident entity.
   * @param \Drupal\eden\Entity\Victim $victim
   *   The victim entity.
   * @param \Drupal\eden\Entity\Violation $violation
   *   The violation entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the incident page.
   */
  public function removeViolation(Incident $eden_incident, Victim $victim, Violation $violation) {
    try {
      // Delete the violation relationship
      $this->database->delete('eden_incident_victim_violation')
        ->condition('incident_id', $eden_incident->id())
        ->condition('victim_id', $victim->id())
        ->condition('violation_id', $violation->id())
        ->execute();

      // Check if this was the last violation for this victim
      $query = $this->database->select('eden_incident_victim_violation', 'ivv');
      $query->condition('incident_id', $eden_incident->id());
      $query->condition('victim_id', $victim->id());
      $count = $query->countQuery()->execute()->fetchField();

      // If no more violations exist for this victim, remove the victim relationship too
      if ($count == 0) {
        $this->database->delete('eden_incident_victim')
          ->condition('incident_id', $eden_incident->id())
          ->condition('victim_id', $victim->id())
          ->execute();
      }

      $this->messenger()->addStatus($this->t('The violation has been removed from the incident.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was an error removing the violation. Please try again.'));
      \Drupal::logger('eden')->error('Error removing violation from incident: @error', ['@error' => $e->getMessage()]);
    }

    return $this->redirect('entity.eden_incident.add_victim', ['eden_incident' => $eden_incident->id()]);
  }

  /**
   * Removes a perpetrator from an incident.
   *
   * @param \Drupal\eden\Entity\Incident $eden_incident
   *   The incident entity.
   * @param \Drupal\eden\Entity\Perpetrator $perpetrator
   *   The perpetrator entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the incident perpetrators page.
   */
  public function removePerpetrator(Incident $eden_incident, Perpetrator $perpetrator) {
    try {
      $this->database->delete('eden_incident_perpetrator')
        ->condition('incident_id', $eden_incident->id())
        ->condition('perpetrator_id', $perpetrator->id())
        ->execute();

      $this->messenger()->addStatus($this->t('The perpetrator has been removed from the incident.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was an error removing the perpetrator. Please try again.'));
      \Drupal::logger('eden')->error('Error removing perpetrator from incident: @error', ['@error' => $e->getMessage()]);
    }

    return $this->redirect('entity.eden_incident.add_perpetrator', ['eden_incident' => $eden_incident->id()]);
  }

  /**
   * Removes a victim from an incident.
   *
   * @param \Drupal\eden\Entity\Incident $eden_incident
   *   The incident entity.
   * @param \Drupal\eden\Entity\Victim $victim
   *   The victim entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the incident victims page.
   */
  public function removeVictim(Incident $eden_incident, Victim $victim) {
    try {
      // Check if there are any violations for this victim
      $query = $this->database->select('eden_incident_victim_violation', 'ivv');
      $query->condition('incident_id', $eden_incident->id());
      $query->condition('victim_id', $victim->id());
      $count = $query->countQuery()->execute()->fetchField();

      if ($count > 0) {
        $this->messenger()->addError($this->t('Cannot remove victim. Please remove all violations associated with this victim first.'));
      }
      else {
        // Remove the victim relationship
        $this->database->delete('eden_incident_victim')
          ->condition('incident_id', $eden_incident->id())
          ->condition('victim_id', $victim->id())
          ->execute();

        $this->messenger()->addStatus($this->t('The victim has been removed from the incident.'));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was an error removing the victim. Please try again.'));
      \Drupal::logger('eden')->error('Error removing victim from incident: @error', ['@error' => $e->getMessage()]);
    }

    return $this->redirect('entity.eden_incident.add_victim', ['eden_incident' => $eden_incident->id()]);
  }
} 