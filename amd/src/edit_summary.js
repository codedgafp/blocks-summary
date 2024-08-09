/**
 * Javascript containing function of the edit summary
 */

define([
    'jquery',
    'jqueryui',
    'core/templates'
], function ($, ui, templates) {
    var edit_summary = {
        /**
         * Init edit summary page.
         *
         * @param {Object} params
         */
        init: function (params) {
            // Get param.
            this.params = params;

            // Check if page is lock.
            if (this.params.lock) {
                this.popup_lock();
                return;
            }

            // Set data.
            this.set_sections_list(); // Set sections list with data.
            this.removeSections = []; // Remove sections list.
            this.widthDepth = 30; // Depth size by level.
            this.lockIntervalCheck = 30; // Check lock interval by second.
            this.lastPosition = 0; // Last position for the element being sorted.
            this.itemWithChilds = []; // List section with child and their data.

            // Call item action.
            $('.edit-list-main').on('click', '.edit-item button', function (event) {
                var action = event.currentTarget.dataset.action;

                if (action === undefined) {
                    return;
                }

                var element = event.currentTarget.parentElement;

                edit_summary.call_action(action, element);
            });

            // Add new section.
            $('.edit-form > .add-section').on('click', function (event) {
                event.preventDefault();
                edit_summary.add_section();
            });

            // Save summary.
            $('.edit-form > #save').on('click', function (event) {
                event.preventDefault();
                edit_summary.disable_all_edit_title();
                edit_summary.confirm();
            });

            // Set sortable for sections list.
            $('.edit-list').sortable({
                items: '> li.edit-item',
                // Just "move" class button to drag and drop.
                handle: '.move',
                // Necessary for "handle" work.
                cancel: '',
                placeholder: "placeholder",
                start: function (event, ui) {
                    var element = $(ui.item[0]);
                    var depth = element[0].dataset.depth;

                    if (depth > 0) {
                        return;
                    }

                    // Set list section with child and their data.
                    edit_summary.set_item_with_childs();

                    // Check if section has child
                    edit_summary.hasChild = edit_summary.itemWithChilds.some(function (e) {
                        return $(e.item).is(element);
                    });

                    if (edit_summary.hasChild) {
                        // Link child with section.
                        edit_summary.link_child();
                    }
                },
                sort: function (event, ui) {
                    // Set section data.
                    var placeHolder = ui.placeholder[0];
                    var afterElement = $(placeHolder).next();
                    var samePosition = $($(placeHolder).prev()).is($(ui.item));
                    var item = $(ui.item);

                    if (edit_summary.hasChild ||
                        $(placeHolder).is(':first-child') ||
                        (samePosition && item.is(':first-child'))
                    ) {
                        // Set depth to 0 level.
                        edit_summary.lastPosition = 0;
                        edit_summary.set_depth_element(placeHolder, 0);
                        return;
                    }

                    if (afterElement.data('depth') === 1) {
                        if (samePosition && item.data('depth') === 0) {
                            // Set depth to 0 level.
                            edit_summary.lastPosition = 0;
                            edit_summary.set_depth_element(placeHolder, 0);
                            return;
                        }

                        // Set depth to 1 level.
                        edit_summary.lastPosition = 1;
                        edit_summary.set_depth_element(placeHolder, 1);
                        return;
                    }

                    if (ui.offset.left > 50) {
                        // Set depth to 1 level.
                        edit_summary.lastPosition = 1;
                        edit_summary.set_depth_element(placeHolder, 1);
                        return;
                    }

                    // Set depth to 0 level.
                    edit_summary.lastPosition = 0;
                    edit_summary.set_depth_element(placeHolder, 0);
                },
                stop: function (event, ui) {
                    var item = ui.item[0];

                    // Set depth to defined level in "sort" event function.
                    edit_summary.set_depth_element(item, edit_summary.lastPosition);

                    if (edit_summary.hasChild) {
                        // Unlink child to section.
                        edit_summary.unlink_child();
                    }

                    // Init data.
                    edit_summary.lastPosition = 0;
                    edit_summary.hasChild = false;
                    edit_summary.set_sections_list();
                },
            });

            this.unlock();
        },
        /**
         * Set element depth.
         *
         * @param {jquery} element
         * @param {number} depth
         */
        set_depth_element: function (element, depth) {
            $(element)
                .css({
                    '--item-depth': (depth * edit_summary.widthDepth) + 'px'
                });
            element.dataset.depth = depth;
        },
        /**
         * Call item action (Drag / Edit / Hide / Remove).
         *
         * @param {string} action
         * @param {jquery} element
         */
        call_action: function (action, element) {
            this[action](element);
        },
        /**
         * Add new section to course and summary.
         */
        add_section: function () {
            // Create new section data to template item.
            var itemData = {
                name: M.util.get_string('newsectionname', 'block_summary', this.params.lastnumbersection),
                hide: 1,
                id: -1
            };

            // Create new section data to template and add at the end sumary.
            templates.renderForPromise('block_summary/edit-item', itemData)
                .then(function (_ref) {
                    var html = _ref.html;
                    $('.edit-list-main').append(html);
                    edit_summary.params.lastnumbersection++;
                    edit_summary.set_sections_list();
                });
        },
        /**
         * Set section list data.
         * Send data when save summary.
         */
        set_sections_list: function () {
            this.sectionList = $('.edit-list > .edit-item').map(function () {
                return {
                    id: this.dataset.id,
                    name: $(this).find('.title')[0].innerText,
                    visible: $(this).children('div').hasClass('hide') ? 0 : 1,
                    depth: this.dataset.depth
                };
            }).toArray();

            // User has made an action.
            this.lockAction = true;
        },
        /**
         * Generate dialog to confirm save data with removed section.
         */
        confirm: function () {
            // No removed section, no dialog
            if (edit_summary.removeSections.length === 0) {
                edit_summary.send();
                return;
            }

            var data = {
                sectionsname: edit_summary.removeSections
            };

            // Generate dialog.
            templates.renderForPromise('block_summary/confirm-popin', data)
                .then(function (_ref) {
                    var html = _ref.html;
                    $(html).dialog({
                        title: M.util.get_string('modifiedsummary', 'block_summary'),
                        resizable: false,
                        height: "auto",
                        width: 600,
                        modal: true,
                        buttons: [
                            {
                                text: M.util.get_string('valid', 'block_summary'),
                                class: "btn-primary",
                                click: function () {
                                    // Send data.
                                    edit_summary.send();
                                    $(this).dialog("close");
                                }
                            },
                            {
                                text: M.util.get_string('cancel', 'moodle'),
                                class: "btn-secondary",
                                click: function () {
                                    // No send data.
                                    $(this).dialog("close");
                                }
                            }
                        ]
                    });
                });
        },
        /**
         * Save summary and send data.
         */
        send: function () {
            // Check if user has summary lock.
            edit_summary.check_lock(function (result) {
                if (result) {
                    $.ajax({
                        method: 'POST',
                        url: M.cfg.wwwroot + '/blocks/summary/ajax/ajax.php',
                        data: {
                            controller: 'summary',
                            action: 'update_summary',
                            format: 'json',
                            PROFILEME: 1,
                            courseid: edit_summary.params.courseid,
                            sectionslist: JSON.stringify(edit_summary.sectionList)
                        },
                        error: function () {
                            console.log('error');
                        }
                    }).done(function () {
                        // Refresh page.
                        $(window).off('beforeunload');
                        window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + edit_summary.params.courseid;
                    });
                    return;
                }

                // Other user has summary lock.
                edit_summary.lock();
                clearInterval(edit_summary.intervalLockCheck);
            });
        },
        /**
         * Edit section name.
         *
         * @param element
         */
        edit: function (element) {
            // Set section data.
            var elementItem = $(element);
            var elementEdit = elementItem.find('.edit');
            var elementTitle = elementItem.find('.title');

            // Already in editing mode
            if (elementItem.hasClass('edit-on')) {
                // Save new title name.
                var editInputValue = elementItem.find('.edit-input').val();
                elementItem.removeClass('edit-on');
                elementEdit.removeClass('fa-check');
                elementEdit.addClass('fa-pencil');
                elementTitle.html(editInputValue);
                edit_summary.set_sections_list();
                return;
            }

            // Activates title edit mode.
            var title = elementTitle.text();
            var newEditInput = $('<input class="edit-input" value="' + title + '" type="text">');

            edit_summary.disable_all_edit_title();
            elementItem.addClass('edit-on');
            elementEdit.removeClass('fa-pencil');
            elementEdit.addClass('fa-check');
            elementTitle.html(newEditInput);
            newEditInput.focus();
        },
        /**
         * Disable all input edit in title element.
         */
        disable_all_edit_title: function () {
            $('.edit-list > .edit-item.edit-on')
                .find('.edit').click();
        },
        /**
         * Update hide class to element.
         *
         * @param element
         */
        hide: function (element) {
            if ($(element).hasClass('hide')) {
                $(element).removeClass('hide');
                $(element).find('button.hide').removeClass('fa-eye-slash');
                $(element).find('button.hide').addClass('fa-eye');
            } else {
                $(element).addClass('hide');
                $(element).find('button.hide').removeClass('fa-eye');
                $(element).find('button.hide').addClass('fa-eye-slash');
            }

            edit_summary.set_sections_list();
        },
        /**
         * Delete element to summary.
         *
         * @param element
         */
        delete: function (element) {
            $('<div id="dialog-delete" class="action-message"><p>' +
                M.util.get_string('deletedialogcontent', 'block_summary') +
                '</p></div>').dialog({
                title: M.util.get_string('deletedialogtitle', 'block_summary'),
                resizable: false,
                height: "auto",
                width: 600,
                modal: true,
                buttons: [
                    {
                        text: M.util.get_string('deletedialogbutton', 'block_summary'),
                        class: "btn-primary",
                        click: function () {
                            edit_summary.removeSections.push($(element).find('.title').text());
                            $(element).parent().remove();
                            edit_summary.set_sections_list();
                            $(this).dialog("close");
                        }
                    },
                    {
                        text: M.util.get_string('deletedialogbuttoncancel', 'block_summary'),
                        class: "btn-secondary",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        },
        /**
         * Lock page user.
         * Not edit possibility.
         */
        lock: function () {
            this.popup_lock();
            $('.add-section').prop('disabled', true);
            $('#save').prop('disabled', true);
            $('.edit-list-main').addClass('lock');
            $('.edit-list').sortable('destroy');
        },
        /**
         * Unlock user.
         * Edit possibility.
         */
        unlock: function () {
            $('.add-section').prop('disabled', false);
            $('#save').prop('disabled', false);
            $('.edit-list-main').removeClass('lock');
            this.manage_lock();
        },
        /**
         * Create lock popup information.
         */
        popup_lock: function () {
            $('<div id="dialog-warning" class="impossible-edit"><p>' +
                M.util.get_string('impossibleeditcontent', 'block_summary') +
                '</p></div>').dialog({
                title: M.util.get_string('impossibleedittitle', 'block_summary'),
                resizable: false,
                height: "auto",
                width: 600,
                modal: true,
                close: function () {
                    $(this).dialog("destroy");
                    window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + edit_summary.params.courseid;
                },
                buttons: [
                    {
                        text: M.util.get_string('closebuttontitle', 'moodle'),
                        class: "btn-primary",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        },
        /**
         * Manage lock event.
         * Send ping when say user is active.
         * Remove lock when user quit page.
         */
        manage_lock: function () {
            this.lockAction = false;

            // Send active ping.
            this.intervalLockCheck = setInterval(function () {
                // No activity.
                if (!edit_summary.lockAction) {
                    return;
                }

                // Check summary lock.
                edit_summary.check_lock(function (result) {
                    if (result) {
                        // User still has lock.
                        edit_summary.lockAction = false;
                        return;
                    }

                    // User loose lock.
                    clearInterval(edit_summary.intervalLockCheck);
                });
            }, edit_summary.lockIntervalCheck * 1000);

            // Remove user lock when to close page.
            window.onunload = function () {
                edit_summary.remove_lock();
            };

            // Remove user lock when to change page.
            $(window).on('beforeunload', function () {
                var urlRedirect = document.activeElement.href;
                var url = window.location.href;

                if (urlRedirect !== undefined && urlRedirect !== url) {
                    edit_summary.remove_lock();
                }
            });
        },
        /**
         * Check summary lock.
         * Use callback function to manage result.
         *
         * @param {function} callback
         */
        check_lock: function (callback) {
            $.ajax({
                method: 'POST',
                url: M.cfg.wwwroot + '/blocks/summary/ajax/ajax.php',
                data: {
                    controller: 'summary',
                    action: 'check_lock',
                    format: 'json',
                    PROFILEME: 1,
                    courseid: this.params.courseid,
                },
                error: function () {
                    console.log('error');
                }
            }).done(function (response) {
                response = JSON.parse(response);
                var result = response.message;
                callback(result);
            });
        },
        /**
         * Remove summary lock.
         */
        remove_lock: function () {
            $.ajax({
                method: 'POST',
                url: M.cfg.wwwroot + '/blocks/summary/ajax/ajax.php',
                data: {
                    controller: 'summary',
                    action: 'delete_lock',
                    format: 'json',
                    PROFILEME: 1,
                    courseid: this.params.courseid,
                },
                error: function () {
                    console.log('error');
                }
            }).done(function (response) {
                console.log(response);
            });
        },
        /**
         * Set list section with child and their data.
         */
        set_item_with_childs: function () {
            edit_summary.itemWithChilds = [];

            $('.edit-list > li[data-depth="0"]').each(function (indexn, e) {
                var depth = e.dataset.depth;
                var childs = edit_summary.try_childs($(e), depth);
                var childsNumber = childs.length;

                if (childsNumber === 0) {
                    return;
                }

                edit_summary.itemWithChilds.push({
                    item: e,
                    childs: childs,
                    childsNumber: childsNumber
                });
            });
        },
        /**
         * Check if section has child and list child section.
         *
         * @param element
         * @param depthMax
         * @returns {*[]}
         */
        try_childs: function (element, depthMax) {
            var childs = [];

            (function listNext(e, c) {
                var next = $(e).next();

                // Pass placeholder element.
                if (next.hasClass('ui-sortable-placeholder')) {
                    listNext(next, c);
                    return;
                }

                // Is not child.
                if (next.length === 0) {
                    return;
                }

                // Is not child.
                if (next[0].dataset.depth < depthMax + 1) {
                    return;
                }

                // Add child to list and move on.
                c.push(next);
                listNext(next, c);
            })(element, childs);

            return childs;
        },
        /**
         * Link child to sections
         */
        link_child: function () {
            edit_summary.itemWithChilds.forEach(function (e) {
                var itemChildHolder = $(e.item).find('.child-holder');
                itemChildHolder.append(e.childs);
            });
        },
        // Unlink child to sections
        unlink_child: function () {
            edit_summary.itemWithChilds.forEach(function (e) {
                $(e.item).after(e.childs);
            });
            edit_summary.itemWithChilds = [];
        }
    };

    //add object to window to be called outside require
    window.edit_summary = edit_summary;
    return edit_summary;
});