if (!STUDIP.CC) STUDIP.CC = {};
STUDIP.CC.form = {
    'get_data_from_div': function (div) {
        var snd_data = {};
        jQuery(div).find("input, select, textarea").each(function () {
            var name = jQuery(this).attr("name");
            if (!name) {
                return;
            }
            if (jQuery(this).is("[type=checkbox]")) {
                var data = jQuery(this).is(":checked") ? 1 : 0;
            } else if(jQuery(this).is("[type=radio]")) {
                if (jQuery(this).is(":checked")) {
                    var data = jQuery(this).val();
                } else {
                    return;
                }
            } else {
                var data = jQuery(this).val();
            }
            name = name.split(/[\[\]]/);
            jQuery.each(name.reverse(), function (index, element) {
                if (element) {
                    var new_data = {};
                    new_data[element] = data;
                    data = new_data;
                }
            });
            snd_data = STUDIP.CC.form.deep_array_merge(snd_data, data);
        });
        return snd_data;
    },
    'deep_array_merge': function (arr1, arr2) {
        jQuery.each(arr2, function (index, element) {
            if (typeof arr1[index] === "object" && typeof element === "object") {
                arr1[index] = STUDIP.CC.form.deep_array_merge(arr1[index], element);
            } else if (typeof arr1[index] === "array" && typeof element === "array") {
                arr1[index] = jQuery.merge(arr1[index], element);
            } else {
                arr1[index] = element;
            }
        });
        return arr1;
    },
    'fill_dataforms_in_div': function (div, data) {
        jQuery(div).find("input, select, textarea").each(function () {
            var name = jQuery(this).attr("name");
            if (!name) {
                if (jQuery(this).is("[type=checkbox]")) {
                    jQuery(this).attr("checked", false);
                } else if (jQuery(this).is("[type=radio]")) {
                    jQuery(this).attr("checked", false);
                } else {
                    
                    jQuery(this).val("");
                }
                return;
            }
            name = name.split(/[\[\]]/);
            var temp_var = data;
            jQuery.each(name, function (index, element) {
                if (element && typeof temp_var !== "undefined") {
                    temp_var = temp_var[element];
                }
            });
            if (temp_var) {
                if (jQuery(this).is("[type=checkbox]")) {
                    jQuery(this).attr("checked", temp_var > 0 ? true : false);
                } else if (jQuery(this).is("[type=radio]")) {
                    if (temp_var == jQuery(this).val()) {
                        jQuery(this).attr("checked", true);
                    }
                } else {
                    jQuery(this).val(temp_var);
                }
            }
        });
    },
    'addslashes': function (text) {
        return encodeURI(text);
        text = text.replace(/(\"|\[|\]|\'|\\|\/)/g,'\\$1');
        console.log(text);
        return text;
    }
};

STUDIP.CC.checks = {
    'test_in_progress': false,
    'test': function () {
        if (STUDIP.CC.checks.test_in_progress) {
            return false;
        }
        jQuery("#progressbar").css('opacity', "1").show();
        jQuery("#test_results").hide();
        var render_progressbar = function (percent, overall_time) {
            jQuery("#progressbar").progressbar({ 'value': percent });
            if (percent <= 100) {
                window.setTimeout(function () {
                    if (STUDIP.CC.checks.test_in_progress) {
                        render_progressbar(percent + 1, overall_time);
                    }
                }, overall_time / 100);
            } else {
                jQuery("#progressbar").css('opacity', "0.5");
            }
        }
        render_progressbar(0, parseInt(jQuery("#progress_time").html(), 10));
        STUDIP.CC.checks.test_in_progress = true;
        starttime = new Date();
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/test/raw_test",
            'success': function (test_result) {
                jQuery("#progressbar").hide();
                jQuery("#test_results")
                    .html(test_result)
                    .fadeIn();
                STUDIP.CC.checks.test_in_progress = false;
                jQuery("#progress_time").html(new Date() - starttime);
            },
            'error': function (jqXHR, textStatus, errorThrown) {
                jQuery("#progressbar").hide();
                jQuery("#test_results")
                    .text(errorThrown)
                    .fadeIn();
                STUDIP.CC.checks.test_in_progress = false;
            }
        });
    }
};

STUDIP.CC.ECS = {
    'save_ecs_data': function () {
        var data = STUDIP.CC.form.get_data_from_div("#ecs_edit_window");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/ecs_save",
            'data': data,
            'type': "post",
            'dataType': "json",
            'success': function (ret) {
                jQuery("#ecs_edit_window").dialog("close");
                if (ret.message) {
                    jQuery("#messages").html(ret.message);
                }
                if (jQuery("#ecs_" + ret.id).length) {
                    jQuery("#ecs_" + ret.id + " > td:first-child").text(data.data.name);
                    if (data.active) {
                        jQuery("#ecs_" + ret.id).addClass("active").removeClass("inactive");
                    } else {
                        jQuery("#ecs_" + ret.id).addClass("inactive").removeClass("active");
                    }
                } else {
                    console.log(1);
                    var new_line = jQuery("#ecs_table > tbody > tr:first-child").clone();
                    jQuery(new_line)
                        .addClass(data.active ? "active" : "inactive")
                        .attr("id", "ecs_" + ret.id)
                        .show()
                        .bind("click", STUDIP.CC.ECS.click)
                        .find("td:first-child").html(data.data.name);
                    jQuery("#ecs_table").append(new_line);
                }
            }
        });
    },
    'new': function () {
        jQuery("#ecs_edit_window").find("select, input:not([type=checkbox], [type=radio]), textarea").val("");
        jQuery("#ecs_edit_window").find("input[type=checkbox]").attr("checked", false);
        jQuery("#ecs_edit_window").find("input[type=radio]").attr("checked", false);
        jQuery("#ecs_delete").hide();
        STUDIP.CC.ECS.open_edit_window(jQuery("#ecs_edit_window_title_new").text());
    },
    'open_edit_window': function (title) {
        jQuery("#ecs_edit_window").dialog({
            'title': title,
            'modal': true,
            'hide': "fade",
            'show': "fade",
            'width': "80%"
        });
    },
    'click': function () {
        var id = jQuery(this).attr("id");
        id = id.substr(id.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/ecs_get_data",
            'data': {
                'id': id
            },
            'dataType': "json",
            'success': function (ret) {
                STUDIP.CC.form.fill_dataforms_in_div("#ecs_edit_window", ret);
                jQuery("#ecs_delete").show();
                STUDIP.CC.ECS.open_edit_window(jQuery("#ecs_edit_window_title").text());
            }
        });
    },
    'del': function () {
        if (jQuery("#ecs_id").val() && window.confirm('Wirklich löschen?')) {
            jQuery.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/ecs_delete",
                'type': "post",
                'data': {
                    'id': jQuery("#ecs_id").val()
                },
                'success': function (success) {
                    if (success === "1") {
                        jQuery("#ecs_" + jQuery("#ecs_id").val()).remove();
                        jQuery("#ecs_edit_window").dialog("close");
                    }
                }
            });
        }
    },
    'connectivity': function () {
    	var data = STUDIP.CC.form.get_data_from_div("#ecs_edit_window");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/ecs_connectivity",
            'data': data,
            'type': "post",
            'dataType': "json",
            'success': function (ret) {
                window.alert(
                    'Verbindung ' + (!ret.is_error ? 'erfolgreich' : 'fehlgeschlagen') +
                    '\nStatus: ' + ret.status +
                    (ret.error ? '\nFehler: ' + ret.error : '')
                );
            }
        });
    },
    'export_stack': function () {
        jQuery('#sync_loader').show();
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/connector/send_changes",
            'success': function () {
                jQuery("#items_to_be_synced").text("0");
                jQuery('#sync_loader').hide();
            }
        });
    },
    'import_changes': function () {
        jQuery('#import_sync_loader').show();
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/connector/receive",
            'success': function () {
                jQuery('#import_sync_loader').hide();
            }
        });
    }
};
STUDIP.CC.participants = {
    'click': function () {
        var id = jQuery(this).attr("id");
        id = id.substr(id.lastIndexOf("_") + 1);
        location.href = STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/participant?id=" + id;
    },
    'save_data': function () {
        var data = STUDIP.CC.form.get_data_from_div(".attribute_table, #import_settings_window, #export_settings_window");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/participant_save",
            'data': data,
            'type': "post",
            'dataType': "json",
            'success': function (ret) {
                if (ret.message) {
                    jQuery("#messages").html(ret.message);
                }
            }
        });
    },
    'setup_import': function () {
        jQuery("#import_settings_window").dialog({
            'modal': true,
            'title': jQuery("#import_settings_window_title").text(),
            'show': "fade",
            'hide': "fade",
            'width': "80%",
            'resizable': false
        });
    },
    'setup_export': function () {
        jQuery("#export_settings_window").dialog({
            'modal': true,
            'title': jQuery("#export_settings_window_title").text(),
            'show': "fade",
            'hide': "fade",
            'width': "80%",
            'resizable': false
        });
    },
    'showImportFields': function () {
        var import_type = jQuery("#import_course_type").val();
        switch (import_type) {
            case "cms":
                jQuery(".kurslink_only").hide();
                jQuery(".kurs_only").hide();
                jQuery(".cms_only").show();
                break;
            case "kurslink":
                jQuery(".cms_only").hide();
                jQuery(".kurs_only").hide();
                jQuery(".kurslink_only").show();
                break;
            case "kurs":
                jQuery(".kurslink_only").hide();
                jQuery(".cms_only").hide();
                jQuery(".kurs_only").show();
                break;
        }
    },
    'showTreeMapping': function () {
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/participant_get_trees",
            'data': {
                'id': jQuery("#participant_id").val()
            },
            'success': function (response) {
                jQuery("#import_directory_trees_settings_window").html(response);
                jQuery("#import_directory_trees_settings_window").dialog({
                    'modal': true,
                    'title': jQuery("#import_directory_trees_settings_window_title").text(),
                    'show': "fade",
                    'hide': "fade",
                    'width': "85%",
                    'height': 580,
                    'resizable': false
                });
                jQuery("#mapping_trees .tree_mapping_tree li > .title").bind("click", STUDIP.CC.participants.match_tree);
                jQuery("#mapping_trees a.send_tree_matching").bind("click", STUDIP.CC.participants.send_tree_matching);
            }
        });
    },
    'match_tree': function () {
        var tree_id = jQuery(this).closest("div.tree_matching_body").attr("id");
        tree_id = tree_id.substr(tree_id.lastIndexOf("_") + 1);
        var node_id = jQuery(this).closest(".tree_node").attr("id");
        node_id = node_id.substr(node_id.lastIndexOf("_") + 1);
        var title = jQuery(this).text();
        var sem_tree_id = jQuery(this).closest("li.tree_node").attr("data-sem_tree_id");
        var sem_tree_id_title = jQuery(this).closest("li.tree_node").attr("data-sem_tree_id_title");

        jQuery(this).closest(".tree_matching_body").find("input[name=sem_tree_id]").val(sem_tree_id);
        jQuery(this).closest(".tree_matching_body").find("input[name=sem_tree_id_parameter]").val(sem_tree_id_title);
        jQuery(this).closest(".tree_matching_body").find("input.directory_id").val(node_id);
        jQuery(this).closest(".tree_matching_body").find(".tree_mapping_directory").text(title);
        jQuery(this).closest(".tree_matching_body").find(".tree_mapping_window").css("visibility", "visible");

        jQuery(".node_selected").removeClass("node_selected");
        jQuery(this).addClass("node_selected");
    },
    'send_tree_matching': function () {
        var tree_id = jQuery(this).closest("div.tree_matching_body").attr("id");
        tree_id = tree_id.substr(tree_id.lastIndexOf("_") + 1);
        var node_id = jQuery(this).closest(".tree_mapping_window").find(".directory_id").val();
        var sem_tree_id = jQuery(this).closest(".tree_mapping_window").find("input[name=sem_tree_id]").val();
        var sem_tree_title = jQuery(this).closest(".tree_mapping_window").find("input[name=sem_tree_id_parameter]").val();
        var sem_tree_id = sem_tree_title && sem_tree_id ? sem_tree_id : "";
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/config/match_tree",
            'data': {
                'participant_id': jQuery("#participant_id").val(),
                'tree_id': tree_id,
                'node_id': node_id,
                'sem_tree_id': sem_tree_id
            },
            'success': function () {
                jQuery("#directory_node_" + node_id).attr("data-sem_tree_id", sem_tree_id);
                jQuery("#directory_node_" + node_id).attr("data-sem_tree_id_title", sem_tree_title);
                if (sem_tree_id) {
                    jQuery("#directory_node_" + node_id).addClass("mapped_directly");
                } else {
                    jQuery("#directory_node_" + node_id).removeClass("mapped_directly");
                }
            }
        });
        return false;
    }
};

jQuery(function () {
    jQuery("#ecs_table > tbody > tr").bind("click", STUDIP.CC.ECS.click);
    jQuery("#participant_table > tbody > tr.selectable").bind("click", STUDIP.CC.participants.click);
});