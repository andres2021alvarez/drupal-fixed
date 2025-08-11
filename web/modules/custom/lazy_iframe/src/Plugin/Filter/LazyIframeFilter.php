<?php

namespace Drupal\lazy_iframe\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

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
class LazyIframeFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $pattern = '/<iframe(?![^>]*loading\s*=)([^>]*)>/i';
    $replacement = '<iframe$1 loading="lazy">';
    $processed_text = preg_replace($pattern, $replacement, $text);
    return new FilterProcessResult($processed_text);
  }

}
