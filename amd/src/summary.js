/**
 * Javascript containing function of the block summary
 */
define([
    'jquery',
    'jqueryui'
], function ($, ui) {
    var summary = {
        /**
         * Init JS to summary block.
         */
        init: function () {
            // Call collapse event.
            this.collapse();
        },
        /**
         * Init summary collapse event.
         */
        collapse: function () {
            $('.summary-button').on('click', function (event) {
                let button = $(this);
                let section = button.parent();
                let content = section.next('.summary-content');

                let icon = button.find('.summary-chevron');
                let isExpanded = button.attr('aria-expanded') === 'true';

                button.attr('aria-expanded', !isExpanded);
                content.slideToggle('fast');
                icon.toggleClass('fa-chevron-up fa-chevron-down');

                event.stopImmediatePropagation();
                button.focus();
            });
        },
    };

    window.summary = summary;
    return summary;
});