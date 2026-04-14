(function () {
  "use strict";

  function initializeCodeMirror(id, mode) {
    const textarea = document.getElementById(id);
    if (!textarea) {
      return null;
    }

    const editor = CodeMirror.fromTextArea(textarea, {
      mode: mode,
      theme: "default",
      lineNumbers: true,
      lineWrapping: true,
      readOnly: false
    });

    const wrapper = editor.getWrapperElement();
    if (wrapper) {
      wrapper.style.width = "100%";
      wrapper.style.border = "1px solid #dfe4ec";
      wrapper.style.borderRadius = "12px";
      wrapper.style.overflow = "hidden";
    }

    const isMobile = window.matchMedia("(max-width: 768px)").matches;
    editor.setSize("100%", isMobile ? 220 : 260);

    return editor;
  }

  document.addEventListener("DOMContentLoaded", function () {
    const editors = [];
    const customCssEditor = initializeCodeMirror("custom-css", "css");
    const customNightCssEditor = initializeCodeMirror("custom-night-css", "css");
    const customJsEditor = initializeCodeMirror("custom-js", "javascript");
    const customFooterJsEditor = initializeCodeMirror("customfooter-js", "javascript");

    [customCssEditor, customNightCssEditor, customJsEditor, customFooterJsEditor].forEach(function (editor) {
      if (editor) {
        editors.push(editor);
      }
    });

    const customCodesForm = document.getElementById("customCodes");
    if (customCodesForm) {
      customCodesForm.addEventListener("submit", function () {
        editors.forEach(function (editor) {
          editor.save();
        });
      });
    }
  });
})();
