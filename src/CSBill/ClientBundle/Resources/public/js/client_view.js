/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function($, Routing, window) {
    "use strict";

    $(function() {

        /**
         * ADD CONTACT
         */
        $('#add-credit-button').ajaxModal('#credit-ajax-modal', function() {
            var modal = $(this.$modal),
                addCredit = function(evt) {
                    var form = $(this);

                    evt.preventDefault();

                    modal.modal('loading');

                    $.ajax({
                        "url": form.attr('action'),
                        "dataType" : "json",
                        "data" : form.serialize(),
                        "method": "post",
                        "success" : function(data) {
                            if ('success' === data.status) {
                                modal.modal('hide');
                                $('#client-credit-value').text(accounting.formatMoney(data.amount));
                            } else {
                                modal.html(data.content);
                                $('form', modal).on('submit', addCredit);
                            }
                        }
                    });
                };

            $('form', modal).on('submit', addCredit);
        });

        /**
         * ADD CONTACT
         */
        $('.add-contact-button').ajaxModal('#contacts-ajax-modal', function() {

            window.attachContactListeners();

            var modal = $(this.$modal),
                addContact = function(evt) {

                    var form = $(this);

                    evt.preventDefault();

                    modal.modal('loading');

                    $.ajax({
                        "url": form.attr('action'),
                        "dataType" : "json",
                        "data" : form.serialize(),
                        "method": "post",
                        "success" : function(data) {

                            var callback = function(func) {
                                return func.call();
                            };

                            if ('success' === data.status && undefined !== data.id) {
                                var promise = $.getJSON(Routing.generate('_clients_contact_card', {"id" : data.id}), function(data) {

                                    var content = $(data.content).hide();

                                    $('#client-contacts-list').append(content);
                                    content.fadeIn(function() {
                                        $('.edit-contact', this).ajaxModal('#contacts-ajax-modal', contactEdit);
                                    });
                                });

                                callback = promise.done;
                            }

                            modal.modal('loading');

                            callback(function() {
                                modal.html(data.content);
                                $('form', modal).on('submit', addContact);
                            });
                        }
                    });
                };

            $('form', modal).on('submit', addContact);
        });

        /**
         * EDIT CONTACT
         */
        var contactEdit = function() {

            window.attachContactListeners();

            var modal = $(this.$modal),
                container = this,
                contactEditForm = function(evt) {

                    var form = $(this);
                    evt.preventDefault();
                    modal.modal('loading');

                    $.ajax({
                        "url" : form.attr('action'),
                        "dataType" : "json",
                        "data" : form.serialize(),
                        "method" : "post",
                        "success" : function(data) {

                            var contactCard = container.$trigger.parents('.contact-card');

                            var callback = function(func) {
                                return func.call();
                            };

                            if ('success' === data.status) {

                                var promise = $.getJSON(Routing.generate('_clients_contact_card', {"id" : contactCard.data('id')}), function(data) {

                                    var content = $(data.content).hide();
                                    contactCard.replaceWith(content);

                                    content.fadeIn(function(){
                                        $('.edit-contact', this).ajaxModal('#contacts-ajax-modal', contactEdit);
                                    });
                                });

                                callback = promise.done;
                            }

                            modal.modal('loading');

                            callback(function() {
                                modal.html(data.content);
                                $('form', modal).on('submit', contactEditForm);
                            });
                        }
                    });
                };

            $('form', modal).on('submit', contactEditForm);
        };

        $('.edit-contact').ajaxModal('#contacts-ajax-modal', contactEdit);

        /**
         * DELETE CONTACT
         */
        $('body').on('click', '.delete-contact', function(evt) {
            evt.preventDefault();

            var contact = $(this).parents('.contact-card'),
                contactId = contact.data('id');

            window.bootbox.confirm('<i class="fa fa-exclamation-circle fa-2x"></i> Are you sure you want to delete this contact?', function(bool) {
                if (true === bool) {
                    $('body').modalmanager('loading');

                    $.post(Routing.generate("_clients_delete_contact", {"id" : contactId}), function() {
                        $('body').modalmanager('loading');
                        var div = $('<div class="alert alert-success clearfix">Contact removed successfully!</div>');
                        contact.replaceWith(div);
                        setTimeout(function() {
                            div.fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    });
                }
            });
        });

        /**
         * Delete Client
         */
        $('#delete-client').on('click', function(evt) {
            evt.preventDefault();

            var link = $(this);

            window.bootbox.confirm("Are you sure you want to delete this client?", function(bool) {
                if (true === bool) {
                    $('body').modalmanager('loading');
                    $.ajax({
                        "url" : link.attr("href"),
                        "dataType" : "json",
                        "method" : "post"
                    }).done(function() {
                        window.document.location = Routing.generate("_clients_index");
                    });
                }
            });
        });
    });
})(window.jQuery, window.Routing, window);