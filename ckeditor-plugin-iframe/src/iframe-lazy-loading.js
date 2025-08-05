import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';


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
        "title", // Mejora: agregar title para accesibilidad
        "class"  // Mejora: permitir clases CSS
      ],
      isObject: true,
    });

    // Configurar conversiones
    this._setupConversions();
    this._setupEventListeners();
  }

  _setupConversions() {
    const editor = this.editor;

    // Conversión upcast (vista a modelo)
    editor.conversion.for("upcast").elementToElement({
      view: "iframe",
      model: "iframe",
    });

    // Conversión downcast (modelo a vista)
    editor.conversion.for("downcast").elementToElement({
      model: "iframe",
      view: "iframe",
    });

    // Conversión de atributos - usar un array para reducir repetición
    const attributes = ["src", "width", "height", "loading", "allowfullscreen", "frameborder", "title", "class"];

    attributes.forEach(attr => {
      editor.conversion.attributeToAttribute({
        model: attr,
        view: attr,
      });
    });
  }

  _setupEventListeners() {
    const editor = this.editor;
    const config = editor.config.get("fsfbIframeLazy") || {};

    if (!config.enabled) {
      return;
    }

    // Procesar contenido al pegar
    editor.editing.view.document.on("paste", (evt, data) => {
      this._processClipboardData(data);
    });

    // Procesar contenido existente cuando esté listo
    editor.data.on("ready", () => {
      this._processExistingContent();
    });

    // Procesar contenido cuando se establezca nuevo contenido
    editor.data.on("set", () => {
      // Usar requestAnimationFrame para mejor rendimiento
      requestAnimationFrame(() => {
        this._processExistingContent();
      });
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

    // Regex mejorado para capturar iframes
    const iframeRegex = /<iframe([^>]*?)>/gi;

    return html.replace(iframeRegex, (match, attributes) => {
      // Verificar si ya tiene loading attribute
      const hasLoading = /loading\s*=\s*["'][^"']*["']/i.test(attributes);

      if (hasLoading) {
        if (config.forceLazy) {
          // Remover loading existente y agregar lazy
          const cleanAttributes = attributes.replace(/loading\s*=\s*["'][^"']*["']/gi, "");
          return `<iframe${cleanAttributes} loading="lazy">`;
        }
        return match; // Mantener loading existente
      }

      // Agregar loading="lazy" si no existe
      return `<iframe${attributes} loading="lazy">`;
    });
  }

  _processExistingContent() {
    const editor = this.editor;
    const config = this.editor.config.get("fsfbIframeLazy") || {};

    // Evitar procesamiento innecesario
    if (!config.autoAdd) {
      return;
    }

    const currentData = editor.getData();
    const processedData = this._addLazyLoadingToHTML(currentData, config);

    // Solo actualizar si hay cambios
    if (currentData !== processedData) {
      // Usar setData de forma más segura
      editor.setData(processedData, { suppressErrorInCollaboration: true });
    }
  }
}