<?php

namespace Drupal\eden\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for Eden search pages.
 */
class EdenSearchController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new EdenSearchController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    SearchPageRepositoryInterface $search_page_repository,
    RequestStack $request_stack
  ) {
    $this->formBuilder = $form_builder;
    $this->searchPageRepository = $search_page_repository;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('search.search_page_repository'),
      $container->get('request_stack')
    );
  }

  /**
   * Displays the search page.
   *
   * @return array
   *   A render array for the search page.
   */
  public function view() {
    $build = [];
    
    // Get all active search pages and find our eden_search page
    $active_pages = $this->searchPageRepository->getActiveSearchPages();
    $page = NULL;
    foreach ($active_pages as $search_page) {
      if ($search_page->id() === 'eden_search') {
        $page = $search_page;
        break;
      }
    }
    
    if ($page) {
      $build['search_form'] = $this->formBuilder->getForm('Drupal\search\Form\SearchPageForm', $page);
      $build['search_results'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['search-results']],
      ];
      
      if ($keys = $this->requestStack->getCurrentRequest()->query->get('keys')) {
        $build['search_results']['content'] = [
          '#theme' => 'search_results',
          '#results' => $page->getPlugin()->buildResults(),
          '#plugin_id' => $page->getPlugin()->getPluginId(),
        ];
      }
    }
    else {
      $build['message'] = [
        '#markup' => $this->t('The Eden search page is not currently active. Please ensure it is enabled in the Search settings.'),
      ];
    }
    
    return $build;
  }

  /**
   * Displays the search help page.
   *
   * @return array
   *   A render array for the help page.
   */
  public function help() {
    return [
      '#markup' => $this->t('
        <h2>Eden Search Help</h2>
        <p>Use the search form to find information across all Eden entities:</p>
        <ul>
          <li>Incidents</li>
          <li>Victims</li>
          <li>Violations</li>
          <li>Locations</li>
          <li>Perpetrators</li>
          <li>Sectors</li>
        </ul>
        <p>You can use the advanced search options to filter results by entity type.</p>
      '),
    ];
  }

} 