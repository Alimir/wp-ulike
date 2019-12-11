/**
 * wp ulike admin statistics
 */
(function ($) {
  $(".wp_ulike_delete").click(function (e) {
    e.preventDefault();
    var parent = $(this).closest("tr");
    var value = $(this).data("id");
    var table = $(this).data("table");
    var nonce = $(this).data("nonce");
    var r = confirm(wp_ulike_admin.logs_notif);
    if (r === true) {
      jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
          action: "ulikelogs",
          id: value,
          nonce: nonce,
          table: table
        },
        beforeSend: function () {
          parent.css("background-color", "#fff59d");
        },
        success: function (response) {
          if (response.success) {
            parent.fadeOut(300);
          } else {
            parent.css("background-color", "#ef9a9a");
          }
        }
      });
    }
  });

  $.fn.WpUlikeAjaxStats = function () {
    // local var
    var theResponse = null;
    // jQuery ajax
    $.ajax({
      type: "POST",
      dataType: "json",
      url: ajaxurl,
      async: false,
      data: {
        action: "wp_ulike_ajax_stats",
        nonce: wp_ulike_admin.nonce_field
      },
      success: function (response) {
        if (response.success) {
          theResponse = JSON.parse(response.data);
        } else {
          theResponse = null;
        }
      }
    });
    // Return the response text
    return theResponse;
  };

  // Charts stack array to save data
  window.wpUlikechartsInfo = [];

  if (wp_ulike_admin.hook_address.indexOf("wp-ulike-statistics") !== -1) {

    // Get all tables data
    window.wpUlikeAjaxDataset = $.fn.WpUlikeAjaxStats();

    if (window.wpUlikeAjaxDataset === null) {
      return;
    }

    // Get single var component
    Vue.component("get-var", {
      props: ["dataset"],
      data: function () {
        return {
          output: "..."
        };
      },
      mounted() {
        this.output = this.fetchData();
        // Remove spinner class
        this.$nextTick(function () {
          this.removeClass(this.$el.offsetParent);
        });
      },
      methods: {
        fetchData() {
          return window.wpUlikeAjaxDataset[this.dataset];
        },
        removeClass(element) {
          element.classList.remove("wp-ulike-is-loading");
        }
      }
    });
    // Get charts object component
    Vue.component("get-chart", {
      props: ["dataset", "identify", "type"],
      mounted() {
        if (this.type == "line") {
          this.planetChartData = this.fetchData();
          this.createLineChart(this.planetChartData);
        } else {
          this.createPieChart();
        }
        // Remove spinner class
        this.$nextTick(function () {
          this.removeClass(this.$el.offsetParent);
        });
      },
      methods: {
        fetchData() {
          return window.wpUlikeAjaxDataset[this.dataset];
        },
        createLineChart(chartData) {
          // Push data stats in dataset options
          chartData.options["data"] = chartData.data;
          // And finally draw it
          this.drawChart({
            // The type of chart we want to create
            type: "line",
            // The data for our dataset
            data: {
              labels: chartData.label,
              datasets: [chartData.options]
            }
          });
          // Set info for this canvas
          this.setInfo(chartData);
        },
        createPieChart() {
          // Define stack variables
          var pieData = [],
            pieBackground = [],
            pieLabels = [];
          // Get the info of each chart
          window.wpUlikechartsInfo.forEach(function (value, key) {
            pieData.push(value.sum);
            pieBackground.push(value.background);
            pieLabels.push(value.label);
          });
          // And finally draw it
          this.drawChart({
            // The type of chart we want to create
            type: "pie",
            // The data for our dataset
            data: {
              datasets: [
                {
                  data: pieData,
                  backgroundColor: pieBackground
                }
              ],
              // These labels appear in the legend and in the tooltips when hovering different arcs
              labels: pieLabels
            }
          });
        },
        drawChart(chartArgs) {
          // Get canvas element
          const ctx = document.getElementById(this.identify);
          // Draw Chart
          const chart = new Chart(ctx, chartArgs);
        },
        setInfo(chartData) {
          var sumStack = 0;
          // Get the sum of total likes
          chartData.data.forEach(function (num) {
            sumStack += parseFloat(num) || 0;
          });
          // Upgrade wpUlikechartsInfo array
          window.wpUlikechartsInfo.push({
            type: this.identify,
            sum: sumStack,
            label: chartData.options.label,
            background: chartData.options.backgroundColor
          });
        },
        removeClass(element) {
          element.classList.remove("wp-ulike-is-loading");
        }
      }
    });

    new Vue({
      el: "#wp-ulike-stats-app"
    });
  }

  // on document ready
  $(function () {
    $(".wp-ulike-match-height").matchHeight();
  });
})(jQuery);
