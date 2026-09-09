(function ($) {
  "use strict";

  function exclusive($root) {
    return $root.attr("data-exclusive") !== "0";
  }

  function isLogicHidden($wrap) {
    return $wrap.css("display") === "none";
  }

  function syncItemVisibility($item) {
    const $wraps = $item.find(".yayextra-option-field-wrap");
    if ($wraps.length === 0) {
      $item.show();
      return true;
    }
    const visible = $wraps.toArray().some(function (el) {
      return !isLogicHidden($(el));
    });
    $item.toggle(visible);
    return visible;
  }

  function openItem($root, $item) {
    if (exclusive($root)) {
      $root.find(".yayextra-accordion__item").each(function () {
        setOpen($(this), $(this).is($item));
      });
      return;
    }
    setOpen($item, true);
  }

  function setOpen($item, isOpen) {
    $item.toggleClass("is-open", isOpen);
    $item.find(".yayextra-accordion__trigger").first().attr("aria-expanded", isOpen ? "true" : "false");
    const $content = $item.find(".yayextra-accordion__content").first();
    $content.toggleClass("is-open", isOpen);
    $content.attr("aria-hidden", isOpen ? "false" : "true");
  }

  function syncOpenState($root) {
    const $visible = $root.find(".yayextra-accordion__item").filter(function () {
      return $(this).css("display") !== "none";
    });
    if ($visible.length === 0) {
      $root.hide();
      return;
    }
    $root.show();
    $root.find(".yayextra-accordion__item").not($visible).each(function () {
      setOpen($(this), false);
    });
    const $open = $visible.filter(".is-open");
    if (exclusive($root) && $open.length > 1) {
      $open.slice(1).each(function () {
        setOpen($(this), false);
      });
    }
  }

  function refresh($root) {
    $root.find(".yayextra-accordion__item").each(function () {
      syncItemVisibility($(this));
    });
    syncOpenState($root);
  }

  function bind() {
    $(document).on("click", ".yayextra-accordion__trigger", function (e) {
      e.preventDefault();
      const $item = $(this).closest(".yayextra-accordion__item");
      const $root = $item.closest(".yayextra-accordion");
      const isOpen = $item.hasClass("is-open");
      if (isOpen) {
        setOpen($item, false);
        return;
      }
      openItem($root, $item);
    });

    document.addEventListener(
      "invalid",
      function (event) {
        const el = event.target;
        if (!(el instanceof Element)) return;
        const item = el.closest(".yayextra-accordion__item");
        const root = el.closest(".yayextra-accordion");
        if (!item || !root) return;
        if (window.getComputedStyle(item).display === "none") return;
        openItem($(root), $(item));
      },
      true
    );

    $(".yayextra-accordion").each(function () {
      refresh($(this));
    });

    $(document).on("change input", "form.cart", function () {
      $(".yayextra-accordion").each(function () {
        refresh($(this));
      });
    });
  }

  $(bind);
})(jQuery);
