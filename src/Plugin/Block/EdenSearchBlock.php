<?php

namespace Drupal\eden\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides an 'Eden Search' block.
 *
 * @Block(
 *   id = "eden_search_block",
 *   admin_label = @Translation("Eden Search"),
 *   category = @Translation("Eden")
 * )
 */
class EdenSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new EdenSearchBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, SearchPageRepositoryInterface $search_page_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->searchPageRepository = $search_page_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('search.search_page_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access eden content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $active_pages = $this->searchPageRepository->getActiveSearchPages();
    foreach ($active_pages as $page) {
      if ($page->getPlugin()->getPluginId() === 'eden_search') {
        return $this->formBuilder->getForm('Drupal\search\Form\SearchBlockForm', $page->id());
      }
    }

    return [];
  }

} 