<?php

namespace Drupal\lazy_iframe\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\lazy_iframe\LazyIframeProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to add lazy loading to iframes.
 *
 * @Filter(
 *   id = "lazy_iframe",
 *   title = @Translation("Add lazy loading to iframes"),
 *   description = @Translation("Automatically adds loading='lazy' attribute to iframes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 50
 * )
 */
class LazyIframeFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The lazy iframe processor.
   *
   * @var \Drupal\lazy_iframe\LazyIframeProcessor
   */
  protected $processor;

  /**
   * Constructs a LazyIframeFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\lazy_iframe\LazyIframeProcessor $processor
   *   The lazy iframe processor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LazyIframeProcessor $processor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('lazy_iframe.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $processed_text = $this->processor->processContent($text);
    return new FilterProcessResult($processed_text);
  }

}
