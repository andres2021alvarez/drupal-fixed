<?php

namespace Drupal\paragraph_conditional_fields\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de configuración para Paragraph Conditional Fields.
 */
class ParagraphConditionalSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ParagraphConditionalSettingsForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['paragraph_conditional_fields.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraph_conditional_fields_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paragraph_conditional_fields.settings');
    $dependencies = $config->get('dependencies') ?: [];

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Configure conditional dependencies between parent and child paragraph fields.') . '</p>',
    ];

    $form['dependencies'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="dependencies-wrapper">',
      '#suffix' => '</div>',
    ];

    $num_dependencies = count($dependencies);
    if ($form_state->get('num_dependencies') !== NULL) {
      $num_dependencies = $form_state->get('num_dependencies');
    }
    $form_state->set('num_dependencies', $num_dependencies);

    for ($i = 0; $i < $num_dependencies; $i++) {
      $dependency = $dependencies[$i] ?? [];
      $form['dependencies'][$i] = $this->buildDependencyForm($dependency, $i);
    }

    $form['add_dependency'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Dependency'),
      '#submit' => ['::addDependency'],
      '#ajax' => [
        'callback' => '::dependenciesAjaxCallback',
        'wrapper' => 'dependencies-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Construye el formulario para una dependencia individual.
   */
  protected function buildDependencyForm($dependency, $index) {
    $paragraph_types = $this->getParagraphTypes();

    $form = [
      '#type' => 'details',
      '#title' => $this->t('Dependency @num', ['@num' => $index + 1]),
      '#open' => TRUE,
    ];

    $form['parent_paragraph_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent Paragraph Type'),
      '#options' => $paragraph_types,
      '#default_value' => $dependency['parent_paragraph_type'] ?? '',
      '#required' => TRUE,
    ];

    $form['parent_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent Field Machine Name'),
      '#description' => $this->t('Machine name of the field in the parent paragraph (e.g., field_tipo)'),
      '#default_value' => $dependency['parent_field'] ?? '',
      '#required' => TRUE,
    ];

    $form['trigger_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger Value'),
      '#description' => $this->t('Value that will trigger the condition (e.g., "uno")'),
      '#default_value' => $dependency['trigger_value'] ?? '',
      '#required' => TRUE,
    ];

    $form['child_paragraph_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Child Paragraph Type'),
      '#options' => $paragraph_types,
      '#default_value' => $dependency['child_paragraph_type'] ?? '',
      '#required' => TRUE,
    ];

    $form['dependent_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dependent Field Machine Name'),
      '#description' => $this->t('Machine name of the field to hide/show (e.g., field_ante_titulo)'),
      '#default_value' => $dependency['dependent_field'] ?? '',
      '#required' => TRUE,
    ];

    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => [
        'hide' => $this->t('Hide when condition is met'),
        'show' => $this->t('Show when condition is met'),
      ],
      '#default_value' => $dependency['action'] ?? 'hide',
    ];

    $form['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#submit' => ['::removeDependency'],
      '#ajax' => [
        'callback' => '::dependenciesAjaxCallback',
        'wrapper' => 'dependencies-wrapper',
      ],
      '#name' => 'remove_' . $index,
    ];

    return $form;
  }

  /**
   * Obtiene los tipos de paragraph disponibles.
   */
  protected function getParagraphTypes() {
    $paragraph_types = [];
    $types = $this->entityTypeManager->getStorage('paragraphs_type')->loadMultiple();

    foreach ($types as $type) {
      $paragraph_types[$type->id()] = $type->label();
    }

    return $paragraph_types;
  }

  /**
   * Ajax callback para actualizar las dependencias.
   */
  public function dependenciesAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['dependencies'];
  }

  /**
   * Submit handler para agregar dependencia.
   */
  public function addDependency(array &$form, FormStateInterface $form_state) {
    $num_dependencies = $form_state->get('num_dependencies') + 1;
    $form_state->set('num_dependencies', $num_dependencies);
    $form_state->setRebuild();
  }

  /**
   * Submit handler para remover dependencia.
   */
  public function removeDependency(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $index = str_replace('remove_', '', $trigger['#name']);

    $dependencies = $form_state->getValue('dependencies');
    unset($dependencies[$index]);
    $dependencies = array_values($dependencies);

    $form_state->setValue('dependencies', $dependencies);
    $form_state->set('num_dependencies', count($dependencies));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dependencies = $form_state->getValue('dependencies');
    $dependencies = array_filter($dependencies);

    $this->config('paragraph_conditional_fields.settings')
      ->set('dependencies', array_values($dependencies))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
