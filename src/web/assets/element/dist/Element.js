(function ($) {

    Craft.OrganizationUserIndex = Craft.BaseElementIndex.extend({

        init: function (elementType, $container, settings) {

            // Add our new settings
            this.setSettings(settings, Craft.OrganizationUserIndex.defaults);

            // Do base init
            this.base(elementType, $container, settings);

            // Remove fixed toolbar...it looks like tabs don't like element lists
            this.removeListener(Garnish.$win, 'resize,scroll');
            this.$toolbar.removeClass('fixed');
            this.$toolbar.css('width', '');
            this.$elements.css('padding-top', '');
            this.$toolbar.css('top', '0');

        },

        /**
         * Returns the data that should be passed to the elementIndex/getElements controller action
         * when loading elements.
         */
        getViewParams: function () {

            // Base params
            var params = this.base();

            // Add the organization
            params.organization = this.settings.sourceElementId;

            return params;

        },

        afterAction: function (action, params) {

            // There may be a new background task that needs to be run
            Craft.cp.runPendingTasks();

            this.onAfterAction(action, params);

        },

        onAfterAction: function (action, params) {
            this.settings.onAfterAction(action, params);
            this.trigger('afterAction', {action: action, params: params});
        },

        // Todo - remove this if/when the POST action can be defined via a setting
        updateElements: function () {
            // Ignore if we're not fully initialized yet
            if (!this.initialized) {
                return;
            }

            this.setIndexBusy();

            var params = this.getViewParams();

            Craft.postActionRequest('organization/user-indexes/get-elements', params, $.proxy(function (response, textStatus) {
                this.setIndexAvailable();

                if (textStatus == 'success') {
                    this._updateView(params, response);
                }
                else {
                    Craft.cp.displayError(Craft.t('app', 'An unknown error occurred.'));
                }

            }, this));
        },

        // Todo - remove this if/when the POST action can be defined via a setting
        submitAction: function (actionClass, actionParams) {
            // Make sure something's selected
            var selectedElementIds = this.view.getSelectedElementIds(),
                totalSelected = selectedElementIds.length,
                totalItems = this.view.getEnabledElements.length;

            if (totalSelected == 0) {
                return;
            }

            // Find the action
            var action;

            for (var i = 0; i < this.actions.length; i++) {
                if (this.actions[i].type == actionClass) {
                    action = this.actions[i];
                    break;
                }
            }

            if (!action || (action.confirm && !confirm(action.confirm))) {
                return;
            }

            // Get ready to submit
            var viewParams = this.getViewParams();

            var params = $.extend(viewParams, actionParams, {
                elementAction: actionClass,
                elementIds: selectedElementIds
            });

            // Do it
            this.setIndexBusy();
            this._autoSelectElements = selectedElementIds;

            Craft.postActionRequest('organization/user-indexes/perform-action', params, $.proxy(function (response, textStatus) {
                this.setIndexAvailable();

                if (textStatus == 'success') {
                    if (response.success) {
                        this._updateView(viewParams, response);

                        if (response.message) {
                            Craft.cp.displayNotice(response.message);
                        }

                        // TODO - suggest to craft to implement
                        this.afterAction(action, params);

                    }
                    else {
                        Craft.cp.displayError(response.message);
                    }
                }
            }, this));
        },

    }, {
        defaults: {
            onAfterAction: $.noop,
            sourceElementId: null
        }
    });

    // Register it!
    Craft.registerElementIndexClass('craft\\elements\\User', Craft.OrganizationUserIndex);

    /**
     * Element Select input
     */
    Craft.OrganizationUserSelectInput = Craft.BaseElementSelectInput.extend({

        init: function (settings) {

            // Add our new settings
            this.setSettings(settings, Craft.OrganizationUserSelectInput.defaults);

            // Do base init
            this.base(settings);

            // Set elements
            // this.$elements = settings.elements;
            this.$elements = [];

            // Don't position the input button
            this.$addElementBtn
                .css('position', '')
                .css('top', '')
                .css(Craft.left, '');

        },

        // Set this to an array of Ids (vs DOM objects)
        // $elements: [],

        // Reset elements
        resetElements: function () {
            this.$elements = []; // this is the only difference
            this.addElements(this.getElements());
        },

        // Get the Id of the element
        _getIdFromElement: function ($element) {

            // Set id
            var id = $element;

            // Check if we're dealing w/ an object
            if (typeof $element == 'object') {

                // Get id from element
                id = $($element).data('id');

            }

            return id;

        },

        getElements: function () {
            return this.$elementsContainer.children('.element');
        },

        // Add elements (we literally make an AJAX call to add them)
        addElements: function ($elements) {

            // Force array
            $elements = $.makeArray($elements);

            // Iterate over array
            for (var i = 0; i < $elements.length; i++) {

                // Get id
                var id = this._getIdFromElement($elements[i]);

                // Add ID to our array of Id's
                this.$elements.push(id);

            }

        },

        // Remove elements
        removeElements: function ($elements) {

            // Force array
            $elements = $.makeArray($elements);

            // Empty array of ids to remove
            var ids = [];

            // Iterate over array
            for (var i = 0; i < $elements.length; i++) {

                var id = $elements[i];

                if (!isNaN(id)) {
                    id = parseInt(this._getIdFromElement(id));
                }

                // Remove id from array
                Craft.removeFromArray(id, this.$elements);

                // push to array for modal
                ids.push(id);

            }

            // Allow the selection of these users (again)
            if (this.modal) {

                // Enable users
                this.modal.elementIndex.enableElementsById(ids);

            }

            // Hook
            this.onRemoveElements();

        },

        // Remove a single element
        removeElement: function ($element) {
            this.removeElements($element);
        },

        // Get an array of element ids (vs DOM Objects)
        getSelectedElementIds: function () {
            return this.$elements;
        },

        // Add
        selectElements: function (elements) {

            // Iterate over each selected element (to add/save)
            for (var i = 0; i < elements.length; i++) {

                // Set element variables
                var element = elements[i],
                    $element = this.createNewElement(element),
                    elementInfo = Craft.getElementInfo($element);

                // Add element
                this.addElements($element);

                // Payload data
                var data = {
                    user: elementInfo.id,
                    organization: this.settings.sourceElementId
                };

                // Make AJAX Call
                Craft.postActionRequest(this.settings.addAction, data, $.proxy(function (response, textStatus) {

                    // Check response status
                    if (textStatus == 'success') {

                        // Update element index
                        Craft.elementIndex.updateElements();

                    } else {

                        // Remove element
                        this.removeElements($element);

                        // Update modal disable status
                        this.updateDisabledElementsInModal();

                    }

                }, this));

            }

        },

        // Get the Ids that are disabled (for the modal)
        getDisabledElementIds: function () {

            var ids = this.base();

            // Add ids from settings
            if (this.settings.disabledElementIds) {
                ids = ids.concat(this.settings.disabledElementIds);
            }

            return ids;

        }
    }, {

        defaults: {
            addAction: '',
            disabledElementIds: []
        }
    });

    /**
     * Owner Select input
     */
    Craft.OrganizationOwnerSelectInput = Craft.BaseElementSelectInput.extend({

        init: function (settings) {

            // Add our new settings
            this.setSettings(settings, Craft.OrganizationUserSelectInput.defaults);

            // Do base init
            this.base(settings);

        },

        // Get the Ids that are disabled (for the modal)
        getDisabledElementIds: function () {

            var ids = this.base();

            // Add ids from settings
            if (this.settings.disabledElementIds) {
                ids = ids.concat(this.settings.disabledElementIds);
            }

            return ids;

        }

    }, {

        defaults: {
            addAction: '',
            disabledElementIds: []
        }
    });

    Craft.OrganizationTypeSwitcher = Garnish.Base.extend(
        {
            $container: null,

            $activeButton: null,
            activeBtn: null,

            $availableButton: null,
            availableBtn: null,


            $spinner: null,
            $fields: null,

            init: function () {
                this.$container = $('#types');

                this.$activeButton = this.$container.find('#active');
                this.$availableButton = this.$container.find('#available');

                this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$availableButton);

                this.$fields = $('#fields');

                this.activeBtn = this.$activeButton.menubtn().data('menubtn');
                this.initActiveBtnListener();

                this.availableBtn = this.$availableButton.menubtn().data('menubtn');
                this.initAvailableBtnListener();

            },

            initActiveBtn: function () {

                // New (it will double init and transfer the list container)
                this.activeBtn = new Garnish.MenuBtn(this.$activeButton);

                // Show/Hide divider
                var $hr = this.activeBtn.menu.$menuList.find('hr');
                if ($hr.length) {

                    if (this.activeBtn.menu.$menuList.children('li').length == 1) {

                        $hr.hide();

                    } else {

                        $hr.show();
                    }

                }

                // Listener
                this.initActiveBtnListener();

            },

            initActiveBtnListener: function () {
                this.activeBtn.on('optionSelect', $.proxy(this, '_handleTypeChange'));
            },

            initAvailableBtnListener: function () {
                this.availableBtn.on('optionSelect', $.proxy(this, '_handleAvailableSelect'));
            },

            initAvailableBtn: function () {

                // New (it will double init and transfer the list container)
                this.availableBtn = new Garnish.MenuBtn(this.$availableButton);

                // Listener
                this.initAvailableBtnListener();

            },

            _handleAvailableSelect: function (ev) {

                var $option = $(ev.option);

                if ($option.hasClass('disabled')) {
                    return;
                }

                if ($option.children('.status').hasClass('active')) {
                    this.dissociateType($option.data('type'), $option.data('organization'));
                } else {
                    this.associateType($option.data('type'), $option.data('organization'));
                }

            },

            _handleTypeChange: function (ev) {

                var $option = $(ev.option);

                if ($option.hasClass('disabled')) {
                    return;
                }

                this.changeType($option);

            },

            changeType: function ($option) {

                // Get menu
                var $menu = $option.parents('ul');

                // Remove active state
                $menu.find('.sel').removeClass('sel');

                // Set active state
                $option.addClass('sel');

                var value = $option.attr('data-id');
                var label = $option.html();

                // Change header/hidden input
                this.$activeButton.html('<input type="hidden" name="type" value="' + value + '">' + label);

                this.initActiveBtn();

                // Switch type
                this.switchType();

            },

            getActiveTypeId: function () {

                var $input = this.$activeButton.find('input[name="type"]');

                if (!$input.length) {
                    return;
                }

                return $input.val();

            },

            ensureAvailableSelected: function () {

                // Get option from menu list
                var $active = this.getActiveOption(this.getActiveTypeId());

                if ($active) {
                    return;
                }

                $active = this.getNextActiveOption();

                if (!$active) {
                    return;
                }

                this.changeType($active);

            },

            getAvailableOption: function (type) {

                // Get selected option
                var $option = this.availableBtn.menu.$options.filter('[data-type="' + type + '"]');

                if (!$option.length) {
                    return;
                }

                return $option;

            },

            getActiveOption: function (id) {

                // Get option from active list
                var $active = this.activeBtn.menu.$options.filter('[data-id="' + id + '"]');

                if (!$active.length) {
                    return;
                }

                return $active;

            },

            getNextActiveOption: function (id) {

                // Get option from active list
                var $active = this.activeBtn.menu.$options.first();

                if (!$active.length) {
                    return;
                }

                return $active;

            },

            associateType: function (type, organization) {

                var data = {
                    type: type,
                    organization: organization
                };

                // Get selected option
                var $option = this.getAvailableOption(type);

                if (!$option) {
                    return;
                }

                // Update status
                $option.children('.status').addClass('active');

                var value = $option.attr('data-type');
                var label = $("<div/>").html($option.html()).text();

                // Append to menu lust
                this.activeBtn.menu.$menuList.append(
                    $('<li><a data-id="' + value + '">' + label + '</a></li>')
                );

                // Add hidden input
                if (value) {
                    this.$availableButton.append(
                        $('<input />').attr("name", "types[]")
                            .attr("type", "hidden")
                            .val(value)
                    );
                }

                // Init new btn
                this.initActiveBtn();

            },

            dissociateType: function (type, organization) {

                var data = {
                    type: type,
                    organization: organization
                };

                // Get selected option
                var $option = this.getAvailableOption(type);

                if (!$option) {
                    return;
                }

                // Remove all active states (from other options)
                $option.children('.status').removeClass('active');

                var value = $option.attr('data-type');

                // Get option from active list
                var $active = this.getActiveOption(value);

                if (!$active) {
                    return;
                }

                // Remove option
                $active.parent('li').remove();

                // Remove hidden input
                var $input = this.$availableButton.find('input[name="types[]"][value="' + value + '"]');

                if ($input.length) {
                    $input.remove();
                }

                // Init new btn
                this.initActiveBtn();

                // Make sure an option is selected (it may have just been removed)
                this.ensureAvailableSelected();

            },

            switchType: function () {
                this.$spinner.removeClass('hidden');

                Craft.postActionRequest('organization/view/organization/switch-type', Craft.cp.$container.serialize(), $.proxy(function (response, textStatus) {
                    this.$spinner.addClass('hidden');

                    if (textStatus == 'success') {

                        // Get the field pane
                        var fieldsPane = this.$fields.data('pane');

                        // Remove tabs
                        fieldsPane.deselectTab();

                        // Copy the user tab + pane
                        var $usersTabLink = this.$fields.find('#tabs ul li a[href="#tab-users"]');
                        var $usersTab = $usersTabLink.parent('li').clone();
                        var $usersContent = this.$fields.find($usersTabLink.attr('href')).clone();

                        // Set new field content
                        this.$fields.html(response.paneHtml);

                        // Destroy the pane
                        fieldsPane.destroy();

                        // Append the users content and tab
                        this.$fields.append($usersContent)
                            .find('#tabs ul').append($usersTab);

                        // New pane
                        this.$fields.pane();

                        // Init UI
                        Craft.initUiElements(this.$fields);

                        Craft.appendHeadHtml(response.headHtml);
                        Craft.appendFootHtml(response.footHtml);

                        // Update the slug generator with the new title input
                        if (typeof slugGenerator !== 'undefined') {
                            slugGenerator.setNewSource('#title');
                        }
                    }
                }, this));
            }

        });

})(jQuery);
