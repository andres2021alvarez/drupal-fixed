(function (Drupal) {
  "use strict";
  Drupal.behaviors.fsfbIframeLazyLoading = {
    attach: function (context, settings) {
      const iframes = context.querySelectorAll("iframe:not([loading='lazy'])");
      iframes.forEach(function (iframe) {
        iframe.setAttribute("loading", "lazy");
      });
      if (typeof MutationObserver !== "undefined") {
        const observer = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            if (mutation.type === "childList") {
              mutation.addedNodes.forEach(function (node) {
                if (
                  node.tagName === "IFRAME" &&
                  !node.hasAttribute("loading")
                ) {
                  node.setAttribute("loading", "lazy");
                }
                const nestedIframe = node.querySelectorAll
                  ? node.querySelectorAll("iframe:not([loading])")
                  : [];
                nestedIframe.forEach(function (iframe) {
                  iframe.setAttribute("loading", "lazy");
                });
              });
            }
          });
        });
        observer.observe(context === document ? document.body : context, {
          childList: true,
          subtree: true,
        });
      }
    },
  };
})(Drupal);
