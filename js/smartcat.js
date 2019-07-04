function printError(message) {
    notice(message, 'error');
}

function printSuccess(message) {
    notice(message, 'success');
}

function notice( message, nclass = 'success') {
    jQuery(function ($) {
        var id = revisedRandId();
        $('.notice.notice-' + nclass + '.is-dismissible.shake-shake-baby').remove();
        var $div = $(document.createElement('div'));
        $div.attr('class', 'notice notice-' + nclass + ' is-dismissible shake-shake-baby');
        $div.attr('id', id);
        var $p = $(document.createElement('p'));
        $p.html(message);
        $div.append($p);
        $div.append(
            '<button type="button" class="notice-dismiss" onclick="dismissById(\'' + id + '\')">'
            + '<span class="screen-reader-text">' + SmartcatFrontend.dismissNotice + '</span></button>'
        );
        $('hr.wp-header-end').first().after($div);
        $("html, body").animate({ scrollTop: 0 }, "slow");
    });
}

function revisedRandId() {
    return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 15);
}

function dismissById( $id ) {
    jQuery(function ($) {
        $('#' + $id).hide('slow', function(){ $('#' + $id).remove(); });
    });
}

jQuery(function ($) {
    function cl(message) {
        console.log(message);
    }

    function add_action_to_serialized_data(data, action) {
         return add_key_to_serialized_data(data, 'action', action);
    }

    function add_key_to_serialized_data(data, key, value) {
        if (data.length > 0) {
            return data + '&' + key + '=' + value;
        } else {
            return key + '=' + value;
        }
    }

    /*
     * Модальное окно и работа с ним
     */

    var addedToModal = 0;

    function prepareInfo(postNumber) {
        var $inlineElement = $('#inline_' + postNumber);
        var title = $inlineElement.find(".post_title").first().html();

        var $translation_connectors_column = $('#translation-connectors-' + postNumber);

        var author = $translation_connectors_column.attr('data-author');
        var status = $inlineElement.find('._status').first().text();

        var translation_slugs = $translation_connectors_column.attr('data-translation-slugs');
        var pll_slugs = $translation_connectors_column.attr('data-post-pll-slugs');
        var isPostHaveAllTranslates = (translation_slugs === pll_slugs);

        var $tr;
        if (!isPostHaveAllTranslates) {
            $tr = $(document.createElement('tr'));
            $tr.html('<td>' + title + '</td><td>' + author + '</td><td>' + status + '</td>');
            addedToModal++;
        } else {
            $tr = '';
        }

        return $tr;
    }

    function add_post_to_hidden(postNumber) {
        var $mwPosts = $('#smartcat-modal-window-posts');
        var val = $mwPosts.val().toString();
        var posts = (
            val === ''
       ) ? [] : val.split(',');

        posts.push(postNumber);
        $mwPosts.val(posts.join(','));
    }

    function modalWindowHandler(event) {
        addedToModal = 0;
        var $info = $("#smartcat-modal-window");
        var $mwPosts = $('#smartcat-modal-window-posts');
        $mwPosts.val('');

        $info.dialog({
            title: SmartcatFrontend.dialogTitle,
            dialogClass: 'wp-dialog',
            height: "auto",
            width: 700,
            modal: true,
            autoOpen: false,
            closeOnEscape: true
        });

        var $tbody = $info.find('table tbody').first();
        $tbody.html('');
        //var $theList = $('#the-list');

        var isChecked = false;

        if (event.target.tagName === 'A') {
            var $a = $(event.target);
            console.log($a.closest('tr'));
            var id = $a.closest('tr').get(0).id;
            var $regOutput = id.match(/post-(\d+)/i);
            var postNumber = $regOutput[1];

            cl(postNumber);

            add_post_to_hidden(postNumber);

            var $tr = prepareInfo(postNumber);
            $tbody.append($tr);

            $info.dialog('open');
        } else {
            $('tbody th.check-column input[type="checkbox"]').each(function () {
                var $this = $(this);

                if ($this.prop("checked")) {
                    isChecked = true;
                    var postNumber = $(this).val();
                    add_post_to_hidden(postNumber);

                    var $tr = prepareInfo(postNumber);
                    $tbody.append($tr);
                }
            });

            if (isChecked) {
                if (addedToModal) {
                    $info.dialog('open');
                } else {
                    printError(SmartcatFrontend.postsAreAlreadyTranslated);
                }
            } else {
                printError(SmartcatFrontend.postsAreNotChoosen);
            }
        }

        return false;
    }

    $('a.send-to-smartcat-anchor').click(function (event) {
        modalWindowHandler(event);
        return false;
    });

    //появление модала
    $("#doaction, #doaction2").click(function (event) {
        var $this = $(this);
        var butId = $this.attr("id");
        var selName = butId.substr(2);

        if ("send_to_smartcat" === $('select[name="' + selName + '"]').val()) {
            modalWindowHandler(event);
            return false;
        }
    });

    var $modalWindow = $('#smartcat-modal-window');

    /*
     * Часть по валидации страницы настроек на фронте
     */
    $('.smartcat-connector form[action="options.php"]').submit(function (event) {
        var $this = $(this);
        var formData = $this.serialize();
        formData = add_action_to_serialized_data(
            formData, SmartcatFrontend.smartcat_table_prefix + 'validate_settings');

        $('.submit button.button-primary').prop("disabled", true);
        $(".sc-spinner").show();
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: formData,
            success: function (responseText) {
                $this.unbind('submit');
                $this.submit();
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(responseJSON.message);
                $(".sc-spinner").hide();
                $('.submit button.button-primary').prop("disabled", false);
            }
        });

        event.preventDefault();
        return false;
    });

    /*
     * Save profile action.
     * P.S. WordPress must die! Getting instances by #ID DID NOT WORK!
     */
    $('.smartcat-connector form.edit-profile-form').submit(function (event) {
        var $this = $(this);
        var formData = $this.serialize();
        formData = add_action_to_serialized_data(
            formData, SmartcatFrontend.smartcat_table_prefix + 'create_profile');

        $('button.edit-profile-submit').prop('disabled', true);
        $(".sc-spinner").show();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: formData,
            success: function (responseText) {
                window.location.href = SmartcatFrontend.adminUrl + '/admin.php?page=sc-profiles';
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
                $('button.edit-profile-submit').prop('disabled', false);
                $(".sc-spinner").hide();
            }
        });

        event.preventDefault();
        return false;
    });

    function deleteProfile(element) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                _wpnonce: $('input[name="_wpnonce"]').val(),
                profile_id: element.data('bind'),
                action: SmartcatFrontend.smartcat_table_prefix + 'delete_profile'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                printSuccess(responseJSON.message);
                var $td = element.closest("tr");
                $td.hide('slow', function(){ $td.remove(); });
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
            }
        });
    }

    $('table.profiles .sc-profile-delete').each(function () {
        $(this).on('click', function () {
            deleteProfile($(this));
        });
    });

    /*
     * Обработчик самого модала
     */

    $modalWindow.find('form').first().submit(function (event) {
        var $this = $(this);
        var formData = $this.serialize();
        formData = add_action_to_serialized_data(
            formData, SmartcatFrontend.smartcat_table_prefix + 'send_to_smartcat');

        $('button.sc-send-button').prop('disabled', true);
        $(".sc-spinner").show();
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: formData,
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                $this.parent().dialog('close');
                printSuccess(responseJSON.message);
                $('button.sc-send-button').prop('disabled', false);
                $(".sc-spinner").hide();
            },
            error: function (responseObject) {
                $this.parent().dialog('close');
                $('button.sc-send-button').prop('disabled', false);
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
                $(".sc-spinner").hide();
            }
        });

        event.preventDefault();
        return false;
    });

    /*
     * Фронт страницы статистики
     */

    var refreshStatButton = $('#smartcat-connector-refresh-statistics');
    var intervalTimer;
    var isStatWasStarted = false;

    function checkStatistics() {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: SmartcatFrontend.smartcat_table_prefix + 'check_statistic'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                var isActive = responseJSON.data.statistic_queue_active;

                if (! isActive) {
                    clearInterval(intervalTimer);
                    isStatWasStarted = false;
                }
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(responseJSON.message);

                if (intervalTimer) {
                    clearInterval(intervalTimer);
                }

                refreshStatButton.prop('disabled', false);
            }
        });
    }

    function updateStatistics() {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: SmartcatFrontend.smartcat_table_prefix + 'start_statistic'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                if (responseJSON.message === 'ok') {
                    checkStatistics();
                }
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(responseJSON.message);
            }
        });
    }

    function refreshStatistics(element) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                _wpnonce: $('input[name="_wpnonce"]').val(),
                stat_id: element.data('bind'),
                action: SmartcatFrontend.smartcat_table_prefix + 'refresh_translation'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                printSuccess(responseJSON.message);
                element.closest("tr").children(".column-status").html(responseJSON.data.statistic.status);
                element.parent().html("-");
                updateStatistics();

            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
            }
        });
    }

    $('table.statistics .refresh_stat_button').each(function () {
        $(this).on('click', function () {
            refreshStatistics($(this));
        });
    });

    function deleteStatistics(element) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                _wpnonce: $('input[name="_wpnonce"]').val(),
                stat_id: element.data('bind'),
                action: SmartcatFrontend.smartcat_table_prefix + 'delete_statistics'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                printSuccess(responseJSON.message);
                var $td = element.closest("tr");
                $td.hide('slow', function(){ $td.remove(); });
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
            }
        });
    }

    $('table.statistics .delete_stat_button').each(function () {
        $(this).on('click', function () {
            deleteStatistics($(this));
        });
    });

    function cancelStatistics(element) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                _wpnonce: $('input[name="_wpnonce"]').val(),
                stat_id: element.data('bind'),
                action: SmartcatFrontend.smartcat_table_prefix + 'cancel_statistics'
            },
            success: function (responseText) {
                var responseJSON = JSON.parse(responseText);
                printSuccess(responseJSON.message);
                element.closest("tr").children(".column-status").html(responseJSON.data.statistic.status);
                element.closest("span").remove();
            },
            error: function (responseObject) {
                var responseJSON = JSON.parse(responseObject.responseText);
                printError(SmartcatFrontend.anErrorOccurred + ' ' + responseJSON.message);
            }
        });
    }

    $('table.statistics .cancel_stat_button').each(function () {
        $(this).on('click', function () {
            cancelStatistics($(this));
        });
    });

    //проверяем на существование, что мы точно на странице статистики
    if (refreshStatButton.length) {
        isStatWasStarted = refreshStatButton.is(':disabled');

        refreshStatButton.click(function (event) {
            //если уже получаем статистику - ничего не делать
            if (isStatWasStarted) {
                event.preventDefault();
                return false;
            }

            isStatWasStarted = true;
            var $this = $(this);
            $this.prop('disabled', true);

            updateStatistics();

            location.reload();
            event.preventDefault();
            return false;
        });

        //если статистика была запущена уже в первый запуск
        if (isStatWasStarted) {
            intervalTimer = setInterval(checkStatistics, 1000*60);
        }

        if (!isStatWasStarted) {
            pageIntervalReload = setInterval(function () {
                if (isStatWasStarted) {
                    return false;
                }

                isStatWasStarted = true;
                var $this = $(this);
                $this.prop('disabled', true);

                updateStatistics();

                location.reload();
            }, 1000 * 60);
        }
    }

});