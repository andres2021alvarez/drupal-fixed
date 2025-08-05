<?php

namespace Drupal\fsfb_iframe_ckeditor\Plugin\CKEditor5Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;

/**
 * CKEditor5 Plugin for iframe lazy loading.
 *
 * @CKEditor5Plugin(
 *   id = "fsfb_iframe_ckeditor_iframe_lazy",
 *   ckeditor5 = {
 *     "plugins" = {"fsfbIframeLazy.IframeLazyLoading"},
 *     "config" = {
 *       "fsfbIframeLazy" = {
 *         "enabled" = true,
 *         "autoAdd" = true,
 *         "forceLazy" = false
 *       }
 *     }
 *   },
 *   drupal = {
 *     "label" = @Translation("FSFB Iframe Lazy Loading"),
 *     "library" = "fsfb_iframe_ckeditor/iframe_lazy",
 *     "elements" = {
 *         "<iframe>",
 *         "<iframe src>",
 *         "<iframe width>",
 *         "<iframe height>",
 *         "<iframe loading>",
 *         "<iframe allowfullscreen>",
 *         "<iframe frameborder>"
 *     }
 *   }
 * )
 */
class IframeLazyLoading extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled' => TRUE,
      'autoAdd' => TRUE,
      'forceLazy' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable iframe lazy loading'),
      '#default_value' => $this->configuration['enabled'] ?? TRUE,
      '#description' => $this->t('Enable automatic lazy loading for iframes inserted via CKEditor.'),
    ];

    $form['autoAdd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto add lazy loading'),
      '#default_value' => $this->configuration['autoAdd'] ?? TRUE,
      '#description' => $this->t('Automatically add loading="lazy" attribute to iframes that don\'t have it.'),
      '#states' => [
        'visible' => [
          ':input[name="editor[settings][plugins][fsfb_iframe_ckeditor_iframe_lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['forceLazy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force lazy loading'),
      '#default_value' => $this->configuration['forceLazy'] ?? FALSE,
      '#description' => $this->t('Override existing loading attributes and force lazy loading on all iframes.'),
      '#states' => [
        'visible' => [
          ':input[name="editor[settings][plugins][fsfb_iframe_ckeditor_iframe_lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validación opcional: si forceLazy está habilitado, autoAdd debe estar habilitado.
    if ($form_state->getValue('forceLazy') && !$form_state->getValue('autoAdd')) {
      $form_state->setErrorByName('autoAdd', $this->t('Auto add must be enabled when Force lazy loading is enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled'] = (bool) $form_state->getValue('enabled');
    $this->configuration['autoAdd'] = (bool) $form_state->getValue('autoAdd');
    $this->configuration['forceLazy'] = (bool) $form_state->getValue('forceLazy');
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return [
      'fsfbIframeLazy' => [
        'enabled' => $this->configuration['enabled'],
        'autoAdd' => $this->configuration['autoAdd'],
        'forceLazy' => $this->configuration['forceLazy'],
      ],
    ];
  }

}
