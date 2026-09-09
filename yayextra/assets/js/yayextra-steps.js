(function ($) {
  "use strict";

  function getPanels($container) {
    return $container.find(".yayextra-step-panel");
  }

  function stripRequiredOnHidden($container) {
    getPanels($container).each(function () {
      const $panel = $(this);
      const isActive = $panel.hasClass("is-active") && !$panel.prop("hidden");
      $panel.find("input, select, textarea").each(function () {
        const $field = $(this);
        if (isActive) {
          if ($field.data("yayeWasRequired")) {
            $field.prop("required", true);
            $field.removeData("yayeWasRequired");
          }
        } else if ($field.prop("required")) {
          $field.data("yayeWasRequired", true);
          $field.prop("required", false);
        }
      });
    });
  }

  function furthestComplete($container) {
    return parseInt($container.attr("data-furthest-complete") || "-1", 10);
  }

  function markCompleteThrough($container, index) {
    $container.attr(
      "data-furthest-complete",
      String(Math.max(furthestComplete($container), index))
    );
  }

  function statusText($container, nextIndex, isReview, last) {
    if (isReview) {
      return $container.attr("data-review-label") || "Review";
    }
    const tpl = $container.attr("data-step-count-tpl") || "Step %1$s of %2$s";
    const text = tpl
      .replace("%1$s", String(nextIndex + 1))
      .replace("%2$s", String(last + 1));
    const name =
      $container.find(".yayextra-steps-status__dot").eq(nextIndex).attr("data-step-name") ||
      "";
    return name ? text + " · " + name : text;
  }

  function updateOutsideStatus($container, nextIndex, isReview) {
    const passed = furthestComplete($container);
    $container.find(".yayextra-steps-status__dot").each(function (i) {
      const isCurrent = !isReview && i === nextIndex;
      const isComplete = isReview || (!isCurrent && i <= passed);
      $(this)
        .toggleClass("is-current", isCurrent)
        .toggleClass("is-complete", isComplete)
        .toggleClass("is-upcoming", !isCurrent && !isComplete);
    });
    const last = Math.max(getPanels($container).length - 1, 0);
    $container.find(".yayextra-steps-status__text").text(statusText($container, nextIndex, isReview, last));
    $container.find(".yayextra-steps-status").toggleClass("is-finished", isReview);
  }

  function setActive($container, index) {
    exitReview($container);
    const $panels = getPanels($container);
    const last = $panels.length - 1;
    const nextIndex = Math.max(0, Math.min(index, last));
    const tpl = $container.attr("data-step-count-tpl") || "Step %1$s of %2$s";

    $panels.each(function (i) {
      const $panel = $(this);
      const isActive = i === nextIndex;
      $panel.toggleClass("is-active", isActive);
      $panel.prop("hidden", !isActive);
    });
    stripRequiredOnHidden($container);

    $container.find(".yayextra-step-dot").each(function (i) {
      $(this).toggleClass("is-active", i <= nextIndex);
    });
    $container.find(".yayextra-steps-count").text(
      tpl.replace("%1$s", String(nextIndex + 1)).replace("%2$s", String(last + 1))
    );
    updateOutsideStatus($container, nextIndex, false);

    $container.find(".yayextra-step-back").prop("disabled", nextIndex === 0);
    $container.find(".yayextra-step-next").prop("hidden", nextIndex === last);
    $container.find(".yayextra-step-done").prop("hidden", nextIndex !== last);
    $container.attr("data-active-step", nextIndex);
  }

  function wrapLabel($wrap) {
    const $name = $wrap.find(".yayextra-option-field-name").first().clone();
    $name.find("span").filter(function () {
      return $(this).text().trim() === "*";
    }).remove();
    return $name.text().replace(/\*$/, "").trim();
  }

  function wrapValue($wrap) {
    const type = $wrap.attr("data-option-field-type") || "";
    if (type === "file_download" || type === "images" || type === "heading" || type === "divider" || type === "spacing" || type === "paragraph" || type === "product_list" || type === "html" || type === "popup" || type === "link") {
      return "";
    }
    if (type === "dropdown") {
      const $sel = $wrap.find("select").first();
      if (!$sel.val()) {
        return "";
      }
      return ($sel.find("option:selected").text() || "").trim();
    }
    if (
      type === "checkbox" ||
      type === "radio" ||
      type === "swatches" ||
      type === "swatches_multi" ||
      type === "button" ||
      type === "button_multi"
    ) {
      const vals = [];
      $wrap.find("input:checked").each(function () {
        const v = ($(this).val() || "").trim();
        if (v) {
          vals.push(v);
        }
      });
      return vals.join(", ");
    }
    if (type === "file_upload" || type === "image_upload") {
      const input = $wrap.find("input[type='file']").get(0);
      if (input && input.files && input.files.length) {
        return Array.prototype.map.call(input.files, function (f) {
          return f.name;
        }).join(", ");
      }
      return ($wrap.find(".yayextra-uploaded-file-name, .file-name").first().text() || "").trim();
    }
    const vals = [];
    $wrap.find("input, textarea, select").each(function () {
      const $el = $(this);
      const t = ($el.attr("type") || "").toLowerCase();
      if (
        t === "hidden" ||
        t === "checkbox" ||
        t === "radio" ||
        t === "file" ||
        t === "button" ||
        t === "submit"
      ) {
        return;
      }
      const v = ($el.val() || "").toString().trim();
      if (v) {
        vals.push(v);
      }
    });
    return vals.join(", ");
  }

  function collectSummary($container) {
    const rows = [];
    $container.find(".yayextra-option-field-wrap").each(function () {
      const $wrap = $(this);
      if ($wrap.css("display") === "none") {
        return;
      }
      const name = wrapLabel($wrap);
      const value = wrapValue($wrap);
      if (!name || !value) {
        return;
      }
      rows.push({ name: name, value: value });
    });
    return rows;
  }

  function renderReview($container, rows) {
    const $review = $container.find(".yayextra-steps-review");
    const emptyLabel = $container.attr("data-review-empty") || "No selections yet";
    $review.empty();
    if (!rows.length) {
      $review.append($("<div>", { class: "yayextra-steps-summary__row", text: emptyLabel }));
    } else {
      rows.forEach(function (row) {
        const $row = $("<div>", { class: "yayextra-steps-summary__row" });
        $row.append($("<span>", { class: "yayextra-steps-summary__name", text: row.name + ":" }));
        $row.append(document.createTextNode(" "));
        $row.append($("<span>", { class: "yayextra-steps-summary__value", text: row.value }));
        $review.append($row);
      });
    }
    $review.prop("hidden", false);
  }

  function enterReview($container) {
    $container.addClass("is-review");
    getPanels($container).each(function () {
      $(this).removeClass("is-active").prop("hidden", true);
    });
    stripRequiredOnHidden($container);
    $container.find(".yayextra-step-dot").addClass("is-active");
    $container.find(".yayextra-steps-count").text($container.attr("data-review-label") || "Review");
    updateOutsideStatus(
      $container,
      parseInt($container.attr("data-active-step") || "0", 10),
      true
    );
    $container.find(".yayextra-step-back").prop("disabled", false);
    $container.find(".yayextra-step-next, .yayextra-step-done").prop("hidden", true);
    renderReview($container, collectSummary($container));
  }

  function exitReview($container) {
    if (!$container.hasClass("is-review")) {
      return;
    }
    $container.removeClass("is-review");
    $container.find(".yayextra-steps-review").prop("hidden", true).empty();
  }

  function validatePanel($panel) {
    const fields = $panel.find("input, select, textarea").get();
    for (let i = 0; i < fields.length; i++) {
      const field = fields[i];
      if (field.disabled) {
        continue;
      }
      if (typeof field.reportValidity === "function" && !field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  }

  function openOverlay($container) {
    $container.addClass("is-open");
    $container.find(".yayextra-steps-overlay").addClass("is-open").attr("aria-hidden", "false");
    $("body").addClass("yayextra-steps-lock");
  }

  function closeOverlay($container) {
    $container.removeClass("is-open");
    $container.find(".yayextra-steps-overlay").removeClass("is-open").attr("aria-hidden", "true");
    if (!$(".yayextra-steps-container.is-open").length) {
      $("body").removeClass("yayextra-steps-lock");
    }
  }

  $(document).ready(function () {
    $(".yayextra-steps-container").each(function () {
      setActive($(this), 0);
    });

    $(document).on("click", ".yayextra-step-next", function (e) {
      e.preventDefault();
      const $container = $(this).closest(".yayextra-steps-container");
      const current = parseInt($container.attr("data-active-step") || "0", 10);
      const $panel = getPanels($container).eq(current);
      if (!validatePanel($panel)) {
        return;
      }
      markCompleteThrough($container, current);
      setActive($container, current + 1);
    });

    $(document).on("click", ".yayextra-step-back", function (e) {
      e.preventDefault();
      const $container = $(this).closest(".yayextra-steps-container");
      if ($container.hasClass("is-review")) {
        const current = parseInt($container.attr("data-active-step") || "0", 10);
        setActive($container, current);
        return;
      }
      const current = parseInt($container.attr("data-active-step") || "0", 10);
      setActive($container, current - 1);
    });

    $(document).on("click", ".yayextra-step-done", function (e) {
      e.preventDefault();
      const $container = $(this).closest(".yayextra-steps-container");
      const current = parseInt($container.attr("data-active-step") || "0", 10);
      const $panel = getPanels($container).eq(current);
      if (!validatePanel($panel)) {
        return;
      }
      markCompleteThrough($container, current);
      enterReview($container);
    });

    $(document).on("click", ".yayextra-steps-open", function (e) {
      e.preventDefault();
      openOverlay($(this).closest(".yayextra-steps-container"));
    });

    $(document).on("click", ".yayextra-steps-close, .yayextra-steps-backdrop", function (e) {
      e.preventDefault();
      closeOverlay($(this).closest(".yayextra-steps-container"));
    });
  });
})(jQuery);
