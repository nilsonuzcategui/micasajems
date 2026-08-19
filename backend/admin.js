/* =====================================================
   JEMS Admin — JS con jQuery
   ===================================================== */
(function ($) {
  "use strict";

  // ----- Toasts -----
  function ensureToastStack() {
    var $stack = $(".toast-stack");
    if ($stack.length === 0) {
      $stack = $('<div class="toast-stack" aria-live="polite"></div>').appendTo("body");
    }
    return $stack;
  }

  window.JemsAdmin = {
    toast: function (message, type) {
      type = type || "info";
      var $stack = ensureToastStack();
      var $t = $(
        '<div class="toast toast-' +
          type +
          '">' +
          $("<div>").text(message).html() +
          "</div>"
      );
      $stack.append($t);
      setTimeout(function () {
        $t.fadeOut(200, function () {
          $(this).remove();
        });
      }, 4500);
    },

    success: function (msg) { this.toast(msg, "success"); },
    error:   function (msg) { this.toast(msg, "error"); },
    warning: function (msg) { this.toast(msg, "warning"); },
    info:    function (msg) { this.toast(msg, "info"); },

    // ----- AJAX helpers -----
    api: function (url, method, data) {
      var token = $('meta[name="csrf-token"]').attr("content") || "";
      return $.ajax({
        url: url,
        method: method || "GET",
        data: data,
        dataType: "json",
        headers: token ? { "X-CSRF-Token": token } : {},
      });
    },

    // ----- Delete con confirmación -----
    confirmDelete: function (form) {
      if (!confirm("¿Eliminar esta actividad? Esta acción no se puede deshacer.")) {
        return false;
      }
      var $form = $(form);
      var action = $form.attr("action");
      var data = $form.serialize();
      var $btn = $form.find('button[type="submit"]');
      $btn.prop("disabled", true).text("Eliminando…");

      $.ajax({
        url: action,
        method: "POST",
        data: data,
        dataType: "json",
        headers: { "X-CSRF-Token": $('input[name="csrf_token"]', $form).val() },
      })
        .done(function (resp) {
          if (resp && resp.ok) {
            JemsAdmin.success("Actividad eliminada.");
            // Quitar la fila de la tabla
            $form.closest("tr").fadeOut(200, function () {
              $(this).remove();
            });
          } else {
            JemsAdmin.error((resp && resp.error) || "No se pudo eliminar.");
            $btn.prop("disabled", false).text("Eliminar");
          }
        })
        .fail(function (xhr) {
          var msg = "Error de red";
          try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
          JemsAdmin.error(msg);
          $btn.prop("disabled", false).text("Eliminar");
        });
      return false;
    },

    // ----- Validación client-side del form de actividad -----
    validateActividadForm: function (form) {
      var errors = [];
      var titulo = form.titulo.value.trim();
      var lugar = form.lugar.value.trim();
      var fecha = form.fecha.value.trim();
      var hora_inicio = form.hora_inicio.value.trim();

      if (!titulo) errors.push("El título es obligatorio.");
      if (!lugar) errors.push("El lugar es obligatorio.");
      if (!fecha) errors.push("La fecha es obligatoria.");
      if (!hora_inicio) errors.push("La hora de inicio es obligatoria.");

      if (errors.length) {
        JemsAdmin.error(errors[0]);
        return false;
      }
      return true;
    },
  };

  // Auto-bind: cualquier form con class="js-delete" usa AJAX
  $(function () {
    $("body").on("submit", "form.js-delete", function () {
      return JemsAdmin.confirmDelete(this);
    });

    $("body").on("submit", "form.js-validate-actividad", function () {
      return JemsAdmin.validateActividadForm(this);
    });
  });
})(jQuery);