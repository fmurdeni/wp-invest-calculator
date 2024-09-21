(function($){"use strict";
    let xhr = [];
    let loader = '<div class="loader"></div>';
    let spinner = '<div class="spinner"></div>';

    $(function() {
        $(".nav-item").on("click", function (e) {
            e.preventDefault();
            let tab = $(this).data("tab");

            $(this).addClass("active").siblings().removeClass("active");
            $( `.nav-content[data-tab=${tab}], .nav-content-right[data-tab=${tab}]` ) .addClass("active") .siblings() .removeClass("active");
        });

        $(".field-item.with-helper .input-field").on("change", function () {
            let value = $(this).val();            
            $(this).parent().find(".input-helper").text(`${value}%`);
        });

        // calculator submit
        $(".calculator-form").on("submit", function (e) {
            e.preventDefault();
            let form = $(this);
            let button = form.find('[type = "submit"]');
            let formdata = fixing_number_value(form);
                formdata["action"] = "ajax_calculator_result";
                formdata["siteSection"] = localStorage.getItem("site-section");
                console.log(formdata);
            
            let formType = form.find("input[name='form_type']").val();
            let resultContainer = $(`.result.nav-content-right[data-tab="${formType}"]`);
            let request = runAjax(formdata);

            // loader
            let buttonText = button.text();
            button.data("text", buttonText).attr('disabled', true).html(loader);
            resultContainer.parent().addClass('loading').prepend(spinner);

            request.done(function (response) {
                console.log(response);
                if (response.success) {
                    let data = response.data;
                    // $(".calculator-result").css("opacity", 1);
                    resultContainer.addClass("filled");
                    resultContainer.find(".username").html(data.username);
                    resultContainer.find(".information").html(data.information);
                    resultContainer
                        .find(".calculator-price")
                        .html(data.calculated);

                    resultContainer
                        .find(".result-products")
                        .html(data.products);

                    resultContainer
                        .find(".cta-category")
                        .attr("href", data.category_link);
                    
                }

                // loader
                button.html(buttonText).attr('disabled', false);
                resultContainer.parent().removeClass('loading').find(".spinner").remove();
            });
        });

        // number Auto type
        $(document).on("keyup", "input.number", function (event) {
            var selection = window.getSelection().toString();
            if (selection !== "") {
                return;
            }

            // When the arrow keys are pressed, abort.
            if ($.inArray(event.keyCode, [38, 40, 37, 39]) !== -1) {
                return;
            }          

            number_view($(this));
        });
    });
    
    const fixing_number_value = (form) => {
        let formdata = form.serializeObject();

        form.find("input.number").each(function () {
            let name = $(this).attr("name");
            formdata[name] = formdata[name].replace(/[($)\s\.,_\-]+/g, "");
        });

        return formdata;
    };
    
    const number_view = (field) => {
        var locale = $(document).find("html").attr("lang");
        // Get the value.
        var input = field.val();

        var input = input.replace(/[\D\s\._\-]+/g, "");
        input = input ? parseInt(input, 10) : 0;

        field.val(function () {
            return input === 0 ? "" : input.toLocaleString(locale);
        });
    };

    const runAjax = (formdata, id = 1) => {
        xhr[id] = $.ajax({
            url: '/wp-admin/admin-ajax.php',
            type: "post",
            data: formdata,
            beforeSend: function () {
                if (xhr[id]) {
                    xhr[id].abort();
                }
            },
        });

        return xhr[id];
    };

    $.fn.serializeObject = function () {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || "");
            } else {
                o[this.name] = this.value || "";
            }
        });
        return o;
    };

})(jQuery);