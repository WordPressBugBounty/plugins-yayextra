(function($) {
  'use strict';
  $(document).ready(function($) {
    // Clean up empty dt/colon next to edit option field link in classic cart
    const yayeOptionEditLinks = $('.yayextra-option-edit-link');
    if (yayeOptionEditLinks.length > 0) {
      $.each(yayeOptionEditLinks, function(_, el) {
        var $el = $(el);
        // Link is in dd: hide the empty dt sibling that only contains ":"
        var $dd = $el.closest('dd');
        if ($dd.length > 0) {
          $dd.prev('dt').hide();
        }
        // Legacy: remove ":" text node after link (old name-based layout)
        var nextSibling = el.nextSibling;
        if (nextSibling && nextSibling.nodeType === 3 && nextSibling.textContent.trim() === ':') {
          nextSibling.remove();
        }
      });
    }
  });
})(jQuery);
