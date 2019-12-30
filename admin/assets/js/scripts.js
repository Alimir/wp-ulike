/*! WP ULike - v4.0.5
 *  https://wpulike.com
 *  TechnoWich 2019;
 */


/* ================== admin/assets/js/src/settings.js =================== */


/*!
 * Settings scripts
 **/
(function($) {
  $(function() {
    var form = $("form.wrap"),
      page = $('input[name="option_page"]', form).val(),
      tabs = $(".wp-ulike-settings-tabs", form),
      current =
        parseInt(sessionStorage.getItem(page + "_current_tab"), 10) || 0;
    $(".wp-ulike-settings-section", form).each(function(i, el) {
      var setting = $(el).val(),
        title = $(el).prev("h2"),
        section = $("<div>").attr("id", page + "_" + setting);
      $(el)
        .nextAll()
        .each(function() {
          var tag = $(this).prop("tagName");
          if (tag === "H2" || tag === "INPUT") {
            return false;
          }
          $(this).appendTo(section);
        });
      if (tabs.length && title.length) {
        section.addClass("wp-ulike-settings-tab").hide();
        title.appendTo(tabs).click(function(e) {
          e.preventDefault();
          if (!title.hasClass("active")) {
            $(".wp-ulike-settings-tab.active", form).fadeOut(
              "fast",
              function() {
                $(".active", form).removeClass("active");
                title.addClass("active");
                section.fadeIn("fast").addClass("active");
              }
            );
            sessionStorage.setItem(page + "_current_tab", i);
          }
        });
        if (current === i) {
          title.addClass("active");
          section.show().addClass("active");
        }
        tabs.after(section);
      } else {
        title.prependTo(section);
        $(el).after(section);
      }
    });
    $('label[for="hidden"]', form).each(function() {
      $(this)
        .parents("tr")
        .addClass("hide-label");
    });
    $(".wp-ulike-settings-media", form).each(function() {
      var frame,
        select = $(".wp-ulike-select-media", this),
        remove = $(".wp-ulike-remove-media", this),
        input = $("input", this),
        preview = $("img", this),
        title = select.attr("title"),
        text = select.text();
      if (input.val() < 1) {
        preview = $('<img class="attachment-medium">');
        preview.prependTo(this).hide();
        remove.hide();
      }
      select.click(function(e) {
        e.preventDefault();
        if (frame) {
          frame.open();
          return;
        }
        frame = wp.media({
          title: title,
          button: {
            text: text
          },
          multiple: false
        });
        frame.on("select", function() {
          var attachment = frame
              .state()
              .get("selection")
              .first()
              .toJSON(),
            thumb;
          input.val(attachment.id);
          thumb = attachment.sizes.medium || attachment.sizes.full;
          preview.attr({
            src: thumb.url,
            width: thumb.width,
            height: thumb.height
          });
          preview.show();
          remove.show();
        });
        frame.open();
      });
      remove.click(function(e) {
        e.preventDefault();
        input.val("");
        preview.hide();
        remove.hide();
      });
    });
    $(".wp-ulike-settings-action", form).each(function() {
      var submit = $('[type="button"]', this),
        spinner = $("<img>")
          .attr({
            src: wp_ulike_admin.spinner,
            alt: "loading"
          })
          .insertAfter(submit)
          .hide(),
        notice = $("<div>")
          .addClass("settings-error")
          .insertBefore(submit)
          .hide(),
        action = {
          data: {
            action: submit.attr("id")
          },
          dataType: "json",
          type: "POST",
          url: ajaxurl,
          beforeSend: function() {
            spinner.fadeIn("fast");
            submit.hide();
          },
          success: function(r) {
            var noticeClass = "error",
              showNotice = function(msg) {
                notice
                  .html("<p>" + String(msg) + "</p>")
                  .addClass(noticeClass)
                  .show();
              };
            if (typeof r === "object") {
              if (r.hasOwnProperty("success") && r.success) {
                noticeClass = "updated";
              }
              if (r.hasOwnProperty("data") && r.data) {
                if (typeof r.data === "object") {
                  if (r.data.hasOwnProperty("reload") && r.data.reload) {
                    document.location.reload();
                    return;
                  }
                  if (r.data.hasOwnProperty("message") && r.data.message) {
                    showNotice(r.data.message);
                  }
                } else {
                  showNotice(r.data);
                }
              }
            } else if (r) {
              showNotice(r);
            }
            spinner.hide();
            submit.fadeIn("fast");
            notice.show("fast");
          },
          error: function(jqXHR, textStatus, errorThrown) {
            notice
              .addClass("error")
              .append("<p>" + jqXHR.responseText + "</p>")
              .show("fast");
            console.log(textStatus, jqXHR, errorThrown);
          }
        };
      submit.click(function(e) {
        e.preventDefault();
        notice.hide("fast", function() {
          var r = confirm(
            "You Are About To Delete Ulike Data/Logs.\nThis Action Is Not Reversible.\n\n Choose 'Cancel' to stop, 'OK' to delete."
          );
          if (r == true) {
            notice.removeClass("error updated").empty();
            $.ajax(action);
          }
        });
      });
    });

    $(".wp-ulike-settings-license-activation", form).each(function() {
      var submit = $('[type="button"]', this),
        spinner = $("<img>")
          .attr({
            src: wp_ulike_admin.spinner,
            alt: "loading"
          })
          .insertAfter(submit)
          .hide(),
        notice = $("<div>")
          .addClass("settings-error")
          .insertBefore(submit)
          .hide();
      submit.click(function(e) {
        e.preventDefault();
        notice.hide("fast", function() {
          notice.removeClass("error updated").empty();
          $.ajax({
            data: {
              action: 'wp_ulike_activate_license',
              nonce: $('#wp_ulike_activate_license').val(),
              license: $(this).siblings('input.license-info').val()
            },
            dataType: "json",
            type: "POST",
            url: ajaxurl,
            beforeSend: function() {
              spinner.fadeIn("fast");
              $(this).hide();
            },
            success: function(r) {
              var noticeClass = "error",
                showNotice = function(msg) {
                  notice
                    .html("<p>" + String(msg) + "</p>")
                    .addClass(noticeClass)
                    .show();
                };
              if (typeof r === "object") {
                if (r.hasOwnProperty("success") && r.success) {
                  noticeClass = "updated";
                  submit.hide();
                }
                if (r.hasOwnProperty("data") && r.data) {
                  if (typeof r.data === "object") {
                    if (r.data.hasOwnProperty("reload") && r.data.reload) {
                      document.location.reload();
                      return;
                    }
                    if (r.data.hasOwnProperty("message") && r.data.message) {
                      showNotice(r.data.message);
                    }
                  } else {
                    showNotice(r.data);
                  }
                }
              } else if (r) {
                showNotice(r);
              }
              spinner.hide();
              $(this).fadeIn("fast");
              notice.show("fast");
            },
            error: function(jqXHR, textStatus, errorThrown) {
              notice
                .addClass("error")
                .append("<p>" + jqXHR.responseText + "</p>")
                .show("fast");
              console.log(textStatus, jqXHR, errorThrown);
            }
          });
        });
      });
    });

    $(".wp-ulike-visual-select input").radioImageSelect();

    $(".wp-ulike-settings-color").spectrum({
      allowEmpty: true,
      showInput: true,
      showAlpha: true,
      disabled: false,

      showSelectionPalette: true,
      showPalette: true,
      hideAfterPaletteSelect: true,
      palette: [["black", "white", " "]],
      clickoutFiresChange: true,
      showInitial: true,
      containerClassName: "wp-ulike-sp-wrapper",
      localStorageKey: "wpulike.spectrum",
      preferredFormat: "hex6",
      change: function(color) {
        if (color === null) {
          $(this).val("");
        } else {
          $(this).val(color.toString());
        }
      }
    });

    $("#wp-ulike-settings_wp_ulike_customize tr:not(:first-child)").addClass(
      "custom-style-show"
    );

    function evaluate() {
      var item = $(this);
      var relatedItem = $(".custom-style-show");

      if (item.is(":checked")) {
        relatedItem.fadeIn();
      } else {
        relatedItem.fadeOut();
      }
    }

    $(".wp_ulike_custom_style_activation")
      .click(evaluate)
      .each(evaluate);

    $(
      "#wp-ulike-settings_wp_ulike_general tr:nth-child(2), #wp-ulike-settings_wp_ulike_general tr:nth-child(3)"
    ).addClass("button-text-show");
    $(
      "#wp-ulike-settings_wp_ulike_general tr:nth-child(4), #wp-ulike-settings_wp_ulike_general tr:nth-child(5)"
    ).addClass("button-icon-show");

    if (
      !$(".wp_ulike_check_text")
        .next("img")
        .hasClass("item-checked")
    ) {
      $(".button-text-show").hide();
    }
    if (
      !$(".wp_ulike_check_image")
        .next("img")
        .hasClass("item-checked")
    ) {
      $(".button-icon-show").hide();
    }

    $(".wp_ulike_check_text, .wp_ulike_check_image")
      .next("img")
      .click(function() {
        if (
          $(".wp_ulike_check_text")
            .next("img")
            .hasClass("item-checked")
        ) {
          $(".button-text-show").fadeIn();
        }
        if (
          !$(".wp_ulike_check_text")
            .next("img")
            .hasClass("item-checked")
        ) {
          $(".button-text-show").hide();
        }
        if (
          $(".wp_ulike_check_image")
            .next("img")
            .hasClass("item-checked")
        ) {
          $(".button-icon-show").fadeIn();
        }
        if (
          !$(".wp_ulike_check_image")
            .next("img")
            .hasClass("item-checked")
        ) {
          $(".button-icon-show").hide();
        }
      });
  });
})(jQuery);