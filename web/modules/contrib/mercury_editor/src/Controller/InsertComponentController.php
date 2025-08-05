<?php

namespace Drupal\mercury_editor\Controller;

use Drupal\Core\Form\FormState;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\mercury_editor\DialogService;
use Drupal\layout_paragraphs\Utility\Dialog;
use Symfony\Component\HttpFoundation\Request;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Event\LayoutParagraphsComponentDefaultsEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\Controller\ComponentFormController;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * InsertComponentController class definition.
 */
class InsertComponentController extends ComponentFormController {

  use LayoutParagraphsLayoutRefreshTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The Mercury Editor Dialog service.
   *
   * @var \Drupal\mercury_editor\MercuryEditorDialog
   */
  protected $mercuryEditorDialog;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    LayoutParagraphsLayoutTempstoreRepository $tempstore,
    DialogService $mercury_editor_dialog) {
    $this->tempstore = $tempstore;
    $this->mercuryEditorDialog = $mercury_editor_dialog;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('mercury_editor.dialog'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function skipInsertForm(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $paragraph_type_id) {
    $skip_for_types = $this->config('mercury_editor.settings')->get('skip_create_form');
    $event = new LayoutParagraphsComponentDefaultsEvent($paragraph_type_id, []);
    $this->eventDispatcher()->dispatch($event, $event::EVENT_NAME);
    $paragraph_type = $this
      ->entityTypeManager()
      ->getStorage('paragraphs_type')
      ->load($event->getParagraphTypeId());

    if (isset($skip_for_types[$paragraph_type->id()])) {

      $response = new AjaxResponse();
      $iframe_ajax_response_wrapper = \Drupal::service('mercury_editor.iframe_ajax_response_wrapper');

      $this->setLayoutParagraphsLayout($layout_paragraphs_layout);

      $parent_uuid = $request->query->get('parent_uuid');
      $region = $request->query->get('region');
      $sibling_uuid = $request->query->get('sibling_uuid');
      $placement = $request->query->get('placement');

      $entity_type = $this->entityTypeManager()->getDefinition('paragraph');
      $bundle_key = $entity_type->getKey('bundle');
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $this->entityTypeManager->getStorage('paragraph')
        ->create([$bundle_key => $paragraph_type->id()]);

      $form_state = new FormState();
      $args = [
        $layout_paragraphs_layout,
        $paragraph_type,
        $parent_uuid,
        $region,
        $sibling_uuid,
        $placement,
      ];

      $form_state
        ->addBuildInfo('args', $args);
      $form = $this->formBuilder()
        ->buildForm('\Drupal\mercury_editor\Form\InsertComponentForm', $form_state);

      /** @var \Drupal\mercury_editor\Form\InsertComponentForm */
      $form_object = $form_state->getFormObject();
      $form_state->setUserInput([]);
      $form_object->validateForm($form, $form_state);
      $form_object->submitForm($form, $form_state);
      $paragraph = $form_object->buildParagraphComponent($form, $form_state);

      if ($sibling_uuid && $placement) {
        switch ($placement) {
          case 'before':
            $this->layoutParagraphsLayout->insertBeforeComponent($sibling_uuid, $paragraph);
            break;

          case 'after':
            $this->layoutParagraphsLayout->insertAfterComponent($sibling_uuid, $paragraph);
            break;
        }
      }
      elseif ($parent_uuid && $region) {
        $this->layoutParagraphsLayout->insertIntoRegion($parent_uuid, $region, $paragraph);
      }
      else {
        $this->layoutParagraphsLayout->appendComponent($paragraph);
      }

      $this->tempstore->set($this->layoutParagraphsLayout);
      $rendered_component = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $paragraph->uuid(),
      ];

      $response->addCommand(new CloseDialogCommand(Dialog::dialogSelector($this->layoutParagraphsLayout)));
      if ($this->needsRefresh()) {
        $layout = $this->renderLayout();
        $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
        $iframe_ajax_response_wrapper->addCommand(new ReplaceCommand($dom_selector, $layout));
        $response->addCommand($iframe_ajax_response_wrapper->getWrapperCommand());
        return $response;
      }

      if ($placement == 'before') {
        $iframe_ajax_response_wrapper->addCommand(new BeforeCommand('[data-uuid="' . $sibling_uuid . '"]', $rendered_component));
      }
      elseif ($placement == 'after') {
        $iframe_ajax_response_wrapper->addCommand(new AfterCommand('[data-uuid="' . $sibling_uuid . '"]', $rendered_component));
      }
      elseif ($parent_uuid && $region) {
        $iframe_ajax_response_wrapper->addCommand(new AppendCommand('[data-region-uuid="' . $parent_uuid . '-' . $region . '"]', $rendered_component));
      }
      $iframe_ajax_response_wrapper->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $paragraph->uuid(), 'component:insert'));

      $response->addCommand($iframe_ajax_response_wrapper->getWrapperCommand());
      return $response;
    }
    return $this->insertForm($request, $layout_paragraphs_layout, $paragraph_type->id());
  }

  /**
   * Returns the form, with ajax if appropriate.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   *
   * @return array|AjaxResponse
   *   The form or ajax response.
   */
  protected function openForm(array $form, LayoutParagraphsLayout $layout_paragraphs_layout) {
    if ($this->isAjax()) {
      $context = [
        'layout' => $layout_paragraphs_layout,
        'form' => $form,
      ];
      if ($form['#paragraph']) {
        $context['paragraph'] = $form['#paragraph'];
        $context['paragraph_type'] = $form['#paragraph']->bundle();
        $context['dialog'] = $context['paragraph_type'] . '_form';
      }
      $response = new AjaxResponse();
      $selector = Dialog::dialogSelector($layout_paragraphs_layout);
      $response->addCommand(new OpenDialogCommand($selector, $form['#title'], $form, $this->mercuryEditorDialog->dialogSettings($context)));
      return $response;
    }
    return $form;
  }

  /**
   * Returns the insert component form class.
   */
  protected function getInsertComponentFormClass() {
    return '\Drupal\mercury_editor\Form\InsertComponentForm';
  }

}
