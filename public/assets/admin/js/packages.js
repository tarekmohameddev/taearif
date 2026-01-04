"use strict";
$(document).ready(function () {
    const featureBoxConfig = {
        vCard: { box: ".v-card-box", hiddenClass: "vcrd-none" },
        projectLimit: { box: ".project-limit-box", hiddenClass: "project-limit-none", reset: "#project_limit_number" },
        real_estate_Limit: { box: ".real_estate-limit-box", hiddenClass: "real_estate-limit-none", reset: "#real_estate_limit_number" },
        whatsapp_Limit: { box: ".whatsapp-limit-box", hiddenClass: "whatsapp-limit-none", reset: "#whatsapp_numbers_limit" },
        employees_Limit: { box: ".employees-limit-box", hiddenClass: "employees-limit-none", reset: "#employees_limit" },
    };

    const syncFeatureBox = (val) => {
        const cfg = featureBoxConfig[val];
        if (!cfg) return;

        const checked = $(`input[name="features[]"][value="${val}"]`).is(":checked");
        const $box = $(cfg.box);

        $box.toggleClass(cfg.hiddenClass, !checked).toggle(checked);
        if (!checked && cfg.reset) $(cfg.reset).val(0);
    };

    // initial render for edit/create
    Object.keys(featureBoxConfig).forEach(syncFeatureBox);

    $(".selectgroup-input").on("change", function () {
        syncFeatureBox($(this).val());
    });

    if ($('#CourseManagement').prop('checked')) {
        $("#max_video_size").show();
        $("#max_file_size").show();
    } else {
        $("#max_video_size").hide();
        $("#max_file_size").hide();
    }

    $(document).on('click', '#CourseManagement', function () {
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            $("#max_video_size").show();
            $("#max_file_size").show();
        } else {
            $("#max_video_size").hide();
            $("#max_file_size").hide();
        }
    });

    // === Frontend Validation: Bind to the Submit Button's Click Event ===       
    $("#submitBtn").on("click", function (e) {
        // Prevent default submission
        e.preventDefault();

        // Clear previous error messages
        $("#errproject_limit_number").text("");
        $("#errreal_estate_limit_number").text("");
        $("#errwhatsapp_numbers_limit").text("");
        $("#erremployees_limit").text("");

        let valid = true;

        // Validate Project Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='projectLimit']").is(":checked")) { 
            const projectLimitVal = $("#project_limit_number").val().trim();      
            if (projectLimitVal === "") {
                $("#errproject_limit_number").text("Please enter a Project Number Limit.");
                valid = false;
            }
        } else {
            $("#project_limit_number").val(0);
        }

        // Validate Real Estate Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='real_estate_Limit']").is(":checked")) {
            const realEstateVal = $("#real_estate_limit_number").val().trim();    
            if (realEstateVal === "") {
                $("#errreal_estate_limit_number").text("Please enter a Real Estate Number Limit.");
                valid = false;
            }
        } else {
            $("#real_estate_limit_number").val(0);
        }

        // Validate WhatsApp Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='whatsapp_Limit']").is(":checked")) {
            const whatsappVal = $("#whatsapp_numbers_limit").val().trim();    
            if (whatsappVal === "") {
                $("#errwhatsapp_numbers_limit").text("Please enter a WhatsApp Number Limit.");
                valid = false;
            }
        } else {
            $("#whatsapp_numbers_limit").val(0);
        }

        // Validate Employees Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='employees_Limit']").is(":checked")) {
            const employeesVal = $("#employees_limit").val().trim();    
            if (employeesVal === "") {
                $("#erremployees_limit").text("Please enter an Employees Limit.");
                valid = false;
            }
        } else {
            $("#employees_limit").val(0);
        }

        // focus on the first empty field If validation fails
        if (!valid) {
            if ($("input[name='features[]'][value='projectLimit']").is(":checked") &&
                $("#project_limit_number").val().trim() === "") {
                $("#project_limit_number").focus();
            } else if ($("input[name='features[]'][value='real_estate_Limit']").is(":checked") &&
                       $("#real_estate_limit_number").val().trim() === "") {      
                $("#real_estate_limit_number").focus();
            } else if ($("input[name='features[]'][value='whatsapp_Limit']").is(":checked") &&
                       $("#whatsapp_numbers_limit").val().trim() === "") {      
                $("#whatsapp_numbers_limit").focus();
            } else if ($("input[name='features[]'][value='employees_Limit']").is(":checked") &&
                       $("#employees_limit").val().trim() === "") {      
                $("#employees_limit").focus();
            }
            return false;
        } else {
            $("#ajaxForm").submit();
        }
    });
});
