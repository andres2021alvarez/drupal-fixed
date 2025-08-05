<?php

namespace Drupal\mercury_editor\Form;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\mercury_editor\Ajax\IFrameAjaxResponseWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\Form\InsertComponentForm as LayoutParagraphsInsertComponentForm;

/**
 * Renders a form for inserting a new component in Mercury Editor.
 */
class InsertComponentForm extends LayoutParagraphsInsertComponentForm {

  /**
   * Defaults for the paragraph.
   *
   * @var array
   */
  protected $paragraphDefaults = [];

  /**
   * {@inheritDoc}
   */
  public function __construct(
    LayoutParagraphsLayoutTempstoreRepository $tempstore,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    ModuleHandlerInterface $module_handler,
    EventDispatcherInterface $event_dispatcher,
    EntityRepositoryInterface $entity_repository,
    protected IFrameAjaxResponseWrapper $iFrameAjaxResponseWrapper
    ) {
    parent::__construct($tempstore, $entity_type_manager, $layout_plugin_manager, $module_handler, $event_dispatcher, $entity_repository);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('module_handler'),
      $container->get('event_dispatcher'),
      $container->get('entity.repository'),
      $container->get('mercury_editor.iframe_ajax_response_wrapper'),
      $container->get('mercury_editor.context')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    LayoutParagraphsLayout $layout_paragraphs_layout = NULL,
    ParagraphsTypeInterface $paragraph_type = NULL,
    string $parent_uuid = NULL,
    string $region = NULL,
    string $sibling_uuid = NULL,
    string $placement = NULL,
    array $paragraph_defaults = [],
  ) {
    $this->paragraphDefaults = $paragraph_defaults;
    return parent::buildForm($form, $form_state, $layout_paragraphs_layout, $paragraph_type, $parent_uuid, $region, $sibling_uuid, $placement);
  }

  /**
   * {@inheritDoc}
   */
  public function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $this->ajaxCloseForm($response);
    if ($this->needsRefresh()) {
      $layout = $this->renderLayout();
      $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
      $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand($dom_selector, $layout));
      $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
      return $response;
    }

    $uuid = $this->paragraph->uuid();
    $rendered_item = $this->renderParagraph($uuid);

    switch ($this->method) {
      case 'before':
        $this->iFrameAjaxResponseWrapper->addCommand(new BeforeCommand($this->domSelector, $rendered_item));
        break;

      case 'after':
        $this->iFrameAjaxResponseWrapper->addCommand(new AfterCommand($this->domSelector, $rendered_item));
        break;

      case 'append':
        $this->iFrameAjaxResponseWrapper->addCommand(new AppendCommand($this->domSelector, $rendered_item));
        break;

      case 'prepend':
        $this->iFrameAjaxResponseWrapper->addCommand(new PrependCommand($this->domSelector, $rendered_item));
        break;
    }

    $this->iFrameAjaxResponseWrapper->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:insert'));
    $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  protected function newParagraph(ParagraphsTypeInterface $paragraph_type, string $langcode) {
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');
    $langcode_key = $entity_type->getKey('langcode');
    $bundle_key = $entity_type->getKey('bundle');
    $values = [
      $bundle_key => $paragraph_type->id(),
      $langcode_key => $langcode,
      '_layoutParagraphsLayout' => $this->getLayoutParagraphsLayout(),
    ] + $this->paragraphDefaults;
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $this->entityTypeManager->getStorage('paragraph')
      ->create($values);
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    return $paragraph;
  }

}
