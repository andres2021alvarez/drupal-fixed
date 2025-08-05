(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.paragraphConditionalFields = {
    attach: function (context, settings) {
      if (typeof settings.paragraph_conditional_fields === "undefined") {
        return;
      }

      var dependencies = settings.paragraph_conditional_fields;

      $.each(dependencies, function (index, dependency) {
        function processForm(formContainer) {
          var parentSelector = formContainer.find(
            '[name="' +
              dependency.parent_field +
              '"], [data-drupal-selector="edit-' +
              dependency.parent_field.replace(/_/g, "-") +
              '"]'
          );
          var childSelector = formContainer.find(
            '[name*="[inline_entity_form][entities]"][name*="[' +
              dependency.dependent_field +
              ']"]'
          );


          if (parentSelector.length === 0 || childSelector.length === 0) {
            return;
          }

          function evaluateCondition() {
            var parentValue = parentSelector.val();
            var shouldHide =
              (dependency.action === "hide" &&
                parentValue === dependency.trigger_value) ||
              (dependency.action === "show" &&
                parentValue !== dependency.trigger_value);

            var targetWrapper = childSelector.closest(
              ".field--type-string, .field--type-text, .js-form-wrapper"
            );

            if (shouldHide) {
              targetWrapper.hide();
            } else {
              targetWrapper.show();
            }
          }

          parentSelector
            .off("change.paragraphConditional")
            .on("change.paragraphConditional", evaluateCondition);

          evaluateCondition();
        }

        $(".layout-paragraphs-component-form", context).each(function () {
          processForm($(this));
        });

        $(".ief-form", context).each(function () {
          var parentForm = $(this).closest(".layout-paragraphs-component-form");
          if (parentForm.length > 0) {
            processForm(parentForm);
          }
        });
      });
    },
  };

  $(document).on("DOMNodeInserted", function (e) {
    var $target = $(e.target);

    if (
      $target.hasClass("layout-paragraphs-component-form") ||
      $target.find(".layout-paragraphs-component-form").length > 0
    ) {
      setTimeout(function () {
        Drupal.behaviors.paragraphConditionalFields.attach(
          $target,
          drupalSettings
        );
      }, 100);
    }

    if ($target.hasClass("ief-form") || $target.find(".ief-form").length > 0) {
      setTimeout(function () {
        Drupal.behaviors.paragraphConditionalFields.attach(
          $target,
          drupalSettings
        );
      }, 100);
    }
  });

  $(document).on("ajaxComplete", function (event, xhr, settings) {
    if (
      settings.url &&
      (settings.url.indexOf("layout_paragraphs") !== -1 ||
        settings.url.indexOf("inline_entity_form") !== -1)
    ) {
      setTimeout(function () {
        Drupal.behaviors.paragraphConditionalFields.attach(
          document,
          drupalSettings
        );
      }, 200);
    }
  });
})(jQuery, Drupal, drupalSettings);
