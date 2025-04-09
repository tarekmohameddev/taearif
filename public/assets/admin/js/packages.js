"use strict";
$(document).ready(function () {
    $(".selectgroup-input").on('change', function () {
        var val = $(this).val();
        console.log("Value:", val, "Checked:", this.checked);

        if (val === 'vCard') {
            if (this.checked) {
                $(".v-card-box").removeClass('vcrd-none').show();
            } else {
                $(".v-card-box").hide();
            }
        }

        if (val === 'projectLimit') {
            if (this.checked) {
                $(".project-limit-box").removeClass('project-limit-none').show();
            } else {
                $(".project-limit-box").addClass('project-limit-none').hide();

            }
        }

        if (val === 'real_estate_Limit') {
            if (this.checked) {
                $(".real_estate-limit-box").removeClass('real_estate-limit-none').show();
            } else {
                $(".real_estate-limit-box").addClass('real_estate-limit-none').hide();

            }
        }
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

        let valid = true;

        // Validate Project Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='projectLimit']").is(":checked")) {
            const projectLimitVal = $("#project_limit_number").val().trim();
            if (projectLimitVal === "") {
                $("#errproject_limit_number").text("Please enter a Project Number Limit.");
                valid = false;
            }
        }

        // Validate Real Estate Limit Field if its checkbox is checked
        if ($("input[name='features[]'][value='real_estate_Limit']").is(":checked")) {
            const realEstateVal = $("#real_estate_limit_number").val().trim();
            if (realEstateVal === "") {
                $("#errreal_estate_limit_number").text("Please enter a Real Estate Number Limit.");
                valid = false;
            }
        }

        // focus on the first empty field If validation fails
        if (!valid) {
            if ($("input[name='features[]'][value='projectLimit']").is(":checked") &&
                $("#project_limit_number").val().trim() === "") {
                $("#project_limit_number").focus();
            } else if ($("input[name='features[]'][value='real_estate_Limit']").is(":checked") &&
                       $("#real_estate_limit_number").val().trim() === "") {
                $("#real_estate_limit_number").focus();
            }
            return false;
        } else {
            $("#ajaxForm").submit();
        }
    });
});
