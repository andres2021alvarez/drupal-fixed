/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore drupalemphasisediting

import { Plugin } from "ckeditor5/src/core";
import { Widget } from "ckeditor5/src/widget";

export default class IframeLazyLoading extends Plugin {
  static get requires() {
    return [Widget];
  }

  static get pluginName() {
    return "IframeLazyLoading";
  }

  init() {
    const editor = this.editor;
    const schema = editor.model.schema;

    // Definir el elemento iframe en el schema
    schema.register("iframe", {
      allowWhere: "$block",
      allowAttributes: [
        "src",
        "width",
        "height",
        "loading",
        "allowfullscreen",
        "frameborder",
      ],
      isObject: true,
    });

    // Conversión desde modelo a vista (downcast)
    editor.conversion.elementToElement({
      model: "iframe",
      view: "iframe",
    });

    // Conversión desde vista a modelo (upcast) - ESTO ES LO QUE FALTABA
    editor.conversion.elementToElement({
      view: "iframe",
      model: "iframe",
    });

    // Conversión de atributos bidireccional
    editor.conversion.attributeToAttribute({
      model: "src",
      view: "src",
    });

    editor.conversion.attributeToAttribute({
      model: "width",
      view: "width",
    });

    editor.conversion.attributeToAttribute({
      model: "height",
      view: "height",
    });

    editor.conversion.attributeToAttribute({
      model: "loading",
      view: "loading",
    });

    editor.conversion.attributeToAttribute({
      model: "allowfullscreen",
      view: "allowfullscreen",
    });

    editor.conversion.attributeToAttribute({
      model: "frameborder",
      view: "frameborder",
    });

    editor.conversion.for("upcast").elementToElement({
      view: "iframe",
      model: "iframe",
    });

    editor.conversion.for("downcast").elementToElement({
      model: "iframe",
      view: "iframe",
    });

    this._setupEventListeners();
  }

  _setupEventListeners() {
    const editor = this.editor;
    const config = editor.config.get("fsfbIframeLazy") || {};
    if (!config.enabled) {
      return;
    }

    editor.editing.view.document.on("paste", (evt, data) => {
      this._processClipboardData(data);
    });

    editor.data.on("ready", () => {
      this._processExistingContent();
    });

    editor.data.on("set", () => {
      setTimeout(() => {
        this._processExistingContent();
      }, 100);
    });
  }

  _processClipboardData(data) {
    const config = this.editor.config.get("fsfbIframeLazy") || {};
    if (data.dataTransfer && data.dataTransfer.getData("text/html")) {
      let html = data.dataTransfer.getData("text/html");
      html = this._addLazyLoadingToHTML(html, config);
      data.dataTransfer.setData("text/html", html);
    }
  }

  _addLazyLoadingToHTML(html, config) {
    if (!config.autoAdd) {
      return html;
    }
    const iframeRegex = /<iframe([^>]*?)>/gi;
    return html.replace(iframeRegex, (match, attributes) => {
      if (attributes.includes("loading=")) {
        if (config.forceLazy) {
          attributes = attributes.replace(/loading=["'][^"'"]+['"]/gi, "");
          return `<iframe${attributes} loading="lazy">`;
        }
        return match;
      }
      return `<iframe${attributes} loading="lazy">`;
    });
  }

  _processExistingContent() {
    const editor = this.editor;
    const config = this.editor.config.get("fsfbIframeLazy") || {};
    let currentData = editor.getData();
    const processData = this._addLazyLoadingToHTML(currentData, config);
    if (currentData !== processData) {
      editor.setData(processData);
    }
  }
}
