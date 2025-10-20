/**
 * GridFieldRelationHandler JavaScript
 *
 * Handles radio button and checkbox interactions for GridField relationship management
 * Compatible with SilverStripe 5.4
 */

jQuery(function($) {
    $.entwine('ss', function($) {
        /**
         * Prevent click propagation on non-editable columns
         * This ensures that clicking on relationship controls doesn't trigger row selection
         */
        $('.ss-gridfield .ss-gridfield-item .col-noedit').entwine({
            onclick: function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        });

        /**
         * Handle state management for relationship inputs (radio buttons and checkboxes)
         */
        $('.ss-gridfield .ss-gridfield-item .col-noedit input').entwine({
            /**
             * Get the GridFieldRelationHandler state from the GridField
             *
             * @return {Object} The current state object
             */
            getState: function() {
                return this.getGridField().getState().GridFieldRelationHandler;
            },

            /**
             * Set the GridFieldRelationHandler state on the GridField
             *
             * @param {Object} val The state object to set
             */
            setState: function(val) {
                this.getGridField().setState('GridFieldRelationHandler', val);
            },

            /**
             * Handle change events on radio buttons and checkboxes
             * Updates the state to reflect the selected relationships
             *
             * @param {Event} e The change event
             */
            onchange: function(e) {
                const state = this.getState();
                const input = $(e.target).closest('input');

                if (input.hasClass('radio')) {
                    // For radio buttons, set the single selected value
                    state.RelationVal = input.val();
                } else if (input.hasClass('checkbox')) {
                    // For checkboxes, manage an array of selected values
                    if (Array.isArray(state.RelationVal)) {
                        if (input.is(':checked')) {
                            // Add to selection
                            state.RelationVal.push(input.val());
                        } else {
                            // Remove from selection
                            const index = state.RelationVal.indexOf(input.val());
                            if (index !== -1) {
                                state.RelationVal.splice(index, 1);
                            }
                        }
                    } else if (input.is(':checked')) {
                        // Initialize with first selection
                        state.RelationVal = [input.val()];
                    } else {
                        // Clear selection
                        state.RelationVal = [];
                    }
                }

                this.setState(state);
            }
        });
    });
});
