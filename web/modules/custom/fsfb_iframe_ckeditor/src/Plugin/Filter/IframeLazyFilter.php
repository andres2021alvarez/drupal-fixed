<?php

namespace Drupal\fsfb_iframe_ckeditor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to add lazy loading to iframes.
 *
 * @Filter(
 *   id = "fsfb_iframe_lazy_filter",
 *   title = @Translation("FSFB Iframe Lazy Loading"),
 *   description = @Translation("Add lazy loading to iframes"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 10
 * )
 */
class IframeLazyFilter extends FilterBase implements FilterInterface {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $pattern = '/<iframe([^>]*?)>/i';

    $replacement = function ($matches) {
      $attributes = $matches[1];

      if (preg_match('/loading\s*=\s*["\'][^"\']*["\']/', $attributes)) {
        return $matches[0];
      }

      return '<iframe' . $attributes . ' loading="lazy">';
    };

    $processed_text = preg_replace_callback($pattern, $replacement, $text);

    if ($processed_text === NULL) {
      return new FilterProcessResult($text);
    }

    return new FilterProcessResult($processed_text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('This filter automatically adds loading="lazy" attribute to iframe elements that don\'t already have a loading attribute.');
    }
    return $this->t('Automatically adds lazy loading to iframes.');
  }

}
